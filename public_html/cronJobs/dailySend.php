<?php
require '../classes/PersistenceStore.php';
$db = new PersistenceStore();

function sendResponseToRequestor($output) {
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

function sendSms() {
  global $db;
  // This script may take a while...
  set_time_limit(0);

  $jobId = $db->startNewDailySendJob();
  // We want to send a response to the requester without making them wait for the script
  // to finish
  sendResponseToRequestor(
    json_encode([
      'status' => 'started',
      'jobId' => $jobId
    ])
  );

  // TODO: For each couple has not received a daily question SMS, send a text
  sleep(10);

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
