<?php
require_once '../controllers/Webhook.php';
echo Webhook::handleStripeWebhook();