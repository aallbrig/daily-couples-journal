<?php
// TODO: Hide away this product retrieval in some sort of controller
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
$price = \Stripe\Price::retrieve($_ENV["STRIPE_PRICE_ID"]);
$product = \Stripe\Product::retrieve($price->product);
// HACKS: Drop last two characters because stripe represents $16 as 1600
$priceStr = money_format('$%.2n', substr($price->unit_amount, 0, -2));
?>
<div class="container">
    <form id="payment-form" class="needs-validation" novalidate="">
        <div class="py-5 text-center">
            <h4>Tell us more about you two!</h4>
        </div>
        <div class="row">
            <div class="col-md-5">
                <label for="primary_firstname">First Name</label>
                <input id="primary_firstname" name="primary_firstname" class="form-control" type="text" required>
                <label for="primary_lastname">Last Name</label>
                <input id="primary_lastname" name="primary_lastname" class="form-control" type="text" required>
                <label for="primary_phonenumber">Phone Number</label>
                <input id="primary_phonenumber" name="primary_phonenumber" class="form-control" type="text" required>
            </div>
            <div class="col-md-5 offset-md-1">
                <label for="secondary_firstname">First Name</label>
                <input id="secondary_firstname" name="secondary_firstname" class="form-control" type="text" required>
                <label for="secondary_lastname">Last Name</label>
                <input id="secondary_lastname" name="secondary_lastname" class="form-control" type="text" required>
                <label for="secondary_phonenumber">Phone Number</label>
                <input id="secondary_phonenumber" name="secondary_phonenumber" class="form-control" type="text" required>
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
                        <input id="date-input" name="start_date" class="form-control" type="date" value="<?php echo date("Y-m-d", strtotime("+1 day")); ?>" min="<?php echo date("Y-m-d", strtotime("+1 day")); ?>" max="<?php echo date('Y-m-d',strtotime(date("Y-m-d", mktime()) . " + 365 day")); ?>" required>
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
                    <span class="badge badge-secondary badge-pill">1</span>
                </h4>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between lh-condensed">
                        <div>
                            <h6 class="my-0"><?php echo $product->name; ?></h6>
                        </div>
                        <span class="text-muted"><?php echo $priceStr; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total (USD)</span>
                        <strong><?php echo $priceStr; ?></strong>
                    </li>
                </ul>
                <label for="card-element">Credit or debit card</label>
                <div id="card-element" class="form-control" style="height: 2.4em; padding-top: .7em; margin-bottom: .5em;"></div>

                <label for="email">Email</label>
                <input id="email" name="email" class="form-control" type="email" required>
                <div class="invalid-feedback">
                    Please input a valid email address
                </div>
                <small class="form-text text-muted">For payment receipt.</small>

                <hr class="mb-4">
                <button class="btn btn-primary btn-lg btn-block" type="submit">Pay</button>

                <div class="form-errors d-none">
                    <hr class="mb-4">
                    <div class="sr-field-error alert alert-danger" role="alert"></div>
                </div>
            </div>
        </div>
    </form>

    <div class="sr-result d-none alert alert-success" role="alert">
        <h3 class="text-center">Payment Complete!</h3>
        <pre>
            <code></code>
        </pre>
    </div>

    <footer class="my-5 pt-5 text-muted text-center text-small">
        <p class="mb-1">© 2020-2020 Allbright Corp</p>
    </footer>
    <script>
        const orderData = {
            items: [{
                id: "<?php echo $product->id; ?>",
                priceId: "<?php echo $price->id; ?>"
            }],
            currency: "usd"
        };
    </script>
</div>
