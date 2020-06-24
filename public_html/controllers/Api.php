<?php
require_once '../vendor/autoload.php';
require '../classes/Texting.php';
require '../classes/Shop.php';
require '../classes/PersistenceStore.php';

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
    $db = new PersistenceStore();

    // TODO: Validation!
    // TODO: Handle errors if insert failed
    $primaryPersonId = $db->savePerson(
      $body->primary_firstname,
      $body->primary_lastname,
      $body->primary_phonenumber
    );
    $secondaryPersonId = $db->savePerson(
      $body->secondary_firstname,
      $body->secondary_lastname,
      $body->secondary_phonenumber
    );
    $coupleId = $db->saveCouple($primaryPersonId, $secondaryPersonId);
    $productOrderId = $db->saveProductOrder($coupleId, $body->stripe_result);

    return json_encode([
      'productOrderId' => $productOrderId
    ]);
  }

  public function createPaymentIntent() {
    $body = $this->apiRequest->body;
    $payment = new Shop();

    $response = $payment->createPaymentIntent($body->items, $body->currency);

    return json_encode($response);
  }

  public function updatePaymentIntent() {
    $body = $this->apiRequest->body;
    $payment = new Shop();

    // TODO: Validate!
    $validUpdatePayload = ['receipt_email' => $body->payload->receipt_email];
    $response = $payment->updatePaymentIntent($body->paymentIntentId, $validUpdatePayload);

    return json_encode($response);
  }
}