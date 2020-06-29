<?php
use Stripe\Stripe;
use Stripe\Price;
use Stripe\PaymentIntent;
use Stripe\Product;
use Stripe\Exception;

// TODO: What happens if program can't connect to Stripe APIs?
// TODO: Error handling while interacting with external API
class Shop
{
  private $expectedPrice;

  public function __construct()
  {
    Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
  }

  private function calculateOrderAmount($items) {
    $price = $this->retrievePriceById($items[0]->priceId);

    if ($this->expectedPrice->unit_amount != $price->unit_amount) {
      http_response_code(500);
      echo json_encode([ 'error' => 'Internal server error.' ]);
      exit;
    }

    return $this->expectedPrice->unit_amount;
  }

  public function createPaymentIntent($items, $currency) {
    try {
      // HACK: Since I'm only selling one product...
      $this->expectedPrice = Price::retrieve($_ENV["STRIPE_PRICE_ID"]);
    } catch (Exception\ApiErrorException $e) {
      return $e;
    }

    try {
      $paymentIntent = PaymentIntent::create([
        'amount' => $this->calculateOrderAmount($items),
        'currency' => $currency,
      ]);

      return [
        'paymentIntentId' => $paymentIntent->id,
        'publishableKey' => $_ENV['STRIPE_PUBLISHABLE_KEY'],
        'clientSecret' => $paymentIntent->client_secret
      ];
    } catch (Exception\ApiErrorException $e) {
      return $e;
    }

  }

  public function updatePaymentIntent($paymentIntentId, $updatePayload) {
    try {
      $updateResponse = PaymentIntent::update($paymentIntentId, $updatePayload);
      return [
        'paymentIntentId' => $updateResponse->id,
        'receipt_email' => $updateResponse->receipt_email
      ];
    } catch (Exception\ApiErrorException $e) {
      return $e;
    }
  }

  public function retrievePaymentIntentById($paymentIntentId) {
    try {
      return PaymentIntent::retrieve($paymentIntentId);
    } catch (Exception\ApiErrorException $e) {
      return $e;
    }
  }

  public function retrievePriceById($priceId) {
    try {
      return Price::retrieve($priceId);
    } catch (Exception\ApiErrorException $e) {
      return $e;
    }
  }

  public function retrieveProductById($productId) {
    try {
      return Product::retrieve($productId);
    } catch (Exception\ApiErrorException $e) {
      return $e;
    }
  }
}

