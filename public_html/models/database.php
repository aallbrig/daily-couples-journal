<?php
// TODO: I don't think this file matches a "models" concept
// TODO: I suppose I could represent the serialize associative arrays as instantiated objects
//       ...but what does that buy me?
function getDatabaseConnection($hostname, $username, $password, $database) {
  $conn = new mysqli($hostname, $username, $password, $database) or die("Connect failed: %s\n". $conn->error);
  return $conn;
}

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

function insertProductOrder($conn, $coupleId, $startDate, $stripePaymentResult) {
  $productOrderId = null;
  if ($stmt = $conn->prepare("INSERT INTO product_order (couple_id, start_date, stripe_result) VALUES (?, CAST(? AS DATETIME), CAST(? AS JSON));")) {
    $stmt->bind_param("sss", $coupleId, $startDate, $stripePaymentResult);
    $stmt->execute();
    $productOrderId = $stmt->insert_id;
    $stmt->close();
  }
  return $productOrderId;
}

function retrieveDailySendJobs($conn) {
  $cronJobStatus = [];
  if ($stmt = $conn->prepare("SELECT * FROM daily_send_cron_status WHERE execution_time >= CURDATE();")) {
    $stmt->execute();
    $result = $stmt->get_result();
    $cronJobStatus = $result->fetch_all(MYSQLI_ASSOC);
  }
  return $cronJobStatus;
}

function insertNewDailySendJob($conn) {
  $cronStatusId = null;
  if ($stmt = $conn->prepare("INSERT INTO daily_send_cron_status (execution_time, status) VALUES (CURDATE(), 'executing');")) {
    $stmt->execute();
    $cronStatusId = $stmt->insert_id;
  }
  return $cronStatusId;
}

function updateDailySendJobToComplete($conn, $cronStatusId) {
  $updateErrors = [];
  if ($stmt = $conn->prepare("UPDATE daily_send_cron_status SET status = 'complete' WHERE id = ?;")) {
    $stmt->bind_param("i", $cronStatusId);
    $stmt->execute();
    $updateErrors = $stmt->error_list;
  }
  return $updateErrors;
}

function retrieveDailyQuestions($conn) {
  $questions = [];
  if ($stmt = $conn->prepare("SELECT * FROM daily_question;")) {
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);
  }
  return $questions;
}

function retrieveLastQuestionSends($conn, $limit = 100) {
  $lastSends = [];
  if ($stmt = $conn ->prepare("
    SELECT po.couple_id, lastsend.previous_question_id
    FROM product_order AS po
        LEFT JOIN (
            SELECT
                couple_id,
                MAX(question_id) AS previous_question_id,
                MAX(send_time) AS send_time
            FROM send_receipt
            GROUP BY couple_id
        ) AS lastsend ON po.couple_id = lastsend.couple_id
    WHERE
      po.start_date >= CURRENT_DATE()
      AND (
          lastsend.previous_question_id IS NULL
          OR
          (
          lastsend.send_time < CURRENT_DATE()
          AND NOT lastsend.previous_question_id >= (SELECT MAX(id) FROM daily_question)
          )
      )
    LIMIT ?;
  ")) {
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastSends = $result->fetch_all(MYSQLI_ASSOC);
  }
  return $lastSends;
}

function insertSendReceipt($conn, $coupleId, $questionId, $twilioSids) {
  $sendReceiptId = null;
  if ($stmt = $conn->prepare("
    INSERT INTO send_receipt (couple_id, question_id, twilio_sids, send_time)
    VALUES (?, ?, CAST(? AS JSON), CURDATE());")) {
    $stmt->bind_param("iis", $coupleId, $questionId, $twilioSids);
    $stmt->execute();
    $sendReceiptId = $stmt->insert_id;
    $stmt->close();
  }
  return $sendReceiptId;
}

function retrievePersonsByCoupleId($conn, $coupleId) {
  $persons = [];
  if ($stmt = $conn->prepare("
    SELECT p.*
    FROM people AS p
        JOIN couple AS c ON p.id = c.primary_person_id
    WHERE c.id = ?
    UNION ALL
    SELECT p2.*
    FROM people AS p2
        JOIN couple AS c ON p2.id = c.secondary_person_id
    WHERE c.id = ?;
  ")) {
    $stmt->bind_param("ii", $coupleId, $coupleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $persons = $result->fetch_all(MYSQLI_ASSOC);
  }
  return $persons;
}
