<?php

require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-logger.php';


class Expertrecsearch_Deactivator {


	public static function deactivate() {
		$log = ExpLogger::loging();
		$log->general( 'deactivate', 'Plugin deactivated' );
		do_action( 'er/debug', 'Plugin Deactivated' );
	}
}
