<?php

/**
* This class holds theme-related code for the registration system.
*/
class reg_theme {

	/**
	* Process a form in our own registration theme. This will allow
	* us to print out certain form elements differently.
	*/ 
	static function theme(&$form) {

		$retval = "";

		$retval .= "<table >";

		$retval .= self::theme_children($form);

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
		$retval = '<input type="text" maxlength="' 
			. $item['#maxlength'] . '" name="' . $item['#name'] 
			. '" id="'. $item['#id'] . '" ' . $size .' value="' 
			. check_plain($item['#value']) . '"' . 
			drupal_attributes($item['#attributes']) . ' />'
			;

		return($retval);

	} // End of theme_textfield()


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
			$retval .= self::theme_select($item[$value]);
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

		$retval = self::theme_select($item["year"])
			. " "
			. self::theme_select($item["month"])
			. " "
			. self::theme_select($item["day"])
			. " "
			;

		return($retval);

	} // End of theme_date()


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
	
			$required = !empty($item['#required']) ? 
				'<span class="reg-form-required" title="' 
				. t('This field is required.') . '"> *</span>' : '';

			$retval .= "<tr>"
				. "<td align=\"right\" class=\"reg-name\">";

			//
			// If we don't know how to render this item, let Drupal render it.
			//
			if ($item["#type"] != "textfield"
				&& $item["#type"] != "select"
				&& $item["#type"] != "checkbox"
				&& $item["#type"] != "date"
				&& $item["#type"] != "cc_exp"
				) {
				$retval .= drupal_render($item);

			} else {
				$retval .= '<div class="reg-form-item">' . "\n";
				$retval .= "<label>" . $item["#title"] . ": " . "</label>";
				$retval .= "</div>";
		
				$retval .= "</td>\n";

				//
				// This form generation code was ripped from theme_textfield().
				// If you need more functionality for generating text fields, 
				// you'll have to rip it from there. :-P
				//
				$retval .= "<td class=\"reg-value\">";
				$retval .= '<div class="reg-form-item">' . "\n";

				if ($item["#type"] == "textfield") {
					$retval .= self::theme_textfield($item);

				} else if ($item["#type"] == "select") {
					$retval .= self::theme_select($item);

				} else if ($item["#type"] == "checkbox") {
					$retval .= self::theme_checkbox($item);

				} else if ($item["#type"] == "date") {
					$retval .= self::theme_date($item);

				} else if ($item["#type"] == "cc_exp") {
					$retval .= self::theme_cc_exp($item);

				} else {
					//
					// This only gets executed if I screwed up the outer
					// if statement. :-P
					//
					$retval .= "No code for item type: " . $item["#type"];

				}

				$retval .= $required;

				$retval .= "</div></td>\n";

				//
				// Ripped from theme_form_element
				//
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

			} 

			$retval .= "</tr>\n";

		} // End of foreach()

		return($retval);

	} // End of theme_children()


} // End of reg_theme class

