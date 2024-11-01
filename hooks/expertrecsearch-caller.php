<?php


if ( ! defined( 'WPINC' ) ) {
	die( "You can't access this file directly" );
}
do_action( 'er/debug', 'expertrecsearch-caller is imported' );

function get_base_url() {
	$base_url = 'https://cseb.expertrec.com/api/';
	do_action( 'er/debug', 'Caller get create ecom id:' . $base_url );

	return $base_url;
}


function update_expertrec_config( $site_id, $update_type, $body ) {
	do_action(
		'er/debug',
		'update expertrec config ' .
		print_r( $update_type, true )
	);
	$base_url = get_base_url();
	$url = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/ECOM/update_conf/' . rawurlencode( $update_type );

	return call_expertrec_api( $url, 'POST', null, $body );
}

function update_expertrec_search_page( $site_id, $body ) {
	do_action( 'er/general', 'update expertrec config - search page' );
	$base_url = get_base_url();
	$url = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/update_conf/update_path';
	do_action( 'er/general', 'making post call to ' . $url );
	return call_expertrec_api( $url, 'POST', null, $body );
}

function index_data( $url, $method, $headers, &$payload ) {
	do_action( 'er/debug', 'Caller index data' );
	$body = null;
	if ( ( 'PUT' == $method || 'POST' == $method ) && null != $payload ) {
		$body = $payload;
	}

	return call_expertrec_api( $url, $method, $headers, $body );
}

function get_create_ecom_id( $options = null ) {
	do_action( 'er/debug', 'Caller get create ecom id' );
	if ( ! $options ) {
		$options = get_option( 'expertrec_options' );
	}
	$current_id = $options['site_id'];
	$base_url   = get_base_url();
	$url        = $base_url . '/7e70731cfb3a6fc453847f952906c82c/wp-generate-ecom-id';
	$current_user             = wp_get_current_user();
	$payload                  = array(
		'site_url' => get_site_url(),
		'cse_id'   => $current_id,
		'name'     => $current_user->display_name,
		'email'    => $current_user->user_email,
	);
	$response                 = call_expertrec_api( $url, 'POST', null, $payload );
	$response                 = wp_remote_retrieve_body( $response );
	$json_data                = json_decode( $response, true );
	$options                  = get_option( 'expertrec_options' );
	$options['ecom_id']       = $json_data['ecom_id'];
	$options['write_api_key'] = $json_data['write_api_key'];
	update_option( 'expertrec_options', $options );

	return $json_data['ecom_id'];
}

function start_crawl() {
	do_action( 'er/debug', 'Crawl started' );
	$base_url = get_base_url();
	$options  = get_option( 'expertrec_options' );
	$site_id  = $options['site_id'];
	$payload  = array( 'request' => 'start_crawl' );
	$url      = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/CSE/wp_start_crawl';

	return call_expertrec_api( $url, 'POST', null, $payload );
}

function get_expertrec_conf( $site_id, $expertrec_engine, $migrated = false ) {
	do_action( 'er/debug', 'Caller get Expertrec conf' );
	$base_url = get_base_url();
	$url      = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/CSE/get_conf?migrated=' . rawurlencode( $migrated ) . '&expertrec_engine='
				. rawurlencode( $expertrec_engine );
	$response = call_expertrec_api( $url, 'GET' );
	if ( $response ) {
		return wp_remote_retrieve_body( $response );
	}

	return $response;
}

function crawl_status() {
	do_action( 'er/debug', 'In Crawler status' );
	$base_url = get_base_url();
	$options  = get_option( 'expertrec_options' );
	$site_id  = $options['site_id'];
	$url      = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/CSE/wp_crawl_status';

	return call_expertrec_api( $url, 'GET' );
}

function stop_crawl() {
	do_action( 'er/debug', 'Crawler stopped' );
	$base_url = get_base_url();
	$options  = get_option( 'expertrec_options' );
	$site_id  = $options['site_id'];
	$payload  = array( 'request' => 'stop_crawl' );
	$url      = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/CSE/wp_stop_crawl';

	return call_expertrec_api( $url, 'POST', null, $payload );
}

function get_days_to_expire() {
	do_action( 'er/debug', 'Caller get days to expire' );
	$base_url = get_base_url();
	$options  = get_option( 'expertrec_options' );
	$site_id  = $options['site_id'];
	$url      = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/days_to_expire';

	return call_expertrec_api( $url, 'GET' );
}

function wp_events( $event_type, $data = array() ) {
	do_action( 'er/general', 'sending wp-event' . $event_type );
	$base_url = get_base_url();
	$options  = get_option( 'expertrec_options' );
	$site_id  = $options['site_id'];
	$url      = $base_url . '/organisation/' . rawurlencode( $site_id ) . '/wp_events/' . rawurlencode( $event_type );

	$site_info = expertrec_site_information();

	$data = array_merge( $site_info, $data );

	return call_expertrec_api( $url, 'POST', null, $data );
}


function expertrec_site_information() {
	$site_info = array();

	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		$is_woocommerce = true;
	} else {
		$is_woocommerce = false;
	}

	if ( defined( 'EXPERTREC_VERSION' ) ) {
		$version = EXPERTREC_VERSION;
	} else {
		$version = '4.0.0';
	}

	$site_info['plugin_version']         = $version;
	$site_info['woocommerce']            = $is_woocommerce;
	$site_info['site_url']               = get_site_url();
	$site_info['admin_email']            = get_option( 'admin_email' );
	$site_info['communication_email']    = get_option( 'EXPERTREC_DB_OPTIONS_COMMUNICATION_EMAIL' );
	$site_info['phone_number']           = get_option( 'EXPERTREC_DB_OPTIONS_COMMUNICATION_PHONE' );
	$site_info['curr_user']              = expertrec_internal_set_current_user();
	$site_info['doc_count']              = getIndexableData()['doc_count'];
	$site_info['REMOTE_ADDR']            = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
	$site_info['HTTP_USER_AGENT']        = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
	$site_info['wp_version']             = expertrec_get_wp_version();
	$site_info['theme_info']             = expertrec_get_theme();
	$site_info['plugin_info']            = expertrec_get_plugin();
	$site_info['php_version']            = expertrec_get_php_version();
	$site_info['actual_site_url']        = get_site_url();
	$site_info['version']                = 'v1';
	$site_info['site_language_bloginfo'] = get_bloginfo( 'language' );
	$site_info['site_language_locale']   = get_locale();
	$site_info['platform_child']         = 'wordpress';

	return $site_info;
}


function expertrec_get_php_version() {
	$php_version = phpversion();
	if (false !== $php_version) {
		return $php_version;
	} else {
		$result = 'not found';
		return $result;
	}
}
function expertrec_get_wp_version() {
	global $wp_version;
	return $wp_version;
}
function expertrec_get_theme() {
	$current_theme = wp_get_theme();

	if ($current_theme->exists()) {
		$theme_name        = $current_theme->get('Name');
		$theme_version     = $current_theme->get('Version');
		$theme_author      = $current_theme->get('Author');
		$theme_description = $current_theme->get('Description');
		$theme_url         = get_stylesheet_directory_uri();
		$author_uri        = $current_theme->get('AuthorURI');
		$text_domain       = $current_theme->get('TextDomain');
		$theme_info = array(
			'name'        => $theme_name,
			'version'     => $theme_version,
			'author'      => $theme_author,
			'description' => $theme_description,
			'url'         => $theme_url,
			'authorurl'   => $author_uri,
			'text_domain' => $text_domain,
		);

		return $theme_info;
	} else {
		return 'not found';
	}
}
function expertrec_get_plugin() {
	$plugins = get_plugins();

	$all_plugins_info = array();

	foreach ($plugins as $plugin_path => $plugin_data) {
		$plugin_info = array(
			'Name'    => $plugin_data['Name'],
			'Version' => $plugin_data['Version'],
		);

		$all_plugins_info[ $plugin_data['Name'] ] = $plugin_info;
	}

	if (empty($all_plugins_info)) {
		$result = 'not found';
	} else {
		$result = $all_plugins_info;
	}

	return $result;
}

function getIndexableData() {
	do_action( 'er/debug', 'Caller get indexable data' );
	$data = array();
	require_once plugin_dir_path( __DIR__ ) . 'includes/class-expertrecsearch-client.php';
	$client   = new ExpClient();
	$docTypes = $client->getPostTypes();
	foreach ( $docTypes as $docType ) {
		$data[ $docType ] = $client->getPostCount( $docType );
	}

	$data['pdf'] = $client->exp_count_pdfs_in_media_library();

	return array( 'doc_count' => $data );
}

function convert_to_json( $data ) {
	do_action( 'er/debug', 'Caller convert to json' );
	if ( defined( 'JSON_INVALID_UTF8_IGNORE' ) ) {
		$encoded_data = json_encode( $data, JSON_INVALID_UTF8_IGNORE );
	} else {
		$encoded_data = json_encode( $data );
	}

	return $encoded_data;
}

function get_expertrec_auth_headers( $currentUser, $secret_key ) {
	$headers = array(
		'Content-Type'           => 'application/json',
		'X-Expertrec-User-Email' => $currentUser,
		'Authorization'          => "Bearer Write-Access-Token:$secret_key",
	);

	return $headers;
}

function get_cpanel_config() {

	$base_url    = get_base_url();
	$options     = get_option( 'expertrec_options' );
	$site_id     = $options['site_id'];
	$secret_key  = isset($options['write_api_key']) ? $options['write_api_key'] : '';
	$currentUser = expertrec_internal_set_current_user();

	$headers = get_expertrec_auth_headers( $currentUser, $secret_key );

	$url = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/ECOM/["cpanel"]["wp_settings"]/partial_conf';

	return call_expertrec_api( $url, 'GET', $headers );
}

function get_category_flag_cp() {

	$base_url    = get_base_url();
	$options     = get_option( 'expertrec_options' );
	$site_id     = $options['site_id'];
	$secret_key  = $options['write_api_key'];
	$currentUser = expertrec_internal_set_current_user();
	$headers     = get_expertrec_auth_headers( $currentUser, $secret_key );
	$url         = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/ECOM/["config"]["categoryConfig"]["isCategoryPagesEnabled"]/partial_conf';
	return call_expertrec_api( $url, 'GET', $headers );
}

function update_cpanel_config( $payload ) {

	$base_url    = get_base_url();
	$options     = get_option( 'expertrec_options' );
	$site_id     = $options['site_id'];
	$secret_key  = $options['write_api_key'];
	$currentUser = expertrec_internal_set_current_user();

	$headers = get_expertrec_auth_headers( $currentUser, $secret_key );

	$url = $base_url . 'organisation/' . rawurlencode( $site_id ) . '/ECOM/["cpanel"]["wp_settings"]/partial_conf';

	return call_expertrec_api( $url, 'POST', $headers, $payload );
}

function expertrec_send_otp_cp($payload) {
	do_action( 'er/debug', 'payload is' . print_r($payload, true));
	$base_url = get_base_url();
	$url      = $base_url . 'accounts/signup';
	return call_expertrec_api( $url, 'POST', null, $payload );
}

function expertrec_resend_otp_cp($payload) {
	do_action( 'er/debug', 'payload is' . print_r($payload, true));
	$base_url = get_base_url();
	$url      = $base_url . 'accounts/resend_otp';
	return call_expertrec_api( $url, 'POST', null, $payload );
}

function expertrec_verify_otp_cp($payload) {
	do_action( 'er/debug', 'payload is' . print_r($payload, true));
	$base_url = get_base_url();
	$url      = $base_url . 'accounts/verify';
	return call_expertrec_api( $url, 'POST', null, $payload );
}

function call_expertrec_api( $url, $method, $headers = null, &$payload = null, $timeout = 10, $prefix = '' ) {
	do_action( 'er/debug', 'call expertrec api ' . $url );
	if ( ! $headers ) {
		$headers = array( 'Content-type' => 'application/json' );
	}
	$version = EXPERTREC_VERSION;

	$headers = array_merge(
		$headers,
		array(
			'User-Agent'   => 'EXP Wordpress Plugin/' . $version,
			'X-Request-Id' => $prefix . uniqid(),
		)
	);
	$args    = array(
		'method'      => $method,
		'headers'     => $headers,
		'timeout'     => $timeout,
		'redirection' => 2,
		'httpversion' => '1.1',
		'blocking'    => true,
	);
	if ( null != $payload ) {
		$encoded_payload = convert_to_json( $payload );
		do_action( 'er/debug', 'call expertrec api - payload ' . $encoded_payload );
		$args['body'] = $encoded_payload;

	}
	$response = wp_remote_get( $url, $args );
		do_action( 'er/debug', 'body ' . print_r( wp_remote_retrieve_body( $response ), true ) );
		do_action( 'er/debug', 'code ' . wp_remote_retrieve_response_code( $response ) );

	return $response;
}
