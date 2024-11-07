jQuery(document).ready(function ($) {

    $("#username").keyup(function () {

        var username = $(this).val().trim();

        if (username != '') {

            $.ajax({
                url: cashier_object.ajax_url,
                type: 'post',
                data: {
                    username: username,
                    action: 'check_username'
                },
                success: function (response) {
                    $('#username_response').html(response);
                }
            })
            ;
        } else {
            $("#username_response").html("");
        }
    });

    $("#email").keyup(function () {

        var email = $(this).val().trim();

        function isEmail(email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }

        if (isEmail(email)) {
            if (email != '') {
                $.ajax({
                    url: cashier_object.ajax_url,
                    type: 'post',
                    data: {
                        email: email,
                        action: 'check_email'
                    },
                    success: function (response) {
                        $('#email_response').html(response);
                    }
                });

            } else {
                $("#email_response").html("");
            }
        }

    });

    $("#coupon").blur(function () {

        var coupon = $(this).val().trim();

        if (coupon != '') {

            $.ajax({
                url: cashier_object.ajax_url,
                type: 'post',
                data: {
                    coupon: coupon,
                    action: 'check_coupon'
                },
                success: function (response) {
                    $('#coupon_response').html(response);
                }
            });
        } else {
            $("#coupon_response").html("");
        }

    });

});