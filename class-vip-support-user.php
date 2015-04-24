<?php

/**
 *
 * @package VipSupportUser
 **/
class VipSupportUser {

	const MSG_BLOCK_UPGRADE_NON_A12N     = 'vip_support_msg_1';
	const MSG_BLOCK_UPGRADE_VERIFY_EMAIL = 'vip_support_msg_2';
	const MSG_BLOCK_NEW_NON_VIP_USER     = 'vip_support_msg_3';
	const MSG_BLOCK_DOWNGRADE            = 'vip_support_msg_4';
	const MSG_MADE_VIP                   = 'vip_support_msg_5';

	const META_VERIFICATION_CODE = 'vip_email_verification_code';
	const META_EMAIL_VERIFIED    = 'vip_verified_email';

	const GET_EMAIL_VERIFY                = 'vip_verify_code';
	const GET_EMAIL_USER_ID               = 'vip_user_id';
	const GET_TRIGGER_RESEND_VERIFICATION = 'vip_trigger_resend';

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
		add_action( 'admin_notices',      array( $this, 'action_admin_notices' ) );
		add_action( 'set_user_role',      array( $this, 'action_set_user_role' ), 10, 3 );
		add_action( 'user_register',      array( $this, 'action_user_register' ) );
		add_action( 'parse_request',      array( $this, 'action_parse_request' ) );
		add_action( 'personal_options',   array( $this, 'action_personal_options' ) );
		add_action( 'load-user-edit.php', array( $this, 'action_load_user_edit' ) );
		add_action( 'load-profile.php',   array( $this, 'action_load_profile' ) );
		add_action( 'admin_head',         array( $this, 'action_admin_head' ) );
		add_action( 'profile_update',     array( $this, 'action_profile_update' ) );

		add_filter( 'wp_redirect',          array( $this, 'filter_wp_redirect' ) );
		add_filter( 'removable_query_args', array( $this, 'filter_removable_query_args' ) );

		$this->reverting_role   = false;
		$this->message_replace  = false;
		$this->registering_vip  = false;
		$this->registering_user = false;
	}

	// HOOKS
	// =====

	public function action_admin_head() {
		if ( in_array( get_current_screen()->base, array( 'user-edit', 'profile' ) ) ) {
			?>
			<style type="text/css">
				.vip-support-email-status {
					padding-left: 1em;
				}
				.vip-support-email-status .dashicons {
					line-height: 1.6;
				}
				.email-not-verified {
					color: #dd3d36;
				}
				.email-verified {
					color: #7ad03a;
				}
			</style>
			<?php
		}
	}

	public function action_load_user_edit() {
		if ( isset( $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) && $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) {
			$user_id = absint( $_GET['user_id'] );
			$this->send_verification_email( $user_id );
		}
	}

	public function action_load_profile() {
		if ( isset( $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) && $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) {
			$user_id = get_current_user_id();
			$this->send_verification_email( $user_id );
		}
	}

	public function action_personal_options( $user ) {
		if ( ! $this->is_a8c_email( $user->user_email ) ) {
			return;
		}

		if ( $this->user_has_verified_email( $user->ID ) ) {
			$message = __( 'email is verified', 'vip-support' );
			?>
			<em id="vip-support-email-status" class="vip-support-email-status email-verified"><span class="dashicons dashicons-yes"></span><?php echo $message; ?></em>
			<?php
		} else {
			$message = __( 'email is not verified', 'vip-support' );
			?>
			<em id="vip-support-email-status" class="vip-support-email-status email-not-verified"><span class="dashicons dashicons-no"></span><?php echo $message; ?></em>
			<?php
		}
?>
		<script type="text/javascript">
			jQuery( 'document').ready( function( $ ) {
				$( '#email' ).after( $( '#vip-support-email-status' ) );
			} );
		</script>
<?php
	}

	public function action_admin_notices() {
		$error   = false;
		$message = false;
		$screen  = get_current_screen();

		if ( 'users' == $screen->base ) {

			$update = false;
			if ( isset( $_GET['update'] ) ) {
				$update = $_GET['update'];
			}

			switch ( $update ) {
				case self::MSG_BLOCK_UPGRADE_NON_A12N :
					$error = __( 'Only users with a recognised Automattic email address can be assigned the VIP Support role.', 'vip-support' );
					break;
				case self::MSG_BLOCK_UPGRADE_VERIFY_EMAIL :
					$error = __( 'This user’s Automattic email address must be verified before they can be assigned the VIP Support role.', 'vip-support' );
					break;
				case self::MSG_BLOCK_NEW_NON_VIP_USER :
					$error = __( 'Only Automattic staff can be assigned the VIP Support role, the new user has been made a "subscriber".', 'vip-support' );
					break;
				case self::MSG_BLOCK_DOWNGRADE :
					$error = __( 'VIP Support users can only be assigned the VIP Support role, or deleted.', 'vip-support' );
					break;
				case self::MSG_MADE_VIP :
					$message = __( 'This user was given the VIP Support role, based on their email address.', 'vip-support' );
					break;
				default:
					return;
			}
		}


		if ( 'profile' == $screen->base ) {
			if ( isset( $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) && $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) {
				$message = __( 'The verification email has been sent, please check your inbox. Delivery may take a few minutes.', 'vip-support' );
			} else {
				$user_id = get_current_user_id();
				$user    = get_user_by( 'id', $user_id );
				$resend_link = $this->get_trigger_resend_verification_url();
				if ( $this->is_a8c_email( $user->user_email ) && ! $this->user_has_verified_email( $user->ID ) ) {
					$error = sprintf( __( 'Your Automattic email address is not verified, <a href="%s">re-send verification email</a>.', 'vip-support' ), esc_url( $resend_link ) );
				}
			}
		}

		if ( 'user-edit' == $screen->base ) {
			if ( isset( $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) && $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) {
				$message = __( 'The verification email has been sent, please ask the user to check their inbox. Delivery may take a few minutes.', 'vip-support' );
			} else {
				$user_id = absint( $_GET['user_id'] );
				$user = get_user_by( 'id', $user_id );
				$resend_link = $this->get_trigger_resend_verification_url();
				if ( $this->is_a8c_email( $user->user_email ) && ! $this->user_has_verified_email( $user->ID ) ) {
					$error = sprintf( __( 'This user’s Automattic email address is not verified, <a href="%s">re-send verification email</a>.', 'vip-support' ), esc_url( $resend_link ) );
				}
			}
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

	public function action_profile_update( $user_id ) {
		$user = new WP_User( $user_id );
		if ( ! $this->is_a8c_email( $user->user_email ) ) {
			return;
		}
		$verified_email = get_user_meta( $user_id, self::META_EMAIL_VERIFIED, true );
		if ( $user->user_email != $verified_email ) {
			$this->send_verification_email( $user_id );
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

	public function filter_removable_query_args( $args ) {
		$args[] = self::GET_TRIGGER_RESEND_VERIFICATION;
		return $args;
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

		$message  = __( 'Dear Automattician,', 'vip-support' );
		$message .= PHP_EOL . PHP_EOL;
		$message .= sprintf( __( 'You need to verify your Automattic email address for your user on %1$s (%2$s). If you are expecting this, please click the link below to verify your email address:', 'vip-support' ), get_bloginfo( 'name' ), home_url() );
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

	protected function get_trigger_resend_verification_url() {
		return add_query_arg( array( self::GET_TRIGGER_RESEND_VERIFICATION => '1' ) );
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