<?php
require '../vendor/autoload.php';
require_once '../models/database.php';
use Twilio\Rest\Client;

// TODO: This class doesn't feel right
class Api
{
  private $body;
  private $conn;
  private $expectedPrice;
  private $twilioClient;

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

    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    $this->twilioClient = new Client($_ENV['TWILIO_SID'], $_ENV['TWILIO_AUTH']);
    $this->expectedPrice = \Stripe\Price::retrieve($_ENV["STRIPE_PRICE_ID"]);
    $this->conn = getDatabaseConnection($_ENV["MYSQL_HOSTNAME"], $_ENV["MYSQL_DATABASE"], $_ENV["MYSQL_USERNAME"], $_ENV["MYSQL_PASSWORD"]);
  }

  public function __destruct()
  {
    $this->conn->close();
  }

  public function saveProduct() {

    $primaryPersonId = insertPerson($this->conn, $this->body->primary_firstname, $this->body->primary_lastname, $this->body->primary_phonenumber);
    $secondaryPersonId = insertPerson($this->conn, $this->body->primary_firstname, $this->body->primary_lastname, $this->body->primary_phonenumber);
    $coupleId = insertCouple($this->conn, $primaryPersonId, $secondaryPersonId);
    $productOrderId = insertProductOrder($this->conn, $coupleId, $this->body->stripe_result);

    return json_encode([
      'productOrderId' => $productOrderId
    ]);
  }

  private function calculateOrderAmount($items) {
    $price = \Stripe\Price::retrieve($items[0]->priceId);
    if ($this->expectedPrice->unit_amount != $price->unit_amount) {
      http_response_code(500);
      echo json_encode([ 'error' => 'Internal server error.' ]);
      exit;
    }
    return $this->expectedPrice->unit_amount;
  }

  public function createPaymentIntent() {
    $paymentIntent = \Stripe\PaymentIntent::create([
      'amount' => $this->calculateOrderAmount($this->body->items),
      'currency' => $this->body->currency,
    ]);

    return json_encode([
      'publishableKey' => $_ENV['STRIPE_PUBLISHABLE_KEY'],
      'clientSecret' => $paymentIntent->client_secret,
    ]);
  }

  public function sendSms() {
    $targetPhoneNumber = $this->body->targetPhoneNumber;
    $question = $this->body->question;
    $result = $this->twilioClient->messages->create(
      $targetPhoneNumber,
      array(
        'from' => $_ENV['TWILIO_PHONE_NUMBER'],
        'body' => $question
      )
    );
   if ($result->errorCode == null) {
     return json_encode([
       'complete' => 'ok'
     ]);
   } else {
      http_response_code(400);
      return json_encode([ 'error' => $result->errorMessage ]);
   }
  }
}