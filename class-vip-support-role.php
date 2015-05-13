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
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 4 );
	}

	// HOOKS
	// =====

	/**
	 * Hooks the admin_init action to run an update method.
	 */
	public function action_admin_init() {
		$this->update();
	}


	/**
	 * Rather than explicitly adding all the capabilities to the admin role, and possibly
	 * missing some custom ones, or copying a role, and possibly being tripped up when
	 * that role doesn't exist, we filter all user capability checks and wave past our
	 * VIP Support users as automattically having the capability being checked.
	 *
	 * @param array   $user_caps An array of all the user's capabilities.
	 * @param array   $caps      Actual capabilities for meta capability.
	 * @param array   $args      Optional parameters passed to has_cap(), typically object ID.
	 * @param WP_User $user      The user object.
	 *
	 * @return array An array of all the user's caps, with the required cap added
	 */
	public function filter_user_has_cap( array $user_caps, array $caps, array $args, WP_User $user ) {
		if ( in_array( self::VIP_SUPPORT_ROLE, $user->roles ) ) {
			$user_caps[$args[0]] = true;
		}
		return $user_caps;
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
		$version = absint( get_option( $option_name, 0 ) );

		if ( $version == self::VERSION ) {
			return;
		}

		if ( $version < 1 ) {
			add_role( self::VIP_SUPPORT_ROLE, __( 'VIP Support', 'a8c_vip_support' ), array( 'read' => true ) );
		    $this->error_log( "VIP Support Role: Added VIP Support role " );
		}

		// N.B. Remember to increment self::VERSION above when you add a new IF

		update_option( $option_name, self::VERSION );
		$this->error_log( "VIP Support Role: Done upgrade, now at version " . self::VERSION );

	}
}

VipSupportRole::init();