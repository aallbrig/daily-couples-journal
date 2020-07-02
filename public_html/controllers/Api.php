<?php
require_once '../vendor/autoload.php';
require '../classes/Texting.php';
require '../classes/Shop.php';
require '../classes/PersistenceStore.php';
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

class SaveProductValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $phoneNumberRegex = '/^([0-9]( |-)?)?(\(?[0-9]{3}\)?|[0-9]{3})( |-)?([0-9]{3}( |-)?[0-9]{4}|[a-zA-Z0-9]{7})$/';

    $emailInput = 'email';
    $dateInput = 'start_date';
    $primaryFirstNameInput = 'primary_firstname';
    $primaryLastNameInput = 'primary_lastname';
    $primaryPhoneNumberInput = 'primary_phonenumber';
    $secondaryFirstNameInput = 'secondary_firstname';
    $secondaryLastNameInput = 'secondary_lastname';
    $secondaryPhoneNumberInput = 'secondary_phonenumber';
    $stripeInput = 'stripe_result';

    $this->v->rules([
      'required' => [
        $primaryFirstNameInput,
        $primaryLastNameInput,
        $primaryPhoneNumberInput,
        $secondaryFirstNameInput,
        $secondaryLastNameInput,
        $secondaryPhoneNumberInput,
        $emailInput
      ],
      'email' => [$emailInput],
      'regex' => [
        [[$primaryPhoneNumberInput, $secondaryPhoneNumberInput], $phoneNumberRegex],
      ],
      'different' => [
        [$primaryFirstNameInput, $primaryLastNameInput],
        [$primaryFirstNameInput, $secondaryFirstNameInput],
        [$secondaryFirstNameInput, $primaryFirstNameInput],
        [$secondaryFirstNameInput, $secondaryLastNameInput],
        [$primaryPhoneNumberInput, $secondaryPhoneNumberInput],
        [$secondaryPhoneNumberInput, $primaryPhoneNumberInput]
      ],
      'lengthMax' => [
        [[
          $primaryFirstNameInput,
          $primaryLastNameInput,
          $secondaryFirstNameInput,
          $secondaryLastNameInput
        ], 64]
      ],
      'dateAfter' => [
        [$dateInput, date('Y-m-d')]
      ],
      'dateBefore' => [
        [$dateInput, date('Y-m-d', strtotime(date("Y-m-d", mktime()) . " + 365 day"))]
      ],
      'validStripeResult' => [$stripeInput],
      'stripeResultNotAlreadyStored' => [$stripeInput]
    ]);
  }
}

class CreatePaymentIntentValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $this->v->rules([
      'required' => [
        'items',
        'currency'
      ],
      'validProductId' => ['items.*.id'],
      'validPriceId' => ['items.*.priceId']
    ]);
  }
}

class UpdatePaymentIntentValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $this->v->rules([
      'required' => [
        'paymentIntentId',
        'payload'
      ],
      'validPaymentIntentId' => ['paymentIntentId'],
      'paymentIntentIdNotAlreadyStored' => ['paymentIntentId'],
      'email' => ['payload.receipt_email']
    ]);
  }
}

class CouponCodeValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $this->v->rules([
      'required' => ['couponCode']
    ]);
  }
}

class ApiRequest
{
  public $body;

  public function __construct()
  {
    header('Content-Type: application/json');
    $input = file_get_contents('php://input');
    $this->body = json_decode($input);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || json_last_error() !== JSON_ERROR_NONE) {
      http_response_code(400);
      echo json_encode([ 'error' => 'Invalid request.' ]);
      exit;
    }
  }
}

class Api
{
  private $apiRequest;

  public function __construct()
  {
    $this->apiRequest = new ApiRequest();
  }

  private function validationProcess($validator, $body) {
    $v = new $validator($body);

    if (!$v->validate()) {
      http_response_code(400);
      echo json_encode([ 'errors' => $v->errors() ]);
      exit;
    }
  }

  public function saveProduct() {
    $body = $this->apiRequest->body;
    $this->validationProcess(SaveProductValidator::class, $body);

    $db = new PersistenceStore();
    $validPrimaryFirstName = $body->primary_firstname;
    $validPrimaryLastName = $body->primary_lastname;
    $validPrimaryPhoneNumber = $body->primary_phonenumber;

    $validSecondaryFirstName = $body->secondary_firstname;
    $validSecondaryLastName = $body->secondary_lastname;
    $validSecondaryPhoneNumber = $body->secondary_phonenumber;

    $validStartDate = date_format(date_create($body->start_date), 'Y-m-d H:i:s');
    $validStripeResult = $body->stripe_result;

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
    $productOrderId = $db->saveProductOrder($coupleId, $validStartDate, $validStripeResult);

    return json_encode([
      'productOrderId' => $productOrderId
    ]);
  }

  public function createPaymentIntent() {
    $body = $this->apiRequest->body;
    $this->validationProcess(CreatePaymentIntentValidator::class, $body);

    $payment = new Shop();
    $response = $payment->createPaymentIntent($body->items, $body->currency);

    return json_encode($response);
  }

  public function updatePaymentIntent() {
    $body = $this->apiRequest->body;
    $this->validationProcess(UpdatePaymentIntentValidator::class, $body);

    $payment = new Shop();
    $validReceiptEmail = $body->payload->receipt_email;
    $validPaymentIntentId = $body->paymentIntentId;
    $validUpdatePayload = ['receipt_email' => $validReceiptEmail];

    $response = $payment->updatePaymentIntent($validPaymentIntentId, $validUpdatePayload);

    return json_encode($response);
  }

  public function retrieveCouponByCouponCode() {
    $body = $this->apiRequest->body;
    $this->validationProcess(CouponCodeValidator::class, $body);

    $shop = new Shop();
    $response = $shop->retrieveCouponByCouponId($body->couponCode);

    if (is_a($response, 'Stripe\Coupon')) {
      return json_encode([
        'id' => $response->id,
        'name' => $response->name,
        'percent_off' => $response->percent_off
      ]);
    } else {
      // Not a valid coupon
      http_response_code(400);
      return json_encode([ 'errors' => 'Invalid coupon' ]);
    }
  }
}
