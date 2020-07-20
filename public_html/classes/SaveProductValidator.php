<?php
include_once 'ApiValidator.php';

class SaveProductValidator extends ApiValidator
{
  public function __construct($dataToValidate)
  {
    parent::__construct($dataToValidate);

    $phoneNumberRegex = '/^([0-9]( |-)?)?(\(?[0-9]{3}\)?|[0-9]{3})( |-)?([0-9]{3}( |-)?[0-9]{4}|[a-zA-Z0-9]{7})$/';

    $emailInput = 'email';
    $dateInput = 'start_date';
    $primaryFirstNameInput = 'primary_firstname';
    $primaryLastNameInput = 'primary_lastname';
    $primaryPhoneNumberInput = 'primary_phonenumber';
    $secondaryFirstNameInput = 'secondary_firstname';
    $secondaryLastNameInput = 'secondary_lastname';
    $secondaryPhoneNumberInput = 'secondary_phonenumber';
    $stripeInput = 'stripe_result';
    $couponCode = 'coupon_code';

    $this->v->rules([
      'required' => [
        $primaryFirstNameInput,
        $primaryLastNameInput,
        $primaryPhoneNumberInput,
        $secondaryFirstNameInput,
        $secondaryLastNameInput,
        $secondaryPhoneNumberInput,
        $emailInput
      ],
      'optional' => [
        [$stripeInput]
      ],
      'requiredWithout' => [
        [$stripeInput, [$couponCode]]
      ],
      'email' => [$emailInput],
      'regex' => [
        [[$primaryPhoneNumberInput, $secondaryPhoneNumberInput], $phoneNumberRegex],
      ],
      'different' => [
        [$primaryFirstNameInput, $primaryLastNameInput],
        [$primaryFirstNameInput, $secondaryFirstNameInput],
        [$secondaryFirstNameInput, $primaryFirstNameInput],
        [$secondaryFirstNameInput, $secondaryLastNameInput],
        [$primaryPhoneNumberInput, $secondaryPhoneNumberInput],
        [$secondaryPhoneNumberInput, $primaryPhoneNumberInput]
      ],
      'lengthMax' => [
        [[
          $primaryFirstNameInput,
          $primaryLastNameInput,
          $secondaryFirstNameInput,
          $secondaryLastNameInput
        ], 64]
      ],
      'dateAfter' => [
        [$dateInput, date('Y-m-d')]
      ],
      'dateBefore' => [
        [$dateInput, date('Y-m-d', strtotime(date("Y-m-d", mktime()) . " + 365 day"))]
      ]
    ]);
  }
}
