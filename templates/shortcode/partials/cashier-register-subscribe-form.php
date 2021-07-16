<?php
if ( isset( $_GET['do'] ) ) {

	if ( $_GET['plan'] == "standard" ) {

		return '';

	} elseif ( $_GET['plan'] == "lite" ) {

		return '';
	}


} else {

	// include states array then build the drop down form input from that data
	include( CASHIER_DIR_PATH . 'templates/states-array.php' );
	$countries_dropdown = "<select name='country' id='country'><option>Select</option>";
	foreach ( $countries as $key => $value ) {
		$countries_dropdown .= "<option value='$key'>$value</option>";
	}
	$countries_dropdown .= "</select>";

	$cashier_options = get_option( 'cashier_settings' );

	// build input strings for existing customer
	if ( isset( $_GET['cust'] ) ) {
		$customer_id       = $_GET['cust'];
		$existing_customer = "<input type='hidden' name='customer_id' value='$customer_id' />";

	} else {
		$existing_customer = "";
	}

	if ( isset( $_GET['plan'] ) ) {
		$plan = $_GET['plan'];

	} else {
		$plan = "standard";
	}

	if ( isset( $_GET['subscription'] ) ) {
		$subscription = $_GET['subscription'];

	} else {
		$subscription = "standard";
	}

	//get keys from plugin options
	$cashier_options = get_option( 'cashier_settings' );

	\Stripe\Stripe::setApiKey( $cashier_options['options_secret_key'] );

	\Stripe\ApplePayDomain::create( [
		'domain_name' => get_site_url(),
	] );

	?>

    <div id="subscribe-form-container">
        <h1>Payment Form</h1>
        <div id="subscribe-form-intro-text"><p></p></div>

        <form method="post" action="<? esc_url( admin_url( 'admin-post.php' ) ); ?>" name="form" id="subscribe-form">
            <table class="form-table" id="subscribe-form">

                <tr valign="top">
                    <th scope="row">Username*</th>
                    <td><input type="text" id="username" name="username" required/>
                        <span id="username_response"></span>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Password*</th>
                    <td><input type="password" id="password" name="password" required/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Name*</th>
                    <td><input type="text" id="name" name="name" required/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">E-Mail Address*</th>
                    <td><input type="text" id="email" name="email" required/>
                        <span id="email_response"></span>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Coupon</th>
                    <td><input type="text" id="coupon" name="coupon"/>
                        <span id="coupon_response"></span>
                    </td>
                </tr>
            </table>

            <h2 id="billing-details-title" class="elementor-heading-title elementor-size-default">Billing Details</h2>

            <table>

                <tr valign="top">
                    <th scope="row">Cardholder Name*</th>
                    <td><input type="text" id="cardholder-name" name="cardholder-name" required/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Street Address*</th>
                    <td><input type="text" id="street-address" name="street-address" required/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Apartment, Suite, Unit, or Building</th>
                    <td><input type="text" id="street-address-line-2" name="street-address-line-2"/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">City*</th>
                    <td><input type="text" id="city" name="city" required/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">State*</th>
                    <td><input type="text" id="state" name="state" required/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Zip Code*</th>
                    <td><input type="text" id="zip-code" name="zip-code" required/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Country*</th>
                    <td><?= $countries_dropdown; ?></td>
                </tr>

            </table>

            <div>
                <label for="card-element">Credit or debit card:</label>
                <div id="card-element-container"
                     style="background-color:#FFFFFF; border:1px solid #000000; padding:2px; width:500px;">
                    <div id="card-element"></div>
                </div>

                <div id="payment-request-button">
                    <!-- A Stripe Element will be inserted here. -->
                </div>

                <div id="card-errors" style="padding:20px; border:0px solid #333333;"></div>
                <div>
					<? wp_nonce_field( 'register_subscribe_form', 'security-code-here' ); ?>

                    <input type="hidden" name="subscription" value="<?= $subscription; ?>"/>

                    <input type="hidden" name="plan" value="<?= $plan; ?>"/>

                    <input type="hidden" name="payment_method" id="payment_method" value=""/>

                    <input type="hidden" name="action" value="stripe_builder_register_subscribe_form_handler"/>

                    <div id="card-errors" style="padding:20px; border:0px solid #333333;"></div>

                    <button id="card-button"
                            class="elementor-button-link elementor-button elementor-size-md elementor-animation-grow"
                            data-secret="">Subscribe
                    </button>
                </div>
        </form>
    </div>

    <script>
        window.onload = (event) => {

            // All subsequent code goes here.
            const stripe = Stripe('<?= $cashier_options["options_publishable_key"]; ?>');
            const elements = stripe.elements();
            const card = elements.create('card', {
                    style: {
                        base: {
                            color: "#000000",
                            fontWeight:
                                500,
                            fontFamily:
                                "Inter UI, Open Sans, Segoe UI, sans-serif",
                            fontSize:
                                "20px",
                            fontSmoothing:
                                "antialiased",

                            "::placeholder":
                                {
                                    color: "#CFD7DF"
                                }
                        }
                        ,
                        invalid: {
                            color: "#E25950"
                        }
                    }
                }
            );

            card.mount('#card-element');

            // Handle real-time validation errors from the card Element.
            card.on('change', function (event) {
                    const displayError = document.getElementById('card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                }
            );

            const cardholderName = document.getElementById('cardholder-name');
            const cardButton = document.getElementById('card-button');
            const clientSecret = cardButton.dataset.secret;

            // create a payment Request instance
            var paymentRequest = stripe.paymentRequest({
                country: 'US',
                currency: 'usd',
                total: {
                    label: 'Demo total',
                    amount: 1099,
                },
                requestPayerName: true,
                requestPayerEmail: true,
            });

            // create and mount the payment request button
            var prButton = elements.create('paymentRequestButton', {
                paymentRequest: paymentRequest,
            });

            // Check the availability of the Payment Request API first.
            paymentRequest.canMakePayment().then(function (result) {
                if (result) {
                    prButton.mount('#payment-request-button');
                } else {
                    document.getElementById('payment-request-button').style.display = 'none';
                }
            });

            //alert(customerId);
            //alert(priceId);
            // Set up Stripe.js and Elements to use in checkout form
            var style = {
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

            //var cardElement = elements.create("card", { style: style });
            //cardElement.mount("#card-element");

            card.on('change', showCardError);

            function showCardError(event) {

                let displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            }

            const cardHolderName = document.getElementById('cardholder-name');

            //alert(clientSecret);

            //dump object function for testeing
            function dump(obj) {
                var out = '';
                for (var i in obj) {
                    out += i + ": " + obj[i] + "\n";
                }

                //alert(out);

                // or, if you wanted to avoid alerts...

                var pre = document.createElement('pre');
                pre.innerHTML = out;
                document.body.appendChild(pre)
            }

            // Upon button clicking, complete the payment:
            cardButton.addEventListener('click', async (event) => {
                    event.preventDefault();

                    addressLine1 = document.getElementById('street-address').value;
                    addressLine2 = document.getElementById('street-address-line-2').value;
                    addressCity = document.getElementById('city').value;
                    addressState = document.getElementById('state').value;
                    addressCountry = document.getElementById('country').value;
                    addressZipCode = document.getElementById('zip-code').value;

                    //how about just add payment source here????????????
                    stripe.createPaymentMethod({
                        type: 'card',
                        card: card,
                        billing_details: {
                            name: cardHolderName,
                            address: {
                                line1: addressLine1,
                                line2: addressLine2,
                                city: addressCity,
                                state: addressState,
                                country: addressCountry,
                                postal_code: addressZipCode
                            }
                        }
                    })
                        .then(function (result) {

                            document.getElementById('payment_method').value = result.paymentMethod.id;
                            document.getElementById('subscribe-form').submit();

                        });


                    if (clientSecret) {

                        try {
                            const result = await stripe.confirmCardPayment(clientSecret, {
                                payment_method: {
                                    card: card,
                                    billing_details: {name: cardholderName.value},
                                }
                            });
                            if (result.error) {
                                document.getElementById('card - errors').textContent = result.error.message;
                                return false;
                            } else {
                                document.getElementById('card').submit();
                            }
                        } catch (err) {
                            document.getElementById('card - errors').textContent = err.message;
                            return false;
                        }

                    }

                }
            );

        }

    </script>
	<?php
}// end if isset get var - do