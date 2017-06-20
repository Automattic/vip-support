<?php

class VIP_Support_REST_Controller {

	private static $namespace;

	function __construct() {
		self::$namespace = 'vip/v1';
		add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
	}

	function permission_callback() {
		return wpcom_vip_go_rest_api_request_allowed( self::$namespace );
	}

	function rest_api_init() {
		register_rest_route( self::$namespace, '/support-user', array(

			// POST /vip/v1/support-user
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'permission_callback' ),
				'args' => array(
					'user_login' => array(
						'required' => true,
					),
					'user_pass' => array(
						'required' => true,
						'validate_callback' => function( $param, $request, $key ) {
							if ( strlen( $param ) < 10 ) {
								return new WP_Error( 'invalid-password', 'Password must be at least 10 characters' );
							}

							return true;
						},
					),
					'user_email' => array(
						'required' => true,
						'validate_callback' => function( $param, $request, $key ) {
							if ( ! is_email( $param ) ) {
								return new WP_Error( 'invalid-email', 'Must be a valid email address' );
							}

							return true;
						},
					),
					'display_name' => array(
						'default' => 'VIP Support',
					),
				),
				'callback' => function( WP_REST_Request $request ) {
					$user_login = $request->get_param( 'user_login' );
					$user_pass = $request->get_param( 'user_pass' );
					$email = $request->get_param( 'user_email' );
					$name = $request->get_param( 'display_name' );

					$user = get_user_by( 'email', $email );

					/** Include admin user functions to get access to wp_delete_user() */
					require_once ABSPATH . 'wp-admin/includes/user.php';

					// If the user already exists, delete and recreate
					if ( ! $user || ! $user->user_login ) {
						if ( is_multisite() ) {
							revoke_super_admin( $user->ID );
							wpmu_delete_user( $user->ID );
						} else {
							wp_delete_user( $user->ID );
						}
					}

					$user_id = wp_insert_user( array(
						'ID' => $user->ID,
						'user_login' => $user_login,
						'password' => $user_pass,
						'user_email' => $email,
						'display_name' => $name,
					));

					if ( is_wp_error( $user_id ) ) {
						return $user_id;
					}

					return array( 'success' => true, 'user_id' => $user_id );
				},
			),
		) );

		register_rest_route( self::$namespace, '/support-user/(?P<id>[\d]+)', array(

			// DELETE /vip/v1/support-user/:id
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'permission_callback' => array( $this, 'permission_callback' ),
				'args' => array(
					'id' => array(
						'required' => true,
					),
				),
				'callback' => function( WP_REST_Request $request ) {
					$id = $request->get_param( 'id' );
					$user = get_user_by( 'id', $id );

					if ( ! $user ) {
						return new WP_Error( 'invalid-user', 'No user exists with that email address' );
					}

					if ( ! WPCOM_VIP_Support_User::user_has_vip_support_role( $user->ID, true )
						&& ! WPCOM_VIP_Support_User::user_has_vip_support_role( $user->ID, false ) ) {

						return new WP_Error( 'invalid-user', 'That is not a VIP Support user' );
					}

					/** Include admin user functions to get access to wp_delete_user() */
					require_once ABSPATH . 'wp-admin/includes/user.php';

					if ( ! $user || ! $user->user_login ) {
						if ( is_multisite() ) {
							revoke_super_admin( $user->ID );
							$deleted = wpmu_delete_user( $user->ID );
							if ( ! $deleted ) {
								return new WP_Error( 'could-not-delete', 'Could not delete given user' );
							}
						} else {
							$deleted = wp_delete_user( $user->ID );
							if ( ! $deleted ) {
								return new WP_Error( 'could-not-delete', 'Could not delete given user' );
							}
						}
					}

					return array( 'success' => true );
				},
			),
		) );
	}
}

new VIP_Support_REST_Controller;
