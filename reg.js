

$(document).ready(function() {

	$("#edit-badge-name").focus();
	$("#edit-search-last").focus();
	$("#edit-name").focus();

	//
	// Register our handler for the payment type, and run it once.
	//
	$("#edit-reg-payment-type-id").change(reg_payment_type_change);
	$("#edit-reg-payment-type-id").each(reg_payment_type_change);

	//
	// Having to defer execution of the focus event like this is
	// completely brain damaged.  It only became necessary when I put
	// the form element in question into a fieldset.
	// If I *don't* do this, the element is never focused on, 
	// despite being there.
	//
	var target = function() {
		$("#edit-search-badge-num").focus();
		}

	setTimeout(target, 100);

});


/**
* This trigger is fired whenever the payment type is changed.
* It disables/enables credit card data fields based on the payment type.
*/
function reg_payment_type_change() {

	var val = $(this).parent().find(":selected").text();

	if (val != "Credit Card") {
		$("#edit-cc-type-id").attr("disabled", true);
		$("#edit-cc-num").attr("disabled", true);
		$("#edit-cc-exp-month").attr("disabled", true);
		$("#edit-cc-exp-year").attr("disabled", true);

	} else {
		$("#edit-cc-type-id").attr("disabled", "");
		$("#edit-cc-num").attr("disabled", "");
		$("#edit-cc-exp-month").attr("disabled", false);
		$("#edit-cc-exp-year").attr("disabled", false);

	}


}

