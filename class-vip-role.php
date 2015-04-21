<?php

/**
 *
 *
 * @package VipRole
 **/
class VipRole {

	const VIP_SUPPORT_ROLE = 'vip_support';

	protected $reverting_role;

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
		add_action( 'set_user_role',   array( $this, 'action_set_user_role' ), 10, 3 );

		$this->reverting_role = false;
		$this->version = 1;
	}

	// HOOKS
	// =====

	public function action_admin_init() {
		$this->update();
	}

	public function action_set_user_role( $user_id, $role, $old_roles ) {
		// Avoid recursing, while we're reverting
		if ( $this->reverting_role ) {
			return;
		}
		$user = new WP_User( $user_id );
		$becoming_support = ( self::VIP_SUPPORT_ROLE == $role );
		$leaving_support = ( in_array( self::VIP_SUPPORT_ROLE, $old_roles ) && ! $becoming_support );
		if ( $becoming_support && ! $this->is_a8c_email( $user->user_email ) ) {
			$this->reverting_role = true;
			// @TODO: What if the user previously had no role, somehow?
			$user->set_role( $old_roles[0] );
			$this->reverting_role = false;
		}
		if ( $leaving_support && $this->is_a8c_email( $user->user_email ) ) {
			$this->reverting_role = true;
			$user->set_role( self::VIP_SUPPORT_ROLE );
			$this->reverting_role = false;
		}
	}

	// CALLBACKS
	// =========

	// UTILITIES
	// =========

	/**
	 *
	 *
	 * @TODO Needs unit tests
	 *
	 * @param $email
	 *
	 * @return bool
	 */
	protected function is_a8c_email( $email ) {
		if ( ! is_email( $email ) ) {
			return false;
		}
		list( $local, $domain ) = explode( '@', $email, 2 );
		$a8c_domains = array(
			'a8c.com',
			'automattic.com',
			'matticspace.com',
		);
		if ( in_array( $domain, $a8c_domains ) ) {
			return true;
		}
		return false;
	}

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
			add_role( self::VIP_SUPPORT_ROLE, __( 'VIP Support', 'a8c_vip_support' ), get_role( 'administrator' )->capabilities );
		    $this->error_log( "VIPRole: Done upgrade, now at version " . $this->version );
		}

		// N.B. Remember to increment $this->version above when you add a new IF

		update_option( $option_name, $this->version );
		$this->error_log( "VIPRole: Done upgrade, now at version " . $this->version );

	}
}


// Initiate the singleton

VipRole::init();