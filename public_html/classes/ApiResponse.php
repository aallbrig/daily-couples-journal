<?php

class ApiResponse
{
  public $statusCode;
  public $jsonPayload;

  public function __construct($jsonBody, $statusCode = 200)
  {
    $this->jsonPayload = $jsonBody;
    $this->statusCode = $statusCode;
  }

  public function __toString()
  {
    header('Content-Type: application/json');
    http_response_code($this->statusCode);
    return json_encode($this->jsonPayload);
  }
}
