<?php

/**
* This class holds theme-related code for the registration system.
*/
class reg_theme extends reg {

	function __construct(&$message, &$fake, &$log) {
		parent::__construct($message, $fake, $log);
	}


	/**
	* Process a form in our own registration theme. This will allow
	* us to print out certain form elements differently.
	*/ 
	function theme(&$form) {

		$retval = "";

		$retval .= "<table >";

		$retval .= $this->theme_children($form);

		$retval .= "</table>";

		return($retval);

	} // End of theme()


	/**
	* Render a text field
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_textfield(&$item) {
		$class = array('form-text');
		_form_set_class($item, $class);

		$size = "size=\"20\"";
		if (!empty($item["#size"])) {
			$size = "size=\"" . $item["#size"] . "\"";
		}

		$retval = '<input type="text" maxlength="' 
			. $item['#maxlength'] . '" name="' . $item['#name'] 
			. '" id="'. $item['#id'] . '" ' . $size .' value="' 
			. check_plain($item['#value']) . '"' . 
			drupal_attributes($item['#attributes']) . ' />'
			;

		return($retval);

	} // End of theme_textfield()


	/**
	* Render a text field
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_item(&$item) {
		$class = array('form-text');
		_form_set_class($item, $class);

		$retval = $item["#value"];

		return($retval);

	} // End of theme_item()


	/**
	* Render a select element
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_select(&$item) {

		$class = array('form-select');
		_form_set_class($item, $class);

		$size = $item['#size'] ? ' size="' . $item['#size'] . '"' : '';
		$multiple = isset($item['#multiple']) && $item['#multiple'];

		$retval .= '<select name="'
			. $item['#name'] . ''. ($multiple ? '[]' : '') . '"' 
			. ($multiple ? ' multiple="multiple" ' : '') 
			. drupal_attributes($item['#attributes']) 
			. ' id="' . $item['#id'] . '" ' . $size . '>' 
			. form_select_options($item) . '</select>';
		return($retval);

	} // End of theme_select()


	/**
	* Render CC expiration form widgets
	*
	* @param array $item Associative array of form items
	*
	* @return string HTML code for the form elements
	*/
	function theme_cc_exp(&$item) {

		$retval = "";
		foreach (element_children($item) as $key => $value) {
			if (!empty($retval)) {
				$retval .= " ";
			}
			$retval .= $this->theme_select($item[$value]);
		}

		return($retval);

	} // End of theme_select_list()


	/**
	* Render a checkbox
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_checkbox(&$item) {

		$class = array('form-checkbox');
		_form_set_class($item, $class);

		$checked = "";
		if (!empty($item["#value"])) {
			$checked = "checked=\"checked\" ";
		}

		$retval = '<input '
			. 'type="checkbox" '
			. 'name="'. $item['#name'] .'" '
			. 'id="'. $item['#id'].'" ' 
			. 'value="'. $item['#return_value'] .'" '
			. $checked
			. drupal_attributes($item['#attributes']) 
			. ' />';

		return($retval);

	} // End of theme_checkbox()


	/**
	* Render a date field
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_date(&$item) {

		$retval = $this->theme_select($item["year"])
			. " "
			. $this->theme_select($item["month"])
			. " "
			. $this->theme_select($item["day"])
			. " "
			;

		return($retval);

	} // End of theme_date()


	/**
	* Render a set of radio buttons for our registration levels.
	*
	* @param array $item Associative array of the item to render.
	*
	* @return string HTML code for the form element.
	*
	* @todo This code is specific to membership levels.  In the future,
	*	I should really rename this function to something like theme_levels()
	*	and have it only called for the reg_level_id field...
	*/
	function theme_radios(&$item) {

		$retval = "";

		//
		// Get a list of our current membership levels for later use.
		//
		$levels = reg_get_valid_levels();

		$class = 'form-radios';
		if (isset($item['#attributes']['class'])) {
			$class .= ' '. $item['#attributes']['class'];
		}

		//
		// Do a pre-liminary loop through our values, and set a flag
		// if we find any default values from a past form submission.
		//
		$value_found = false;
		foreach ($item["#options"] as $key => $value) {
			if (!empty($item["#value"])) {
				$value_found = true;
			}
		}

		foreach ($item["#options"] as $key => $value) {

			//
			// If we did not find any radio buttons that were set, then set
			// the first one in the list.
			//
			if (empty($value_found)) {
				$item["#value"] = $key;
				$value_found = true;
			}

			$retval .= $this->radio($item, $item["#value"], $key, $value);

			$price = $levels[$key]["price"];

			//
			// Create a span tag that contains the price for this level, for
			// later use by jQuery.
			//
			$retval .= "<span id=\"reg-level-id-$key\" "
				. "style=\"display: none; \" "
				. ">$price</span>\n";

		}

		return($retval);

	} // End of theme_radios()


	/**
	* This function creates a single radio button for a reg_level.
	*
	* @param array $item The daa structure for this specific item.
	*
	* @param string $default_value The value of the radio button that was
	*	previously selected.
	*
	* @param integer $key The key for the radio button.  This corresponds to
	*	a reg_level_id value.
	*
	* @param string $value The string displayed with the radio button.  This
	*	corresponds to the description for a membership level.
	*
	* @return string HTML code for the form element.
	*/
	function radio($item, $default_value, $key, $value) {

		$checked = (check_plain($default_value) == $key) ? ' checked="checked" ' : ' ';

		$retval ='<input type="radio" '
			. 'name="' . $item['#name'] .'" '
			. 'value="'. $key .'" '
			. "class=\"reg-level-radio\" "
			. $checked
			. " />"
			;

		if (!is_null($item['#title'])) {
			$retval = "<label class=\"option reg-level\">" . $retval . " " . $value 
				. "</label><br><br>\n";
		}

		return($retval);

	} // End of radio()


	/**
	* Process the childen of a particular form element.
	*
	* @param array $form Associatiave array of one or more form elements
	*	that hold children.  Any direct form elements in them will NOT 
	*	be processed.
	*
	* @return string HTML code for the form, along with table row and column
	*	code.
	*/
	function theme_children(&$form) {

		foreach (element_children($form) as $key => $value) {

			$item = $form[$value];
			$type = $item["#type"];
	
			$required = !empty($item['#required']) ? 
				'<span class="reg-form-required" title="' 
				. t('This field is required.') . '"> *</span>' : '';

			$valign = "";
			if ($type == "radios") {
				$valign = "valign=\"top\" ";
			}

			$attrib = drupal_attributes($item["#attributes"]);
			$retval .= "<tr >"
				. "<td $valign align=\"right\" class=\"reg-name\">";

			//
			// If we don't know how to render this item, let Drupal render it.
			//
			if ($type != "textfield"
				&& $type != "select"
				&& $type != "checkbox"
				&& $type != "date"
				&& $type != "radios"
				&& $type != "cc_exp"
				&& $type != "item"
				) {
				//print $type; // Debugging
				//print "<pre>"; print_r($item); print "</pre>"; // Debugging
				$retval .= drupal_render($item);

			} else {
				$retval .= '<div class="reg-form-item">' . "\n";
				$retval .= "<label>" . $item["#title"] . ": " . "</label>";
				$retval .= "</div>";
		
				$retval .= "</td>\n";

				//
				// Radio buttons get a colspan of 2, since they're for our 
				// membership types.
				//
				$colspan = "";
				if ($type == "radios") {
					$colspan = "colspan=\"2\"";
				}

				//
				// This form generation code was ripped from theme_textfield().
				// If you need more functionality for generating text fields, 
				// you'll have to rip it from there. :-P
				//
				$retval .= "<td class=\"reg-value\" $colspan>";
				$retval .= '<div class="reg-form-item">' . "\n";

				$retval .= $this->render_item($item);

				$retval .= $required;

				$retval .= "</div></td>\n";

				//
				// Ripped from theme_form_element
				//
				if ($type != "radios") {
					$retval .= $this->get_description($item);
				}

			} 

			$retval .= "</tr>\n";

		} // End of foreach()

		return($retval);

	} // End of theme_children()


	/**
	* Render a specific item.
	*
	* @param array $item The data structure for this item.
	*
	*/
	function render_item($item) {
		
		$retval = "";

		if (!empty($item["#field_prefix"])) {
			$retval .= $item["#field_prefix"];
		}

		$type = $item["#type"];

		if ($type == "textfield") {
			$retval .= $this->theme_textfield($item);

		} else if ($type == "select") {
			$retval .= $this->theme_select($item);

		} else if ($type == "checkbox") {
			$retval .= $this->theme_checkbox($item);

		} else if ($type == "date") {
			$retval .= $this->theme_date($item);

		} else if ($type == "radios") {
			$retval .= $this->theme_radios($item);

		} else if ($type == "cc_exp") {
			$retval .= $this->theme_cc_exp($item);

		} else if ($type == "item") {
			$retval .= $this->theme_item($item);

		} else {
			//
			// This only gets executed if I screwed up the outer
			// if statement. :-P
			//
			$retval .= "No code for item type: " . $type;

		}

		return($retval);

	} // End of render_item()


	function get_description(&$item) {

		$retval = "";

			$attrib = drupal_attributes($item["#attributes"]);
		if (!empty($item['#description'])) {
			$retval .= "<td>"
				. "<div class=\"reg-form-item\">"
				. "<div class=\"description\">"
				. $item["#description"] 
				. "</div>"
				. "</div>"
				. "</td>\n"
				;
		} 

		return($retval);

	} // End of get_description()


} // End of reg_theme class

