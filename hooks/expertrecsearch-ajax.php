<?php


if ( ! defined( 'WPINC' ) ) {
	die( "You can't access this file directly" );
}

require_once plugin_dir_path( __DIR__ ) . 'hooks/expertrecsearch-caller.php';
require_once plugin_dir_path( __DIR__ ) . 'includes/class-expertrecsearch-logger.php';
require_once plugin_dir_path( __DIR__ ) . 'shortcodes/expertrecsearch-search-bar.php';


function expertrec_login($body) {
	do_action( 'er/debug', 'Expertrec Login done and org created/retreived' );
		$log = ExpLogger::loging();
		$log->general( 'login', 'login post data: ' . print_r( $body, true ) );
		$site_id          = isset( $body['site_id'] ) ? sanitize_text_field( $body['site_id'] ) : '';
		$expertrec_engine = isset( $body['expertrec_engine'] ) ? sanitize_text_field( $body['expertrec_engine'] ) : '';
		$ecom_id          = '';
		$cse_id           = '';
	if ( 'db' == $expertrec_engine ) {
		$ecom_id = $site_id;
	} else {
		$cse_id = $site_id;
	}
		$write_api_key = sanitize_text_field( isset( $body['write_api_key'] ) ? $body['write_api_key'] : '' );

		$options = get_option( 'expertrec_options' );
	if ( isset( $options ) && isset( $site_id ) ) {
		$options['site_id']                   = $site_id;
		$options['ecom_id']                   = $ecom_id;
		$options['cse_id']                    = $cse_id;
		$options['write_api_key']             = $write_api_key;
		$options['expertrec_account_created'] = true;
		update_option( 'expertrec_options', $options );
		update_option( 'expertrec_engine', $expertrec_engine );
		$log->general( 'login', 'User logged in' );
		wp_events( 'login_completed' );
		do_action( 'er/debug', 'Login Completed, updating search_path to site_id' . $site_id );
		expertrec_read_from_db_and_update_layout_conf();
		expertrec_update_conf();
		$log->general( 'login', 'got details of search page and saved into WP db.' );
		wp_events( 'login_completed' );
		exit();
	}
}


function expertrec_get_site_info() {

	$data = expertrec_get_site_info_internal();

	wp_send_json( $data );
}

function expertrec_get_site_info_internal() {

	do_action( 'er/debug', 'In get site info' );

	$ret = expertrec_site_information();

	do_action( 'er/debug', 'site info :' . print_r( $ret, true ) );
	return $ret;
}


function expertrec_get_index_stats() {
	do_action( 'er/debug', 'In get index stats' );
	$expertrec_options = get_option( 'expertrec_options' );
	if ( array_key_exists( 'index_stats', $expertrec_options ) ) {
		$index_stats          = $expertrec_options['index_stats'];
		$index_stats['other'] = expertrec_aggregate_other_count( $index_stats );
		foreach ( $index_stats as $post_type => $value ) {
			if ( 'currently_indexing' != $post_type ) {
				if ( 0 === $value['indexed'] && 0 === $value['indexable'] ) {
					$label = 'No Data Found';
				} elseif ( 0 === $value['indexed'] && 0 !== $value['indexable'] ) {
					$label = 'Not Started';
				} else {
					$label = $value['indexed'] . '/' . $value['indexable'];
				}
				$index_stats[ $post_type ]['label'] = $label;
			}
		}
		do_action( 'er/debug', 'Index stats :' . print_r( $index_stats, true ) );
		wp_send_json( $index_stats );
	} else {
		wp_send_json( false );
	}
}

function filter_array_by_string($taxonomies, $filter_string) {

	$filtered_array = array_filter(
		$taxonomies,
		function ($taxonomy) use ($filter_string) {
			return strpos($taxonomy, $filter_string) !== false;
		}
	);

	return $filtered_array;
}

function expertrec_get_brand_taxonomy() {
	if ( expertrec_is_woocommerce_internal() ) {

		do_action( 'er/debug', 'In get brand taxonomy' );

		$brand_string               = 'brand';
		$get_all_product_taxonomies = get_object_taxonomies('product');

		$taxonomies_matched_with_brand_substring = filter_array_by_string($get_all_product_taxonomies, $brand_string);

		do_action( 'er/debug', 'Taxonomies matched with brand substring: ' . print_r($taxonomies_matched_with_brand_substring, true) );

		if ( ! empty($taxonomies_matched_with_brand_substring)) {
			$brand_taxonomy = reset($taxonomies_matched_with_brand_substring);
			do_action( 'er/debug', 'Brand taxonomy: ' . $brand_taxonomy );
			return $brand_taxonomy;
		} else {
			do_action( 'er/debug', 'No matching brands found in product taxonomies' . print_r($get_all_product_taxonomies, true) );
			return false;
		}
	}
}


function expertrec_aggregate_other_count( $index_stats ) {
	$debug_message = 'In aggregate other count';
	do_action( 'er/debug', $debug_message );
	$other_indexed   = 0;
	$other_indexable = 0;
	foreach ( $index_stats as $post_type => $value ) {
		if ( ! in_array( $post_type, array( 'product', 'post', 'page', 'currently_indexing' ) ) ) {
			$other_indexed   += $value['indexed'];
			$other_indexable += $value['indexable'];
		}
	}
	$other_data['indexed']   = $other_indexed;
	$other_data['indexable'] = $other_indexable;
	$other_data['label']     = $other_indexed . '/' . $other_indexable;
	return $other_data;
}


function expertrec_is_expired() {
	do_action( 'er/debug', 'In functin expertrec is expired' );
	$resp = get_days_to_expire();
	do_action( 'er/debug', 'Days to expire: ' . print_r( $resp, true ) );
	if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$json_data = wp_remote_retrieve_body( $resp );
		wp_send_json( $json_data );
	} else {
		wp_send_json( false );
	}
}


function expertrec_read_from_db_and_update_layout_conf() {
	do_action( 'er/general', 'In read form db and update layout conf' );
	$options = get_option( 'expertrec_options' );
	$data    = array(
		'layout' => array(
			'path' => $options['search_path'],
		),
	);
	$site_id = $options['site_id'];
	$resp    = update_expertrec_search_page( $site_id, $data );
	if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		do_action( 'er/general', 'read_from_db_and_update_layout_conf search page sync completed, response : ' . wp_remote_retrieve_body( $resp ) );
	} else {
		do_action(
			'er/general',
			'read_from_db_and_update_layout_conf sync has failed with code ' .
								wp_remote_retrieve_response_code( $resp )
		);
	}
}


function expertrec_layout_submit( $body ) {
	do_action( 'er/debug', 'Updating Layout' );

	$template = isset( $body['template'] ) ? sanitize_text_field( $body['template'] ) : '';
	do_action( 'er/debug', 'Updating Layout to ' . $template );

	$data = array(
		'layout' => array(
			'template' => $template,
		),
	);
	if ( 'separate' == $template ) {
		do_action( 'er/debug', 'Updating to separate Layout' );
		do_action( 'er/debug', 'fetching search_path and query_parameter from post' );
		$search_path                       = isset( $body['search_path'] ) ? sanitize_text_field( $body['search_path'] ) : '';
		$query_parameter                   = isset( $body['query_parameter'] ) ? sanitize_text_field( $body['query_parameter'] ) : '';
		$data['layout']['search_path']     = $search_path;
		$data['layout']['query_parameter'] = $query_parameter;
	}
	$option  = get_option( 'expertrec_options' );
	$site_id = $option['site_id'];

	do_action( 'er/debug', 'Updating Layout with data : ' . print_r( $data, true ) );

	$resp = update_expertrec_config( $site_id, 'layout', $data );
	$log  = ExpLogger::loging();
	$log->general( 'Layout', 'Current Layout : ' . print_r( $data, true ) );
	if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$options             = get_option( 'expertrec_options' );
		$options['template'] = $template;
		if ( 'separate' == $template ) {
			$options['search_path']     = $search_path;
			$options['query_parameter'] = $query_parameter;
		}
		update_option( 'expertrec_options', $options );
		$search_page_info = expertrec_get_search_page_data();
		return $search_page_info;
	} else {
		$log->general( 'Layout', 'Layout update failed' );
		wp_send_json( $resp );
	}
}


function expertrec_crawl($body) {
	do_action( 'er/debug', 'Crawl function executed' );

	$func = isset( $body['func_to_call'] ) ? sanitize_text_field( $body['func_to_call'] ) : '';

	if ( 'start_crawl' == $func ) {
		$resp = start_crawl();
		do_action( 'er/debug', 'Crawl started' );
	} elseif ( 'stop_crawl' == $func ) {
		$resp = stop_crawl();
		do_action( 'er/debug', 'Crawl stopped' );
	} elseif ( 'crawl_status' == $func ) {
		$resp = crawl_status();
		do_action( 'er/debug', 'Crawl status : ' . print_r( $resp, true ) );
	}
	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}
		wp_send_json( $response );
}


function expertrec_reset_indexing_progress() {
	do_action( 'er/debug', 'Reseting indexing progress' );
	$options = get_option( 'expertrec_options' );
	if ( array_key_exists( 'index_stats', $options ) ) {
		foreach ( $options['index_stats'] as $post_type => $value ) {
			if ( 'currently_indexing' !== $post_type ) {
				$options['index_stats'][ $post_type ]['indexed'] = 0;
			}
		}
		update_option( 'expertrec_options', $options );
	}
}


function get_expertrec_engine() {
	do_action( 'er/debug', 'In expertrec engine' );
	$exp_eng = get_option( 'expertrec_engine' );
	do_action( 'er/debug', 'Getting engine as : ' . print_r( $exp_eng, true ) );
	wp_send_json( $exp_eng );
}



function expertrec_update_conf( $site_id = null, $migrated = false ) {
	do_action( 'er/debug', 'In expertrec update conf' );
	$expertrec_options = get_option( 'expertrec_options' );
	if ( ! $site_id ) {
		$site_id = $expertrec_options['site_id'];
	}
	$expertrec_engine = get_option( 'expertrec_engine' );
	$resp             = get_expertrec_conf( $site_id, $expertrec_engine, $migrated );
	$log              = ExpLogger::loging();
	$log->general( 'expertrec_update_conf', 'get_conf response : ' . print_r( $resp, true ) );
	$json_data = json_decode( $resp, true );
	$template  = $json_data['template'];
	if ( $json_data['disable_search_results'] ) {
		$template = 'dropdown';
	}
	$expertrec_options['site_id']                    = $site_id;
	$expertrec_options['hook_on_existing_input_box'] = $json_data['hook_on_existing_input_box'];
	$expertrec_options['template']                   = $template;
	$expertrec_options['search_path']                = $json_data['search_path'];
	$expertrec_options['query_parameter']            = $json_data['query_parameter'];
	update_option( 'expertrec_options', $expertrec_options );
}


function expertrec_update_config($body) {
	do_action( 'er/debug', 'Updating config' );
	if (isset($body['data']) && is_array($body['data'])) {
		$data = array_map('sanitize_text_field', $body['data']);
	} else {
		$data = array();
	}
		$data['modified_by'] = get_option( 'admin_email' );
		$log                 = ExpLogger::loging();
		$log->general( 'Search Bar', 'Search Bar Status ' . print_r( $data, true ) );
	if ( array_key_exists( 'hook_on_existing_input_box', $data ) ) {
		if ( 'true' == $data['hook_on_existing_input_box'] || 'True' == $data['hook_on_existing_input_box'] ) {
			$data['hook_on_existing_input_box'] = true;
		} else {
			$data['hook_on_existing_input_box'] = false;
		}
	}
	if ( isset( $body['update_type'] ) ) {
		$update_type = sanitize_text_field( $body['update_type'] );
	} else {
		echo "Error: 'update_type' not found in POST request.";
	}
		$site_id = get_option( 'expertrec_options' )['site_id'];
		$resp    = update_expertrec_config( $site_id, $update_type, $data );
	if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$options = get_option( 'expertrec_options' );
		foreach ( $data as $key => $value ) {
			if ( 'modified_by' != $key ) {
				$options[ $key ] = json_decode( $value );
			}
		}
		update_option( 'expertrec_options', $options );
		wp_send_json( true );
	} else {
		wp_send_json( false );
	}
}


function expertrec_is_account_created() {
	do_action( 'er/debug', 'In is account created' );
	$options = get_option( 'expertrec_options' );
	if ( isset( $options ) && array_key_exists( 'expertrec_account_created', $options ) && array_key_exists( 'first_sync_done', $options ) ) {
		do_action( 'er/debug', 'Expertrec account created' );
		wp_send_json(
			array(
				'account_created' => $options['expertrec_account_created'],
				'first_sync_done' => $options['first_sync_done'],
			)
		);
	} else {
		do_action( 'er/debug', 'Expertrec account not created' );
		wp_send_json(
			array(
				'account_created' => false,
				'first_sync_done' => false,
			)
		);
	}
}


function expertrec_indexing_status() {
	do_action( 'er/debug', 'In indexing status' );
	$indexing_status = get_option( 'expertrec_indexing_status' );
	do_action( 'er/debug', 'Indexing status: ' . $indexing_status );
	if ( isset( $indexing_status ) ) {
		wp_send_json( $indexing_status );
	} else {
		wp_send_json( 'NA' );
	}
}


function expertrec_get_last_sync() {
	$options = get_option( 'expertrec_options' );
	if ( array_key_exists( 'last_successful_sync', $options ) ) {
		do_action( 'er/debug', 'Last Successful sync :' . $options['last_successful_sync'] );
		wp_send_json( $options['last_successful_sync'] );
	} else {
		wp_send_json( 'NA' );
	}
}



function expertrec_option_nocache( $option ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

	if ( is_object( $row ) ) {
		return $row->option_value;
	} else {
		return false;
	}
}


function expertrec_reindex_data() {
	do_action( 'er/debug', 'Reindexing data' );
	$options = get_option( 'expertrec_options' );
	$site_id = $options['site_id'];
	$log     = ExpLogger::loging();
	if ( '58c9e0e4-78e5-11ea-baf0-0242ac130002' != $site_id ) {
		$options['first_sync_done'] = true;
		update_option( 'expertrec_options', $options );
		$client = new ExpClient();
		$log->general( 'Indexing status: ', 'Indexing Docs Started' );
		do_action( 'er/debug', 'Indexing Docs Started' );
		$started = $client->start_sync();
		if ( ! $started ) {
			wp_send_json_error( 'not started - indexing already in progress' );
		}
		$client->indexDocs();
		$client->end_sync();
		do_action( 'er/indexing', 'about to write option as complete' );
		$updated = update_option( 'expertrec_indexing_status', 'complete' );
		do_action( 'er/indexing', 'write operation has completed with ' . $updated ? 'true' : 'false' );
		$log->general( 'Indexing status: ', 'Indexing Completed' . $updated ? 'true' : 'false' );
		$log->general( 'Indexing status: ', get_option( 'expertrec_indexing_status' ) );
		unset( $client );
	}
	do_action( 'er/debug', 'Indexing completed' );
	wp_send_json( 'complete' );
}




function expertrec_notify_deactivation($body) {
		$value           = isset( $body['value'] ) ? sanitize_text_field( $body['value'] ) : '';
		$selected_option = isset( $body['selected_option'] ) ? sanitize_text_field( $body['selected_option'] ) : '';
		$description     = isset( $body['description'] ) ? sanitize_text_field( $body['description'] ) : '';
		$data            = array(
			'value'                   => $value,
			'reason_for_deactivation' => $selected_option,
			'description'             => $description,
		);
		wp_events( 'plugin_deactivated', $data );
		do_action( 'er/debug', 'Plugin Deactivated' );
		exit();
}
function expertrec_signup_clicked($body) {
		do_action( 'er/general', 'expertrec signup clicked' );
		$actual_site_url  = isset( $body['site_url'] ) ? sanitize_text_field( $body['site_url'] ) : '';
		$expertrec_engine = isset( $body['expertrec_engine'] ) ? sanitize_text_field( $body['expertrec_engine'] ) : '';
		wp_events(
			'expertrec_signup_clicked',
			array(
				'actual_site_url'  => $actual_site_url,
				'expertrec_engine' => $expertrec_engine,
			)
		);
		do_action( 'er/general', 'expertrec signup clicked notified' );
}

function expertrec_update_attribute_index() {
	do_action( 'er/general', 'expertrec update attribute called' );
	$json = file_get_contents( 'php://input' );
	update_option( EXPERTREC_DB_OPTIONS_KEY_PRODUCT_ATTRIBUTES, $json );
	expertrec_get_product_attribute_to_index();
}

function expertrec_get_product_attribute_to_index() {
	do_action( 'er/general', 'expertrec get attribute called' );
	$json = get_option( EXPERTREC_DB_OPTIONS_KEY_PRODUCT_ATTRIBUTES, '[]' );
	$selected_data = json_decode( $json, true );
	global $wpdb;
	$attribute_taxonomies = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $wpdb->prefix ) . 'woocommerce_attribute_taxonomies ORDER BY attribute_name ASC;' ); // phpcs:ignore
	set_transient( 'exp_wc_attribute_taxonomies', $attribute_taxonomies );

	foreach ( $attribute_taxonomies as $attribute ) {
		$attribute->attribute_name = 'pa_' . $attribute->attribute_name;
		$attribute->selected       = in_array( $attribute->attribute_name, $selected_data );
	}
	wp_send_json( $attribute_taxonomies );
}

function expertrec_update_meta_index() {
	do_action( 'er/general', 'expertrec update meta called' );
	$json = file_get_contents( 'php://input' );
	update_option( EXPERTREC_DB_OPTIONS_KEY_META_ATTRIBUTES, $json );
	expertrec_get_meta_keys_to_index();
}

function expertrec_get_meta_keys_to_index() {
	do_action( 'er/general', 'expertrec get meta keys called' );
	global $wpdb;
	$meta_keys = $wpdb->get_col( 'SELECT DISTINCT meta_key FROM ' . $wpdb->postmeta . ' ORDER BY meta_key' );
	set_transient( 'exp_meta_keys', $meta_keys );
	$json          = get_option( EXPERTREC_DB_OPTIONS_KEY_META_ATTRIBUTES, '[]' );
	$selected_data = json_decode( $json, true );
	$out_keys      = array_map(
		function ( $item ) use ( $selected_data ) {
			return (object) array(
				'name'     => $item,
				'selected' => in_array( $item, $selected_data ),
			);
		},
		$meta_keys
	);

	wp_send_json( $out_keys );
}

function expertrec_update_options() {
	do_action( 'er/general', 'expertrec update options called' );

	$json = file_get_contents( 'php://input' );
	$data    = json_decode( $json, true );
	$options = get_option( 'expertrec_options' );
	foreach ( $data as $option => $value ) {
			do_action(
				'er/general',
				'expertrec updated ' . $option . ' from '
									. $options[ $option ] . ' to ' . $value
			);
			$options[ $option ] = $value;
	}
	update_option( 'expertrec_options', $options );
	update_option( EXPERTREC_DB_OPTIONS_KEY_JSON, wp_json_encode( $options ) );
}

function expertrec_set_debug() {
	do_action( 'er/general', 'expertrec debug on options called' );

	update_option( 'expertrec_debug', true );
	return expertrec_get_debug();
}

function expertrec_reset_debug() {
	do_action( 'er/general', 'expertrec debug off options called' );

	update_option( 'expertrec_debug', false );
	return expertrec_get_debug();
}

function expertrec_get_debug() {
	do_action( 'er/general', 'expertrec get debug option called' );

	return wp_send_json( get_option( 'expertrec_debug', false ) );
}

function expertrec_startindex() {
	$log = ExpLogger::loging();
	$log->indexing( 'expertrec_startindex', 'Reindexing data' );
	$options = get_option( 'expertrec_options' );
	$site_id = $options['site_id'];
	if ( '58c9e0e4-78e5-11ea-baf0-0242ac130002' == $site_id ) {
		wp_send_json_error( 'Site Id is not yet configured' );
	}
	$brand_taxonomy = expertrec_get_brand_taxonomy();
	update_option( 'EXPERTREC_DB_OPTIONS_BRAND_TAXONOMY', $brand_taxonomy );
	$client = new ExpClient();
	$status = $client->new_start_sync();
	if ( ! $status['status'] ) {
		wp_send_json_error( $status, $status['code'] );
	}

	update_option( 'expertrec_stop_indexing', false );
	$start_index_data             = expertrec_recompute_indexable( false );
	$start_index_data['batch_id'] = $status['batch_id'];
	return wp_send_json( $start_index_data );
}

define( 'EXPERTREC_CATEGORY_KEY', 'category' );

function expertrec_recompute_indexable( $first_time = false ) {
	$indexable_data = getIndexableData()['doc_count'];
	if ( expertrec_is_woocommerce_internal() ) {
		$woo_cat_count                            = expertrec_get_woo_category_count();
		$indexable_data[ EXPERTREC_CATEGORY_KEY ] = $woo_cat_count;
	}
	$indexed_data = $indexable_data;
	foreach ( $indexed_data as $key => $value ) {
		$indexed_data[ $key ] = 0;
	}
	$start_index_data = array(
		'indexable_data'     => $indexable_data,
		'indexed_data'       => $indexed_data,
		'currently_indexing' => '',
		'first_time_index'   => $first_time,
		'indexing_complete'  => false,
	);
	update_option( 'expertrec_index_status', wp_json_encode( $start_index_data ) );
	return $start_index_data;
}

function expertrec_get_index_status() {
	$index_status = get_option( 'expertrec_index_status' );
	if ( $index_status ) {
		$index_status = json_decode( $index_status, true );
		return wp_send_json( $index_status );
	} else {
		return wp_send_json( expertrec_recompute_indexable( true ) );
	}
}

function expertrec_continueindex() {
	$json = file_get_contents( 'php://input' );
	$data = json_decode( $json, true );
	$post_type                = $data['post_type'];
	$batch_size               = $data['batch_size'];
	$batch_id                 = $data['batch_id'];
	$offset                   = $data['offset'];
	$index_status             = get_option( 'expertrec_index_status' );
	$index_status             = json_decode( $index_status, true );
	$index_status['batch_id'] = $batch_id;

	if ( EXPERTREC_CATEGORY_KEY == $post_type ) {
		$offset                                       = expertrec_index_category_data( $batch_id );
		$index_status['indexable_data'][ $post_type ] = $offset;
		$index_status['indexed_data'][ $post_type ]   = $offset;
	} elseif ('pdf' == $post_type ) {
		$client = new ExpClient();
		$offset = $client->index_pdf( $batch_size, $batch_id, $offset );
		if ( 0 != $offset ) {
			$index_status['indexed_data'][ $post_type ] = $offset;
		} else {
			do_action( 'er/indexing', 'Indexing pre-mature complete for post type ' . $post_type );
			do_action( 'er/indexing', $index_status['indexed_data'][ $post_type ] . ' of ' . $index_status['indexable_data'][ $post_type ] );
			$index_status['indexable_data'][ $post_type ] = $index_status['indexed_data'][ $post_type ];
		}
	} else {
		$client = new ExpClient();
		$offset = $client->index_post_type( $post_type, $batch_size, $batch_id, $offset );
		if ( 0 != $offset ) {
			$index_status['indexed_data'][ $post_type ] = $offset;
		} else {
			do_action( 'er/indexing', 'Indexing pre-mature complete for post type ' . $post_type );
			do_action( 'er/indexing', $index_status['indexed_data'][ $post_type ] . ' of ' . $index_status['indexable_data'][ $post_type ] );
			$index_status['indexable_data'][ $post_type ] = $index_status['indexed_data'][ $post_type ];
		}
	}
	$index_status['currently_indexing'] = $post_type;
	$index_status['first_time_index']   = false;
	update_option( 'expertrec_index_status', wp_json_encode( $index_status ) );
	return wp_send_json( $index_status );
}

function expertrec_endindex() {
	$json = file_get_contents( 'php://input' );
	$data            = json_decode( $json, true );
	$batch_id        = $data['batch_id'];
	$client          = new ExpClient();
	$end_sync_status = $client->end_sync( $batch_id );
	if ( ! $end_sync_status ) {
		do_action( 'er/indexing', 'End index call for batch id ' . $batch_id . ' failed' );
		do_action( 'er/general', 'End index call for batch id ' . $batch_id . ' failed' );
	}
	$index_status                      = get_option( 'expertrec_index_status' );
	$index_status                      = json_decode( $index_status, true );
	$index_status['indexing_complete'] = true;
	update_option( 'expertrec_index_status', wp_json_encode( $index_status ) );
	expertrec_set_last_successful_sync();
	return wp_send_json( $index_status );
}

function expertrec_islinked() {
	$options = get_option( 'expertrec_options' );
	if ( $options ) {
		$account_created = $options['expertrec_account_created'];
		if ( isset( $account_created ) && $account_created ) {
			return wp_send_json( array( 'is_linked' => true ) );
		}
	}
	return wp_send_json( array( 'is_linked' => false ) );
}

function expertrec_siteid_writeapi_key() {
	$options = get_option( 'expertrec_options' );
	if ( $options ) {
		$site_id   = $options['site_id'];
		$write_key = isset($options['write_api_key']) ? $options['write_api_key'] : '';
		return wp_send_json(
			array(
				'site_id'       => $site_id,
				'write_api_key' => $write_key,
			)
		);
	} else {
		return wp_send_json(
			array(
				'site_id'       => '',
				'write_api_key' => '',
			)
		);
	}
}

function expertrec_generate_category_data() {
	$log = ExpLogger::loging();
	$log->indexing( 'expertrec_continueindex', 'start to collect category data ' );

	$categories    = get_categories(
		array(
			'taxonomy'   => 'product_cat', // Taxonomy name for product categories
			'hide_empty' => false,       // Show even empty categories
		)
	);
	$category_data = array();

	foreach ( $categories as $category ) {
		$category_link = get_category_link( $category->term_id );

		$category_info  = get_term( $category->term_id, 'product_cat' );
		$category_count = isset( $category_info->count ) ? $category_info->count : 0;

		$image_url = get_term_meta( $category->term_id, 'thumbnail_id', true );
		$image_url = wp_get_attachment_url( $image_url );

		$category_item = array(
			'id'          => 'category' . $category->term_id,
			'title'       => htmlspecialchars_decode($category->name, ENT_QUOTES),
			'description' => htmlspecialchars_decode($category->description, ENT_QUOTES),
			'post_type'   => 'collection', // Replace with the desired type
			'url'         => $category_link,
			'count'       => $category_count,
			'image'       => $image_url,
		);

		$category_data[] = $category_item;
	}
	do_action( 'er/debug', 'collected category data ' . count( $category_data ) );
	return $category_data;
}

function expertrec_get_woo_category_count() {
	$category_count = wp_count_terms( 'product_cat' );
	if ( ! is_wp_error( $category_count ) ) {
		return $category_count;
	} else {
		return 0;
	}
}

function expertrec_index_category_data( $batch_id ) {
	$client = new ExpClient();
	do_action( 'er/debug', 'batch id - ' . $batch_id );
	$ret = $client->index_category( $batch_id, expertrec_generate_category_data() );
	do_action( 'er/debug', 'returning size as ' . $ret);
	return $ret;
}

function expertrec_is_woocommerce() {

		return wp_send_json( array( 'is_woo_active' => expertrec_is_woocommerce_internal() ) );
}

function expertrec_is_woocommerce_internal() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	$is_woocommerce = is_plugin_active( 'woocommerce/woocommerce.php' );
	return $is_woocommerce;
}

function expertrec_hook_existing_box_status() {
	$options = get_option( 'expertrec_options' );
	if ( $options ) {
		$hook = $options['hook_on_existing_input_box'];
		return wp_send_json(
			array(
				'hook'          => $hook,
				'options_found' => true,
			)
		);
	}
	return wp_send_json(
		array(
			'hook'          => false,
			'options_found' => false,
		)
	);
}

function expertrec_get_options() {
	$options = get_option( EXPERTREC_DB_OPTIONS_KEY );
	return wp_send_json( $options );
}

function expertrec_compare_post_created_and_modified_date( $post ) {
	$prodcut_date_created  = $post->post_date;
	$prodcut_date_modified = $post->post_modified;

	if ( $prodcut_date_created === $prodcut_date_modified ) {
		return true;
	} else {
		return false;
	}
}

function generateRequestId( $batchId ) {
	if ( ! empty( $batchId ) ) {
		return $batchId . '-' . uniqid();
	} else {
		return uniqid();
	}
}

function expertrec_get_cpanel_config() {
	do_action( 'er/debug', 'get cpanel config function executed' );

	$resp = get_cpanel_config();

	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );

		$phpObject = json_decode( $response );

		update_option( EXPERTREC_DB_OPTIONS_SELECTED_DOC_TYPES, $phpObject->doc_type );
		update_option( EXPERTREC_DB_OPTIONS_INDEX_VARIANTS, $phpObject->index_variants );

		return wp_send_json( $phpObject );
	} else {
		$response = null;
		return wp_send_json( $response );
	}
}


function expertrec_update_doc_type_CP() {
	do_action( 'er/debug', 'expertrec update doc type in Control Panel Backend called' );
	$json = file_get_contents( 'php://input' );

	$selectedDocTypes = json_decode( $json );

	update_option( EXPERTREC_DB_OPTIONS_SELECTED_DOC_TYPES, $selectedDocTypes );

	$payload = array(
		'doc_type' => $selectedDocTypes,
	);

	$resp = update_cpanel_config( $payload );

	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}

	return wp_send_json( $response );
}


function expertrec_update_speed_CP() {
	do_action( 'er/debug', 'expertrec update speed in Control Panel Backend called' );
	$speed = file_get_contents( 'php://input' );

	$str = str_replace( '"', '', $speed ); // Remove double quotes
	$num = intval( $str, 10 );

	$payload = array(
		'speed' => $num,
	);

	$resp = update_cpanel_config( $payload );

	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}

	return wp_send_json( $response );
}

function expertrec_set_last_successful_sync() {
	$current_epoch = time();

	update_option( EXPERTREC_DB_OPTIONS_LAST_SUCCESSFUL_SYNC, $current_epoch );
}


function expertrec_get_last_successful_sync() {
	do_action( 'er/debug', 'expertrec get last successful sync called' );

	$last_full_index_time = get_option( EXPERTREC_DB_OPTIONS_LAST_SUCCESSFUL_SYNC );

	$subsequent_index_time = get_option( EXPERTREC_DB_OPTIONS_SUBSEQUENT_UPDATES );

	$last_successful_sync_array = array(
		'last_successful_sync' => $last_full_index_time,
		'subsequent_updates'   => $subsequent_index_time,
	);

	return wp_send_json( $last_successful_sync_array );
}

function expertrec_internal_set_current_user() {

	$current_user = wp_get_current_user();

	if ( isset( $current_user->data ) && ! empty( $current_user->data ) ) {

		$curr_user_data = $current_user->data;

		$data = isset( $curr_user_data->user_email ) ? $curr_user_data->user_email
		: ( isset( $curr_user_data->display_name ) ? $curr_user_data->display_name
		: ( isset( $curr_user_data->user_login ) ? $curr_user_data->user_login
		: ( isset( $curr_user_data->user_nicename ) ? $curr_user_data->user_nicename
		: get_option( 'admin_email' ) ) ) );

	} else {
		$data = get_option( 'admin_email' );
	}

	return $data;
}

function expertrec_get_sentry() {
	$data = get_option( EXPERTREC_DB_OPTIONS_SENTRY_ENABLED );

	return wp_send_json( $data );
}

function expertrec_set_sentry() {
	update_option( EXPERTREC_DB_OPTIONS_SENTRY_ENABLED, 'on' );

	return expertrec_get_sentry();
}

function expertrec_reset_sentry() {
	update_option( EXPERTREC_DB_OPTIONS_SENTRY_ENABLED, 'off' );

	return expertrec_get_sentry();
}

function expertrec_get_index_variants() {
	$data = get_option( 'EXPERTREC_DB_OPTIONS_INDEX_VARIANTS' );

	return wp_send_json( $data );
}

function expertrec_set_index_variants() {
	update_option( 'EXPERTREC_DB_OPTIONS_INDEX_VARIANTS', true );

	$payload = array(
		'index_variants' => true,
	);
	$resp    = update_cpanel_config( $payload );
	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}

	return wp_send_json( $response );
}

function expertrec_reset_index_variants() {
	update_option( 'EXPERTREC_DB_OPTIONS_INDEX_VARIANTS', false );

	$payload = array(
		'index_variants' => false,
	);
	$resp    = update_cpanel_config( $payload );
	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}

	return wp_send_json( $response );
}

function expertrec_set_user_contact_details() {
	$contact_details = file_get_contents( 'php://input' );

	$contact_details = json_decode( $contact_details );

	$communication_email = $contact_details[0];
	$communication_phone = $contact_details[1];

	update_option( 'EXPERTREC_DB_OPTIONS_COMMUNICATION_EMAIL', $communication_email );
	update_option( 'EXPERTREC_DB_OPTIONS_COMMUNICATION_PHONE', $communication_phone );

	return wp_send_json( $contact_details );
}

function check_for_admin() {

	return true;
}

function expertrec_get_init() {
	do_action( 'er/general', 'expertrec get init option called' );

	return wp_send_json( get_option('EXPERTREC_DB_OPTIONS_INIT') );
}

function expertrec_set_init() {
	do_action( 'er/general', 'expertrec init on options called' );

	update_option( 'EXPERTREC_DB_OPTIONS_INIT', true );
	return expertrec_get_init();
}

function expertrec_reset_init() {
	do_action( 'er/general', 'expertrec init off options called' );

	update_option( 'EXPERTREC_DB_OPTIONS_INIT', false );
	return expertrec_get_init();
}

function expertrec_get_category_flag_cp() {
	$resp = get_category_flag_cp();

	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
		$phpObject = json_decode( $response );
		return wp_send_json( $phpObject );
	} else {
		$response = null;
		return wp_send_json( $response );
	}
}

function expertrec_search_bar_shortcode_fn() {

	$exp_eng = get_option( 'expertrec_engine' );

	$search_bar_to_render = '';

	if ( 'db' === $exp_eng ) {
		$search_bar_to_render = expertrec_db_way_search_bar_shortcode();
	} else {
		$search_bar_to_render = expertrec_crawl_way_search_bar_shortcode();
	}

	return $search_bar_to_render;
}

function expertrec_send_otp($body) {
	$user_email = isset( $body['user_email'] ) ? sanitize_text_field( $body['user_email'] ) : '';
	$payload    = array(
		'email' => $user_email,
	);
	$resp       = expertrec_send_otp_cp( $payload );
	do_action( 'er/debug', 'resp is' . print_r($resp, true));
	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}

	return wp_send_json( $response );
}

function expertrec_resend_otp($body) {
	$user_email = isset( $body['user_email'] ) ? sanitize_text_field( $body['user_email'] ) : '';
	$payload    = array(
		'email' => $user_email,
	);
	$resp       = expertrec_resend_otp_cp( $payload );
	do_action( 'er/debug', 'resp is' . print_r($resp, true));
	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}

	return wp_send_json( $response );
}

function expertrec_sync_layout__config_from_db() {
	$expertrec_options = get_option( 'expertrec_options' );

	$site_id          = $expertrec_options['site_id'];
	$expertrec_engine = get_option( 'expertrec_engine' );
	$migrated         = false;

	$resp = get_expertrec_conf( $site_id, $expertrec_engine, $migrated );

	$response = json_decode( $resp, true );

	$expertrec_options['query_parameter'] = $response['query_parameter'];
	$expertrec_options['search_path']     = $response['search_path'];
	$expertrec_options['template']        = $response['template'];

	update_option( 'expertrec_options', $expertrec_options );

	do_action( 'er/debug', 'expertrec_options after update from CP_BE : ' . print_r( $expertrec_options, true ) );
}

function expertrec_get_layout() {

	do_action( 'er/debug', 'expertrec_get_layout function called' );

	expertrec_sync_layout__config_from_db();

	$search_page_info = expertrec_get_search_page_data();

	return wp_send_json( $search_page_info );
}

function expertrec_get_search_page_data() {

	do_action( 'er/debug', 'expertrec_get_search_page_data fucntion called' );

	$expertrec_options = get_option( 'expertrec_options' );

	$post_id = $expertrec_options['expertrec_search_page_id'];

	$get_page_details = get_post( $post_id );
	if ( $get_page_details ) {
		$search_page_title = $get_page_details->post_title;
	} else {
		$search_page_title = '';
	}

	$search_page_info = array(
		'template'                  => $expertrec_options['template'],
		'search_path'               => $expertrec_options['search_path'],
		'query_parameter'           => $expertrec_options['query_parameter'],
		'expertrec_search_page_url' => $expertrec_options['expertrec_search_page_url'],
		'search_page_title'         => $search_page_title,
	);

	do_action( 'er/debug', 'search page info', print_r( $search_page_info, true ) );

	return $search_page_info;
}

function expertrec_verify_otp($body) {
	$user_email       = isset( $body['user_email'] ) ? sanitize_text_field( $body['user_email'] ) : '';
	$user_otp         = isset($body['otp']) ? sanitize_text_field( $body['otp'] ) : '';
	$platform         = isset($body['platform']) ? sanitize_text_field( $body['platform'] ) : '';
	$site_url         = isset($body['site_url']) ? sanitize_text_field( $body['site_url'] ) : '';
	$expertrec_engine = isset($body['expertrec_engine']) ? sanitize_text_field( $body['expertrec_engine'] ) : '';

	$otpNumber               = intval($user_otp);
	$payload                 = array(
		'email'    => $user_email,
		'otp'      => $otpNumber,
		'platform' => $platform,
	);
	$ret                     = expertrec_get_site_info_internal();
	$ret['site_url']         = $site_url;
	$ret['expertrec_engine'] = $expertrec_engine;
	$payload['wp_data']      = $ret;
	do_action( 'er/debug', 'verify response' . print_r($payload, true));
	$resp = expertrec_verify_otp_cp( $payload );
	do_action( 'er/debug', 'resp is' . print_r($resp, true));
	if ( isset( $resp ) && ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) == '200' ) {
		$response = wp_remote_retrieve_body( $resp );
	} else {
		$response = false;
	}

	return wp_send_json( $response );
}

function expertrec_create_search_page($create_search_page_data) {

	$post_content = array(
		'post_title'   => $create_search_page_data['search_page_title'],
		'post_type'    => 'page',
		'post_name'    => $create_search_page_data['search_path'],
		'post_status'  => 'publish',
		'post_content' => '<!-- wp:html -->
				<div class="ci-search-results alignfull"></div>
				<!-- /wp:html -->',
	);

	$page_id = wp_insert_post( $post_content, false, false );

	return $page_id;
}

function expertrec_update_search_page_options($expertrec_options, $page_id) {
	$search_page               = get_permalink( $page_id );
	$search_page_relative_path = wp_make_link_relative( $search_page );
	$expertrec_options['expertrec_search_page_id']  = $page_id;
	$expertrec_options['expertrec_search_page_url'] = $search_page;
	$expertrec_options['search_path']               = $search_page_relative_path;
	update_option( 'expertrec_options', $expertrec_options );

	return $search_page_relative_path;
}

function expertrec_create_or_update_search_page_config() {
	$json = file_get_contents( 'php://input' );
	$create_search_page_data = (array) json_decode( $json );

	if ( 'overlay' === $create_search_page_data['template'] ) {
		$search_page_info_after_updating = expertrec_layout_submit( $create_search_page_data );
		return wp_send_json( $search_page_info_after_updating );
	}

	$expertrec_options = get_option( 'expertrec_options' );
	$post_id           = $expertrec_options['expertrec_search_page_id'];

	$get_page_details = get_post( $post_id );

	if ( $get_page_details ) {
		$search_page_path  = $get_page_details->post_name;
		$search_page_title = $get_page_details->post_title;
	}

	if ( ! $get_page_details || $create_search_page_data['search_path'] !== $search_page_path ) {

		$page_id = expertrec_create_search_page( $create_search_page_data );

		if ( is_wp_error( $page_id ) ) {
			return wp_send_json( array( 'error' => 'Error in creating a new page' ) );
		}

		$new_search_path = expertrec_update_search_page_options( $expertrec_options, $page_id );

		$create_search_page_data['search_path'] = $new_search_path;

	} else {


		if ( $create_search_page_data['search_page_title'] !== $search_page_title ) {
			$post_content = array(
				'ID'         => $post_id,
				'post_title' => $create_search_page_data['search_page_title'],
			);
			wp_update_post( $post_content );
		}

		$create_search_page_data['search_path'] = '/' . $create_search_page_data['search_path'] . '/';

		$query_parameter = $expertrec_options['query_parameter'];
		$current_layout  = $expertrec_options['template'];

		if ($query_parameter === $create_search_page_data['query_parameter'] && $current_layout === $create_search_page_data['template']) {
			return wp_send_json( $create_search_page_data );
		}
	}

	$search_page_info_after_updating = expertrec_layout_submit( $create_search_page_data );

	return wp_send_json( $search_page_info_after_updating );
}


function expertrec_woo_add_to_cart() {

	$json = file_get_contents( 'php://input' );

	do_action('er/debug', 'expertrec_woo_add_to_cart json payload: ' . $json);

	$payload_data = (array) json_decode( $json );

	$payload_data = $payload_data['items'];

	$product_id = $payload_data[0]->id;
	$quantity   = $payload_data[0]->quantity;

	do_action('er/debug', 'product_id: ' . $product_id . ' quantity: ' . $quantity);

	$product_data = wc_get_product($product_id);

	if ( ! $product_data instanceof \WC_Product) {
		wp_send_json_error(array( 'status' => 'invalid_product' ), 400);
	}

	$variation_id = 0;

	if ($product_data instanceof \WC_Product_Variable) {
		foreach ($product_data->get_available_variations() as $v) {
			if ($v['is_in_stock']) {
				$variation_id = $v['variation_id'];
				break;
			}
		}
	}

	$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);

	if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id)) {
		WC_AJAX::get_refreshed_fragments();
		wp_send_json( array( 'status' => 'success' ) );
	} else {
		wp_send_json_error( array( 'status' => 'error' ), 400 );
	}
}

function register_expertrec_rest_get_api() {

	register_rest_route(
		'expertrec/v1',
		'/get_debug',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_debug',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/get_sentry',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_sentry',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/set_sentry',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_set_sentry',
			'permission_callback' => 'check_for_admin',
		)
	);
	register_rest_route(
		'expertrec/v1',
		'/set_debug',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_set_debug',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/reset_debug',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_reset_debug',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/reset_sentry',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_reset_sentry',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/get_init',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_init',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/set_init',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_set_init',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/reset_init',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_reset_init',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/get_index_status',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_index_status',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/get_cpanel_config',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_cpanel_config',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/startindex',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_startindex',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/get_last_successfull_sync',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_last_successful_sync',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_get_metakeys_to_index',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_meta_keys_to_index',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_get_product_attribute_to_index',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_product_attribute_to_index',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/get_layout',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_layout',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_hook_existing_boxstatus',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_hook_existing_box_status',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_get_indexvariants',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_get_index_variants',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_set_indexvariants',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_set_index_variants',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_reset_indexvariants',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_reset_index_variants',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_siteid_writeapikey',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_siteid_writeapi_key',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/get_expertrec_engine',
		array(
			'methods'             => 'GET',
			'callback'            => 'get_expertrec_engine',
			'permission_callback' => 'check_for_admin',
		)
	);
	register_rest_route(
		'expertrec/v1',
		'/expertrec_is_woocommerce',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_is_woocommerce',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_is_expired',
		array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => 'expertrec_is_expired',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_islinked',
		array(
			'methods'             => 'GET',
			'callback'            => 'expertrec_islinked',
			'permission_callback' => 'check_for_admin',
		)
	);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_update_options',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => 'expertrec_update_options',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_notify_deactivation',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => 'expertrec_notify_deactivation',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_update_setting',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => 'update_expertrec_settings',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_get_indexstats',
			array(
				'methods'             => 'GET',
				'callback'            => 'expertrec_get_index_stats',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_isaccount_created',
			array(
				'methods'             => 'GET',
				'callback'            => 'expertrec_is_account_created',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_get_lastsync',
			array(
				'methods'             => 'GET',
				'callback'            => 'expertrec_get_last_sync',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_index_categorydata',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => 'expertrec_index_category_data',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_get_options',
			array(
				'methods'             => 'GET',
				'callback'            => 'expertrec_get_options',
				'permission_callback' => 'check_for_admin',
			)
		);

		register_rest_route(
			'expertrec/v1',
			'/expertrec_get_category_flag',
			array(
				'methods'             => 'GET',
				'callback'            => 'expertrec_get_category_flag_cp',
				'permission_callback' => 'check_for_admin',
			)
		);
}

function register_expertrec_rest_post_api() {
	register_rest_route(
		'expertrec/v1',
		'/site_info',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_get_site_info',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/endindex',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_endindex',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/continueindex',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_continueindex',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/update_speed_cp',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_update_speed_CP',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_update_doctype_CP',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_update_doc_type_CP',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_update_metakeys_to_index',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_update_meta_index',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_update_attribute_index',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_update_attribute_index',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_set_user_contactdetail',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_set_user_contact_details',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_signup_clicked',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_signup_clicked',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/crawl_status',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_crawl',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/recrawl',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_crawl',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/stopcrawl',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_crawl',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_login_response',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_login',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_update_config',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_update_config',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_layout_submit',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_layout_submit',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/update_layout',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_create_or_update_search_page_config',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_send_otp',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_send_otp',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_resend_otp',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_resend_otp',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/expertrec_verify_otp',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_verify_otp',
			'permission_callback' => 'check_for_admin',
		)
	);

	register_rest_route(
		'expertrec/v1',
		'/woo_add_to_cart',
		array(
			'methods'             => 'POST',
			'callback'            => 'expertrec_woo_add_to_cart',
			'permission_callback' => '__return_true',
		)
	);
}
