<?php

/**
 * Provides the VIP Support role
 *
 * @package VipSupportRole
 **/
class VipSupportRole {

	const VIP_SUPPORT_ROLE = 'vip_support';

	/**
	 * A version used to determine any necessary
	 * update routines to be run.
	 */
	const VERSION = 1;

	/**
	 * Initiate an instance of this class if one doesn't
	 * exist already. Return the VipSupportRole instance.
	 *
	 * @access @static
	 *
	 * @return VipSupportRole object The instance of VipRole
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new VipSupportRole;
		}

		return $instance;

	}

	/**
	 * Class constructor. Handles hooking actions and filters,
	 * and sets some properties.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
	}

	// HOOKS
	// =====

	/**
	 * Hooks the admin_init action to run an update method.
	 */
	public function action_admin_init() {
		$this->update();
	}

	// UTILITIES
	// =========

	/**
	 * Log errors if WP_DEBUG is defined and true.
	 *
	 * @param string $message The message to log
	 */
	protected function error_log( $message ) {
		if ( defined( 'WP_DEBUG' ) || WP_DEBUG ) {
			error_log( $message );
		}

	}

	/**
	 * Checks the version option value against the version
	 * property value, and runs update routines as appropriate.
	 *
	 */
	protected function update() {
		$option_name = 'vipsupportrole_version';
		$version = get_option( $option_name, 0 );

		if ( $version == self::VERSION ) {
			return;
		}

		if ( $version < 1 ) {
			add_role( self::VIP_SUPPORT_ROLE, __( 'VIP Support', 'a8c_vip_support' ), get_role( 'administrator' )->capabilities );
		    $this->error_log( "VIP Support Role: Added VIP Support role " );
		}

		// N.B. Remember to increment self::VERSION above when you add a new IF

		update_option( $option_name, self::VERSION );
		$this->error_log( "VIP Support Role: Done upgrade, now at version " . self::VERSION );

	}
}

VipSupportRole::init();