let Card;
let ClientSecret;
let stripe;
let paymentIntentId;
let payOnceResult = null;

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
        Card = card;
        ClientSecret = clientSecret;
        document.querySelector("button").disabled = false;
    });

// Handle form submission.
const form = document.getElementById("payment-form");
form.addEventListener("submit", function(event) {
    event.preventDefault();
    // Initiate payment when the submit button is clicked
    pay(stripe, Card, ClientSecret)
        .then((result) => {
            // If successful, save the user's form data
            const formData = new FormData(form);
            formData.append("client_secret", ClientSecret);
            formData.append("stripe_result", JSON.stringify(result));

            fetch("/api/save-product.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
                .then(async (result) => {
                    console.log(result);
                    if (result.status == 200) {
                        orderComplete(ClientSecret);
                    } else if (result.status == 400) {
                        const json = await result.json();
                        // form.classList.add('was-validated');
                        Object.keys(json.errors).forEach((key) => {
                            const elem = document.getElementById(key);
                            const invalidFeedbackElem = document.getElementById(`${key}-feedback`);
                            if (invalidFeedbackElem) {
                                invalidFeedbackElem.innerHTML = json.errors[key].join("<br />")
                            }
                            elem.classList.add('is-invalid');
                        })
                        showError(JSON.stringify(json.errors, null, 4));
                    } else {
                        showError('Something went wrong');
                    }
                })
        })
        .catch((error) => {
            showError(error.message);
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
        (payOnceResult ?
            new Promise((res) => res(payOnceResult))
            : stripe.confirmCardPayment(clientSecret, { payment_method: { card: card } })
        )
            .then(function(result) {
                if (result.error) {
                    reject(result.error);
                } else {
                    payOnceResult = result;
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
    }, 10000);
};