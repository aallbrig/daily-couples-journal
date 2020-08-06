<?php
require_once '../models/database.php';

// TODO: What happens if program can't connect to DB?
class PersistenceStore
{
  public $conn;

  public function __construct()
  {
    $this->conn = getDatabaseConnection(
      $_ENV["MYSQL_HOSTNAME"],
      $_ENV["MYSQL_USERNAME"],
      $_ENV["MYSQL_PASSWORD"],
      $_ENV["MYSQL_DATABASE"]
    );
  }

  public function __destruct()
  {
    $this->conn->close();
  }

  public function savePerson($firstName, $lastName, $phoneNumber) {
    return insertPerson($this->conn, $firstName, $lastName, $phoneNumber);
  }

  public function saveCouple($primaryPersonId, $secondaryPersonId) {
    return insertCouple($this->conn, $primaryPersonId, $secondaryPersonId);
  }

  public function saveProductOrder($coupleId, $startDate, $active) {
    return insertProductOrder($this->conn, $coupleId, $startDate, $active);
  }

  public function savePaymentIntentId($paymentIntentId) {
    $stripeDataId = null;
    if ($stmt = $this->conn->prepare("
      INSERT INTO stripe_data (payment_intent_id)
      VALUES (?);
    ")) {
      $stmt->bind_param("s", $paymentIntentId);
      $stmt->execute();
      $stripeDataId = $stmt->insert_id;
      $stmt->close();
    }
    return $stripeDataId;
  }

  public function saveCouponId($paymentIntentId, $couponId) {
    $updateErrors = [];
    if ($stmt = $this->conn->prepare("
      UPDATE stripe_data
      SET coupon_id = ?
      WHERE payment_intent_id = ?;
    ")) {
      $stmt->bind_param("ss", $couponId, $paymentIntentId);
      $stmt->execute();
      $updateErrors = $stmt->error_list;
    }
    return $updateErrors;
  }

  public function retrieveDailySendJobs() {
    return retrieveDailySendJobs($this->conn);
  }

  public function startNewDailySendJob() {
    return insertNewDailySendJob($this->conn);
  }

  public function completeDailySendJob($jobId) {
    return updateDailySendJobToComplete($this->conn, $jobId);
  }

  public function retrieveDailyQuestions() {
    return retrieveDailyQuestions($this->conn);
  }

  public function retrieveLastQuestionSends($limit = 100) {
    return retrieveLastQuestionSends($this->conn, $limit);
  }

  public function insertSendReceipt($coupleId, $questionId, $twilioSids) {
    return insertSendReceipt($this->conn, $coupleId, $questionId, $twilioSids);
  }

  public function retrievePersonsByCoupleId($coupleId) {
    return retrievePersonsByCoupleId($this->conn, $coupleId);
  }

  public function relateProductOrderToStripeData($paymentIntentId, $productOrderId) {
    $productOrderToStripeDataId = null;
    if ($stmt = $this->conn->prepare("
      INSERT INTO product_order_to_stripe_data (product_order_id, stripe_data_payment_intent_id)
      VALUES (?, ?);
    ")) {
      $stmt->bind_param("is", $productOrderId, $paymentIntentId);
      $stmt->execute();
      $productOrderToStripeDataId = $stmt->insert_id;
      $stmt->close();
    }
    return $productOrderToStripeDataId;
  }
}
