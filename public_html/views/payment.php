<?php
function priceToStr($price) {
    return '$' . $price->unit_amount / 100;
}

function sumPrice($prices) {
    $total = 0;
    foreach ($prices as $p) {
        $total += $p->unit_amount;
    }
    return '$' . $total / 100;
}

class PriceProduct {
    public $price;
    public $product;
}

$shop = new Shop();
$smsJournalProduct = new PriceProduct();
$smsJournalProduct->price = $shop->retrievePriceById($_ENV["STRIPE_PRICE_ID"]);
$smsJournalProduct->product = $shop->retrieveProductById($smsJournalProduct->price->product);
$priceProducts = [$smsJournalProduct];

$tomorrow = date("Y-m-d", strtotime("+1 day"));
$oneYearFromToday = date('Y-m-d', strtotime(date("Y-m-d", mktime()) . " + 365 day"));
?>
<div class="container">
    <form id="payment-form" class="needs-validation" novalidate="">
        <div class="py-5 text-center">
            <h4>Tell us more about the two of you...</h4>
        </div>
        <div class="row">
            <div class="col-md-5">
                <div class="card mt-3">
                    <div class="card-header">
                        Person One
                    </div>
                    <div class="card-body">
                        <div>
                            <label for="primary_firstname">First Name</label>
                            <input id="primary_firstname" name="primary_firstname" class="form-control" type="text" required>
                            <div id="primary_firstname-feedback" class="invalid-feedback">
                                Please input a valid first name
                            </div>
                        </div>
                        <div>
                            <label for="primary_lastname">Last Name</label>
                            <input id="primary_lastname" name="primary_lastname" class="form-control" type="text" required>
                            <div id="primary_lastname-feedback" class="invalid-feedback">
                                Please input a valid last name
                            </div>
                        </div>
                        <div>
                            <label for="primary_phonenumber">Phone Number</label>
                            <input id="primary_phonenumber"
                                   name="primary_phonenumber"
                                   class="form-control"
                                   type="tel"
                                   required>
                            <div id="primary_phonenumber-feedback" class="invalid-feedback">
                                Please input a valid phone number
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 offset-md-2">
                <div class="card mt-3">
                    <div class="card-header">
                        Person Two
                    </div>
                    <div class="card-body">
                        <div>
                            <label for="secondary_firstname">First Name</label>
                            <input id="secondary_firstname" name="secondary_firstname" class="form-control" type="text" required>
                            <div id="secondary_firstname-feedback" class="invalid-feedback">
                                Please input a valid first name
                            </div>
                        </div>
                        <div>
                            <label for="secondary_lastname">Last Name</label>
                            <input id="secondary_lastname" name="secondary_lastname" class="form-control" type="text" required>
                            <div id="secondary_lastname-feedback" class="invalid-feedback">
                                Please input a valid last name
                            </div>
                        </div>
                        <div>
                            <label for="secondary_phonenumber">Phone Number</label>
                            <input id="secondary_phonenumber"
                                   name="secondary_phonenumber"
                                   class="form-control"
                                   type="tel"
                                   required>
                            <div id="secondary_phonenumber-feedback" class="invalid-feedback">
                                Please input a valid phone number
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-5 text-center">
            <h4>When would you like to start receiving texts?</h4>
        </div>

        <div class="row">
            <div class="col-md-6 offset-md-3 order-md-2">
                <div class="form-group row">
                    <label for="date-input" class="col-2 col-form-label">Date</label>
                    <div class="col-10">
                        <input id="start_date" name="start_date" class="form-control" type="date" value="<?php echo $tomorrow; ?>" min="<?php echo $tomorrow; ?>" max="<?php echo $oneYearFromToday; ?>" required>
                        <small class="form-text text-muted">You can receive texts starting as soon as tomorrow!</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-3 text-center">
            <h2>Ready to start?</h2>
        </div>

        <div class="row">
            <div class="col-md-8 offset-md-2 order-md-2 mb-4">
                <div class="card">
                    <div class="card-header text-right">
                        <span class="text-muted">Your cart</span>
                        <span class="badge badge-primary badge-pill"><?php echo count($priceProducts); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <ul id="cart_list" class="list-group list-group-flush mb-3">
                          <?php
                          foreach ($priceProducts as $pp) {
                            echo '
                    <li class="list-group-item d-flex justify-content-between lh-condensed">
                        <div class="mr-3">
                            <h6 class="my-0 mb-2">' . $pp->product->name . '</h6>
                            <p class="text-muted">
                                ' . $pp->product->description . '
                            </p>
                        </div>
                        <span class="price">' . priceToStr($pp->price) . '</span>
                        <input type="hidden" name="price" value="' . $pp->price->id . '" />
                    </li>
                           ';
                          }
                          ?>
                            <li class="list-group-item d-flex justify-content-between"
                                style="border-bottom-width: 1px;">
                                <strong>Total (USD)</strong>
                                <strong id="display_price"><?php echo sumPrice(array_map(function ($pp) {
                                    return $pp->price;
                                  }, $priceProducts)); ?></strong>
                            </li>
                        </ul>
                        <div class="row m-0 mb-3">
                            <div class="col-md-2">
                                <label for="coupon_code">Coupon</label>
                            </div>
                            <div class="col-md-7">
                                <input id="coupon_code" name="coupon_code" type="text" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <button id="coupon_code_btn" class="btn btn-primary btn-block">Redeem</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4 mb-4">
                    <div class="col-md-2">
                        <label for="email">Email</label>
                    </div>
                    <div class="col-md-10">
                        <input id="email" name="email" class="form-control" type="email" required>
                    </div>
                    <div class="col-12">
                        <small class="form-text text-muted">
                            Where should we send your receipt?
                        </small>
                        <div id="email-feedback" class="invalid-feedback">
                            Please input a valid email address
                        </div>
                    </div>
                </div>

                <div id="cc-section">
                    <label for="card-element">Credit or debit card</label>
                    <div id="card-element" class="form-control" style="height: 2.4em; padding-top: .7em; margin-bottom: .5em;"></div>
                </div>

                <hr class="mb-4">
                <button class="btn btn-primary btn-lg btn-block" type="submit">Pay</button>

            </div>
        </div>
    </form>

    <div class="form-errors d-none">
        <hr class="mb-4">
        <div class="sr-field-error alert alert-danger" role="alert" style="white-space: pre;"></div>
    </div>

    <script>
        const orderData = {
            items: [
                <?php
                    foreach ($priceProducts as $pp) {
                        echo '{
                    id: "' . $pp->product->id . '",
                    priceId: "' . $pp->price->id . '"
                },';
                    }
                ?>

            ],
            currency: "usd"
        };
    </script>
</div>
