<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * This class displays plugin settings and statistics 
 * 
 */
class Wcpi_Settings extends Wcpi_Core {

	const CHECK_RESULT_OK = 'ok';

	public static function add_page_to_menu() {

		add_management_page(
						__('Plugivery settings', WCPI_TEXT_DOMAIN), // page title
						__('Plugivery settings', WCPI_TEXT_DOMAIN), // menu title
						'manage_options',
						'wcpi-settings', // menu slug
						array('Wcpi_Settings', 'render_settings_page') // callback.
		);
	}

	public static function do_action() {

		$result = '';

		if (isset($_POST['wcpi-button-save'])) {

			switch ($_POST['wcpi-button-save']) {
				case self::ACTION_SAVE_OPTIONS:
				
					$stored_options = get_option('wc_plugivery_options', array());

					foreach ( self::$default_option_values as $option_name => $option_value ) {
						if (isset($_POST[$option_name])) {
							$stored_options[$option_name] = filter_input(INPUT_POST, $option_name); 
						}
					}

					// special case for checkbox
					if ( ! isset($_POST['plugivery_enabled']) ) {
						$stored_options['plugivery_enabled'] = false;
					} else {
						$stored_options['plugivery_enabled'] = true;
					}

					update_option( 'wc_plugivery_options', $stored_options );
					break;
			}
		}

		return $result;
	}

	public static function render_settings_page() {

		$action_results = '';

		if (isset($_POST['wcpi-button-save'])) {
			$action_results = self::do_action();
		}

		echo $action_results;

		self::load_options();
		
		?>

			<h1><?php esc_html_e('Plugivery Integration', WCPI_TEXT_DOMAIN ); ?></h1>
			
			<br><br><br>
		
		<?php 
		self::render_settings_form();
	}


	
	public static function render_settings_form() {

		$settings_field_set = array(
			array(
				'name' => "plugivery_enabled",
				'type' => 'checkbox',
				'label' => 'Enable integration',
				'default' => '',
				'value' => self::$option_values['plugivery_enabled'],
			),
			array(
				'name' => "plugivery_api_key",
				'type' => 'text',
				'size' => 36,
				'label' => 'Plugivery API Key',
				'default' => '',
				'value' => self::$option_values['plugivery_api_key'],
			),
			array(
				'name' => "email_subject",
				'type' => 'text',
				'size' => 36,
				'label' => 'Email Subject',
				'default' => '',
				'value' => self::$option_values['email_subject'],
			),
			array(
				'name' => "email_body",
				'type' => 'textarea',
				'rows' => 8,
				'cols' => 40,
				'label' => 'Email Content',
				'default' => '',
				'value' => self::$option_values['email_body'],
			)
		);
		?> 

		<form method="POST" >

				<table class="wcpi-global-table">
						<tbody>
								<?php self::display_field_set($settings_field_set); ?>
						</tbody>
				</table>

				<p class="submit">  
						<input type="submit" id="wcpi-button-save" name="wcpi-button-save" class="button button-primary" style="" value="<?php echo self::ACTION_SAVE_OPTIONS; ?>" />
				</p>

		</form>

		<?php
	}
}
