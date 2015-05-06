<?php

/**
 * Manages VIP Support users.
 *
 * @package VipSupportUser
 **/
class VipSupportUser {

	/**
	 * GET parameter for a message: We blocked this user from the
	 * support role because they're not an A12n.
	 */
	const MSG_BLOCK_UPGRADE_NON_A12N     = 'vip_support_msg_1';

	/**
	 * GET parameter for a message: We blocked this user from the
	 * support role because they have not verified their
	 * email address.
	 */
	const MSG_BLOCK_UPGRADE_VERIFY_EMAIL = 'vip_support_msg_2';

	/**
	 * GET parameter for a message: We blocked this NEW user from
	 * the support role because they're not an A12n.
	 */
	const MSG_BLOCK_NEW_NON_VIP_USER     = 'vip_support_msg_3';

	/**
	 * GET parameter for a message: We blocked this user from
	 * LEAVING the support role because they have not verified
	 * their email address.
	 */
	const MSG_BLOCK_DOWNGRADE            = 'vip_support_msg_4';

	/**
	 * GET parameter for a message: This user was added to the
	 * VIP Support role.
	 */
	const MSG_MADE_VIP                   = 'vip_support_msg_5';

	/**
	 * Meta key for the email verification code.
	 */
	const META_VERIFICATION_CODE = 'vip_email_verification_code';

	/**
	 * Meta key for the email which HAS been verified.
	 */
	const META_EMAIL_VERIFIED    = 'vip_verified_email';

	/**
	 * GET parameter for the code in the verification link.
	 */
	const GET_EMAIL_VERIFY                = 'vip_verify_code';

	/**
	 * GET parameter for the user ID for the user being verified.
	 */
	const GET_EMAIL_USER_ID               = 'vip_user_id';

	/**
	 * GET parameter to indicate to trigger a resend if true.
	 */
	const GET_TRIGGER_RESEND_VERIFICATION = 'vip_trigger_resend';

	/**
	 * A flag to indicate reversion and then to prevent recursion.
	 *
	 * @var bool True if the role is being reverted
	 */
	protected $reverting_role;

	/**
	 * Set to a string to indicate a message to replace, but
	 * defaults to false.
	 *
	 * @var bool|string
	 */
	protected $message_replace;

	/**
	 * A flag to indicate the user being registered is an
	 * A12n (i.e. VIP).
	 *
	 * @var bool
	 */
	protected $registering_a12n;

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
			$instance = new VipSupportUser;
		}

		return $instance;

	}

	/**
	 * Class constructor. Handles hooking actions and filters,
	 * and sets some properties.
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
		$this->registering_a12n  = false;
	}

	// HOOKS
	// =====

	/**
	 * Hooks the admin_head action to add some CSS into the
	 * user edit and profile screens.
	 */
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

	/**
	 * Hooks the load action on the user edit screen to
	 * send verification email if required.
	 */
	public function action_load_user_edit() {
		if ( isset( $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) && $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) {
			$user_id = absint( $_GET['user_id'] );
			$this->send_verification_email( $user_id );
		}
	}

	/**
	 * Hooks the load action on the profile screen to
	 * send verification email if required.
	 */
	public function action_load_profile() {
		if ( isset( $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) && $_GET[self::GET_TRIGGER_RESEND_VERIFICATION] ) {
			$user_id = get_current_user_id();
			$this->send_verification_email( $user_id );
		}
	}

	/**
	 * Hooks the personal_options action on the user edit
	 * and profile screens to add verification status for
	 * the user's email.
	 *
	 * @param object $user The WP_User object representing the user being edited
	 */
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

	/**
	 * Hooks the admin_notices action to add some admin notices,
	 * also resends verification emails when required.
	 */
	public function action_admin_notices() {
		$error   = false;
		$message = false;
		$screen  = get_current_screen();

		// Messages on the users list screen
		if ( in_array( $screen->base, array( 'users', 'user-edit', 'profile' ) ) ) {

			$update = false;
			if ( isset( $_GET['update'] ) ) {
				$update = $_GET['update'];
			}

			switch ( $update ) {
				case self::MSG_BLOCK_UPGRADE_NON_A12N :
					$error = __( 'Only users with a recognised Automattic email address can be assigned the VIP Support role.', 'vip-support' );
					break;
				case self::MSG_BLOCK_UPGRADE_VERIFY_EMAIL :
				case self::MSG_MADE_VIP :
					$error = __( 'This user’s Automattic email address must be verified before they can be assigned the VIP Support role.', 'vip-support' );
					break;
				case self::MSG_BLOCK_NEW_NON_VIP_USER :
					$error = __( 'Only Automattic staff can be assigned the VIP Support role, the new user has been made a "subscriber".', 'vip-support' );
					break;
				case self::MSG_BLOCK_DOWNGRADE :
					$error = __( 'VIP Support users can only be assigned the VIP Support role, or deleted.', 'vip-support' );
					break;
				default:
					return;
			}
		}

		// Messages on the user's own profile edit screen
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

		// Messages on the user edit screen for another user
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

		// For is-dismissible see https://make.wordpress.org/core/2015/04/23/spinners-and-dismissible-admin-notices-in-4-2/
		if ( $error ) {
			echo '<div id="message" class="notice is-dismissible error"><p>' . $error . '</p></div>';

		}
		if ( $message ) {
			echo '<div id="message" class="notice is-dismissible updated"><p>' . $message . '</p></div>';

		}
	}

	/**
	 * Hooks the set_user_role action to check if we're setting the user to the
	 * VIP Support role. If we are setting to the VIP Support role, various checks
	 * are run, and the transition may be reverted.
	 *
	 * @param int $user_id The ID of the user having their role changed
	 * @param string $role The name of the new role
	 * @param array $old_roles Any roles the user was assigned to previously
	 */
	public function action_set_user_role( $user_id, $role, $old_roles ) {
		// Avoid recursing, while we're reverting
		if ( $this->reverting_role ) {
			return;
		}
		$user = new WP_User( $user_id );

		// Try to make the conditional checks clearer
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

	/**
	 * Filters wp_redirect so we can replace the query string arguments
	 * and manipulate the admin notice shown to the user to reflect what
	 * has happened (e.g. role setting has been rejected as the user is
	 * not an A12n).
	 *
	 * @param $location
	 *
	 * @return string
	 */
	public function filter_wp_redirect( $location ) {
		if ( ! $this->message_replace && ! $this->registering_a12n ) {
			return $location;
		}
		if ( $this->message_replace ) {
			$location = add_query_arg( array( 'update' => $this->message_replace ), $location );
			$location = esc_url_raw( $location );
		}
		if ( $this->registering_a12n ) {
			$location = add_query_arg( array( 'update' => self::MSG_MADE_VIP ), $location );
			$location = esc_url_raw( $location );
		}
		return $location;
	}

	/**
	 * Hooks the user_register action to determine if we're registering an
	 * A12n, and so need an email verification. Also checks if the registered
	 * user cannot be set to VIP Support role (as not an A12n).
	 *
	 * @param int $user_id The ID of the user which has been registered.
	 */
	public function action_user_register( $user_id ) {
		$user = new WP_User( $user_id );
		if ( $this->is_a8c_email( $user->user_email ) ) {
			$user->set_role( VipSupportRole::VIP_SUPPORT_ROLE );
			$this->registering_a12n = true;
			update_user_meta( $user_id, self::META_EMAIL_VERIFIED, false );
			$this->send_verification_email( $user_id );
		} else {
			if ( self::MSG_BLOCK_UPGRADE_NON_A12N == $this->message_replace ) {
				$this->message_replace = self::MSG_BLOCK_NEW_NON_VIP_USER;
			}
		}
	}

	/**
	 * Hooks the profile_update action to send the verification email if
	 * we've not verified this email.
	 *
	 * @param int $user_id The ID of the user whose profile was just updated
	 */
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

	/**
	 * Hooks the parse_request action to do any email verification.
	 */
	public function action_parse_request() {
		if ( ! isset( $_GET[self::GET_EMAIL_VERIFY] ) ) {
			return;
		}

		$user_id = absint( $_GET[self::GET_EMAIL_USER_ID] );
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		// We only want the user who was sent the email to be able to verify their email
		// (i.e. not another logged in or anonymous user clicking the link).
		// @FIXME: Should we expire the link at this point, so an attacker cannot iterate the IDs?
		if ( get_current_user_id() != $user_id ) {
			$message = __( 'This email verification link was not created for your user. Please log in as the user in the email sent to you, and then click the link again.', 'vip-support' );
			$title = __( 'Verification failed', 'vip-support' );
			// 403 Forbidden – The server understood the request, but is refusing to fulfill it.
			// Authorization will not help and the request SHOULD NOT be repeated.
			wp_die( $message, $title, array( 'response' => 403 ) );
		}


		$stored_verification_code = (string) get_user_meta( $user_id, self::META_VERIFICATION_CODE, true );
		$hash_sent                = (string) $_GET[self::GET_EMAIL_VERIFY];

		// The hash sent in the email verification link is composed of the user ID, a verification code
		// generated and stored when the email was sent (a random string), and the user email. The idea
		// being that each verification link is tied to a user AND a particular email address, so a link
		// does not work if the user has subsequently changed their email and does not work for another
		// logged in or anonymous user.
		$check_hash = wp_hash( get_current_user_id() . $stored_verification_code . $user->user_email );

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

		// If the user is an A12n, add them to the support role
		if ( $this->is_a8c_email( $user->user_email ) ) {
			$user->add_role( VipSupportRole::VIP_SUPPORT_ROLE );
		}

		$message = sprintf( __( 'Your email has been verified as %s', 'vip-support' ), $user->user_email );
		$title = __( 'Verification succeeded', 'vip-support' );
		wp_die( $message, $title, array( 'response' => 200 ) );
	}

	/**
	 * Hooks the removable_query_args filter to add our arguments to those
	 * tidied up by Javascript so the user sees nicer URLs.
	 *
	 * @param array $args An array of URL parameter names which are tidied away
	 *
	 * @return array An array of URL parameter names which are tidied away
	 */
	public function filter_removable_query_args( $args ) {
		$args[] = self::GET_TRIGGER_RESEND_VERIFICATION;
		return $args;
	}

	// UTILITIES
	// =========

	/**
	 * Send a user an email with a verification link for their current email address.
	 *
	 * See the action_parse_request for information about the hash
	 * @see VipSupportUser::action_parse_request
	 *
	 * @param int $user_id The ID of the user to send the email to
	 */
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

	/**
	 * Check if a user has verified their email address.
	 *
	 * @param int $user_id The ID of the user to check
	 *
	 * @return bool True if the user has a verified email address, otherwise false
	 */
	protected function user_has_verified_email( $user_id ) {
		$user = new WP_User( $user_id );
		$verified_email = get_user_meta( $user_id, self::META_EMAIL_VERIFIED, true );
		return ( $user->user_email == $verified_email );
	}

	/**
	 * Create and return a URL with a parameter which will trigger the
	 * resending of a verification email.
	 *
	 * @return string A URL with a parameter to trigger a verification email
	 */
	protected function get_trigger_resend_verification_url() {
		return add_query_arg( array( self::GET_TRIGGER_RESEND_VERIFICATION => '1' ) );
	}

	/**
	 * Is a provided string an email address using an A8c domain.
	 *
	 * @TODO Needs unit tests
	 *
	 * @param string $email An email address to check
	 *
	 * @return bool True if the string is an email with an A8c domain
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

VipSupportUser::init();
