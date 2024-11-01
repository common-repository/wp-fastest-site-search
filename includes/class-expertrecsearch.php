<?php



require_once EXPERTREC_PLUGIN_DIR_PATH . 'hooks/expertrecsearch-caller.php';
require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-logger.php';

class Expertrecsearch {


	public $main_file = null;
	protected $loader;
	protected $plugin_name;
	protected $version;
	protected $templates;
	private $log = null;
	private $demo_site_id;

	public function __construct( $main_file ) {
		do_action( 'er/debug', 'Plugin constructed' );

		$this->main_file   = $main_file;
		$this->version     = EXPERTREC_VERSION;
		$this->plugin_name = EXPERTREC_NAME;
		$this->log         = ExpLogger::loging();
		do_action( 'er/init', 'logger constructed' );

		register_activation_hook( $this->main_file, array( $this, 'activate_expertrecsearch' ) );
		register_deactivation_hook( $this->main_file, array( $this, 'deactivate_expertrecsearch' ) );

		add_action( 'admin_menu', array( $this, 'load_expertrec_menus' ) );
		add_action( 'admin_init', array( $this, 'expertrec_plugin_redirect' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'expertrec_ajax_load_scripts' ) );

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->demo_site_id = '58c9e0e4-78e5-11ea-baf0-0242ac130002';
	}

	private function load_dependencies() {
		do_action( 'er/init', 'Expertrec dependencies' );
		require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-loader.php';

		require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-i18n.php';

		require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-admin.php';

		require_once EXPERTREC_PLUGIN_DIR_PATH . 'public/class-expertrecsearch-public.php';

		$this->loader = new Expertrecsearch_Loader();
	}

	private function set_locale() {
		do_action( 'er/init', 'Set locale called' );
		$plugin_i18n = new Expertrecsearch_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
		do_action( 'er/init', 'Setting up all the admin ajax hooks' );

		add_action('rest_api_init', 'register_expertrec_rest_get_api');
		add_action('rest_api_init', 'register_expertrec_rest_post_api');

		add_shortcode('expertrec_search_bar', 'expertrec_search_bar_shortcode_fn');
		add_action( 'init', array( $this, 'checkAndUpgrade' ) );
	}

	private function define_admin_hooks() {
		do_action( 'er/init', 'Defining Admin hooks ' );
		$plugin_admin = new Expertrecsearch_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

		$exp_eng = get_option( 'expertrec_engine' );
		$site_id = get_option( 'expertrec_options' )['site_id'];
		if ( 'db' == $exp_eng && $site_id != $this->demo_site_id ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && ! is_admin() ) {
				$this->loader->add_action( 'woocommerce_product_set_stock_status', $plugin_admin, 'expertrec_stock_status_change', 10, 3 );
				do_action( 'er/init', 'woocommerce plugin detected' );
			}
			$current_url = home_url( add_query_arg( null, null ) );
			if ( strpos($current_url, 'pmxi-admin') == false ) {
				$this->loader->add_action( 'wp_after_insert_post', $plugin_admin, 'expertrec_wp_after_insert_post', 10, 3 );
				$this->loader->add_action( 'woocommerce_update_product', $plugin_admin, 'expertrec_woocommerce_update_product', 99, 2 );
				$this->loader->add_action( 'transition_post_status', $plugin_admin, 'expertrec_transition_post_status', 99, 3 );
			}
			$this->loader->add_action( 'woocommerce_rest_insert_product_object', $plugin_admin, 'expertrec_woocommerce_rest_insert_product_object', 10, 3 );
			$this->loader->add_action( 'woocommerce_rest_delete_product_object', $plugin_admin, 'expertrec_woocommerce_rest_delete_product_object', 10, 3 );
			$this->loader->add_action( 'future_to_publish', $plugin_admin, 'expertrec_future_to_publish' );
			$this->loader->add_action( 'pmxi_saved_post', $plugin_admin, 'expertrec_pmxi_saved_post', 10, 3 );
			$this->loader->add_action( 'trashed_post', $plugin_admin, 'expertrec_trashed_post' );
		}
	}

	public function get_plugin_name() {
		return $this->plugin_name;
		do_action( 'er/debug', 'Plugin name :' . $this->plugin_name );
	}

	public function get_version() {
		return $this->version;
		do_action( 'er/debug', 'Plugin version: ' . $this->version );
	}

	public function __destruct() {
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
				'Version upgrade needed in expertrecsearch flow.  Current version: ' .
									$current_version . ' New version: ' . EXPERTREC_VERSION
			);

			$data = get_option( EXPERTREC_DB_OPTIONS_SENTRY_ENABLED );
			if ( false === $data ) {
				update_option( EXPERTREC_DB_OPTIONS_SENTRY_ENABLED, 'on' );
			}

			$index_variants = get_option( 'EXPERTREC_DB_OPTIONS_INDEX_VARIANTS' );
			if ( false === $index_variants ) {
				update_option( 'EXPERTREC_DB_OPTIONS_INDEX_VARIANTS', false );
			}

			$init_log = get_option( 'EXPERTREC_DB_OPTIONS_INIT' );
			if ( false === $init_log ) {
				update_option( 'EXPERTREC_DB_OPTIONS_INIT', false );
			}

			return true;
		}
		return false;
	}

	public function activate_expertrecsearch() {
		require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-activator.php';
		Expertrecsearch_Activator::activate();
		do_action( 'er/debug', 'Search activated' );
	}

	public function upgrade() {
		require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-activator.php';
		Expertrecsearch_Activator::versionUpgrade();
		do_action( 'er/debug', 'version upgraded' );
	}

	public function deactivate_expertrecsearch() {
		require_once EXPERTREC_PLUGIN_DIR_PATH . 'includes/class-expertrecsearch-deactivator.php';
		Expertrecsearch_Deactivator::deactivate();
		do_action( 'er/debug', 'Search deactivated' );
	}

	public function expertrec_plugin_redirect() {
		do_action( 'er/init', 'ExpertRec redirect check' );

		if ( get_option( 'expertrec_plugin_do_activation_redirect', false ) ) {
			do_action( 'er/debug', 'ExpertRec needs redirection' );

			delete_option( 'expertrec_plugin_do_activation_redirect' );
			do_action( 'er/debug', 'future redirection cleared' );

			wp_safe_redirect( 'admin.php?page=Expertrec' );
			do_action( 'er/debug', 'redirection requested' );

			wp_events( 'plugin_activated' );
			do_action( 'er/debug', 'activation event sent' );
			do_action( 'er/debug', 'dying' );
			exit;
		} else {
			do_action( 'er/init', 'No Redirection' );
			1 == 1;
		}
	}

	public function load_expertrec_menus() {
		do_action( 'er/init', 'In load expertrec menus' );
		$options = get_option( 'expertrec_options' );
		if ( false == $options ) {
			return;
		}
		if ( array_key_exists( 'expertrec_account_created', $options ) ) {
			$account_created = $options['expertrec_account_created'];
		} else {
			$account_created = null;
		}
		add_menu_page(
			'WP Fastest Site Search',
			'Site Search',
			'manage_options',
			'Expertrec',
			array( $this, 'expertrec_menu_content' ),
			plugin_dir_url( __DIR__ ) . 'assets/images/expertrec.png'
		);
		do_action( 'er/init', 'Added Expertrec menu page' );
	}

	public function expertrec_menu_content() {
		do_action( 'er/debug', 'In Expertrec menu content' );
		include plugin_dir_path( __DIR__ ) . 'views/expertrec-ui.php';
		return;
	}

	public function expertrec_layout_page() {
		do_action( 'er/debug', 'In Expertrec layout page' );
		$options         = get_option( 'expertrec_options' );
		$account_created = $options['expertrec_account_created'];
		if ( isset( $account_created ) && $account_created ) {
			include plugin_dir_path( __DIR__ ) . 'views/expertrec-layout.php';
		} else {
			include plugin_dir_path( __DIR__ ) . 'views/expertrec-login.php';
		}
	}

	public function expertrec_settings_page() {
		do_action( 'er/debug', 'In Expertrec settings page' );
		$options = get_option( 'expertrec_options' );
		$account_created = $options['expertrec_account_created'];
		if ( isset( $account_created ) && $account_created ) {
			include EXPERTREC_PLUGIN_DIR_PATH . 'views/expertrec-settings.php';
		} else {
			include EXPERTREC_PLUGIN_DIR_PATH . 'views/expertrec-login.php';
		}
	}

	public function expertrec_advanced_page() {
		do_action( 'er/debug', 'In Expertrec advanced page' );
		$options         = get_option( 'expertrec_options' );
		$account_created = $options['expertrec_account_created'];
		if ( isset( $account_created ) && $account_created ) {
			include plugin_dir_path( __DIR__ ) . 'views/expertrec-advanced.php';
		} else {
			include plugin_dir_path( __DIR__ ) . 'views/expertrec-login.php';
		}
	}


	public function expertrec_indexing_page() {
		do_action( 'er/debug', 'In Expertrec indexing page' );
		$options = get_option( 'expertrec_options' );
		$account_created = $options['expertrec_account_created'];
		if ( isset( $account_created ) && $account_created ) {
			include EXPERTREC_PLUGIN_DIR_PATH . 'views/expertrec-indexing.php';
		} else {
			include EXPERTREC_PLUGIN_DIR_PATH . 'views/expertrec-login.php';
		}
	}


	public function expertrec_help_page() {
		do_action( 'er/debug', 'In Expertrec help page' );
		include plugin_dir_path( __DIR__ ) . 'views/expertrec-help.php';
	}

	public function expertrec_ajax_load_scripts() {
		do_action( 'er/init', 'Ajax load scripts' );



		wp_enqueue_script( 'ajax-expertrec-deactivate-form', plugin_dir_url( __DIR__ ) . 'assets/js/deactivate.js', array( 'jquery' ), $this->version );
		wp_localize_script(
			'ajax-expertrec-deactivate-form',
			'the_ajax_script',
			array(
				'ajaxurl'                => admin_url( 'admin-ajax.php' ),
				'expertrec_search_nonce' => wp_create_nonce( 'expertrec_search_nonce' ),
			)
		);
		wp_localize_script( 'ajax-expertrec-deactivate-form', 'expertrecPath', array( 'pluginsUrl' => plugin_dir_url( __DIR__ ) ) );
	}

	public function checkAndUpgrade() {
		if ( self::needsUpgrade() ) {
			$this->upgrade();
		}
	}
	public function run() {
		$this->loader->run();
		do_action( 'er/init', 'Loader running : executing all the hooks' );
	}



	public function get_loader() {
		return $this->loader;
	}

	private function expertrec_init_data() {
		do_action( 'er/debug', 'In Expertrec init data' );
		$expertrec_options = get_option( 'expertrec_options' );
		if ( $expertrec_options ) {
			$hook_on_existing_input_box = $expertrec_options['hook_on_existing_input_box'];
			if ( array_key_exists( 'version', $expertrec_options ) ) {
				$version = $expertrec_options['version'];
			}
		}
		if ( ! isset( $hook_on_existing_input_box ) ) {
			$this->expertrec_options_init();
		}
		$expertrec_options = get_option( 'expertrec_options' );
		if ( ( ! isset( $version ) || $version != $this->version ) && '58c9e0e4-78e5-11ea-baf0-0242ac130002' != $expertrec_options['site_id'] ) {
			$log_msg = "Upgrading from : $version to $this->version";
			$this->log->general( 'Upgrade', $log_msg );
			do_action( 'er/debug', 'New settings added' );
			$this->set_options_after_upgrade();
			$data = array(
				'old_plugin_version' => isset( $version ) ? $version : 'NA',
			);
			wp_events( 'old_plugin_upgraded', $data );
			$this->log->general( 'Upgrade', 'Upgrade is done!' );
			do_action( 'er/debug', 'Old plugin upgraded' );
		}
	}

	private function expertrec_options_init() {
		do_action( 'er/debug', 'In expertec optons init' );
		$settings = get_option( 'expertrec_options' );
		if ( empty( $settings ) ) {

			$this->log->general( 'Install', "Plugin Installed version : $this->version" );
			$settings = array(
				'version'                    => $this->version,
				'site_id'                    => '58c9e0e4-78e5-11ea-baf0-0242ac130002',
				'hook_on_existing_input_box' => true,
				'template'                   => 'separate',
				'search_path'                => '/expertrec-search/',
				'query_parameter'            => 's',
				'expertrec_account_created'  => false,
				'first_sync_done'            => false,
				'er_batch_size'              => 5,
				'index_stats'                => array(
					'product'            => array(
						'indexed'   => 0,
						'indexable' => 0,
					),
					'page'               => array(
						'indexed'   => 0,
						'indexable' => 0,
					),
					'post'               => array(
						'indexed'   => 0,
						'indexable' => 0,
					),
					'currently_indexing' => 'NA',
				),
			);
			add_option( 'expertrec_options', $settings, '', 'yes' );
			add_option( 'expertrec_engine', 'db', '', 'yes' );
			add_option( 'expertrec_indexing_status', 'NA', '', 'yes' );
		}
	}



	private function set_options_after_upgrade() {
		$this->log->general( 'Upgrade', 'get_conf started ' );
		expertrec_update_conf();
		do_action( 'er/debug', 'Setting options after upgrade' );
		$this->log->general( 'Upgrade', 'get_conf completed ' );

		$options                              = get_option( 'expertrec_options' );
		$options['version']                   = $this->version;
		$options['cse_id']                    = array_key_exists( 'cse_id', $options ) ? $options['cse_id'] : $options['site_id'];
		$options['expertrec_account_created'] = true;
		$options['er_batch_size']             = 5;

		if ( ( ! array_key_exists( 'expertrec_search_page_url', $options ) ) || ( ! array_key_exists( 'expertrec_search_page_id', $options ) ) ) {
			$is_expertrec_search_page = preg_match( '/expertrec-search/i', $options['search_path'] );
			if ( 'separate' == $options['template'] && $is_expertrec_search_page ) {
				do_action( 'er/debug', 'Expertrec search page : true' );
				$expertrec_search_page = get_page_by_path( $options['search_path'], OBJECT );
				$full_url              = $expertrec_search_page->guid;
				$id                    = $expertrec_search_page->ID;
				$this->log->general( 'Upgrade', "template : separate, page id : $id, full url : $full_url " );
				$options['expertrec_search_page_url'] = $full_url;
				$options['expertrec_search_page_id']  = $id;
				$options['upgrade_sync']              = true;
			} else {
				$this->log->general( 'Upgrade', 'template is not separate' );
				do_action( 'er/debug', 'Expertrec search page : false' );
			}
		}
		update_option( 'expertrec_options', $options );

		$options = get_option( 'expertrec_options' );
		if ( array_key_exists( 'upgrade_sync', $options ) ) {
			if ( $options['upgrade_sync'] ) {
				do_action( 'er/debug', 'Upgrade sync flag for 1st upgrade' );
				expertrec_read_from_db_and_update_layout_conf();
				$options['upgrade_sync'] = false;
				update_option( 'expertrec_options', $options );
			}
		}
	}
}
