<?php

/**
 * @TODO: Verify the A12n's email
 *
 * @package VipSupportUser
 **/
class VipSupportUser {

	const MSG_BLOCKED_UPGRADE   = 'vip_blocked_upgrade';
	const MSG_BLOCKED_DOWNGRADE = 'vip_blocked_downgrade';
	const MSG_MADE_VIP          = 'vip_made_vip';

	protected $reverting_role;

	protected $message_replace;
	protected $registering_vip;

	/**
	 * Singleton stuff.
	 *
	 * @access @static
	 *
	 * @return VipSupportRole object The instance of VipRole
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new VipSupportUser;
		}

		return $instance;

	}

	/**
	 * Class constructor
	 *
	 */
	public function __construct() {
		add_action( 'admin_notices',   array( $this, 'action_admin_notices' ) );
		add_action( 'set_user_role',   array( $this, 'action_set_user_role' ), 10, 3 );
		add_action( 'user_register',   array( $this, 'action_user_register' ) );

		add_filter( 'wp_redirect',     array( $this, 'filter_wp_redirect' ) );

		$this->reverting_role   = false;
		$this->message_replace  = false;
		$this->registering_vip  = false;
	}

	// HOOKS
	// =====

	public function action_admin_notices() {
		if ( 'users' != get_current_screen()->base ) {
			return;
		}
		if ( isset( $_GET['update'] ) ) {
			$update = $_GET['update'];
		} else {
			return;
		}
		$error = false;
		$message = false;
		switch ( $update ) {
			case self::MSG_BLOCKED_UPGRADE :
				$error = __('Only Automattic staff can be assigned the VIP Support role.', 'vip-support');
				break;
			case self::MSG_BLOCKED_DOWNGRADE :
				$error = __('VIP Support users can only be assigned the VIP Support role, or deleted.', 'vip-support');
				break;
			case self::MSG_MADE_VIP :
				$message = __('This user was given the VIP Support role, based on their email address.', 'vip-support');
				break;
			default:
				return;
		}
		if ( $error ) {
			echo '<div id="message" class="notice is-dismissible error"><p>' . $error . '</p></div>';

		}
		if ( $message ) {
			echo '<div id="message" class="notice is-dismissible updated"><p>' . $message . '</p></div>';

		}
	}

	public function action_set_user_role( $user_id, $role, $old_roles ) {
		// Avoid recursing, while we're reverting
		if ( $this->reverting_role ) {
			return;
		}
		$user = new WP_User( $user_id );
		$becoming_support = ( VipSupportRole::VIP_SUPPORT_ROLE == $role );
		if ( $becoming_support && ! $this->is_a8c_email( $user->user_email ) ) {
			$this->reverting_role = true;
			if ( ! is_array( $old_roles ) || ! isset( $old_roles[0] ) ) {
				$revert_role_to = 'subscriber';
			} else {
				$revert_role_to = $old_roles[0];
			}
			// @TODO: Need to stop the current message saying the role has been changed, and show a msg saying the action was blocked
			$user->set_role( $revert_role_to );
			$this->message_replace = self::MSG_BLOCKED_UPGRADE;
			$this->reverting_role = false;
		}
		$leaving_support = ( in_array( VipSupportRole::VIP_SUPPORT_ROLE, $old_roles ) && ! $becoming_support );
		if ( $leaving_support && $this->is_a8c_email( $user->user_email ) ) {
			$this->reverting_role = true;
			// @TODO: Need to stop the current message saying the role has been changed, and show a msg saying the action was blocked
			$user->set_role( VipSupportRole::VIP_SUPPORT_ROLE );
			$this->message_replace = self::MSG_BLOCKED_DOWNGRADE;
			$this->reverting_role = false;
		}
	}

	public function filter_wp_redirect( $location ) {
		if ( ! $this->message_replace && ! $this->registering_vip ) {
			return $location;
		}
		if ( $this->message_replace ) {
			$location = add_query_arg( array( 'update' => $this->message_replace ), $location );
			$location = esc_url_raw( $location );
		}
		if ( $this->registering_vip ) {
			$location = add_query_arg( array( 'update' => self::MSG_MADE_VIP ), $location );
			$location = esc_url_raw( $location );
		}
		return $location;
	}

	public function action_user_register( $user_id ) {
		$this->registering_user = true;
		$user = new WP_User( $user_id );
		if ( $this->is_a8c_email( $user->user_email ) ) {
			$user->set_role( VipSupportRole::VIP_SUPPORT_ROLE );
			$this->registering_vip = true;
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

}


// Initiate the singleton

VipSupportUser::init();