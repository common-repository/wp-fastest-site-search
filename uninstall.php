<?php


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die( "You can't access this file directly" );
}

if ( ! defined( 'WPINC' ) ) {
	die( "You can't access this file directly" );
}

require_once __DIR__ . '/includes/class-expertrecsearch-logger.php';
require_once __DIR__ . '/hooks/expertrecsearch-caller.php';
$log = ExpLogger::loging();
$log->general( 'uninstall', 'Plugin start of uninstall' );
do_action( 'er/general', 'Plugin start uninstall' );

$expertrec_options = get_option( 'expertrec_options' );
if ( isset( $expertrec_options ) && is_array( $expertrec_options ) ) {
	$safe_id_in_url = rawurlencode( array_key_exists( 'site_id', $expertrec_options ) ? $expertrec_options['site_id'] : 'NA' );
	$ecom_id        = rawurlencode( array_key_exists( 'ecom_id', $expertrec_options ) ? $expertrec_options['ecom_id'] : 'NA' );
	$cse_id         = rawurlencode( array_key_exists( 'cse_id', $expertrec_options ) ? $expertrec_options['cse_id'] : 'NA' );

	if ( array_key_exists( 'expertrec_search_page_id', $expertrec_options ) ) {
		$expertrec_search_page_id = $expertrec_options['expertrec_search_page_id'];
		do_action( 'er/general', 'page id found, deleting ' . $expertrec_search_page_id );
	} else {
		$page_path = '/expertrec-search/';
		if ( array_key_exists( 'search_path', $expertrec_options ) ) {
			$page_path = $expertrec_options['search_path'];
			do_action( 'er/general', 'page search_path found, in db ' . $page_path );
		}
		$expertrec_search_page = get_page_by_path( $page_path, OBJECT );
		if ( isset( $expertrec_search_page ) ) {
			do_action( 'er/general', 'page path found valid' . $page_path );
			$expertrec_search_page_id = $expertrec_search_page->ID;
		}
	}
	if ( isset( $expertrec_search_page_id ) ) {
		$success = wp_delete_post( $expertrec_search_page_id, true );
		if ( ! $success ) {
			do_action( 'er/general', 'deleted search page - failed ' );
		} else {
			do_action( 'er/general', 'deleted search page - success ' );
		}
	}
} else {
	$safe_id_in_url = 'NA';
	$ecom_id        = 'NA';
	$cse_id         = 'NA';
}

delete_option( 'expertrec_engine' );
delete_option( 'expertrec_index_status' );
delete_option( 'expertrec_indexing_status' );
delete_option( 'expertrec_options' );
delete_option( 'expertrec_options_RO' );
delete_option( 'expertrec_stop_indexing' );

do_action( 'er/general', 'Deleted db keys' );

$url  = 'https://cseb.expertrec.com/api/plugin_uninstalled/' . $safe_id_in_url . '?platform=Wordpress&ecom_id=' .
	$ecom_id . '&cse_id=' . $cse_id;
$args = array(
	'method'      => 'GET',
	'headers'     => array(
		'Content-type' => 'application/json',
		'X-Request-Id' => uniqid(),
	),
	'timeout'     => 10,
	'redirection' => 1,
	'httpversion' => '1.1',
	'blocking'    => false,
);
$response = wp_remote_get( $url, $args );
do_action( 'er/general', 'notified backend about uninstall uninstall' );
