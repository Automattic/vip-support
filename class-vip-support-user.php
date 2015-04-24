<?php

/**
 * @TODO: Verify the A12n's email on registration
 * @TODO: The email to be verified should be hashed with a nonce or with the verification code, so the verification is tied to the specific email address
 * @TODO: We should do a nonce check when the email is verified, so it's only the logged in user who can verify the email
 * @TODO: Verify the A12n's email when it changes, even if previous email was not A8c
 * @TODO: Block logging in until the user has verified their email address
 * @TODO: Show a tick or something when a user's email has been verified
 *
 * @package VipSupportUser
 **/
class VipSupportUser {

	const MSG_BLOCK_UPGRADE_NON_A12N     = 'vip_1';
	const MSG_BLOCK_UPGRADE_VERIFY_EMAIL = 'vip_2';
	const MSG_BLOCK_NEW_NON_VIP_USER     = 'vip_3';
	const MSG_BLOCK_DOWNGRADE            = 'vip_4';
	const MSG_MADE_VIP                   = 'vip_5';

	const META_VERIFICATION_CODE = 'vip_email_verification_code';
	const META_EMAIL_VERIFIED    = 'vip_verified_email';

	const GET_EMAIL_VERIFY  = 'vip_verify_code';
	const GET_EMAIL_USER_ID = 'vip_user_id';

	protected $reverting_role;

	protected $message_replace;
	protected $registering_user;
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
		add_action( 'parse_request',   array( $this, 'action_parse_request' ) );

		add_filter( 'wp_redirect',     array( $this, 'filter_wp_redirect' ) );

		$this->reverting_role   = false;
		$this->message_replace  = false;
		$this->registering_vip  = false;
		$this->registering_user = false;
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
			case self::MSG_BLOCK_UPGRADE_NON_A12N :
				$error = __('Only users with a recognised Automattic email address can be assigned the VIP Support role.', 'vip-support');
				break;
			case self::MSG_BLOCK_UPGRADE_VERIFY_EMAIL :
				$error = __('This user’s Automattic email address must be verified before they can be assigned the VIP Support role.', 'vip-support');
				break;
			case self::MSG_BLOCK_NEW_NON_VIP_USER :
				$error = __('Only Automattic staff can be assigned the VIP Support role, the new user has been made a "subscriber".', 'vip-support');
				break;
			case self::MSG_BLOCK_DOWNGRADE :
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

		$becoming_support         = ( VipSupportRole::VIP_SUPPORT_ROLE == $role );
		$leaving_support          = ( in_array( VipSupportRole::VIP_SUPPORT_ROLE, $old_roles ) && ! $becoming_support );
		$valid_and_verified_email = ( $this->is_a8c_email( $user->user_email ) && $this->user_has_verified_email( $user_id ) );

		if ( $becoming_support && ! $valid_and_verified_email ) {
			$this->reverting_role = true;
			if ( ! is_array( $old_roles ) || ! isset( $old_roles[0] ) ) {
				$revert_role_to = 'subscriber';
			} else {
				$revert_role_to = $old_roles[0];
			}
			$user->set_role( $revert_role_to );
			if ( $this->is_a8c_email( $user->user_email ) && ! $this->user_has_verified_email( $user_id ) ) {
				$this->message_replace = self::MSG_BLOCK_UPGRADE_VERIFY_EMAIL;
			} else {
				$this->message_replace = self::MSG_BLOCK_UPGRADE_NON_A12N;
			}
			$this->reverting_role = false;
		}


		if ( $leaving_support && $this->is_a8c_email( $user->user_email ) ) {
			$this->reverting_role = true;
			$user->set_role( VipSupportRole::VIP_SUPPORT_ROLE );
			$this->message_replace = self::MSG_BLOCK_DOWNGRADE;
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
			update_user_meta( $user_id, self::META_EMAIL_VERIFIED, false );
			$this->send_verification_email( $user_id );
		} else {
			if ( self::MSG_BLOCK_UPGRADE_NON_A12N == $this->message_replace ) {
				$this->message_replace = self::MSG_BLOCK_NEW_NON_VIP_USER;
			}
		}
	}

	public function action_parse_request() {
		if ( ! isset( $_GET[self::GET_EMAIL_VERIFY] ) ) {
			return;
		}

		$user_id = absint( $_GET[self::GET_EMAIL_USER_ID] );
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$stored_verification_code = (string) get_user_meta( $user_id, self::META_VERIFICATION_CODE, true );
		$hash_sent                = (string) $_GET[self::GET_EMAIL_VERIFY];

		$check_hash = wp_hash( $user_id . $stored_verification_code . $user->user_email );

		if ( $check_hash !== $hash_sent ) {
			$message = __( 'This email verification link was not recognised, has been invalidated, or has already been used.', 'vip-support' );
			$title = __( 'Verification failed', 'vip-support' );
			// 403 Forbidden – The server understood the request, but is refusing to fulfill it.
			// Authorization will not help and the request SHOULD NOT be repeated.
			wp_die( $message, $title, array( 'response' => 403 ) );
		}

		// It's all looking good. Verify the email.
		update_user_meta( $user_id, self::META_EMAIL_VERIFIED, $user->user_email );
		delete_user_meta( $user_id, self::META_VERIFICATION_CODE );

		$message = sprintf( __( 'Your email has been verified as %s', 'vip-support' ), $user->user_email );
		$title = __( 'Verification succeeded', 'vip-support' );
		wp_die( $message, $title, array( 'response' => 200 ) );
	}

	// CALLBACKS
	// =========

	// UTILITIES
	// =========

	protected function send_verification_email( $user_id ) {
		// @FIXME: Should the verification code expire?
		$verification_code = get_user_meta( $user_id, self::META_VERIFICATION_CODE, true );
		if ( ! $verification_code ) {
			$verification_code = uniqid();
			update_user_meta( $user_id, self::META_VERIFICATION_CODE, $verification_code );
		}
		$user = new WP_User( $user_id );
		$hash = wp_hash( $user_id . $verification_code . $user->user_email );

		$hash              = urlencode( $hash );
		$user_id           = absint( $user_id );
		$verification_link = add_query_arg( array( self::GET_EMAIL_VERIFY => $hash, self::GET_EMAIL_USER_ID => $user_id ), home_url() );

		$user = new WP_User( $user_id );

		$message  = __( 'Dear VIP Support User,', 'vip-support' );
		$message .= PHP_EOL . PHP_EOL;
		$message .= sprintf( __( 'Somebody has added you as a support user on %1$s (%2$s). If you are expecting this, please click the link below to verify your email address:', 'vip-support' ), get_bloginfo( 'name' ), home_url() );
		$message .= PHP_EOL;
		$message .= $verification_link;
		$message .= PHP_EOL . PHP_EOL;
		$message .= __( 'If you have any questions, please contact the WordPress.com VIP Support Team.' );

		$subject = sprintf( __( 'Email verification for %s', 'vip-support' ), get_bloginfo( 'name' ) );

		wp_mail( $user->user_email, $subject, $message );
	}

	protected function user_has_verified_email( $user_id ) {
		$user = new WP_User( $user_id );
		$verified_email = get_user_meta( $user_id, self::META_EMAIL_VERIFIED, true );
		return ( $user->user_email == $verified_email );
	}

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