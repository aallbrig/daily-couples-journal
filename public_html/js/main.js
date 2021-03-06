// TODO: This file is a mess, lol +2
// TODO: These global variables seem to make the code smelly
let Card;
let ClientSecret;
let stripe;
let paymentIntentId;
let payOnceResult = null;

const moneyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
})

const navigateToThankYouPage = (startDate) => {
    window.location.href = `/thank-you.php?start_date=${startDate}`;
};

const recalculateCartTotal = () => {
    const displayPrice = document.getElementById('display_price');
    const cart = document.getElementById('cart_list');
    const currentList = cart.getElementsByTagName('li');
    const total = Array.from(currentList)
        // The last item is the display price
        .slice(0, currentList.length - 1)
        .reduce((total, currentLi) => {
            // Assumes there is only one span in the li
            const [span] = currentLi.getElementsByTagName('span');

            if (span.classList.contains('price')) {
                total += parseFloat(span.textContent.replace(/\$/, ''));
            } else if (span.classList.contains('percent-off')) {
                const percentOffDecimal = parseFloat(span.textContent.replace(/-/, '').replace(/%/, '')) / 100;
                total = total - (total * percentOffDecimal);
            }

            return total;
        }, 0);

    displayPrice.textContent = `${moneyFormatter.format(total)}`;

    return total;
};

const couponCartItem = (name, price) => {
    // Create DOM nodes from inside out
    const itemName = document.createElement('h6');
    itemName.classList.add('my-0');
    itemName.textContent = name;

    const div = document.createElement('div');
    div.appendChild(itemName)

    const span = document.createElement('span');
    span.classList.add('text-muted', 'percent-off');
    span.textContent = price;

    const li = document.createElement('li');
    li.classList.add('coupon', 'list-group-item', 'd-flex', 'justify-content-between', 'lh-condensed', 'list-group-item-success');
    li.append(div, span);

    return li;
};

const clearPreviousCoupons = () => {
    const coupons = document.getElementById('cart_list').getElementsByClassName('coupon');
    return Array.from(coupons).map((coupon) => coupon.remove());
};

const appendCoupon = (itemName, itemPrice) => {
    const cart = document.getElementById('cart_list');
    const currentCartItems = cart.getElementsByTagName('li');
    const newCouponCartItem = couponCartItem(itemName, itemPrice);
    // The last item in the list is a total
    const displayPrice = currentCartItems[currentCartItems.length - 1];
    cart.insertBefore(newCouponCartItem, displayPrice);
};

// Disable the button until we have Stripe set up on the page
document.querySelector('button').disabled = true;

document
    .getElementById('coupon_code_btn')
    .addEventListener('click', async (e) => {
        e.preventDefault();
        const ccInput = document.getElementById('coupon_code');
        if (ccInput.checkValidity()) {
            const couponCode = ccInput.value;
            const res = await fetch('/api/verify-coupon-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    couponCode
                })
            });

            const couponCodeInput = document.getElementById('coupon_code');
            clearPreviousCoupons();
            if (res.status === 200) {
                const json = await res.json();
                // HACK: Assume coupons are only "percent off"
                couponCodeInput.classList.remove('is-invalid');
                couponCodeInput.classList.add('is-valid');
                appendCoupon(`${json.name} coupon`, `- ${json.percent_off} %`)
            } else {
                couponCodeInput.classList.remove('is-valid');
                couponCodeInput.classList.add('is-invalid');
                couponCodeInput.value = '';
            }

            const ccSection = document.getElementById('cc-section')
            const total = recalculateCartTotal();

            if (total === 0) {
                Card.unmount();
                ccSection.classList.add('d-none');
            } else {
                Card.mount();
                ccSection.classList.remove('d-none');
            }
        }
    });

const inputIds = [
    'email',
    'start_date',
    'primary_firstname',
    'primary_lastname',
    'primary_phonenumber',
    'secondary_firstname',
    'secondary_lastname',
    'secondary_phonenumber'
];

inputIds.forEach((inputId) => {
    document.getElementById(inputId).addEventListener(inputId === 'start_date' ? 'blur' : 'change', (e) => {
        if (e.target.checkValidity()) {
            e.target.classList.remove('is-invalid');
            e.target.classList.add('is-valid');
        } else {
            e.target.classList.remove('is-valid');
            e.target.classList.add('is-invalid');
        }
    })
});

document.getElementById('email').addEventListener('blur', (e) => {
    if (e.target.checkValidity()) {
       const emailValue = e.target.value;
       updatePaymentIntent(paymentIntentId, emailValue);
    }
});

[
    'primary_firstname',
    'primary_phonenumber',
    'secondary_firstname',
    'secondary_phonenumber'
].forEach((inputId) => {
    document.getElementById(inputId).addEventListener('change', (e) => {
        const [who, what] = inputId.split('_');
        const oppositeWho = (who === 'primary' ? 'secondary': 'primary');
        const oppositeElem = document.getElementById(`${oppositeWho}_${what}`);
        if (oppositeElem.checkValidity()) {
            oppositeElem.classList.remove('is-invalid');
            oppositeElem.classList.add('is-valid');
        }
    });
});

fetch('/api/create-payment-intent.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(orderData)
})
    .then((result) => result.json())
    .then((json) => {
        paymentIntentId = json.paymentIntentId
        return setupElements(json)
    })
    .then(({ stripe, card, clientSecret }) => {
        Card = card;
        ClientSecret = clientSecret;
        document.querySelector('button').disabled = false;
    });

// Handle form submission.
const form = document.getElementById('payment-form');
form.addEventListener('submit', async (e) => {
    e.preventDefault();

    e.target.classList.add('was-validated');
    if (e.target.checkValidity()) {
        await updatePaymentIntent(paymentIntentId, document.getElementById('email').value);
        const formData = new FormData(e.target);
        formData.append('client_secret', ClientSecret);
        formData.append('payment_intent', paymentIntentId);

        const saveProductOrderRes = await fetch('/api/save-product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        });

        if (saveProductOrderRes.status === 200) {
            orderComplete(ClientSecret);
        } else if (saveProductOrderRes.status === 400) {
            const json = await saveProductOrderRes.json();

            inputIds.forEach((id) => {
                const elem = document.getElementById(id);
                if (elem.checkValidity() && elem) {
                    elem.classList.remove('is-invalid');
                    elem.classList.add('is-valid');
                }
            })

            e.target.classList.remove('was-validated');
            Object.keys(json.errors).forEach((key) => {
                const elem = document.getElementById(key);
                const invalidFeedbackElem = document.getElementById(`${key}-feedback`);
                if (invalidFeedbackElem) {
                    invalidFeedbackElem.innerHTML = json.errors[key].join('<br />')
                }
                if (elem) {
                    elem.classList.remove('is-valid');
                    elem.classList.add('is-invalid');
                }
            })
            showError(JSON.stringify(json.errors, null, 4));
        } else {
            showError('Something went wrong');
        }
    }
});

// Set up Stripe.js and Elements to use in checkout form
const setupElements = (data) => {
    stripe = Stripe(data.publishableKey);
    const elements = stripe.elements();
    const style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    const card = elements.create('card', { style: style });
    card.mount('#card-element');

    return {
        stripe: stripe,
        card: card,
        clientSecret: data.clientSecret
    };
};

const pay = (stripe, card, clientSecret) => {
    return new Promise((resolve, reject) => {
        (payOnceResult ?
            new Promise((res) => res(payOnceResult))
            : stripe.confirmCardPayment(clientSecret, { payment_method: { card: card } })
        )
            .then((result) => {
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
   return fetch('/api/update-payment-intent.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
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
const orderComplete = async (clientSecret) => {
    document.querySelector('#payment-form').classList.add('d-none');
    await stripe.retrievePaymentIntent(clientSecret);

    const date = new Date(document.getElementById('start_date').value);
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

    navigateToThankYouPage(date.toLocaleDateString('en-US', dateOptions));
};

const showError = (errorMsgText) => {
    document.querySelector('.form-errors').classList.remove('d-none');
    const errorMsg = document.querySelector('.sr-field-error');
    errorMsg.textContent = errorMsgText;
    setTimeout(() => {
        document.querySelector('.form-errors').classList.add('d-none');
        errorMsg.textContent = '';
    }, 15000);
};