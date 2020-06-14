<?php

require_once 'shared.php';

function calculateOrderAmount($items) {
    // Replace this constant with a calculation of the order's amount
    // Calculate the order total on the server to prevent
    // people from directly manipulating the amount on the client
    return 1400;
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
