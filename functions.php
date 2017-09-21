<?php
/**
 * Generic plugin functions
 */

namespace Automattic\VIP\Support_User;
use WP_Error;
use WP_User;

/**
 * Add a new support user for the given request
 *
 * Will delete an existing user with the same user_login as is to be created
 *
 * @param array $user_data Array of data to create user.
 * @return int|WP_Error
 */
function new_support_user( $user_data ) {
	// A user with this email address may already exist, in which case
	// we should update that user record
	$user = get_user_by( 'email', $user_data['user_email'] );

	/** Include admin user functions to get access to wp_delete_user() */
	require_once ABSPATH . 'wp-admin/includes/user.php';

	// If the user already exists, we should delete and recreate them,
	// it's the only way to be sure we get the right user_login
	if ( false !== $user && $user_data['user_login'] !== $user->user_login ) {
		if ( is_multisite() ) {
			revoke_super_admin( $user->ID );
			wpmu_delete_user( $user->ID );
		} else {
			wp_delete_user( $user->ID, null );
		}
		$user = false;
	} elseif ( $user && $user->ID ) {
		$user_data['ID'] = $user->ID;
	}

	if ( false === $user ) {
		$user_id = wp_insert_user( $user_data );
	} else {
		add_filter( 'send_password_change_email', '__return_false' );
		$user_id = wp_update_user( $user_data );
		remove_filter( 'send_password_change_email', '__return_false' );
	}

	// It's possible the user update/insert will fail, we need to log this
	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	remove_action( 'set_user_role', array( WPCOM_VIP_Support_User::init(), 'action_set_user_role' ), 10 );
	$user = new WP_User( $user_id );
	add_action( 'set_user_role', array( WPCOM_VIP_Support_User::init(), 'action_set_user_role' ), 10, 3 );

	// Seems polite to notify the admin that a support user got added to their site
	// @TODO Tailor the notification email so it explains that this is a support user
	wp_new_user_notification( $user_id, null, 'admin' );

	WPCOM_VIP_Support_User::init()->mark_user_email_verified( $user->ID, $user->user_email );
	$user->set_role( WPCOM_VIP_Support_Role::VIP_SUPPORT_ROLE );

	// If this is a multisite, commence super powers!
	if ( is_multisite() ) {
		grant_super_admin( $user->ID );
	}

	return $user_id;
}

/**
 * Remove a VIP Support user
 *
 * @param mixed $user_id User ID, login, or email. See `get_user_by()`.
 * @param string $by What to search for user by. See `get_user_by()`.
 * @return bool|WP_Error
 */
function remove_support_user( $user_id, $by = 'email' ) {
	// Let's find the user
	$user = get_user_by( $by, $user_id );

	if ( false === $user ) {
		return new WP_Error( 'invalid-user', 'User does not exist' );
	}

	// Check user has the active or inactive VIP Support role,
	// and bail out if not
	if ( ! WPCOM_VIP_Support_User::user_has_vip_support_role( $user->ID, true )
		&& ! WPCOM_VIP_Support_User::user_has_vip_support_role( $user->ID, false ) ) {
		return new WP_Error( 'not-support-user', 'Specified user is not a support user' );
	}

	/** Include admin user functions to get access to wp_delete_user() */
	require_once ABSPATH . 'wp-admin/includes/user.php';

	// If the user already exists, we should delete and recreate them,
	// it's the only way to be sure we get the right user_login
	if ( is_multisite() ) {
		revoke_super_admin( $user->ID );
		return wpmu_delete_user( $user->ID );
	}

	return wp_delete_user( $user->ID, null );
}
