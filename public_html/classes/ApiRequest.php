<?php

class ApiRequest
{
  public $body;

  public function __construct()
  {
    header('Content-Type: application/json');
    $input = file_get_contents('php://input');
    $this->body = json_decode($input);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || json_last_error() !== JSON_ERROR_NONE) {
      http_response_code(400);
      echo json_encode([ 'error' => 'Invalid request.' ]);
      exit;
    }
  }
}