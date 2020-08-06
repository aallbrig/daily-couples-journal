<?php
require_once '../vendor/autoload.php';
require '../classes/ApiRequest.php';
require '../classes/ApiResponse.php';
require '../classes/Texting.php';
require '../classes/Shop.php';
require '../classes/PersistenceStore.php';
require '../classes/CouponCodeValidator.php';
require '../classes/CreatePaymentIntentValidator.php';
require '../classes/SaveProductValidator.php';
require '../classes/UpdatePaymentIntentValidator.php';

class Api
{
  private $apiRequest;

  public function __construct()
  {
    $this->apiRequest = new ApiRequest();
  }

  public function saveProduct() {
    $body = $this->apiRequest->body;
    $hasCoupon = property_exists($body, 'coupon_code') && $body->coupon_code !== null && $body->coupon_code != '';
    $active = 0;

    $v = new SaveProductValidator($body, $hasCoupon);
    if (!$v->validate()) {
      return new ApiResponse([
        'errors' => $v->errors()
      ], 400);
    }

    // Determine if product order is active
    $s = new Shop();
    $db = new PersistenceStore();
    $pmtInt = $s->retrievePaymentIntentById($body->payment_intent);
    if ($hasCoupon) {
      // Check if coupon makes price 100% off
      $pr = $s->retrievePriceById($body->price);
      $c = $s->retrieveCouponByCouponId($body->coupon_code);

      // HACK: Assume coupons are only percentage off
      $priceAfterCoupon = $pr->unit_amount - ($pr->unit_amount * ($c->percent_off / 100));
      if ($priceAfterCoupon !== 0 && $pmtInt->status !== 'succeeded') {
        return new ApiResponse([
          'errors' => ['proof_of_purchase' => 'Payment is required']
        ]);
      } else {
        $db->saveCouponId($pmtInt->id, $c->id);
        $active = 1;
      }
    } else {
      if ($pmtInt->status === 'succeeded') {
        $active = 1;
      }
    }

    $validPrimaryFirstName = $body->primary_firstname;
    $validPrimaryLastName = $body->primary_lastname;
    $validPrimaryPhoneNumber = $body->primary_phonenumber;

    $validSecondaryFirstName = $body->secondary_firstname;
    $validSecondaryLastName = $body->secondary_lastname;
    $validSecondaryPhoneNumber = $body->secondary_phonenumber;

    $validStartDate = date_format(date_create($body->start_date), 'Y-m-d H:i:s');

    // TODO: Handle errors if insert failed
    $primaryPersonId = $db->savePerson(
      $validPrimaryFirstName,
      $validPrimaryLastName,
      $validPrimaryPhoneNumber
    );
    $secondaryPersonId = $db->savePerson(
      $validSecondaryFirstName,
      $validSecondaryLastName,
      $validSecondaryPhoneNumber
    );
    $coupleId = $db->saveCouple($primaryPersonId, $secondaryPersonId);
    $productOrderId = $db->saveProductOrder($coupleId, $validStartDate, $active);
    $db->relateProductOrderToStripeData($pmtInt->id, $productOrderId);

    return new ApiResponse([
      'productOrderId' => $productOrderId
    ]);
  }

  public function createPaymentIntent() {
    $body = $this->apiRequest->body;
    $v = new CreatePaymentIntentValidator($body);

    if (!$v->validate()) {
      return new ApiResponse([
        'errors' => $v->errors()
      ], 400);
    }

    $payment = new Shop();
    $response = $payment->createPaymentIntent($body->items, $body->currency);

    return new ApiResponse($response);
  }

  public function updatePaymentIntent() {
    $body = $this->apiRequest->body;

    $v = new UpdatePaymentIntentValidator($body);
    if (!$v->validate()) {
      return new ApiResponse([
        'errors' => $v->errors()
      ], 400);
    }

    $payment = new Shop();
    $validReceiptEmail = $body->payload->receipt_email;
    $validPaymentIntentId = $body->paymentIntentId;
    $validUpdatePayload = ['receipt_email' => $validReceiptEmail];

    $response = $payment->updatePaymentIntent($validPaymentIntentId, $validUpdatePayload);

    return new ApiResponse($response);
  }

  public function retrieveCouponByCouponCode() {
    $body = $this->apiRequest->body;
    $v = new CouponCodeValidator($body);
    if (!$v->validate()) {
      return new ApiResponse([
        'errors' => $v->errors()
      ], 400);
    }

    $shop = new Shop();
    $response = $shop->retrieveCouponByCouponId($body->couponCode);

    if (is_a($response, 'Stripe\Coupon')) {
      return new ApiResponse([
        'id' => $response->id,
        'name' => $response->name,
        'percent_off' => $response->percent_off
      ]);
    } else {
      // Not a valid coupon
      return new ApiResponse([ 'errors' => 'Invalid coupon' ]);
    }
  }
}
