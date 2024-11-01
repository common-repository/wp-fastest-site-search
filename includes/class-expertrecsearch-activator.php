<?php

require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-logger.php';


class Expertrecsearch_Activator {


	public static function activate() {
		do_action( 'er/general', 'Plugin Activated' );
		if ( self::needsUpgrade() ) {
			self::versionUpgrade();
		}

		add_option( 'expertrec_plugin_do_activation_redirect', true );
	}

	public static function needsUpgrade() {
		$options = get_option( 'expertrec_options' );
		if ( ! $options || ( ! is_array( $options ) ) ) {
			return true;
		}
		$current_version = $options[ EXPERTREC_DB_OPTIONS_VERSION_KEY ];
		if ( ! $current_version ) {
			return true;
		}
		if ( version_compare( $current_version, EXPERTREC_VERSION, '<' ) ) {
			do_action(
				'er/general',
				'Version upgrade needed in activator flow.  Current version: ' .
				$current_version . ' New version: ' . EXPERTREC_VERSION
			);
			return true;
		}
		return false;
	}
	public static function versionUpgrade() {
		do_action( 'er/general', 'Reading current options for upgrade' );

		$options = get_option( 'expertrec_options' );
		do_action(
			'er/general',
			'reading expertrec options as ' .
			print_r( $options, true )
		);

		$newoptions = array(
			EXPERTREC_DB_OPTIONS_VERSION_KEY => EXPERTREC_VERSION,
			'site_id'                        => '58c9e0e4-78e5-11ea-baf0-0242ac130002',
			'hook_on_existing_input_box'     => 1,
			'template'                       => 'separate',
			'search_path'                    => '/expertrec-search/',
			'query_parameter'                => 's',
			'expertrec_account_created'      => false,
			'first_sync_done'                => false,
			'er_batch_size'                  => 5,
		);


		if ( ! $options || ( ! is_array( $options ) ) ||
			( ! array_key_exists( EXPERTREC_DB_OPTIONS_VERSION_KEY, $options ) ) ) {
			do_action( 'er/general', 'Fresh install activation sequence followed' );

			update_option( EXPERTREC_DB_OPTIONS_SENTRY_ENABLED, 'on' );

			update_option( 'EXPERTREC_DB_OPTIONS_INDEX_VARIANTS', false );

			update_option( 'EXPERTREC_DB_OPTIONS_INIT', false );

			$expertrec_options = $newoptions;
			self::createSearchPage( $expertrec_options );
		} else {
			$current_version = $options[ EXPERTREC_DB_OPTIONS_VERSION_KEY ];
			do_action( 'er/general', 'upgrading from ' . $current_version . ' to ' . EXPERTREC_VERSION );


			$expertrec_options = array( EXPERTREC_DB_OPTIONS_VERSION_KEY => EXPERTREC_VERSION ) +
				$options + $newoptions;

			$page_id   = $expertrec_options['expertrec_search_page_id'];
			$page_done = false;
			if ( $page_id ) {
				do_action( 'er/general', 'existing page id retrieved as ' . $page_id );
				$current_page = get_post( $page_id );
				do_action( 'er/general', 'get_post came back ' );
				if ( $current_page ) {
					do_action( 'er/general', 'getting permalink' );
					$permalink = get_permalink( $current_page );
					do_action( 'er/general', 'existing page permalink for id retrieved as ' . $permalink );

					$page_done = true;
					if ( version_compare( $options[ EXPERTREC_DB_OPTIONS_VERSION_KEY ], '5.1.1', '<' ) ) {
						do_action( 'er/general', 'older version of plugin detected, updating the page content ' . $options[ EXPERTREC_DB_OPTIONS_VERSION_KEY ] );
						self::updateSearchPage( $current_page );
					}
				} else {
					do_action( 'er/general', 'get post was not valid for id ' . $page_id );
				}
			}

			if ( ! $page_done ) {
				$current_page = $options['search_path'];
				if ( $current_page ) {
					do_action( 'er/general', 'existing page path retrieved as ' . $current_page );
					$page = get_page_by_path( $current_page );
					if ( ! $page ) {
						do_action( 'er/general', 'existing page not found, creating ...' );
						self::createSearchPage( $expertrec_options );
					} else {
						do_action( 'er/general', 'existing page upgraded as ' . print_r( $page, true ) );
						self::updateSearchPage( $page );
						$expertrec_options['expertrec_search_page_id'] = $page->ID;
					}
				} else {
					do_action( 'er/general', 'no existing page found - creating' );

					self::createSearchPage( $expertrec_options );
				}
			}
		}
		do_action( 'er/general', 'writing to db expertrec options as ' . print_r( $expertrec_options, true ) );
		update_option( 'expertrec_options', $expertrec_options );
		update_option( EXPERTREC_DB_OPTIONS_KEY_JSON, wp_json_encode( $expertrec_options ) );
		$product_attributes = get_option( EXPERTREC_DB_OPTIONS_KEY_PRODUCT_ATTRIBUTES );
		if ( ! $product_attributes ) {
			update_option( EXPERTREC_DB_OPTIONS_KEY_PRODUCT_ATTRIBUTES, '[]' );
			do_action( 'er/general', 'wrote empty product attributes ' );
		}
		$meta_keys = get_option( EXPERTREC_DB_OPTIONS_KEY_META_ATTRIBUTES );
		if ( ! $meta_keys ) {
			update_option( EXPERTREC_DB_OPTIONS_KEY_META_ATTRIBUTES, '[]' );
			do_action( 'er/general', 'wrote empty meta keys ' );
		}
		$doc_types = get_option( EXPERTREC_DB_OPTIONS_SELECTED_DOC_TYPES );
		if ( ! $doc_types ) {
			update_option( EXPERTREC_DB_OPTIONS_SELECTED_DOC_TYPES, array() );
			do_action( 'er/general', 'wrote empty doc types ' );
		}
		$data = array(
			'old_plugin_version' => isset( $current_version ) ? $current_version : 'NA',
		);
		wp_events( 'old_plugin_upgraded', $data );
		do_action( 'er/general', 'Upgrade is done!' );
		wp_cache_flush();
	}

	private static $post_content = array(
		'post_title'          => 'Search results',
		'post_type'           => 'page',
		'post_name'           => 'expertrec-search',
				'post_status' => 'publish',
		'post_content'        => '<!-- wp:html -->
	 <div class="ci-search-results alignfull"></div>
<!-- /wp:html -->
',
	);

	public static function updateSearchPage( $post ) {

		$expertrec_options = get_option( 'expertrec_options' );

		$post_content = array(
			'post_title'   => 'Search results',
			'post_type'    => 'page',
			'post_name'    => $expertrec_options['search_path'],
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:html -->
					<div class="ci-search-results alignfull"></div>
					<!-- /wp:html -->',
		);

		$post->post_content = $post_content;
		do_action( 'er/general', 'updating old post content with new data' );
		$success = wp_update_post( $post, false, false );
		if ( ! $success ) {
			do_action( 'er/general', 'Updation of post content failed: ' . $post->ID );
		}
	}

	public static function createSearchPage( array &$expertrec_options ) {
		$page_id = wp_insert_post( self::$post_content, false, false );
		if ( ! is_wp_error( $page_id ) && $page_id ) {
			do_action( 'er/general', 'Search page created with page ID: ' . print_r( $page_id, true ) );

			$search_page = get_permalink( $page_id );
			do_action( 'er/general', 'Full url of Search page is : ' . $search_page );
			$expertrec_options['expertrec_search_page_id'] = $page_id;
			$expertrec_options['expertrec_search_page_url'] = $search_page;
			$expertrec_options['search_path'] = wp_make_link_relative( $search_page );


			if ( '58c9e0e4-78e5-11ea-baf0-0242ac130002' != $expertrec_options['site_id'] ) {
				expertrec_read_from_db_and_update_layout_conf();
				expertrec_update_conf();
				do_action( 'er/debug', 'User already signed in : syncing search page with cp backend' );
			}
		} else {
			do_action( 'er/general', 'Error while creating search page. ' . print_r( $page_id, true ) );
		}
	}
}
