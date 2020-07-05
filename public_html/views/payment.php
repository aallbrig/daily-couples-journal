<?php
function priceToStr($price) {
    // HACK: Drop last two characters because stripe represents $16 as 1600
    return money_format('$%.2n', substr($price->unit_amount, 0, -2));
}

function sumPrice($prices) {
    $total = 0;
    foreach ($prices as $p) {
        $total += $p->unit_amount;
    }
    return money_format('$%.2n', substr($total, 0, -2));
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
            <h4>Tell us more about you two!</h4>
        </div>
        <div class="row">
            <div class="col-md-5">
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
            <div class="col-md-5 offset-md-1">
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

        <div class="py-5 text-center">
            <h4>When would you like to start receiving texts?</h4>
        </div>

        <div class="row">
            <div class="col-md-6 offset-md-3 order-md-2 mb-4">
                <div class="form-group row">
                    <label for="date-input" class="col-2 col-form-label">Date</label>
                    <div class="col-10">
                        <input id="start_date" name="start_date" class="form-control" type="date" value="<?php echo $tomorrow; ?>" min="<?php echo $tomorrow; ?>" max="<?php echo $oneYearFromToday; ?>" required>
                        <small class="form-text text-muted">You will receive texts starting as soon as tomorrow!</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-5 text-center">
            <h2>Ready to start?</h2>
        </div>

        <div class="row">
            <div class="col-md-6 offset-md-3 order-md-2 mb-4">
                <h4 class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted">Your cart</span>
                    <span class="badge badge-secondary badge-pill"><?php echo count($priceProducts); ?></span>
                </h4>
                <ul class="list-group mb-3">
                    <?php
                        foreach ($priceProducts as $pp) {
                           echo '
                    <li class="list-group-item d-flex justify-content-between lh-condensed">
                        <div>
                            <h6 class="my-0">' . $pp->product->name . '</h6>
                        </div>
                        <span class="text-muted">' . priceToStr($pp->price) . '</span>
                    </li>
                           ';
                        }
                    ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total (USD)</span>
                        <strong><?php echo sumPrice(array_map(function ($pp) {
                            return $pp->price;
                          }, $priceProducts)); ?></strong>
                    </li>
                </ul>
                <label for="card-element">Credit or debit card</label>
                <div id="card-element" class="form-control" style="height: 2.4em; padding-top: .7em; margin-bottom: .5em;"></div>

                <label for="email">Email</label>
                <input id="email" name="email" class="form-control" type="email" required>
                <div id="email-feedback" class="invalid-feedback">
                    Please input a valid email address
                </div>
                <small class="form-text text-muted">For payment receipt.</small>

                <hr class="mb-4">
                <button class="btn btn-primary btn-lg btn-block" type="submit">Pay</button>

            </div>
        </div>
    </form>

    <div class="form-errors d-none">
        <hr class="mb-4">
        <div class="sr-field-error alert alert-danger" role="alert" style="white-space: pre;"></div>
    </div>

    <div class="sr-result d-none">
        <div class="modal" style="display: block" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Payment Complete!</h4>
                    </div>
                    <div class="modal-body">
                        <h5>Next Steps...</h5>
                        <p>Congrats on signing up for this experience!</p>
                        <p>You will receive texts as a couple starting on <b id="start_date"><?php echo $tomorrow; ?></b>.</p>
                        <p>Once you receive the text for the day, it will be up to the two of you to have the conversation!</p>
                        <p>Remember to have fun! 📱</p>
                    </div>
                </div>
            </div>
        </div>
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
