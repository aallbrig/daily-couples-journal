<?php
require_once '../vendor/autoload.php';
require '../classes/Texting.php';
require '../classes/Shop.php';
require '../classes/PersistenceStore.php';
use Valitron\Validator;

abstract class ApiValidator {
  protected $v;
  public function __construct($dataToValidate)
  {
    $assocArray = null;
    if (!is_object($dataToValidate)) {
      $assocArray = $dataToValidate;
    } else {
      $assocArray = json_decode(json_encode($dataToValidate), true);
    }

    $this->v = new Validator($assocArray);
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
    $primaryFirstNameInput = 'primary_firstname';
    $primaryLastNameInput = 'primary_lastname';
    $primaryPhoneNumberInput = 'primary_phonenumber';
    $secondaryFirstNameInput = 'secondary_firstname';
    $secondaryLastNameInput = 'secondary_lastname';
    $secondaryPhoneNumberInput = 'secondary_phonenumber';

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
      ]
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
