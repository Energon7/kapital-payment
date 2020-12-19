<?php
/*
Plugin Name: WooCommerce Kapitalbank Payment Gateway
Plugin URI: https://simple.az/
Description: Kapitalbank Payment Gateway for Woocommerce
Version: 1.0.0
Author: Azer Mammadov
Author URI: https://simple.az/
*/

class WC_Kapitalbank {

	/**
	 * Constructor
	 */
	public function __construct() {
		define( 'WC_Kapitalbank_VERSION', '1.0.0' );
		define( 'WC_Kapitalbank_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_Kapitalbank_PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
		define( 'WC_Kapitalbank_MAIN_FILE', __FILE__ );

		// Actions
		add_action( 'wp_loaded', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_Kapitalbank_scripts' ) );
	}

	/**
	 * Init localisations and files
	 */
	public function init() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Includes
		include_once( 'includes/class-capitalbank-gateway-woocommerce.php' );
		include_once( 'kapitalbank-gateway.php' );
		include_once( 'reversal.php' );

	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {

		$methods[] = 'WC_Gateway_Kapitalbank';
		return $methods;

	}

	/**
	 * Include jQuery and our scripts
	 */
	public function add_Kapitalbank_scripts() {

		wp_enqueue_script( 'edit_billing_details', WC_Kapitalbank_PLUGIN_URL . '/js/admin.js', array( 'jquery' ) );

	}
}

new WC_Kapitalbank();