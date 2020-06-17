<?php

require_once 'shared.php';

function insertPerson($conn, $firstname, $lastname, $phonenumber) {
  $personId = null;
  if ($stmt = $conn->prepare("INSERT INTO people (first_name, last_name, phone_number) VALUES (?, ?, ?);")) {
    $stmt->bind_param("sss", $firstname, $lastname, $phonenumber);
    $stmt->execute();
    $personId = $stmt->insert_id;
    $stmt->close();
  }
  return $personId;
}

function insertCouple($conn, $primaryPersonId, $secondaryPersonId) {
  $coupleId = null;
  if ($stmt = $conn->prepare("INSERT INTO couple (primary_person_id, secondary_person_id) VALUES (?, ?);")) {
    $stmt->bind_param("ii", $primaryPersonId, $secondaryPersonId);
    $stmt->execute();
    $coupleId = $stmt->insert_id;
    $stmt->close();
  }
  return $coupleId;
}

function insertProductOrder($conn, $coupleId, $stripePaymentResult) {
  $productOrderId = null;
  if ($stmt = $conn->prepare("INSERT INTO product_order (couple_id, stripe_result) VALUES (?, CAST(? AS JSON));")) {
    $stmt->bind_param("ss", $coupleId, $stripePaymentResult);
    $stmt->execute();
    $productOrderId = $stmt->insert_id;
    $stmt->close();
  }
  return $productOrderId;
}

$hostname = $_ENV["MYSQL_HOSTNAME"];
$database = $_ENV["MYSQL_DATABASE"];
$username = $_ENV["MYSQL_USERNAME"];
$password = $_ENV["MYSQL_PASSWORD"];
$conn = new mysqli($hostname, $username, $password, $database) or die("Connect failed: %s\n". $conn->error);

$primaryPersonId = insertPerson($conn, $body->primary_firstname, $body->primary_lastname, $body->primary_phonenumber);
$secondaryPersonId = insertPerson($conn, $body->primary_firstname, $body->primary_lastname, $body->primary_phonenumber);
$coupleId = insertCouple($conn, $primaryPersonId, $secondaryPersonId);
$productOrderId = insertProductOrder($conn, $coupleId, $body->stripe_result);

$conn->close();

echo json_encode([
  'productOrderId' => $productOrderId
]);
