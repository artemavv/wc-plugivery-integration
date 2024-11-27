<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Wcpi_Core {

	public static $plugin_root;

	public static $error_messages = [];
	public static $messages = [];

	public static $option_values = array();
	
	// Actions triggered by buttons in backend area
	public const ACTION_SAVE_OPTIONS                     = 'Save settings';
	public const ACTION_RUN_EMAIL_TEST                   = 'Send Plugivery email for order';
	public const ACTION_RUN_API_TEST                     = 'Test Plugivery API for order';
	
	// Key to use in "wp_options" table to save plugin settings
	public const OPTIONS_NAME = 'wc_plugivery_options';
	
	/**
	 * List of default values for plugin settings
	 * Used when installing into Wordpress
	 * 
	 * @var array
	 */
	public static $default_option_values = [
		'plugivery_api_key' => '',
		'plugivery_enabled' => 1,
		'email_subject'     => 'You have purchased a Plugivery product!',
		'email_template'    => "Hello!\r\nHere is your license key: {redeem_code} .\r\n"
		                     . "You can download the product here: {redeem_url}.\r\nHave a nice day!"
		
	];
	
	public static function init() {
		self::load_options();
	}

	public static function load_options() {
		$stored_options = get_option( self::OPTIONS_NAME, array());

		foreach (self::$default_option_values as $option_name => $default_option_value) {
			if (isset($stored_options[$option_name])) {
				self::$option_values[$option_name] = $stored_options[$option_name];
			} else {
				self::$option_values[$option_name] = $default_option_value;
			}
		}
	}

	protected static function render_message( $message_text, $is_error = false ) {
		
		if ( ! $is_error )  {
			$out = '<div class="notice-info notice is-dismissible"><p>'
								. '<strong>'
								. $message_text
								. '</strong></p>'
								. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
								. '</div>';
		} else {
			$out = '<div class="notice-error settings-error notice is-dismissible"><p>'
								. '<strong>'
								. $message_text
								. '</strong></p>'
								. '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
								. '</div>';
		}
		
		return $out;
	}
		
	protected function display_messages( $error_messages, $messages ) {
		
		$out = '';
		
		if (count($error_messages)) {
			foreach ($error_messages as $message) {

				if (is_wp_error($message)) {
					$message_text = $message->get_error_message();
				} else {
					$message_text = trim($message);
				}

				$out .= self::render_message( $message_text, true );
			}
		}

		if (count($messages)) {
			foreach ($messages as $message) {
				$out .= self::render_message( $message_text, false );
			}
		}

		return $out;
	}

	/**
	 * Returns HTML table rows each containing field, field name, and field description
	 * 
	 * @param array $field_set 
	 * @return string HTML
	 */
	public static function render_fields_row($field_set) {

		$out = '';

		foreach ($field_set as $field) {

			$value = $field['value'];

			if ((!$value) && ( $field['type'] != 'checkbox' )) {
				$value = $field['default'] ?? '';
			}

			$out .= self::display_field_in_row($field, $value);
		}

		return $out;
	}

	/**
	 * Generates HTML code for input row in table
	 * @param array $field
	 * @param array $value
	 * @return string HTML
	 */
	public static function display_field_in_row($field, $value) {

		$label = $field['label']; // $label = __($field['label'], DDB_TEXT_DOMAIN);

		$value = htmlspecialchars($value);
		$field['id'] = str_replace('_', '-', $field['name']);

		// 1. Make HTML for input
		switch ($field['type']) {
			case 'text':
				$input_HTML = self::make_text_field($field, $value);
				break;
			case 'dropdown':
				$input_HTML = self::make_dropdown_field($field, $value);
				break;
			case 'textarea':
				$input_HTML = self::make_textarea_field($field, $value);
				break;
			case 'checkbox':
				$input_HTML = self::make_checkbox_field($field, $value);
				break;
			case 'hidden':
				$input_HTML = self::make_hidden_field($field, $value);
				break;
			default:
				$input_HTML = '[Unknown field type "' . $field['type'] . '" ]';
		}


		// 2. Make HTML for table cell
		switch ($field['type']) {
			case 'hidden':
				$table_cell_html = <<<EOT
    <td class="col-hidden" style="display:none;" >{$input_HTML}</td>
EOT;
				break;
			case 'text':
			case 'textarea':
			case 'checkbox':
			default:
				$table_cell_html = <<<EOT
    <td>{$input_HTML}</td>
EOT;
		}

		return $table_cell_html;
	}

	/**
	 * Generates HTML code with TR rows containing specified field set
	 * 
	 * @param array $field
	 * @param mixed $value
	 * @return string HTML
	 */
	public static function display_field_set($field_set) {
		foreach ($field_set as $field) {

			$value = $field['value'] ?? false;

			$field['id'] = str_replace('_', '-', $field['name']);

			echo self::make_field($field, $value);
		}
	}

	/**
	 * Generates HTML code with TR row containing specified field input
	 * 
	 * @param array $field
	 * @param mixed $value
	 * @return string HTML
	 */
	public static function make_field($field, $value) {
		$label = $field['label'];

		if (!isset($field['style'])) {
			$field['style'] = '';
		}

		// 1. Make HTML for input
		switch ($field['type']) {
			case 'checkbox':
				$input_html = self::make_checkbox_field($field, $value);
				break;
			case 'text':
				$input_html = self::make_text_field($field, $value);
				break;
			case 'date':
				$input_html = self::make_date_field($field, $value);
				break;
			case 'dropdown':
				$input_html = self::make_dropdown_field($field, $value);
				break;
			case 'textarea':
				$input_html = self::make_textarea_field($field, $value);
				break;
			case 'hidden':
				$input_html = self::make_hidden_field($field, $value);
				break;
			default:
				$input_html = '[Unknown field type "' . $field['type'] . '" ]';
		}

		if (isset($field['display'])) {
			$display = $field['display'] ? 'table-row' : 'none';
		} else {
			$display = 'table-row';
		}

		// 2. Make HTML for table row
		switch ($field['type']) {
			case 'hidden':
				$table_row_html = <<<EOT
    <tr style="display:none" >
      <td colspan="3" class="col-hidden">{$input_html}</td>
    </tr>
EOT;
				break;
			case 'dropdown':
			case 'text':
			case 'number':
			case 'textarea':
			case 'checkbox':
			default:
				if (isset($field['description']) && $field['description']) {
					$table_row_html = <<<EOT
    <tr style="display:{$display}" >
      <td class="col-name" style="{$field['style']}"><label for="wcpi_{$field['id']}">$label</label></td>
      <td class="col-input">{$input_html}</td>
      <td class="col-info">
        {$field['description']}
      </td>
    </tr>
EOT;
				} else {
					$table_row_html = <<<EOT
    <tr style="display:{$display}" >
      <td class="col-name" style="{$field['style']}"><label for="wcpi_{$field['id']}">$label</label></td>
      <td class="col-input">{$input_html}</td>
      <td class="col-info"></td>
    </tr>
EOT;
				}
		}


		return $table_row_html;
	}

	/**
	 * Generates HTML code for hidden input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_hidden_field($field, $value) {
		$out = <<<EOT
      <input type="hidden" id="wcpi_{$field['id']}" name="{$field['name']}" value="{$value}">
EOT;
		return $out;
	}

	/**
	 * Generates HTML code for text field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_text_field($field, $value) {

		$size = $field['size'] ?? 25;

		$out = <<<EOT
      <input type="text" id="wcpi_{$field['id']}" name="{$field['name']}" size="{$size}"value="{$value}" class="wcpi-text-field">
EOT;
		return $out;
	}

	/**
	 * Generates HTML code for date field input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_date_field($field, $value) {

		$min = $field['min'] ?? '2023-01-01';

		$out = <<<EOT
      <input type="date" id="wcpi_{$field['id']}" name="{$field['name']}" value="{$value}" min="{$min}" class="wcpi-date-field">
EOT;
		return $out;
	}

	/**
	 * Generates HTML code for textarea input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_textarea_field($field, $value) {
		$out = <<<EOT
      <textarea id="wcpi_{$field['id']}" name="{$field['name']}" cols="{$field['cols']}" rows="{$field['rows']}" value="">{$value}</textarea>
EOT;
		return $out;
	}

	/**
	 * Generates HTML code for dropdown list input
	 * @param array $field
	 * @param array $value
	 */
	public static function make_dropdown_field($field, $value) {

		$autocomplete = $field['autocomplete'] ?? false;

		$class = $autocomplete ? 'wcpi-autocomplete' : '';

		$out = "<select class='$class' name='{$field['name']}' id='wcpi_{$field['id']}' >";

		foreach ($field['options'] as $optionValue => $optionName) {
			$selected = ((string) $value == (string) $optionValue) ? 'selected="selected"' : '';
			$out .= '<option ' . $selected . ' value="' . $optionValue . '">' . $optionName . '</option>';
		}

		$out .= '</select>';
		return $out;
	}

	/**
	 * Generates HTML code for checkbox 
	 * @param array $field
	 */
	public static function make_checkbox_field($field, $value) {
		$chkboxValue = $value ? 'checked="checked"' : '';
		$out = <<<EOT
      <input type="checkbox" id="wcpi_{$field['id']}" name="{$field['name']}" {$chkboxValue} value="1" class="wcpi-checkbox-field"/>
EOT;
		return $out;
	}

	/**
	 * Write into WooCommerce log. 
	 * 
	 * @param string $message
	 * @param array $data
	 */
	public static function wc_log(string $message, array $data = array() ) {

		$data['source'] = 'wc-plugivery-integration';

		wc_get_logger()->info(
			$message,
			$data
		);
	}
}
