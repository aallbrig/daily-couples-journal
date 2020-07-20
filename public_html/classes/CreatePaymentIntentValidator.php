<?php
include_once 'ApiValidator.php';

class CreatePaymentIntentValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $this->v->rules([
      'required' => [
        'items',
        'currency'
      ],
      'validProductId' => ['items.*.id'],
      'validPriceId' => ['items.*.priceId']
    ]);
  }
}
