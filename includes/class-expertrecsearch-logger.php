<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class ExpLogger {


	private static $log                    = null;
	private $version                       = null;
	private $upload_dir_info               = null;
	private $log_file_dir_container        = null;
	private $log_files_dir                 = null;
	private $indexing_log_file             = null;
	private $general_log_file              = null;
	private $log_file_dir_inside_plugin    = null;
	private $expertrec_indexing_file_log   = null;
	private $expertrec_general_file_log    = null;
	private $site_id                       = null;
	private $site_url                      = null;
	private $backend_loging                = null;
	private $subsequent_log_file           = null;
	private $expertrec_subsequent_file_log = null;
	private function __construct() {
		do_action( 'er/debug', 'In logger construct' );
		if ( defined( 'EXPERTREC_VERSION' ) ) {
			$this->version = EXPERTREC_VERSION;
		} else {
			$this->version = '4.0.0';
		}
		$this->set_error_log_location();
		$this->indexing_log_file   = $this->log_files_dir . 'expertrec_indexing.gz';
		$this->general_log_file    = $this->log_files_dir . 'expertrec_gen.gz';
		$this->subsequent_log_file = $this->log_files_dir . 'expertrec_subsequent_update.gz';
		if ( is_writable( $this->log_files_dir ) ) {
			$this->expertrec_indexing_file_log   = fopen( $this->indexing_log_file, 'a+' );
			$this->expertrec_general_file_log    = fopen( $this->general_log_file, 'a+' );
			$this->expertrec_subsequent_file_log = fopen($this->subsequent_log_file, 'a+');
			$this->backend_loging                = false;
		} else {
			$this->backend_loging = true;
		}
		if ( is_null( $this->site_id ) || is_null( $this->site_url ) ) {
			$expertrec_options = get_option( 'expertrec_options' );
			if ( isset( $expertrec_options ) && is_array( $expertrec_options ) ) {
				$this->site_id = get_option( 'expertrec_options' )['site_id'];
			}
			$this->site_url = get_site_url();
		}
		add_action( 'er/indexing', array( $this, 'er_log_indexing' ), 10, 1 );
		add_action( 'er/general', array( $this, 'er_log_general' ), 10, 1 );
		add_action( 'er/subsequent_update', array( $this, 'er_log_subsequent_update' ), 10, 1 );
		if ( get_option( 'expertrec_debug' ) ) {
			add_action( 'er/debug', $this->er_log_indexing_class( 'debug' ), 10, 1 );

		}

		if ( get_option('EXPERTREC_DB_OPTIONS_INIT', false)) {
			add_action( 'er/init', $this->er_log_indexing_class( 'init' ), 10, 1 );
		}
	}

	private function set_error_log_location() {
		do_action( 'er/debug', 'Setting error log location' );
		$this->upload_dir_info        = wp_upload_dir();
		$this->log_file_dir_container = $this->upload_dir_info['basedir'] . '/expertrec_search/';
		$this->log_files_dir          = $this->log_file_dir_container . 'logs/';
		$this->_createAndSetErrorLogPermissions();
	}

	private function _createAndSetErrorLogPermissions() {
		do_action( 'er/debug', 'In create and set error log permission' );
		if ( ! file_exists( $this->log_files_dir ) ) {
			wp_mkdir_p( $this->log_files_dir );
		}
	}

	public static function loging() {
		if ( null == self::$log ) {
			self::$log = new ExpLogger();
		}
		return self::$log;
	}

	public function er_log_indexing_class( $class ) {
		return function ( $message ) use ( $class ) {
			$this->er_log_indexing($message, $class);
		};
	}
	public function er_log_indexing( $message, $class = '' ) {
		$function_calls = $this->getFunction_calls();

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';

		self::$log->indexing( $request_uri . ' [' . $class . '] | ' . $function_calls, $message );
	}

	public function er_log_general( $message ) {
		$function_calls = $this->getFunction_calls();

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';

		self::$log->general( $request_uri . ' | ' . $function_calls, $message );
	}

	public function er_log_subsequent_update( $message, $class = '' ) {
		$function_calls = $this->getFunction_calls();

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';

		self::$log->subsequent_log( $request_uri . ' [' . $class . '] | ' . $function_calls, $message );
	}

	public function __destruct() {
		do_action( 'er/init', 'In logger destruct' );
		if ( $this->expertrec_indexing_file_log ) {
			fclose( $this->expertrec_indexing_file_log );
		}
		if ( $this->expertrec_general_file_log ) {
			fclose( $this->expertrec_general_file_log );
		}
		if ($this->expertrec_subsequent_file_log) {
			fclose($this->expertrec_subsequent_file_log);
		}
	}

	public function indexing( $fun_name, $log_msg ) {
		$this->logit( $fun_name, $log_msg, $this->expertrec_indexing_file_log, 'Indexing' );
	}

	public function logit( $fun_name, $log_msg, $file_writer, $log_type ) {
		$timestamp = $this->get_timestamp();
		$log_msg   = print_r( $timestamp, true ) . ' | ' . print_r( $fun_name, true ) . ' | ' . print_r( $log_msg, true );
		if ( $file_writer ) {
			$ret = fwrite( $file_writer, $log_msg . "\n" );
			if ( ! $ret ) {
				echo 'Error writing to log file';
				print_r( $file_writer );
				echo esc_html( $log_msg );
				exit;
			}
		}
	}

	private function get_timestamp() {
		$date = new DateTime( 'now', new DateTimeZone( 'Asia/Kolkata' ) );
		$date = $date->format( 'M d, Y - H:i:s' );
		return $date;
	}

	public function getFunction_calls() /*: string */ {
		$trace          = debug_backtrace();
		$function_calls = '';
		for ( $i = 5; $i < min( 9, count( $trace ) ); $i++ ) {
			$caller         = $trace[ $i ];
			$function       = ( isset( $caller['class'] ) ? $caller['class'] : '' ) . ( isset( $caller['type'] ) ? $caller['type'] : '' ) . ( isset( $caller['function'] ) ? $caller['function'] : '' ) . ( isset( $caller['file'] ) ? $caller['file'] : '' ) . ':' . ( isset( $caller['line'] ) ? $caller['line'] : '' );
			$function_calls = $function . '>' . $function_calls;
		}

		return $function_calls;
	}

	private function logit_cp_backend( $log_msg, $log_type ) {
		do_action( 'er/debug', 'In logit cp backend' );
		if ( ! $this->backend_loging ) {
			do_action( 'er/debug', 'Backend logging not needed' );
			return;
		}
		$body = $this->site_id . ' : ' . $this->site_url . ' : ' . $log_type . ' : ' . $log_msg;
		$url = 'https://wordpress.expertrec.com/log';
		try {
			$timeout = 1;
			$resp    = call_expertrec_api( $url, 'POST', null, $body, $timeout );
		} catch ( Exception $e ) {
			1 == 1;
		}
	}

	public function general( $fun_name, $log_msg ) {
		$this->logit( $fun_name, $log_msg, $this->expertrec_general_file_log, 'General' );
	}
	public function subsequent_log( $fun_name, $log_msg ) {
		$this->logit( $fun_name, $log_msg, $this->expertrec_subsequent_file_log, 'Subsequent' );
	}
	public function truncate_log_file( $file_name ) {
		do_action( 'er/debug', 'Truncating log files' );
		if ( 'expertrec_indexing' == $file_name ) {
			$file = $this->expertrec_indexing_file_log;
		} elseif ('expertrec_newlog' == $file_name) {
			$file = $this->expertrec_subsequent_file_log;
		} else {
			$file = $this->expertrec_general_file_log;
		}
		if ( $file ) {
			ftruncate( $file, 0 );
			rewind( $file );
		}
	}
}
