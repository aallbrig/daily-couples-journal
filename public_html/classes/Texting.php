<?php
require_once '../vendor/autoload.php';
use Twilio\Rest\Client;

class Texting
{
  private $twilioClient;

  public function __construct()
  {
    $this->twilioClient = new Client($_ENV['TWILIO_SID'], $_ENV['TWILIO_AUTH']);
  }

  public function sendQuestion($phoneNumber, $question)
  {
    return $this->twilioClient->messages->create(
      $phoneNumber,
      array(
        'from' => $_ENV['TWILIO_PHONE_NUMBER'],
        'body' => $question
      )
    );
  }
}
