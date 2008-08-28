

$(document).ready(function() {

	$("#edit-badge-name").focus();
	$("#edit-search-last").focus();
	$("#edit-name").focus();

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


