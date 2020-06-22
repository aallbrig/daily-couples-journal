<?php
require_once '../models/database.php';

class PersistenceStore
{
  public $conn;

  public function __construct()
  {
    $this->conn = getDatabaseConnection(
      $_ENV["MYSQL_HOSTNAME"],
      $_ENV["MYSQL_DATABASE"],
      $_ENV["MYSQL_USERNAME"],
      $_ENV["MYSQL_PASSWORD"]
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

  public function saveProductOrder($coupleId, $paymentResultJSON) {
    return insertProductOrder($this->conn, $coupleId, $paymentResultJSON);
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
}

