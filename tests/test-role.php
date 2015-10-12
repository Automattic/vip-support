<?php

/**
 * @group vip_support_role
 */
class VIPSupportRoleTest extends WP_UnitTestCase {

	/* THIS IS A STRAIGHT COPY FROM CORE users/capabilities.php TESTS */

	protected function _getSingleSiteCaps() {
		return array(

			'unfiltered_html'        => array( 'administrator', 'editor' ),

			'manage_network'         => array(),
			'manage_sites'           => array(),
			'manage_network_users'   => array(),
			'manage_network_plugins' => array(),
			'manage_network_themes'  => array(),
			'manage_network_options' => array(),

			'activate_plugins'       => array( 'administrator' ),
			'create_users'           => array( 'administrator' ),
			'delete_plugins'         => array( 'administrator' ),
			'delete_themes'          => array( 'administrator' ),
			'delete_users'           => array( 'administrator' ),
			'edit_files'             => array( 'administrator' ),
			'edit_plugins'           => array( 'administrator' ),
			'edit_themes'            => array( 'administrator' ),
			'edit_users'             => array( 'administrator' ),
			'install_plugins'        => array( 'administrator' ),
			'install_themes'         => array( 'administrator' ),
			'upload_plugins'         => array( 'administrator' ),
			'upload_themes'          => array( 'administrator' ),
			'update_core'            => array( 'administrator' ),
			'update_plugins'         => array( 'administrator' ),
			'update_themes'          => array( 'administrator' ),
			'edit_theme_options'     => array( 'administrator' ),
			'customize'              => array( 'administrator' ),
			'export'                 => array( 'administrator' ),
			'import'                 => array( 'administrator' ),
			'list_users'             => array( 'administrator' ),
			'manage_options'         => array( 'administrator' ),
			'promote_users'          => array( 'administrator' ),
			'remove_users'           => array( 'administrator' ),
			'switch_themes'          => array( 'administrator' ),
			'edit_dashboard'         => array( 'administrator' ),

			'moderate_comments'      => array( 'administrator', 'editor' ),
			'manage_categories'      => array( 'administrator', 'editor' ),
			'edit_others_posts'      => array( 'administrator', 'editor' ),
			'edit_pages'             => array( 'administrator', 'editor' ),
			'edit_others_pages'      => array( 'administrator', 'editor' ),
			'edit_published_pages'   => array( 'administrator', 'editor' ),
			'publish_pages'          => array( 'administrator', 'editor' ),
			'delete_pages'           => array( 'administrator', 'editor' ),
			'delete_others_pages'    => array( 'administrator', 'editor' ),
			'delete_published_pages' => array( 'administrator', 'editor' ),
			'delete_others_posts'    => array( 'administrator', 'editor' ),
			'delete_private_posts'   => array( 'administrator', 'editor' ),
			'edit_private_posts'     => array( 'administrator', 'editor' ),
			'read_private_posts'     => array( 'administrator', 'editor' ),
			'delete_private_pages'   => array( 'administrator', 'editor' ),
			'edit_private_pages'     => array( 'administrator', 'editor' ),
			'read_private_pages'     => array( 'administrator', 'editor' ),

			'edit_published_posts'   => array( 'administrator', 'editor', 'author' ),
			'upload_files'           => array( 'administrator', 'editor', 'author' ),
			'publish_posts'          => array( 'administrator', 'editor', 'author' ),
			'delete_published_posts' => array( 'administrator', 'editor', 'author' ),

			'edit_posts'             => array( 'administrator', 'editor', 'author', 'contributor' ),
			'delete_posts'           => array( 'administrator', 'editor', 'author', 'contributor' ),

			'read'                   => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),

			'level_10'               => array( 'administrator' ),
			'level_9'                => array( 'administrator' ),
			'level_8'                => array( 'administrator' ),
			'level_7'                => array( 'administrator', 'editor' ),
			'level_6'                => array( 'administrator', 'editor' ),
			'level_5'                => array( 'administrator', 'editor' ),
			'level_4'                => array( 'administrator', 'editor' ),
			'level_3'                => array( 'administrator', 'editor' ),
			'level_2'                => array( 'administrator', 'editor', 'author' ),
			'level_1'                => array( 'administrator', 'editor', 'author', 'contributor' ),
			'level_0'                => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),

		);

	}

	protected function _getMultiSiteCaps() {
		return array(

			'unfiltered_html'        => array(),

			'manage_network'         => array(),
			'manage_sites'           => array(),
			'manage_network_users'   => array(),
			'manage_network_plugins' => array(),
			'manage_network_themes'  => array(),
			'manage_network_options' => array(),
			'activate_plugins'       => array(),
			'create_users'           => array(),
			'delete_plugins'         => array(),
			'delete_themes'          => array(),
			'delete_users'           => array(),
			'edit_files'             => array(),
			'edit_plugins'           => array(),
			'edit_themes'            => array(),
			'edit_users'             => array(),
			'install_plugins'        => array(),
			'install_themes'         => array(),
			'upload_plugins'         => array(),
			'upload_themes'          => array(),
			'update_core'            => array(),
			'update_plugins'         => array(),
			'update_themes'          => array(),

			'edit_theme_options'     => array( 'administrator' ),
			'customize'              => array( 'administrator' ),
			'export'                 => array( 'administrator' ),
			'import'                 => array( 'administrator' ),
			'list_users'             => array( 'administrator' ),
			'manage_options'         => array( 'administrator' ),
			'promote_users'          => array( 'administrator' ),
			'remove_users'           => array( 'administrator' ),
			'switch_themes'          => array( 'administrator' ),
			'edit_dashboard'         => array( 'administrator' ),

			'moderate_comments'      => array( 'administrator', 'editor' ),
			'manage_categories'      => array( 'administrator', 'editor' ),
			'edit_others_posts'      => array( 'administrator', 'editor' ),
			'edit_pages'             => array( 'administrator', 'editor' ),
			'edit_others_pages'      => array( 'administrator', 'editor' ),
			'edit_published_pages'   => array( 'administrator', 'editor' ),
			'publish_pages'          => array( 'administrator', 'editor' ),
			'delete_pages'           => array( 'administrator', 'editor' ),
			'delete_others_pages'    => array( 'administrator', 'editor' ),
			'delete_published_pages' => array( 'administrator', 'editor' ),
			'delete_others_posts'    => array( 'administrator', 'editor' ),
			'delete_private_posts'   => array( 'administrator', 'editor' ),
			'edit_private_posts'     => array( 'administrator', 'editor' ),
			'read_private_posts'     => array( 'administrator', 'editor' ),
			'delete_private_pages'   => array( 'administrator', 'editor' ),
			'edit_private_pages'     => array( 'administrator', 'editor' ),
			'read_private_pages'     => array( 'administrator', 'editor' ),

			'edit_published_posts'   => array( 'administrator', 'editor', 'author' ),
			'upload_files'           => array( 'administrator', 'editor', 'author' ),
			'publish_posts'          => array( 'administrator', 'editor', 'author' ),
			'delete_published_posts' => array( 'administrator', 'editor', 'author' ),

			'edit_posts'             => array( 'administrator', 'editor', 'author', 'contributor' ),
			'delete_posts'           => array( 'administrator', 'editor', 'author', 'contributor' ),

			'read'                   => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),

			'level_10'               => array( 'administrator' ),
			'level_9'                => array( 'administrator' ),
			'level_8'                => array( 'administrator' ),
			'level_7'                => array( 'administrator', 'editor' ),
			'level_6'                => array( 'administrator', 'editor' ),
			'level_5'                => array( 'administrator', 'editor' ),
			'level_4'                => array( 'administrator', 'editor' ),
			'level_3'                => array( 'administrator', 'editor' ),
			'level_2'                => array( 'administrator', 'editor', 'author' ),
			'level_1'                => array( 'administrator', 'editor', 'author', 'contributor' ),
			'level_0'                => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),

		);

	}

	protected function getCapsAndRoles() {
		if ( is_multisite() ) {
			return $this->_getMultiSiteCaps();
		} else {
			return $this->_getSingleSiteCaps();
		}
	}

	function test_role_order() {

		// Arrange
		// Trigger the update method call on admin_init,
		// this sets up the role
		WPCOM_VIP_Support_Role::init()->action_admin_init();

		// Act
		$roles = get_editable_roles();
		$role_names = array_keys( $roles );

		// Assert
		// To show up last, the VIP Support role will be
		// the first index in the array
		$first_role = array_shift( $role_names );
		$this->assertTrue( WPCOM_VIP_Support_Role::VIP_SUPPORT_ROLE === $first_role, "VIP Support should be the first index in the roles array" );
	}

	// test the tests
	function test_single_and_multisite_cap_tests_match() {
		$single = $this->_getSingleSiteCaps();
		$multi  = $this->_getMultiSiteCaps();
		$this->assertEquals( array_keys( $single ), array_keys( $multi ) );
	}

	/* END STRAIGHT COPY */

	function test_vip_support_single_site_caps() {
//		if ( ! is_multisite() ) {
//			$this->markTestSkipped( 'Test only runs in multisite' );
//			return;
//		}
		$caps = $this->getCapsAndRoles();

		$user = $this->factory->user->create_and_get( array( 'role' => WPCOM_VIP_Support_Role::VIP_SUPPORT_ROLE ) );
//		grant_super_admin( $user->ID );

//		$this->assertTrue( is_super_admin( $user->ID ) );

		foreach ( $caps as $cap => $roles ) {
			$this->assertTrue( $user->has_cap( $cap ), "VIP Support users should have the {$cap} capability" );
			$this->assertTrue( user_can( $user, $cap ), "VIP Support users should have the {$cap} capability" );
		}

//		$this->assertFalse( $user->has_cap( 'do_not_allow' ), 'VIP Support users should not have the do_not_allow capability' );
//		$this->assertFalse( user_can( $user, 'do_not_allow' ), 'VIP Support users should not have the do_not_allow capability' );
	}

	function test_vip_support_multisite_caps() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Test only runs in multisite' );
			return;
		}
		$caps = $this->getCapsAndRoles();

		$user = $this->factory->user->create_and_get( array( 'role' => WPCOM_VIP_Support_Role::VIP_SUPPORT_ROLE ) );
		// We should be auto-granting a VIP Support user with super admin
		// grant_super_admin( $user->ID );

		$this->assertTrue( is_super_admin( $user->ID ) );

		foreach ( $caps as $cap => $roles ) {
			$this->assertTrue( $user->has_cap( $cap ), "VIP Support users should have the {$cap} capability" );
			$this->assertTrue( user_can( $user, $cap ), "VIP Support users should have the {$cap} capability" );
		}

		$this->assertFalse( $user->has_cap( 'do_not_allow' ), 'VIP Support users should not have the do_not_allow capability' );
		$this->assertFalse( user_can( $user, 'do_not_allow' ), 'VIP Support users should not have the do_not_allow capability' );
	}

	function test_role_caps() {

	}
}
