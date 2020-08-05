<?php
require_once '../vendor/autoload.php';
require '../classes/ApiRequest.php';
require '../classes/ApiResponse.php';

class Webhook
{
  static public function handleStripeWebhook() {
    $apiRequest = new ApiRequest();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $webhook_signing_secret = $_ENV['STRIPE_WEBHOOK_SIGNING_SECRET'];
    $event = null;
    try {
      $event = \Stripe\Webhook::constructEvent(
        $apiRequest->raw, $sig_header, $webhook_signing_secret
      );
    } catch (\UnexpectedValueException $e) {
      return new ApiResponse([], 400);
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
      return new ApiResponse([], 400);
    }

    switch ($event->type) {
      case 'payment_intent.created':
      case 'charge.failed':
      case 'charge.succeded':
      case 'payment_intent.failed':
      case 'payment_intent.succeeded':
        // TODO: Store succeeded information after payment successfully processed.
        break;
    }

    return new ApiResponse();
  }
}