<?php


class Expertrecsearch_I18n {



	public function load_plugin_textdomain() {
		do_action( 'er/init', 'In load plugin textdomain' );
		load_plugin_textdomain(
			'expertrecsearch',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
