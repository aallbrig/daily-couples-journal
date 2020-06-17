<?php

require_once 'shared.php';

$expectedPrice = \Stripe\Price::retrieve($_ENV["STRIPE_PRICE_ID"]);

function calculateOrderAmount($items) {
    global $expectedPrice;
    $price = \Stripe\Price::retrieve($items[0]->priceId);
    if ($expectedPrice->unit_amount != $price->unit_amount) {
      http_response_code(500);
      echo json_encode([ 'error' => 'Internal server error.' ]);
      exit;
    }
    return $expectedPrice->unit_amount;
}

$paymentIntent = \Stripe\PaymentIntent::create([
    'amount' => calculateOrderAmount($body->items),
    'currency' => $body->currency,
]);

$output = [
    'publishableKey' => $_ENV['STRIPE_PUBLISHABLE_KEY'],
    'clientSecret' => $paymentIntent->client_secret,
];

echo json_encode($output);
