<?php
include_once 'ApiValidator.php';

class UpdatePaymentIntentValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $this->v->rules([
      'required' => [
        'paymentIntentId',
        'payload',
        'payload.receipt_email'
      ],
      'validPaymentIntentId' => ['paymentIntentId'],
      'paymentIntentIdNotAlreadyStored' => ['paymentIntentId'],
      'email' => ['payload.receipt_email']
    ]);
  }
}
