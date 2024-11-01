<?php
require_once plugin_dir_path( __DIR__ ) . 'includes/class-expertrecsearch-client.php';

class Expertrecsearch_Admin {


	private $plugin_name;

	private $version;

	private $demo_site_id;
	private $selected_doc_types;
	private $post_id_for_new_indexing;

	public function __construct( $plugin_name, $version ) {
		do_action( 'er/init', 'In admin construct' );
		$this->plugin_name  = $plugin_name;
		$this->version      = $version;
		$this->demo_site_id = '58c9e0e4-78e5-11ea-baf0-0242ac130002';
		add_filter( 'plugin_action_links', array( $this, 'addExpertrecPluginActionLinks' ), 10, 2 );
		$selected_doc_types_from_db = get_option( EXPERTREC_DB_OPTIONS_SELECTED_DOC_TYPES );
		$this->selected_doc_types   = $selected_doc_types_from_db ? $selected_doc_types_from_db : array();
		do_action( 'er/init', 'selected doc types: ' . gettype( $selected_doc_types_from_db ) . ' with value [' . print_r( $selected_doc_types_from_db, true ) . ']' );
		$this->post_id_for_new_indexing = array();
	}

	public function enqueue_styles() {
		do_action( 'er/init', 'In enqueue_styles' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __DIR__ ) . 'assets/css/expertrecsearch-admin.css', array(), $this->version, 'all' );
	}


	private function expertrec_is_selected_doc_type_match( $post_type ) {

		if ( in_array( $post_type, $this->selected_doc_types ) ) {
			do_action( 'er/debug', 'selected doc type matched with post_type' );
			return true;
		} else {
			do_action( 'er/debug', 'selected doc type not matched with post_type' );
			return false;
		}
	}


	public function expertrec_transition_post_status( $new_status, $old_status, $post ) {

		do_action( 'er/debug', 'In transition post status hook' );

		if ( ! $this->expertrec_is_selected_doc_type_match( $post->post_type ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		do_action( 'er/debug', 'In transition post status hook - looking for old status: ' . $old_status . ' and new status: ' . $new_status );

		if ('publish' === $new_status && 'publish' !== $old_status ) {
			do_action( 'er/debug', 'publish detected, adding to post_id_for_new_indexing' );
			array_push( $this->post_id_for_new_indexing, $post->ID );
			return;
		}

		if ( 'publish' == $old_status && 'publish' != $new_status ) {
			do_action( 'er/debug', 'unpublish detected, deleting publish=>' . $new_status );
			$client = new ExpClient();
			$client->deleteDoc( $post->ID, 'expertrec_transition_post_status' );
		}

		return;
	}


	public function expertrec_future_to_publish( $post ) {
		do_action( 'er/debug', 'In expertrec future to publish' );

		if ( ! $this->expertrec_is_selected_doc_type_match( $post->post_type ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		if ( 'publish' == $post->post_status ) {
			$client = new ExpClient();
			$client->indexNewDoc( $post->ID, 'expertrec_future_to_publish');
		}
	}

	public function expertrec_wp_after_insert_post( $post, $update, $post_before ) {
		do_action( 'er/debug', 'Post saved hook triggered' );

		if ( is_numeric( $post ) ) {
			$postId = $post;
		} else {
			$postId = $post->ID;
		}

		$post = get_post( $postId );

		if ( ! $this->expertrec_is_selected_doc_type_match( $post->post_type ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		do_action( 'er/debug', 'Post new status (looking for publish) got ' . $post->post_status );

		if ( 'publish' == $post->post_status ) {
			do_action( 'er/debug', 'Published state, triggering a index hit' );

			$client = new ExpClient();

			if ( in_array( $postId, $this->post_id_for_new_indexing ) ) {
				do_action( 'er/debug', 'New post detected, indexing' );
				$client->indexNewDoc( $postId, 'expertrec_save_post' );
				$this->post_id_for_new_indexing = array_diff( $this->post_id_for_new_indexing, array( $postId ) );
				return;
			}

			do_action( 'er/debug', 'Existing post detected, updating' );
			$client->indexUpdatedDoc( $postId, 'expertrec_save_post' );
			return;
		}

		do_action( 'er/debug', 'Not published state, ignoring' );

		return;
	}

	public function expertrec_woocommerce_rest_insert_product_object( $product, $request, $creating ) {
		do_action( 'er/debug', 'In expertrec woocommerce rest insert product object' );

		if ( ! $this->expertrec_is_selected_doc_type_match( 'product' ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		$client = new ExpClient();
		if ( 'publish' == $product->get_status() ) {
			if ( $creating ) {
				$client->indexNewDoc( $product->get_id(), 'expertrec_woocommerce_rest_insert_product_object');
				return;
			}

			$client->indexUpdatedDoc( $product->get_id(), 'expertrec_woocommerce_rest_insert_product_object');
		}

		return;
	}

	public function expertrec_woocommerce_rest_delete_product_object( $product, $response, $request ) {
		do_action( 'er/debug', 'In expertrec woocommerce rest delete product object' );

		if ( ! $this->expertrec_is_selected_doc_type_match( 'product' ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		$client = new ExpClient();
		$client->deleteDoc( $product->get_id(), 'expertrec_woocommerce_rest_delete_product_object');

		return;
	}

	public function expertrec_woocommerce_update_product( $product_id, $product ) {
		do_action( 'er/debug', 'In expertrec woocommerce update product' );

		if ( ! $this->expertrec_is_selected_doc_type_match( 'product' ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		$client = new ExpClient();
		if ( 'publish' == $product->get_status() ) {
			$client->indexUpdatedDoc( $product_id, 'expertrec_woocommerce_update_product');
		}

		return;
	}

	public function expertrec_pmxi_saved_post( $post_id, $xml_node, $is_update ) {
		do_action( 'er/debug', 'Wp Import post saved hook triggered' );

		$post = get_post( $post_id );

		if ( ! $this->expertrec_is_selected_doc_type_match( $post->post_type ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		do_action( 'er/debug', 'Post new status (looking for publish) got ' . $post->post_status );

		if ( 'publish' == $post->post_status ) {
			do_action( 'er/debug', 'Published state, triggering a index hit' );

			$client = new ExpClient();
			if ( $is_update ) {
				do_action( 'er/debug', 'Existing post detected, updating' );
				$client->indexUpdatedDoc( $post_id, 'expertrec_pmxi_saved_post' );
				return;
			}

			do_action( 'er/debug', 'New post detected, indexing' );
			$client->indexNewDoc( $post_id, 'expertrec_pmxi_saved_post' );
			return;
		}

		do_action( 'er/debug', 'Not published state, ignoring' );
		return;
	}

	public function expertrec_trashed_post( $postId ) {
	}

	public function expertrec_stock_status_change( $product_id, $product_stock_status, $product ) {

		do_action('er/debug', "In expertrec stock status change, product ID - $product_id, current status - $product_stock_status");

		$prodcut_date_created  = $product->date_created;
		$prodcut_date_modified = $product->date_modified;

		if ( $prodcut_date_created == $prodcut_date_modified || ! $prodcut_date_modified ) {
			do_action( 'er/debug', 'Product created and modified date are same, hence returning' );
			return;
		}

		if ( ! $this->expertrec_is_selected_doc_type_match( 'product' ) ) {
			do_action( 'er/debug', 'selected doc types not matched with post_type, hence returning' );
			return;
		}

		do_action( 'er/debug', 'Stock status change called' );
		$client = new ExpClient();
		$client->indexUpdatedDoc( $product_id, 'expertrec_stock_status_change' );
	}

	public function addExpertrecPluginActionLinks( $links, $plugin_base_name ) {
		do_action( 'er/debug', 'In add plugin action links ' . $plugin_base_name );
		if ( strpos( $plugin_base_name, 'expertrec' ) ) {
			return array_merge(
				array(
					'<a href="' . admin_url( 'admin.php?page=Expertrec' ) . '">' . __( 'Settings', 'expertrec' ) . '</a>',
				),
				$links
			);
		}
		return $links;
	}
}
