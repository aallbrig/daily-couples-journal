<?php
use Valitron\Validator;

abstract class ApiValidator {
  protected $v;
  protected $assocArray;

  public function __construct($dataToValidate)
  {
    if (!is_object($dataToValidate)) {
      $this->assocArray = $dataToValidate;
    } else {
      $this->assocArray = json_decode(json_encode($dataToValidate), true);
    }

    $this->v = new Validator($this->assocArray);

    $this->v->addInstanceRule('stripeResultNotAlreadyStored', function ($field, $value, $params, $fields) {
      $db = null;
      $json = json_decode($value);
      if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
      }
      $db = new PersistenceStore();
      $dbResults = $db->retrieveProductOrderByPaymentIntentId($json->paymentIntent->id);
      // is the stripe result payment ID already in DB?
      if (count($dbResults) > 0) {
        return false;
      }
      return true;
    }, '{field} is invalid -- this payment is already associated with an order!');

    $this->v->addInstanceRule('paymentIntentIdNotAlreadyStored', function ($field, $value, $params, $fields) {
      $db = new PersistenceStore();
      $dbResults = $db->retrieveProductOrderByPaymentIntentId($value);
      // is the stripe result payment ID already in DB?
      if (count($dbResults) > 0) {
        return false;
      }
      return true;
    }, '');

    $this->v->addInstanceRule('validPaymentIntentId', function ($field, $value, $params, $fields) {
      $shop = new Shop();
      $paymentIntent = $shop->retrievePaymentIntentById($value);
      if (is_a($paymentIntent, 'Stripe\Exception\InvalidRequestException')) {
        return false;
      }
      if (is_a($paymentIntent, 'Stripe\Exception\ApiConnectionException')) {
        return false;
      }
      if ($paymentIntent->last_payment_error != null) {
        return false;
      }
      return true;
    }, '{field} is not a valid payment intent id');

    $this->v->addInstanceRule('validProductId', function ($field, $value, $params, $fields) {
      $shop = new Shop();
      $product = $shop->retrieveProductById($value);
      if (is_a($product, 'Stripe\Exception\InvalidRequestException')) {
        return false;
      }
      if (is_a($product, 'Stripe\Exception\ApiConnectionException')) {
        return false;
      }
      return true;
    }, '{field} is not a valid product id');

    $this->v->addInstanceRule('validCouponCodeId', function ($field, $value, $params, $fields) {
      $shop = new Shop();
      $product = $shop->retrieveCouponByCouponId($value);
      if (is_a($product, 'Stripe\Exception\InvalidRequestException')) {
        return false;
      }
      if (is_a($product, 'Stripe\Exception\ApiConnectionException')) {
        return false;
      }
      return true;
    }, '{field} is not a valid coupon code id');

    $this->v->addInstanceRule('validPriceId', function ($field, $value, $params, $fields) {
      if ($value != $_ENV['STRIPE_PRICE_ID']) {
        return false;
      }
      $shop = new Shop();
      $price = $shop->retrievePriceById($value);
      if (is_a($price, 'Stripe\Exception\InvalidRequestException')) {
        return false;
      }
      if (is_a($price, 'Stripe\Exception\ApiConnectionException')) {
        return false;
      }
      return true;
    }, '{field} is not a valid price id');

    $this->v->addInstanceRule('validStripeResult', function ($field, $value, $params, $fields) {
      $shop = null;
      $json = json_decode($value);
      if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
      }
      $shop = new Shop();
      $intentResult = $shop->retrievePaymentIntentById($json->paymentIntent->id);
      // is the stripe result payment ID valid?
      if ($intentResult->status != "succeeded") {
        return false;
      }
      if ($intentResult->client_secret != $fields['client_secret']) {
        return false;
      }
      if ($intentResult->receipt_email != $fields['email']) {
        return false;
      }
      if ($intentResult->last_payment_error != null) {
        return false;
      }
      if ($intentResult->canceled_at != null) {
        return false;
      }
      return true;
    }, '{field} is invalid - please reload and try again');
  }

  public function validate() {
    return $this->v->validate();
  }

  public function errors() {
    return $this->v->errors();
  }
}
