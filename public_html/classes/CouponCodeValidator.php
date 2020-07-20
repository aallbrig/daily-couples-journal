<?php
include_once 'ApiValidator.php';

class CouponCodeValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $this->v->rules([
      'required' => ['couponCode']
    ]);
  }
}
