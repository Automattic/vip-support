<?php

/**
 *
 *
 * @package VipRole
 **/
class VipRole {

	/**
	 * Singleton stuff.
	 *
	 * @access @static
	 *
	 * @return VipRole object The instance of VipRole
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new VipRole;
		}

		return $instance;

	}

	/**
	 * Class constructor
	 *
	 */
	public function __construct() {
		add_action( 'admin_init',      array( $this, 'action_admin_init' ) );

		$this->version = 1;
	}

	// HOOKS
	// =====

	public function action_admin_init() {
		$this->update();
	}

	// CALLBACKS
	// =========

	// UTILITIES
	// =========

	protected function error_log( $message ) {
		if ( defined( 'WP_DEBUG' ) || WP_DEBUG ) {
			error_log( $message );
		}

	}

	/**
	 * Runs any updates.
	 *
	 */
	protected function update() {
		$option_name = 'viprole_version';
		$version = get_option( $option_name, 0 );

		if ( $version == $this->version ) {
			return;
		}

		if ( $version < 1 ) {
			add_role( 'vip_support', __( 'VIP Support', 'a8c_vip_support', get_role( 'administrator' )->capabilities ) );
		    $this->error_log( "VIPRole: Done upgrade, now at version " . $this->version );
		}

		// N.B. Remember to increment $this->version above when you add a new IF

		update_option( $option_name, $this->version );
		$this->error_log( "VIPRole: Done upgrade, now at version " . $this->version );

	}
}


// Initiate the singleton

VipRole::init();