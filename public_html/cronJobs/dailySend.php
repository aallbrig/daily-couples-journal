<?php
require '../classes/PersistenceStore.php';
require '../classes/Texting.php';
$db = new PersistenceStore();

function sendResponseToRequest($output) {
  ignore_user_abort(true);
  ob_start();

  echo $output;

  header('Content-Encoding: none');
  header('Content-Length: ' . ob_get_length());
  header('Connection: close');
  ob_end_flush();
  ob_flush();
  flush();
}

function acceptableTime() {
  // HACK: Assume Indy time is okay for "acceptable time" condition
  date_default_timezone_set('America/Indiana/Indianapolis');
  $acceptableTimeStart = DateTime::createFromFormat('H:i a', '8:00 am');
  $acceptableTimeEnd = DateTime::createFromFormat('H:i a', '6:00 pm');
  $currentTime = DateTime::createFromFormat('H:i a', date('H:i a'));
  return $currentTime > $acceptableTimeStart && $currentTime < $acceptableTimeEnd;
}

function sendSms() {
  // make sure these texts are not being sent at unreasonable hours
  if (!acceptableTime()) {
    echo json_encode([
      'status' => 'not started',
      'reason' => 'unacceptable texting time'
    ]);
    exit;
  }

  global $db;
  $texting = new Texting();
  // This script may take a while...
  set_time_limit(0);

  $jobId = $db->startNewDailySendJob();
  // We want to send a response to the requester without making them wait for the script
  // to finish
  sendResponseToRequest(
    json_encode([
      'status' => 'started',
      'jobId' => $jobId
    ])
  );

  $questions = $db->retrieveDailyQuestions();
  $batchSize = $_ENV['DAILY_SEND_BATCH_SIZE'];
  $lastSentBatch = $db->retrieveLastQuestionSends($batchSize);
  while (count($lastSentBatch) > 0) {
    // TODO: Error handling behavior
    foreach ($lastSentBatch as $lastSend) {
      // get the next question we need to send
      $nextQuestion = null;
      if ($lastSend['previous_question_id'] == null) {
        $nextQuestion = $questions[0];
      } else {
        $nextQuestion = $questions[$lastSend['previous_question_id'] + 1];
      }
      // get the people by couple id so we know what number to send to
      $persons = $db->retrievePersonsByCoupleId($lastSend['couple_id']);
      $twilioSids = [];
      foreach ($persons as $person) {
        // send the text message!
        $twilioResult = $texting->sendQuestion($person['phone_number'], $nextQuestion['question']);
        $twilioSids[] = $twilioResult->sid;
      }
      // add new record to the send receipt table to keep track of the last send
      $db->insertSendReceipt($lastSend['couple_id'], $nextQuestion['id'], json_encode($twilioSids));
    }
    $lastSentBatch = $db->retrieveLastQuestionSends($batchSize);
  }

  $db->completeDailySendJob($jobId);
  exit();
}

$dailySendJobsRunToday = $db->retrieveDailySendJobs();

// Since this job only runs once a day, if there is a job that is showing as running/completed
// then there is no need to start another job
if (count($dailySendJobsRunToday) > 0) {
  echo json_encode($dailySendJobsRunToday);
  exit();
} else {
  sendSms();
}
