let stripe;
let paymentIntentId;

// Disable the button until we have Stripe set up on the page
document.querySelector("button").disabled = true;
// TODO: Add in validation experience for all form inputs
document.querySelector("#email").addEventListener('change', (e) => {
    if (e.target.checkValidity()) {
        e.target.classList.remove('is-invalid');
        e.target.classList.add('is-valid');
       const emailValue = e.target.value;
       updatePaymentIntent(paymentIntentId, emailValue);
    } else {
        e.target.classList.add('is-invalid');
    }
});

fetch("/api/create-payment-intent.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify(orderData)
})
    .then((result) => result.json())
    .then((json) => {
        paymentIntentId = json.paymentIntentId
        return setupElements(json)
    })
    .then(function({ stripe, card, clientSecret }) {
        document.querySelector("button").disabled = false;

        // Handle form submission.
        const form = document.getElementById("payment-form");
        form.addEventListener("submit", function(event) {
            event.preventDefault();
            // Initiate payment when the submit button is clicked
            pay(stripe, card, clientSecret).then((result) => {
                // If successful, save the user's form data
                const formData = new FormData(form);
                formData.append("client_secret", clientSecret);
                formData.append("stripe_result", JSON.stringify(result));

                fetch("/api/save-product.php", {
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json"
                  },
                  body: JSON.stringify(Object.fromEntries(formData))
                })
            });
        });
    });

// Set up Stripe.js and Elements to use in checkout form
const setupElements = function(data) {
    stripe = Stripe(data.publishableKey);
    const elements = stripe.elements();
    const style = {
        base: {
            color: "#32325d",
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: "antialiased",
            fontSize: "16px",
            "::placeholder": {
                color: "#aab7c4"
            }
        },
        invalid: {
            color: "#fa755a",
            iconColor: "#fa755a"
        }
    };

    const card = elements.create("card", { style: style });
    card.mount("#card-element");

    return {
        stripe: stripe,
        card: card,
        clientSecret: data.clientSecret
    };
};

const pay = function(stripe, card, clientSecret) {
    return new Promise((resolve, reject) => {
        stripe
            .confirmCardPayment(clientSecret, { payment_method: { card: card } })
            .then(function(result) {
                if (result.error) {
                    showError(result.error.message);
                    reject(result.error);
                } else {
                    orderComplete(clientSecret);
                    resolve(result);
                }
            });
    });
};

const updatePaymentIntent = (paymentIntentId, email) => {
   return fetch("/api/update-payment-intent.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            paymentIntentId: paymentIntentId,
            payload: {
                receipt_email: email
            }
        })
    })
};

/* ------- Post-payment helpers ------- */

/* Shows a success / error message when the payment is complete */
const orderComplete = function(clientSecret) {
    stripe.retrievePaymentIntent(clientSecret).then(function(result) {
        var paymentIntent = result.paymentIntent;
        var paymentIntentJson = JSON.stringify(paymentIntent, null, 2);

        document.querySelector("#payment-form").classList.add("d-none");
        document.querySelector("pre").textContent = paymentIntentJson;

        document.querySelector(".sr-result").classList.remove("d-none");
        setTimeout(function() {
            document.querySelector(".sr-result").classList.add("d-block");
        }, 200);
    });
};

const showError = function(errorMsgText) {
    document.querySelector(".form-errors").classList.remove("d-none");
    const errorMsg = document.querySelector(".sr-field-error");
    errorMsg.textContent = errorMsgText;
    setTimeout(function() {
        document.querySelector(".form-errors").classList.add("d-none");
        errorMsg.textContent = "";
    }, 4000);
};