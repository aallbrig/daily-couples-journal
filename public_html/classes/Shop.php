<?php
use Stripe\Stripe;
use Stripe\Price;
use Stripe\PaymentIntent;

class Shop
{
  private $expectedPrice;

  public function __construct()
  {
    Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    $this->expectedPrice = Price::retrieve($_ENV["STRIPE_PRICE_ID"]);
  }

  private function calculateOrderAmount($items) {
    $price = Price::retrieve($items[0]->priceId);
    if ($this->expectedPrice->unit_amount != $price->unit_amount) {
      http_response_code(500);
      echo json_encode([ 'error' => 'Internal server error.' ]);
      exit;
    }
    return $this->expectedPrice->unit_amount;
  }

  public function createPaymentIntent($items, $currency) {
    $paymentIntent = PaymentIntent::create([
      'amount' => $this->calculateOrderAmount($items),
      'currency' => $currency,
    ]);

    return [
      'publishableKey' => $_ENV['STRIPE_PUBLISHABLE_KEY'],
      'clientSecret' => $paymentIntent->client_secret,
    ];
  }
}

