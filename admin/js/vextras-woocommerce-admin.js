(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
})( jQuery );

function vextras_admin_login_user()
{
    var pass = jQuery("#vextras-woocommerce-vextras_password").val();
    var user = jQuery("#vextras-woocommerce-vextras_email").val();

    if (user.length <= 3) {
        alert('Your email is not valid');
        return;
    }

    if (pass.length <= 3) {
        alert('Your password is not valid');
        return;
    }

    try {
        var request_url = vextras_plugin_data.ajax_url + "?action=vextras_log_in";
        var request = new XMLHttpRequest;

        var self = jQuery('body');
        var bucket = jQuery('<span/>', {'class': 'loader-image-container'}).insertAfter( self );
        var loader = jQuery('<img/>', {src: '/wp-admin/images/loading.gif', 'class': 'loader-image'}).appendTo(bucket);

        request.open("POST", request_url, true);

        request.onload = function () {

            if (request.status >= 200 && request.status < 400) {

                // parse the WP response
                var server_response = JSON.parse(request.responseText);

                if (!server_response.success) {
                    var message = server_response.message || (server_response.data.message || 'Something went wrong');
                    jQuery('#result_message').html('<p style="color:red;">'+message+'</p>');
                    return;
                }

                // if we make it here, that means the request succeeded and on
                // reload this screen will not be visible
                location.reload();
            }
        };
        request.onerror = function () {
            console.log("vextras.log_in.request.error", request.responseText)
        };

        request.setRequestHeader("Content-Type", "application/json");
        request.setRequestHeader("Accept", "application/json");
        request.send(JSON.stringify({username: user, password: pass, action: 'login'}));

    } catch (a) {
        console.log("vextras.log_in.error", a);
    }
}

function vextras_admin_create_new_account()
{
	var pass = jQuery("#vextras-woocommerce-vextras_password").val();
	var user = jQuery("#vextras-woocommerce-vextras_email").val();

    if (user.length <= 3) {
        alert('Your email is not valid');
        return;
    }

    if (pass.length <= 3) {
        alert('Your password is not valid');
        return;
    }

    //alert('i am going to add a loading image here until the ajax request finishes');

    var self = jQuery('body');
    var bucket = jQuery('<span/>', {'class': 'loader-image-container'}).insertAfter( self );
    var loader = jQuery('<img/>', {src: '/wp-admin/images/loading.gif', 'class': 'loader-image'}).appendTo(bucket);

    try {
        var request_url = vextras_plugin_data.ajax_url + "?action=vextras_sign_up";
        var request = new XMLHttpRequest;

        request.open("POST", request_url, true);
        request.onload = function () {
            if (request.status >= 200 && request.status < 400) {

                // parse the WP response
                var server_response = JSON.parse(request.responseText);

                if (!server_response.success) {
                    var message = server_response.message || (server_response.data.message || 'Something went wrong');
                    jQuery('#result_message').html('<p style="color:red;">'+message+'</p>');
                    return;
                }

                // if we make it here, that means the request succeeded and on
                // reload this screen will not be visible.
                location.reload();
            }

            console.log("vextras.create_new_account.error", request.responseText);
        };
        request.onerror = function () {
            console.log("vextras.create_new_account.request.error", request.responseText)
        };
        request.setRequestHeader("Content-Type", "application/json");
        request.setRequestHeader("Accept", "application/json");
        request.send(JSON.stringify({username: user, password: pass, action: 'create'}));
    } catch (a) {
        console.log("vextras.create_new_account.error", a)
    }
}
