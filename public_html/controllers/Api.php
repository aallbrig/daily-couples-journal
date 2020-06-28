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

  public function saveProduct() {
    $body = $this->apiRequest->body;
    $v = new SaveProductValidator($body);

    // TODO: Validation
    if (!$v->validate()) {
      http_response_code(400);
      echo json_encode([ 'errors' => $v->errors() ]);
      exit;
    }

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
    $payment = new Shop();

    // TODO: Validation!
    $response = $payment->createPaymentIntent($body->items, $body->currency);

    return json_encode($response);
  }

  public function updatePaymentIntent() {
    $body = $this->apiRequest->body;
    $payment = new Shop();

    // TODO: Validation!
    $validReceiptEmail = $body->payload->receipt_email;
    $validPaymentIntentId = $body->paymentIntentId;
    $validUpdatePayload = ['receipt_email' => $validReceiptEmail];

    $response = $payment->updatePaymentIntent($validPaymentIntentId, $validUpdatePayload);

    return json_encode($response);
  }
}
