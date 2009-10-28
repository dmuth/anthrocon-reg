

$(document).ready(function() {

	$("#edit-badge-name").focus();
	$("#edit-search-last").focus();

	//
	// This was causing inappropriate focus on the username box for
	// users that weren't logged in, and had the effect of pushing 
	// the page down.  I'm commenting this out for now, since I can't seem
	// to determine where it was used in the reg system anyway--commenting
	// it out during testing has not caused any adverse affects.
	//
	//$("#edit-name").focus();

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


	//
	// Update our total on initial loading of the page
	//
	var cost = reg_get_current_level_cost();
	update_total(cost);

	//
	// Register handlers for when the registration level or donation 
	// is changed.
	//
	$(".reg-level").change(function() {
		reg_level_change(this);
		});

	$("#edit-donation").change(function() {
		var cost = reg_get_current_level_cost();
		update_total(cost);
		});

	$("#edit-badge-cost").change(function() {
		var cost = reg_get_current_level_cost();
		update_total(cost);
		});

	//
	// Apply our style (shading and a different cursor) when a membership 
	// level is hovered over.
	//
	$(".reg-level").mouseover(function() {
		$(this).addClass("reg-hover");
		});

	$(".reg-level").mouseout(function() {
		$(this).removeClass("reg-hover");
		});

	/**
	* If the checkbox is checked, show the shipping form elements.
	*	Otherwise, hide them.
	*/
	$("#edit-shipping-checkbox").click(function() {
		check_shipping_checkbox();
		});

	//
	// Check the status of our shipping checkbox at load time, too.
	//
	check_shipping_checkbox();

});


/**
* Check the status of the shipping checkbox, and hide/show it accordingly.
*/
function check_shipping_checkbox() {

	var obj = $("#edit-shipping-checkbox");
	var checked = $(obj).attr("checked");

	if (checked) {
		$(".reg-hidden").show();

	} else {
		$(".reg-hidden").hide();

	}

} // End of check_shipping_checkbox()


/**
* This trigger is fired whenever the registration level is changed.
* It gets the current cost and updates the total.
*/
function reg_level_change(obj) {

	var reg_level_id = $(obj).find("input").val();
	var cost = reg_get_current_level_cost(reg_level_id);
	update_total(cost);

} // End of reg_level_change()


/**
* This function is used to get the cost for a specific level id.
*/
function reg_get_current_level_cost() {

	var reg_level_id = $(".reg-level-radio:checked").val();

	if (reg_level_id) {
		var id = "reg-level-id-" + reg_level_id;
		var cost = $("#" + id).text();

	} else {
		var cost =$("#edit-badge-cost").val(cost);

	}

	return(cost);

} // End of reg_get_current_level_cost()


/**
* Update the total cost with the new value.
*/
function update_total(cost) {

	//
	// If cost wasn't specified, set it to 0.
	//
	if (!cost) {
		cost = 0.00;
	}

	$("#reg-membership-cost").text(cost);

	var donation = $("#edit-donation").val();

	if (!donation) {
		donation = 0.00;
	}

	//
	// Since we can get an insane number of decimal places due to the way
	// floats are stored, we'll round to the nearest 100th.
	//
	var total = parseFloat(cost) + parseFloat(donation);
	total *= 100;
	total = Math.round(total);
	total /= 100;

	$("#reg-total").text(String(total));

} // End of update_total()


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

