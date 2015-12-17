<?php

/**
 * Implements a WP CLI command that converts guid users to meta users
 * Class command
 *
 * @package a8c\vip_support
 */
class WPCOM_VIP_Support_CLI  extends WP_CLI_Command {


	/**
	 * Creates a user in the VIP Support role, already verified,
	 * and suppresses all emails.
	 *
	 * @subcommand create-user
	 *
	 * @synopsis <user-login> <user-email> [--user-pass=<user-pass>] [--display-name=<display-name>]
	 *
	 * ## EXAMPLES
	 *
	 *     wp vipsupport create-user username user@domain.tld display_name
	 *
	 */
	public function add_support_user( $args, $assoc_args ) {

		$user_login   = $args[0];
		$user_email   = $args[1];
		$user_pass = $assoc_args['user-pass'];
		$display_name = $assoc_args['display-name'];

		// @TODO Verify the email address
		if ( ! is_email( $user_email ) ) {
			\WP_CLI::error( "User cannot be added as '{$user_email}' is not a valid email address" );
		}

		if ( empty( $user_pass ) ) {
			$user_pass = wp_generate_password( 64 );
		}

		$user_data = array();
		$user_data['user_pass']    = $user_pass;
		$user_data['user_login']   = $user_login;
		$user_data['user_email']   = $user_email;
		$user_data['display_name'] = $display_name;

		$user_id = wp_insert_user( $user_data );

		// It's possible the user insert will fail, we need to cope with this
		if ( is_wp_error( $user_id ) ) {
			\WP_CLI::error( $user_id );
		}
		remove_action( 'set_user_role', array( WPCOM_VIP_Support_User::init(), 'action_set_user_role' ), 10, 3 );
		$user = new WP_User( $user_id );
		add_action( 'set_user_role', array( WPCOM_VIP_Support_User::init(), 'action_set_user_role' ), 10, 3 );

		// Seems polite to notify the admin that a support user got added to their site
		// @TODO Tailor the notification email so it explains that this is a support user
		wp_new_user_notification( $user_id, null, 'admin' );

		WPCOM_VIP_Support_User::init()->mark_user_email_verified( $user->ID, $user->user_email );
		$user->set_role( WPCOM_VIP_Support_Role::VIP_SUPPORT_ROLE );

		// Print a success message
		\WP_CLI::success( "Added user $user_id with login {$user_login}, they are verified as a VP Support user and ready to go" );
	}


	/**
	 * Marks the user with the provided ID as having a verified email.
	 *
	 *
	 * <user-id>
	 * : The WP User ID to mark as having a verified email address
	 *
	 * @subcommand verify
	 *
	 * ## EXAMPLES
	 *
	 *     wp vipsupport verify 99
	 *
	 */
	public function verify( $args, $assoc_args ) {

		$user_id = absint( $args[0] );
		if ( ! $user_id ) {
			\WP_CLI::error( "Please provide the ID of the user to verify" );
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			\WP_CLI::error( "Could not find a user with ID $user_id" );
		}

		WPCOM_VIP_Support_User::init()->mark_user_email_verified( $user->ID, $user->user_email );

		// Print a success message
		\WP_CLI::success( "Verified user $user_id with email {$user->user_email}, you can now change their role to VIP Support" );
	}

}

\WP_CLI::add_command( 'vipsupport', 'WPCOM_VIP_Support_CLI' );