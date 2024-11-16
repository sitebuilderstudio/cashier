jQuery(document).ready(function($) {
    // Initialize Stripe
    const stripe = Stripe(cashier_object.stripe_public_key);
    const elements = stripe.elements();

    // Create card element
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');

    // Form step handling
    $('#next-step').click(function(e) {
        e.preventDefault();
        // Validate first step
        if (validateStep1()) {
            $('#step-1').hide();
            $('#step-2').show();
        }
    });

    $('#prev-step').click(function() {
        $('#step-2').hide();
        $('#step-1').show();
    });

    // Username availability check
    $("#username").on('keyup change', function() {
        var username = $(this).val().trim();
        if (username.length >= 3) {
            $.ajax({
                url: cashier_object.ajax_url,
                type: 'post',
                data: {
                    username: username,
                    action: 'check_username',
                    nonce: cashier_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#username_response')
                            .removeClass('error')
                            .addClass('success')
                            .html(response.data.message);
                    } else {
                        $('#username_response')
                            .removeClass('success')
                            .addClass('error')
                            .html(response.data.message);
                    }
                },
                error: function() {
                    $('#username_response')
                        .removeClass('success')
                        .addClass('error')
                        .html('Error checking username availability');
                }
            });
        } else {
            $('#username_response')
                .removeClass('success')
                .addClass('error')
                .html('Username must be at least 3 characters long');
        }
    });

    // Email availability check
    $("#email").on('keyup change', function() {
        var email = $(this).val().trim();
        if (isValidEmail(email)) {
            $.ajax({
                url: cashier_object.ajax_url,
                type: 'post',
                data: {
                    email: email,
                    action: 'check_email',
                    nonce: cashier_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#email_response')
                            .removeClass('error')
                            .addClass('success')
                            .html(response.data.message);
                    } else {
                        $('#email_response')
                            .removeClass('success')
                            .addClass('error')
                            .html(response.data.message);
                    }
                },
                error: function() {
                    $('#email_response')
                        .removeClass('success')
                        .addClass('error')
                        .html('Error checking email availability');
                }
            });
        } else {
            $('#email_response')
                .removeClass('success')
                .addClass('error')
                .html('Please enter a valid email address');
        }
    });

    // Password strength and match validation
    $('#password').on('keyup change', function() {
        validatePassword($(this).val());
    });

    $('#confirm_password').on('keyup change', function() {
        validatePasswordMatch();
    });

    // Coupon validation
    $("#coupon").blur(function() {
        var coupon = $(this).val().trim();
        if (coupon != '') {
            $.ajax({
                url: cashier_object.ajax_url,
                type: 'post',
                data: {
                    coupon: coupon,
                    action: 'check_coupon'
                },
                success: function(response) {
                    $('#coupon_response').html(response);
                }
            });
        }
    });

    // Form submission
    $('#registration-payment-form').submit(function(event) {
        event.preventDefault();
        const submitButton = $('#submit-button');
        submitButton.prop('disabled', true);
        submitButton.text('Processing...');

        // Create payment method
        stripe.createPaymentMethod('card', cardElement).then(function(result) {
            if (result.error) {
                $('#card-errors').text(result.error.message);
                submitButton.prop('disabled', false);
                submitButton.text('Complete Registration');
            } else {
                // Add payment method ID to form
                $('#payment_method').val(result.paymentMethod.id);
                // Submit form
                event.target.submit();
            }
        });
    });

    // Helper functions
    function validateStep1() {
        const username = $('#username').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        if (!username || !email || !password || !confirmPassword) {
            alert('Please fill in all required fields');
            return false;
        }

        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return false;
        }

        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return false;
        }

        return true;
    }

    function isValidEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }

    function validatePassword(password) {
        const strength = {
            1: 'Very Weak',
            2: 'Weak',
            3: 'Medium',
            4: 'Strong',
            5: 'Very Strong'
        };

        let score = 1;

        if (password.length >= 8) score++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
        if (password.match(/\d/)) score++;
        if (password.match(/[^a-zA-Z\d]/)) score++;

        $('#password_strength').text('Strength: ' + strength[score]);
        $('#password_strength').css('color', score >= 3 ? 'green' : 'red');
    }

    function validatePasswordMatch() {
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        if (password === confirmPassword) {
            $('#password_match').text('Passwords match').css('color', 'green');
        } else {
            $('#password_match').text('Passwords do not match').css('color', 'red');
        }
    }
});