<?php
require_once '../controllers/Api.php';

$api = new Api();
echo $api->sendSms();
