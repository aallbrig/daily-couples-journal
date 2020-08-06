<?php
include_once 'ApiValidator.php';


class SaveProductValidator extends ApiValidator
{
  public function __construct($dataToValidate, $includeCoupon = false)
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
    $paymentIntent = 'payment_intent';
    $couponCode = 'coupon_code';
    $price = 'price';

    $rules = [
      'required' => [
        $primaryFirstNameInput,
        $primaryLastNameInput,
        $primaryPhoneNumberInput,
        $secondaryFirstNameInput,
        $secondaryLastNameInput,
        $secondaryPhoneNumberInput,
        $emailInput,
        $dateInput,
        $price,
        $paymentIntent
      ],
      'validPriceId' => [$price],
      'validPaymentIntentId' => [$paymentIntent],
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
    ];

    if ($includeCoupon) {
      $rules = array_merge($rules, [
        'validCouponCodeId' => [$couponCode],
      ]);
    }

    $this->v->rules($rules);
  }
}
