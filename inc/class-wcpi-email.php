<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * A custom Order Completed WooCommerce Email class
 *
 * @extends \WC_Email
 */

class Wcpi_Order_Completed_Email extends WC_Email {
	
	/**
	 * Set email defaults
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// set ID, this simply needs to be a unique name
		$this->id = 'wc_plugivery_order_completed';
		$this->customer_email = true;

		// this is the title in WooCommerce Email settings
		$this->title = 'Order Completed (with Plugivery info)';

		// this is the description in WooCommerce email settings
		$this->description = __( 'This email is sent to customers and contains Plugivery details about bought products.', WCPI_TEXT_DOMAIN );

		// these are the default heading and subject lines that can be overridden using the settings
		$this->heading = 'Your Order is Completed.';
		$this->subject = 'Audio Plugin Big Deal Order Completed. {product_email_subject}';

		// Trigger on new completed orders
		add_action('woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 1000 );

		// Call parent constructor to load any other defaults not explicity defined here
		parent::__construct();

	}

	public function is_plugivery_order( $order_id ) {
		
		$is_plugivery = get_post_meta( $order_id, 'plugivery_order_type', true );
		
		return $is_plugivery;
	}
	
	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		// check if order has any Plugivery products
		if ( ! $this->is_plugivery_order( $order_id ) ) {
			return;
		}

		// setup order object
		$this->object = new WC_Order( $order_id );
		$this->recipient = $this->object->billing_email;
		
		if ( $this->get_option('subject') != '' ) {
			$this->subject = $this->get_option('subject');
		}
		
		if ( $this->get_option('heading') != '' ) {
			$this->heading = $this->get_option('heading');
		}

		$this->find['order-date'] = '{order_date}';
		$this->find['order-number'] = '{order_number}';

		$this->replace['order-date'] = date_i18n(wc_date_format(), strtotime($this->object->order_date));
		$this->replace['order-number'] = $this->object->get_order_number();

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
		
		$emails_to_send = $this->get_content();

		if ( count($emails_to_send) ) {
			foreach ( $emails_to_send as $email ) {
				
				$this->send( $this->get_recipient(), $email['subject'], $email['body'], $this->get_headers(), $this->get_attachments() );
			}
		}

	}

	/**
	 * Compose and output emil template to test its content
	 *
	 * @param int $order_id
	 */
	public function test_email( $order_id ) {

		// bail if no order ID is present
		if (!$order_id) {
			return;
		}

		// check if order has any Plugivery products
		if ( ! $this->is_plugivery_order( $order_id ) ) {
			return;
		}

    // setup order object
		$this->object = new WC_Order($order_id);
		$this->recipient = $this->object->billing_email;
		
		$this->find['order-date'] = '{order_date}';
		$this->find['order-number'] = '{order_number}';

		$this->replace['order-date'] = date_i18n(wc_date_format(), strtotime($this->object->order_date));
		$this->replace['order-number'] = $this->object->get_order_number();

		if (!$this->is_enabled() || !$this->get_recipient()) {
			return;
		}

		$emails_to_send = $this->get_content();

		$results = array();
		
		if ( count( $emails_to_send ) ) {
			foreach ( $emails_to_send as $email ) {
				// output email
				$results[] = array( $this->get_recipient(), $email['subject'], $email['body'], $this->get_headers(), $this->get_attachments() );
			}
    }
		
		return $results;
	}

	/**
	 * get_content_html function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_html() {
		$email_heading = $this->get_heading();
		$email = $this;

		ob_start();
		/**
		 * @hooked WC_Emails::email_header() Output the email header
		 */
		do_action( 'woocommerce_email_header', $email_heading, $email );

		?>

		<div>
				{product_email_text}
		</div>

		<?php

		/**
		 * @hooked WC_Emails::email_footer() Output the email footer
		 */
		do_action( 'woocommerce_email_footer', $email );

		$html = ob_get_contents();
		ob_clean();
		return $this->prepare_emails_to_send( $html );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		$email_heading = $this->get_heading();		
		ob_start();
		echo "= " . $email_heading . " =\n\n";

		echo sprintf( __( "Hi there. Your recent order on %s has been completed. Your order details are shown below for your reference:", 'woocommerce' ), get_option( 'blogname' ) ) . "\n\n";

		echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

		echo '{product_email_text}';

		echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

		echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
		$html = ob_get_contents();
		ob_clean();

		return $this->prepare_emails_to_send( $html );
	}

	public function prepare_emails_to_send( $html ) {

		$order = $this->object;

		$items = $order->get_items();
		
		$emails = [];
		
		foreach ( $items as $item ) {
			
			$data = $this->get_plugivery_data( $item );
			
			if ( $data ) {
	
				$body = str_replace('{product_email_text}', $this->get_wcpi_setting( 'email_template' ), $html );
				$subject = str_replace('{product_email_subject}', $this->get_wcpi_setting( 'email_template' ), $this->subject );
				
				$search = array(
					'{product_name}',
					'{customer_name}',
					'{redeem_code}',
					'{redeem_url}',
				);
				
				$replace = array(
					$item['name'],
					$order->billing_first_name,
					$data['redeem_code'],
					$data['redeem_url']
				);
				
				$email_body = str_replace( $search, $replace, $body );
				$email_subject = str_replace( $search, $replace, $subject );
				
				$emails[] = array(
					'subject'	=> $email_body,
					'body'		=> $email_subject
				);
			}
		}
		
		return $emails;
	}
	
	
	public function get_wcpi_setting( $setting_name ) {
	
		$plugin_options = get_option( Wcpi_Core::OPTIONS_NAME, array() );
		
		$value = $plugin_options[ $setting_name ] ?? false;

		return $value;
	}

    /**
     * Initialize Settings Form Fields
     *
     * @since 0.1
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
								'label'   => 'Enable this email notification. <br> Avaliable shortcodes: '
									. '<br>{product_name}, {customer_name}, {redeem_code}, {redeem_url}',
                'default' => 'yes'
            ),
            'subject' => array(
                'title' => 'Subject',
                'type' => 'text',
                'description' => sprintf('This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject),
                'placeholder' => '',
                'default' => $this->subject
            ),
            'heading' => array(
                'title' => 'Email Heading',
                'type' => 'text',
                'description' => sprintf(__('This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.'), $this->heading),
                'placeholder' => '',
                'default' => $this->heading
            ),
            'email_type' => array(
                'title' => 'Email type',
                'type' => 'select',
                'description' => 'Choose which format of email to send.',
                'default' => 'html',
                'class' => 'email_type',
                'options' => array(
                    'plain' => 'Plain text',
                    'html' => 'HTML'
                )
            )
        );
    }

    public static function log($data) {

			$filename = pathinfo(__FILE__, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'log.txt';
			
			if (isset($_GET['wcpi_log_to_screen']) && $_GET['wcpi_log_to_screen'] == 1) {
				echo('log::<pre>' . print_r($data, 1) . '</pre>');
			}
			else {
				file_put_contents($filename, date("Y-m-d H:i:s") . " | " . print_r($data,1) . "\r\n\r\n", FILE_APPEND);
			}
    }
}