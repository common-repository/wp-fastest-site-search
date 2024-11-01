<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


require_once plugin_dir_path( __DIR__ ) . 'includes/class-expertrecsearch-logger.php';
require_once plugin_dir_path( __DIR__ ) . 'hooks/expertrecsearch-caller.php';
require_once plugin_dir_path( __DIR__ ) . 'expertrecsearch.php';

use Smalot\PdfParser\Parser;

function expertrec_shutdown() {
	do_action( 'er/indexing', 'shutdown called, shutting down' );

	$indexing = get_option( 'expertrec_indexing_status' );
	if ( 'indexing' == $indexing ) {
		update_option( 'expertrec_indexing_status', 'resume' );
		do_action( 'er/indexing', 'indexing was in progress, marking it to resume' );
	}
}

function expertrec_shutdown_signal( $signo ) {
	do_action( 'er/indexing', 'shutdown called, shutting down with signal ' . $signo );
	expertrec_shutdown();
}
class ExpClient {

	public $write_api_key   = null;
	public $siteId          = null;
	public $acfs            = null;
	private $version        = null;
	private $searchIndexUrl = 'https://data.expertrec.com/1/';
	private $index_varinats = null;
	private $brand_taxonomy = null;
	private $dummySiteId          = '58c9e0e4-78e5-11ea-baf0-0242ac130002';
	private $defaultBatchSize     = 50;
	private $max_cust_field_limit = 1000;
	private $log                  = null;
	private $stop_indexing        = false;
	private $mock_api             = null;

	const IMAGE_SIZE     = 300;
	const THUMBNAIL_SIZE = 70;

	private function getProductImage( $image_id, $size ) {
		$image_url = '';

		$image_url = apply_filters( 'er_get_product_image_pre', $image_url, $image_id, $size );

		if ( empty( $image_url ) && ! empty( $image_id ) && ! empty( $size ) ) {

			$image_src = wp_get_attachment_image_src( $image_id, array( $size, $size ), true );

			if ( ! empty( $image_src ) ) {
				$image_url = $image_src[0];
			}
		}

		return apply_filters( 'er_get_product_image_post', $image_url, $image_id, $size );
	}


	public function __construct() {
		if ( defined( 'EXPERTREC_VERSION' ) ) {
			$this->version = EXPERTREC_VERSION;
		} else {
			$this->version = '4.0.0';
		}
		$expertrec_options = get_option( 'expertrec_options' );
		if ( array_key_exists( 'ecom_id', $expertrec_options ) ) {
			$this->siteId = $expertrec_options['ecom_id'];
		}
		$this->write_api_key  = array_key_exists( 'write_api_key', $expertrec_options ) ? $expertrec_options['write_api_key'] : 'NA';
		$this->index_varinats = get_option( 'EXPERTREC_DB_OPTIONS_INDEX_VARIANTS' );
		$this->brand_taxonomy = get_option( 'EXPERTREC_DB_OPTIONS_BRAND_TAXONOMY' );
		$this->mock_api       = get_option( 'EXPERTREC_MOCK_API', false );
		$this->log            = ExpLogger::loging();
		if ( null == $this->acfs ) {
			$this->acfs = array(
				'images'   => array(),
				'snippets' => array(),
				'texts'    => array(),
				'titles'   => array(),
			);
		}

		$debug_url = get_option( 'expertrec_debug_url' );
		if ( $debug_url ) {
			$this->searchIndexUrl = $debug_url;
		}
	}

	public function __destruct() {
	}


	public function deleteDoc( $docId, $callFrom = '' ) {
		if ($callFrom) {
			$this->log->subsequent_log("$callFrom - deleteDoc", "Deleting hdoc for $docId having url: " . get_permalink($docId));
		} else {
			$this->log->indexing("$callFrom - deleteDoc", "Deleting hdoc for $docId having url: " . get_permalink($docId));
		}

		$url     = $this->searchIndexUrl . "indexes/{$this->siteId}/$docId";
		$payload = null;
		$resp    = $this->sendData( $url, 'DELETE', $payload, true, "$callFrom - deleteDoc", '', 'log_into_subsequent_update_file', 'delete' );
		if ( $resp ) {
			$this->updateLastSyncStatus();
		}
		return $resp;
	}

	public function startIndex() {
		$url     = '';
		$method  = 'POST';
		$payload = '{}';
		$headers = array(
			'User-Agent'          => 'EXP Wordpress Plugin/' . $this->version,
			'X-Expertrec-API-Key' => $this->write_api_key,
			'Content-type'        => 'application/json',
			'X-Request-Id'        => uniqid(),
		);

		$response = call_expertrec_api( $url, $method, $headers, $payload, $timeout = 10 );
	}

	private function sendData( $url, $method, &$payload, $protected, $caller = 'NA', $batchid = '', $printlog = 'NA', $index_Type = 'batch' ) {
		do_action('er/debug', 'Calling sendData function');

		if ( $protected && ( null === $this->write_api_key || 0 === strlen( $this->write_api_key ) ) ) {
			$failure           = array();
			$failure['status'] = 'failure';
			return $failure;
		}
		$headers = array(
			'User-Agent'             => 'EXP Wordpress Plugin/' . $this->version,
			'X-Expertrec-API-Key'    => $this->write_api_key,
			'Content-type'           => 'application/json',
			'X-Request-Id'           => generateRequestId( $batchid ),
			'X-Batch-Id'             => $batchid,
			'X-Expertrec-Index-Type' => $index_Type,
			'X-Expertrec-Call-From'  => $caller,
		);
		do_action( 'er/debug', 'URL is: ' . print_r( $url, true ) );
		if ('NA' === $printlog) {
			$this->log->indexing( $caller, 'Request-Id is: ' . print_r( $headers['X-Request-Id'], true ) );
		} else {
			$this->log->subsequent_log( $caller, 'Request-Id is: ' . print_r( $headers['X-Request-Id'], true ) );
		}

		do_action( 'er/debug', 'Headers are:' . print_r( $headers, true ) );

		if ( $this->mock_api ) {
			if ('NA' === $printlog) {
				$this->log->indexing( $caller, 'Mock API is enabled, so not sending data to expertrec' );
			} else {
				$this->log->subsequent_log( $caller, 'Mock API is enabled, so not sending data to expertrec' );
			}
			return true;
		}

		$response = index_data( $url, $method, $headers, $payload );
		do_action( 'er/debug', 'Response is: ' . wp_remote_retrieve_body( $response ) );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( ! is_wp_error( $response ) && '200' == $response_code ) {
			do_action( 'er/debug', 'update success' );
			$current_epoch = time();
			update_option( EXPERTREC_DB_OPTIONS_SUBSEQUENT_UPDATES, $current_epoch );
			return true;
		} else {
			if ('NA' === $printlog) {
				$this->log->indexing( 'sendData', 'Error Error, update not successful with response code ' . $response_code );
			} else {
				$this->log->subsequent_log( 'sendData', 'Error Error, update not successful with response code ' . $response_code );
			}

			if ( ! $response_code ) {
				if ('NA' === $printlog) {
					$this->log->indexing( 'sendData', 'Since response_code is not there, it is deemed a timeout - but success ' );
				} else {
					$this->log->subsequent_log( 'sendData', 'Since response_code is not there, it is deemed a timeout - but success ' );
				}
			}

			return true;
		}
	}

	private function updateLastSyncStatus() {
		$expertrec_options                         = get_option( 'expertrec_options' );
		$expertrec_options['last_successful_sync'] = time();        // get current time and update value
		update_option( 'expertrec_options', $expertrec_options );    // update expertrec_options
	}


	public function indexNewDoc( $postId, $callFrom = '' ) {
		$post = get_post( $postId );
		$docs = $this->createDoc( $post, $callFrom );

		$url  = $this->searchIndexUrl . "indexes/{$this->siteId}/$postId";
		$resp = $this->sendData( $url, 'POST', $docs, false, "$callFrom - indexNewDoc", '', 'log_into_subsequent_update_file', 'create' );
		return $resp;
	}


	public function indexUpdatedDoc( $postId, $callFrom = '' ) {
		$post = get_post( $postId );
		$docs = $this->createDoc( $post, $callFrom );

		$url  = $this->searchIndexUrl . "indexes/{$this->siteId}/$postId";
		$resp = $this->sendData( $url, 'POST', $docs, false, "$callFrom - indexUpdatedDoc", '', 'log_into_subsequent_update_file', 'update' );
		return $resp;
	}


	public function createDoc( $postId, $callFrom = 'expertrec_continueindex' ) {
		$doc = array();

		try {
			$post = get_post( $postId );
			$doc['id'] = $post->ID;
			$doc['title'] = $this->getSanitizedData( $post->post_title, true );
			$post_url     = get_permalink( $post );
			$doc['url']   = $post_url;
			$value = $this->getDocpath( $post_url, true );
			if ( null != $value ) {
				$doc['DocPath'] = $value;
			}

			if ('expertrec_continueindex' === $callFrom) {
				$this->log->indexing("$callFrom - createDoc", "Creating doc for $post->ID having url: $post_url");
			} else {
				$this->log->subsequent_log("$callFrom - createDoc", "Creating doc for $post->ID having url: $post_url");
			}
			$publish_date          = $this->convert_to_tz_format( $post->post_date );
			$doc['published_date'] = $publish_date;


			$excerpt = $post->post_excerpt;
			$content = apply_filters( 'the_content', $post->post_content );
			if ( function_exists( 'get_field' ) ) {
				foreach ( $this->acfs['texts'] as $text_field_id ) {
					$cust_field = get_field( str_replace( 'xxx', '_', $text_field_id ), $post->ID );
					if ( $cust_field ) {
						$content = $content . $cust_field;
					}
				}
				foreach ( $this->acfs['snippets'] as $text_field_id ) {
					$cust_field = get_field( str_replace( 'xxx', '_', $text_field_id ), $post->ID );
					if ( $cust_field ) {
						$excerpt = $cust_field;
					}
				}
				foreach ( $this->acfs['titles'] as $text_field_id ) {
					$cust_field = get_field( str_replace( 'xxx', '_', $text_field_id ), $post->ID );
					if ( $cust_field ) {
						$doc['title'] = $this->getSanitizedData( $cust_field, true );
					}
				}
			}

			$content = $this->getSanitizedData( $content, true );

			$wp_categories        = get_the_category( $post->ID );
			$wp_categories_name   = $this->getValues( $wp_categories );
			$doc['wp_categories'] = $this->getSanitizedData( $wp_categories_name, true );

			$post_type        = $post->post_type;
			$doc['post_type'] = $post_type;

			if ( 'product' == $post_type ) {
				$woo_categories = get_the_terms( $post->ID, 'product_cat' );
				if ( is_array( $woo_categories ) || ! empty( $woo_categories ) ) {
					$woo_category_values = $this->getValues( $woo_categories );
					$doc['wc_category']  = $this->getSanitizedData( $woo_category_values, true );
					$woo_cat_hierarchy   = array();
					foreach ( $woo_categories as $category ) {
						$ancestors = array_reverse( get_ancestors( $category->term_id, 'product_cat' ) );
						if ( ! empty( $ancestors ) ) {

							$formatted_term = array();
							foreach ( $ancestors as $ancestor ) {
								$formatted_term[] = get_term( $ancestor, 'product_cat' )->name;
							}
							$formatted_term[] = $category->name;
							$thecatnameis = implode( '>', $formatted_term );

						} else {
							$thecatnameis = $category->name;
						}
						$woo_cat_hierarchy[] = $thecatnameis;
					}
					$doc['wc_category_hierarchy'] = $this->getSanitizedData( $woo_cat_hierarchy, true );
				}
			}

			$author        = get_the_author_meta( 'display_name', $post->post_author );
			$doc['author'] = $author;

			$tags        = wp_get_post_tags( $post->ID );
			$tag_values  = $this->getValues( $tags );
			$doc['tags'] = $tag_values;

			$doc['description'] = $excerpt ? $this->getSanitizedData( $excerpt, true ) : substr( $content, 0, 350 );
			$doc['content']     = $content;

			$post_image          = get_the_post_thumbnail_url( $post );
			$original_post_image = $post_image;
			if ( function_exists( 'get_field' ) ) {
				foreach ( $this->acfs['images'] as $image_field_id ) {
					$cust_field = get_field( str_replace( 'xxx', '_', $image_field_id ), $post->ID );
					if ( $cust_field && isset( $cust_field['sizes'] ) && isset( $cust_field['sizes']['thumbnail'] ) ) {
						$post_image = $cust_field['sizes']['thumbnail'];
					} else {
						$img_obj     = wp_get_attachment_image_src( $cust_field, 'thumbnail' );
						$post_images = $img_obj;
						if ( false !== $img_obj && 'false' !== $img_obj ) {
							$post_image = $img_obj[0];
						}
					}
				}
			}
			if ( isset( $post_images ) && $post_images ) {
				$doc['images'] = $post_images;
			}
			if ( false !== $post_image ) {
				$doc['image'] = $post_image;
			} elseif ( isset( $original_post_image ) ) {
				$doc['image'] = $original_post_image;
			}

			if ( 'post' == $post_type || 'page' == $post_type ) {
				$images = $this->get_images_from_content( $post );
				if ( isset( $images ) && ! empty( $images ) ) {
					$doc['images'] = $images;
					$doc['image']  = $images[0];
				}
			}

			$imageTextsString = '';
			$attached_images  = get_attached_media( 'image', $post->ID );
			if ( $attached_images ) {
				foreach ( $attached_images as $attached_image ) {
					$image_alt = get_post_meta( $attached_image->ID, '_wp_attachment_image_alt', true );
					if ( null != $image_alt && '' != $image_alt ) {
						if ( '' != $imageTextsString ) {
							$imageTextsString = $imageTextsString . ', ';
						}
						$imageTextsString = $imageTextsString . $image_alt;
					}
				}
			}

			$doc['img_text'] = $imageTextsString;
			if ( 'product' == $post_type ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					$prod = $this->getProductFields( $post->ID );
					$doc     = array_merge( $doc, $prod );
					$product = wc_get_product( $post->ID );

					$woocommerce_attributes_json = get_option( EXPERTREC_DB_OPTIONS_KEY_PRODUCT_ATTRIBUTES, '[]' );
					$woocommerce_attributes = json_decode( $woocommerce_attributes_json );
					if ( count( $woocommerce_attributes ) > 0 ) {
						$product_meta = get_post_meta( $post->ID, '_product_attributes' );
						if ( count( $product_meta ) > 0 ) {
							$keys = array_keys( $product_meta[0] );
							foreach ( $keys as $key ) {
								$meta_def        = $product_meta[0][ $key ];
								$name            = $meta_def['name'];
								$name_without_pa = substr( $name, 3 );
								if ( in_array( $name, $woocommerce_attributes ) ) {
									$val = $meta_def['value'];
									if ( strlen( $val ) == 0 ) {
										$att = $product->get_attribute( $key );
										$doc[ $name_without_pa ] = explode( ', ', $att );
									} else {
										$doc[ $name_without_pa ] = explode( '|', $val );
									}
								}
							}
						}
					}
				}

				if ( ! array_key_exists( 'price', $doc ) || null == $doc['price'] ) {
					$product_price = get_post_meta( $post->ID, '_price' );
					if ( count( $product_price ) > 0 ) { // Price filter
						$doc['price'] = $product_price[0];
					}
				}
				do_action( 'er/debug', 'INDEX_VARIANTS' . $this->index_varinats );
				if ( $product->is_type( 'variable' ) && $this->index_varinats ) {
					do_action( 'er/debug', 'product is variable' . $post->ID );
					$variations       = $product->get_available_variations();
					$variations_array = array();
					$options          = array();
					foreach ( $variations as $v ) {
						$variant = array(
							'product_code' => $v['sku'],
							'id'           => $v['variation_id'],
							'price'        => (float) $v['display_price'],
							'list_price'   => (float) $v['display_regular_price'],
							'is_in_stock'  => $v['is_in_stock'] ? 'Y' : 'N', // TODO: Need further check for variant manage stock ( toggle stock etc)
							'description'  => $v['variation_description'],
							'active'       => $v['variation_is_active'] ? 'Y' : 'N',
							'visible'      => $v['variation_is_visible'] ? 'Y' : 'N',
							'image_link'   => '',
						);

						if ( ! empty( $v['attributes'] ) ) {
							foreach ( $v['attributes'] as $attr_name => $attr_val ) {
								$attribute_label = wc_attribute_label( $attr_name );
								if ( strpos( $attr_name, 'attribute_' ) !== false ) {
									$taxonomy = str_replace( 'attribute_', '', $attr_name );
									$term     = get_term_by( 'slug', $attr_val, $taxonomy );

									if ( ! empty( $term ) && is_object( $term ) && is_a( $term, 'WP_Term' ) ) {
										$attribute_label = wc_attribute_label( $attr_name );
										$attr_val        = $term->name;
										do_action( 'er/debug', ' attribute taxonomy found ' . print_r( $term->name, true ) );
									}
								}

								do_action( 'er/debug', 'adding attribute ' . $attr_name . ' ' . $attr_val );
								if ( ! in_array( $attribute_label, $options ) ) {
									$options[] = $attribute_label;
								}
								$variant[ $attribute_label ] = $attr_val;
							}
						}
						$image_url             = $this->getProductImage( $v['image_id'], self::IMAGE_SIZE );
						$variant['image_link'] = ! empty( $image_url ) ? $image_url : '';

						$variations_array[] = $variant;
					}
					$doc['variations'] = $variations_array;
					$doc['options']    = $options;
				} else {
					do_action( 'er/debug', 'product is not variable' . $post->ID . ' (' . $product->get_type() . ' )' );
					$myStringDummy = 'lint fix';
				}
			}

			$meta_json = get_option( EXPERTREC_DB_OPTIONS_KEY_META_ATTRIBUTES );
			if ( $meta_json ) {
				$meta_keys = json_decode( $meta_json );
				if ( count( $meta_keys ) > 0 ) {
					foreach ( $meta_keys as $key ) {
						$meta_val = get_post_meta( $post->ID, $key, true );
							$doc[ $key ] = $meta_val;
					}
				}
			}
			$taxonomies = get_taxonomies(
				array(
					'public'   => true,
					'_builtin' => false,
				),
				'names'
			);
			if ( '' != $taxonomies && is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
				$taxonomy_names = array_keys( $taxonomies );
				for ( $i = 0; $i < count( $taxonomy_names ); $i++ ) {
					$doc[ 'taxonomy_' . $taxonomy_names[ $i ] ] = wp_get_post_terms( $post->ID, $taxonomy_names[ $i ], array( 'fields' => 'names' ) );
				}
			}
			return $doc;
		} catch ( Exception $e ) {
			if ('expertrec_continueindex' === $callFrom) {
				$this->log->indexing( 'createDoc', $e->getMessage() );
			} else {
				$this->log->subsequent_log( 'createDoc', $e->getMessage() );
			}
		}
		return $doc;
	}

	private function getSanitizedData( $data, $remove_shortcodes = false, $depth = 0 ) {
		if ( is_array( $data ) ) {
			if ( 1 == $depth ) {
				return '';
			}
			$sanitized_data = array();
			foreach ( $data as $d ) {
				array_push( $sanitized_data, $this->getSanitizedData( $d, $remove_shortcodes, $depth + 1 ) );
			}
			return $sanitized_data;
		}
		try {
			$data = wp_strip_all_tags( $data );

			if ( $remove_shortcodes ) {
				$data = strip_shortcodes( $data );
				$data = $this->removeShortcodes( $data );
			}

			$data = html_entity_decode( $data, ENT_QUOTES, 'UTF-8' );
		} catch ( Exception $e ) {
			$this->log->indexing( 'getSanitizedData', 'Exception while sanitizing the data: ' . $e->getMessage() );
		}
		return $data;
	}

	private function removeShortcodes( $content ) {
		$str = preg_replace( '#\[[^\]]+\]#', '', $content );
		return $str;
	}

	public function getDocpath( $url ) {
		$new_url = parse_url( $url );
		$new_url = $new_url['path'];
		$parts   = explode( '/', $new_url );
		if ( count( $parts ) <= 2 ) {
			return;
		}
		$new_url = $parts[1];
		if ( null != $new_url ) {
			$new_url = preg_replace( '/\W/', ' ', $new_url );
			return $new_url;
		}
	}

	private function convert_to_tz_format( $date ) {
		$timestamp = strtotime( $date );
		$tz_date   = gmdate( 'Y-m-d\TH:i:s.000\Z', $timestamp );
		if ( ! $timestamp || ! $tz_date ) {
			return '';
		}
		return $tz_date;
	}

	private function getCustomFields( $postId ) {
		$cust_fields        = array();
		$post_type_features = get_post_custom( $postId );
		foreach ( $post_type_features as $key => $value ) {
			if ( '_' !== substr( $key, 0, 1 ) ) {
				$key = preg_replace( '/[^A-Za-z0-9_]/', '', $key );
				$sanitized_value = $this->getSanitizedData( $value );
				if ( is_string( $sanitized_value ) && strlen( $sanitized_value ) > $this->max_cust_field_limit ) {
					$sanitized_value = substr( $sanitized_value, 0, $this->max_cust_field_limit );
				}
				$cust_fields[ 'cust_' . $key ] = $sanitized_value;
			}
		}
		return $cust_fields;
	}

	private function getTopCategory( $categories ) {
		$category_by_parent = array();
		foreach ( $categories as $category ) {
			$parentCategoryId = $category->category_parent;
			if ( 0 == $parentCategoryId && strlen( $category->name ) > 0 ) {
				return $category->name;
			}
			$categoryId                              = $category->cat_ID;
			$category_by_parent[ $parentCategoryId ] = $categoryId;
		}
		foreach ( $categories as $category ) {
			if ( ! isset( $category_by_parent[ $category->category_parent ] ) && strlen( $category->name ) > 0 ) {
				return $category->name;
			}
		}
		return null;
	}

	private function get_images_from_content( $post ) {
		$images = array();
		if ( $post->post_content ) {
			$dom_obj = new DOMDocument();
			@$dom_obj->loadHTML( $post->post_content );
			foreach ( $dom_obj->getElementsByTagName( 'img' ) as $item ) {
				array_push( $images, $item->getAttribute( 'src' ) );
			}
		}
		return $images;
	}

	private function safe_get_product_data($product, $method) {
		try {
			return $product->$method();
		} catch (Throwable $e) {
			$this->log->indexing( 'found error in getProductFields for' . $method, $e->getMessage() );
			return null;
		}
	}

	private function getProductFields( $postId ) {
		$prod = array();
		try {
			$product = wc_get_product( $postId );

			try {
				if ( function_exists( 'get_fields' ) ) {
					$post_cust_fields = get_fields( $postId );
					if ( $post_cust_fields && array_key_exists( 'price_um', $post_cust_fields ) ) {
						$prod['price_um'] = $post_cust_fields['price_um'];
					}
					if ( $post_cust_fields && array_key_exists( 'availability', $post_cust_fields ) ) {
						$prod['availability'] = $post_cust_fields['availability'];
					}
					if ( $post_cust_fields && array_key_exists( 'brand_name', $post_cust_fields ) ) {
						$prod['brand'] = $this->getSanitizedData( $post_cust_fields['brand_name'], true );
					}
				}
			} catch (Throwable $e) {
				$this->log->indexing( 'found error in getProductFields for get_fields', $e->getMessage() );
			}
			$prod['type']     = $this->getSanitizedData( $this->safe_get_product_data($product, 'get_type'), true );
			$prod['wc_title'] = $this->safe_get_product_data($product, 'get_title');
			$prod['slug']     = $this->safe_get_product_data($product, 'get_slug');
			$date_created     = $this->safe_get_product_data($product, 'get_date_created');
			if ( null != $date_created ) {
				$date_created = $date_created->__toString();
			}
			$prod['date_created'] = $this->convert_to_tz_format( $date_created );
			$date_modified        = $this->safe_get_product_data($product, 'get_date_modified');
			if ( null != $date_modified ) {
				$date_modified = $date_modified->__toString();
			}
			$prod['date_modified']      = $this->convert_to_tz_format( $date_modified );
			$prod['status']             = $this->safe_get_product_data($product, 'get_status');
			$prod['featured']           = $this->safe_get_product_data($product, 'get_featured');
			$prod['catalog_visibility'] = $this->safe_get_product_data($product, 'get_catalog_visibility');
			$prod['sku']                = $this->safe_get_product_data($product, 'get_sku');
			$prod['menu_order']         = $this->safe_get_product_data($product, 'get_menu_order');
			$prod['virtual']            = $this->safe_get_product_data($product, 'get_virtual');
			$prod['permalink']          = get_permalink( $product->get_id() );
			$prod['price']         = $this->safe_get_product_data($product, 'get_price');
			$prod['regular_price'] = $this->safe_get_product_data($product, 'get_regular_price');
			$prod['sale_price']    = $this->safe_get_product_data($product, 'get_sale_price');
			$date_on_sale_from     = $this->safe_get_product_data($product, 'get_date_on_sale_from');
			if ( null != $date_on_sale_from ) {
				$date_on_sale_from = $date_on_sale_from->__toString();
			}
			$prod['date_on_sale_from'] = $this->convert_to_tz_format( $date_on_sale_from );
			$date_on_sale_to           = $this->safe_get_product_data($product, 'get_date_on_sale_to');
			if ( null != $date_on_sale_to ) {
				$date_on_sale_to = $date_on_sale_to->__toString();
			}
			$prod['date_on_sale_to'] = $this->convert_to_tz_format( $date_on_sale_to );
			$prod['total_sales']     = $this->safe_get_product_data($product, 'get_total_sales');
			$prod['tax_status']        = $this->safe_get_product_data($product, 'get_tax_status');
			$prod['tax_class']         = $this->safe_get_product_data($product, 'get_tax_class');
			$prod['manage_stock']      = $this->safe_get_product_data($product, 'get_manage_stock');
			$prod['stock_quantity']    = $this->safe_get_product_data($product, 'get_stock_quantity');
			$prod['stock_status']      = $this->safe_get_product_data($product, 'get_stock_status');
			$prod['backorders']        = $this->safe_get_product_data($product, 'get_backorders');
			$prod['sold_individually'] = $this->safe_get_product_data($product, 'get_sold_individually');
			$prod['purchase_note']     = $this->safe_get_product_data($product, 'get_purchase_note');
			$prod['shipping_class_id'] = $this->safe_get_product_data($product, 'get_shipping_class_id');
			$prod['weight'] = $this->safe_get_product_data($product, 'get_weight');
			$prod['length'] = $this->safe_get_product_data($product, 'get_length');
			$prod['width']  = $this->safe_get_product_data($product, 'get_width');
			$prod['height'] = $this->safe_get_product_data($product, 'get_height');
			$prod['upsell_ids']     = $this->safe_get_product_data($product, 'get_upsell_ids');
			$prod['cross_sell_ids'] = $this->safe_get_product_data($product, 'get_cross_sell_ids');
			$parent_id              = $this->safe_get_product_data($product, 'get_parent_id');
			if ( 0 !== $parent_id ) {
				$prod['parent_id'] = $parent_id;
			}
			try {
				$prod['currency']        = get_woocommerce_currency();
				$prod['currency_symbol'] = $this->getSanitizedData( get_woocommerce_currency_symbol() );

				$delimiter = "\001"; // SOH character

				$productCategoryList = wc_get_product_category_list( $postId, $delimiter );
				do_action( 'er/debug', 'Got product category list: ' . $productCategoryList );

				$strippedCategoryList = wp_strip_all_tags( $productCategoryList );
				do_action( 'er/debug', 'Stripped HTML tags from category list: ' . $strippedCategoryList );

				$decodedCategoryList = html_entity_decode( $strippedCategoryList, ENT_COMPAT );
				do_action( 'er/debug', 'Decoded HTML entities: ' . $decodedCategoryList );

				$prod['wc_categories'] = explode( $delimiter, $decodedCategoryList );
				do_action( 'er/debug', 'Exploded category list into an array: ' . print_r( $prod['wc_categories'], true ) );
			} catch ( Throwable $e ) {
				$this->log->indexing( 'found error in getProductFields', $e->getMessage() );
			}

			$prod['wc_category_ids'] = $this->safe_get_product_data($product, 'get_category_ids');
			$prod['tag_ids']         = $this->safe_get_product_data($product, 'get_tag_ids');
			$prod['wc_tags']         = explode( $delimiter, wp_strip_all_tags( wc_get_product_tag_list( $postId, $delimiter ) ) );
			$prod['download_expiry'] = $this->safe_get_product_data($product, 'get_download_expiry');
			$prod['downloadable']    = $this->safe_get_product_data($product, 'is_downloadable');
			$prod['download_limit']  = $this->safe_get_product_data($product, 'get_download_limit');
			$prod['image_id'] = $this->safe_get_product_data($product, 'get_image_id');
			$htmlImg          = $this->safe_get_product_data($product, 'get_image');
			if ( null != $htmlImg ) {
				try {
					$htmlImg   = str_replace( '\"', '"', $htmlImg );
					$wc_images = array();
					$dom_obj   = new DOMDocument();
					@$dom_obj->loadHTML( $htmlImg );
					foreach ( $dom_obj->getElementsByTagName( 'img' ) as $item ) {
						array_push( $wc_images, $item->getAttribute( 'src' ) );
						array_push( $wc_images, $item->getAttribute( 'srcset' ) );
					}
					$prod['wc_image'] = $wc_images;
				} catch (Throwable $e) {
					$prod['wc_image'] = '';
					$this->log_error( 'Error parsing product image: ' . $e->getMessage() );
				}
			}
			$prod['gallery_image_ids'] = $this->safe_get_product_data($product, 'get_gallery_image_ids');
			$prod['reviews_allowed'] = $this->safe_get_product_data($product, 'get_reviews_allowed');
			$prod['rating_counts']   = $this->safe_get_product_data($product, 'get_rating_counts');
			$prod['average_rating']  = $this->safe_get_product_data($product, 'get_average_rating');
			$prod['review_count']    = $this->safe_get_product_data($product, 'get_review_count');
			if (false !== $this->brand_taxonomy) {
				$prod['product_brand'] = wp_get_post_terms($postId, $this->brand_taxonomy, array( 'fields' => 'names' ));
			}

			return $prod;
		} catch ( Throwable $e ) {
			$this->log->indexing( 'found error in getProductFields', $e->getMessage() );

			return $prod;
		}
	}

	public function indexDocs() {

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		update_option( 'expertrec_stop_indexing', false );
		$this->stop_indexing = false;

		$this->log->indexing( 'indexDocs', 'Indexing Docs started' );
		$docTypes = $this->getPostTypes();
		$url      = $this->searchIndexUrl . "indexes/{$this->siteId}/batch";
		$expertrec_options = get_option( 'expertrec_options' );
		$total_docs        = 0;
		foreach ( $docTypes as $docType ) {
			$expertrec_options['index_stats'][ $docType ]['indexed'] = 0;
			$cur_size = (int) $this->getPostCount( $docType );
			$expertrec_options['index_stats'][ $docType ]['indexable'] = $cur_size;
			$total_docs += $cur_size;
		}

		if ( ! empty( $expertrec_options['er_batch_size'] ) ) {
			$batchSize = $expertrec_options['er_batch_size'];
		} else {
			$batchSize                          = min( 200, max( 5, $total_docs / 100 ) );
			$expertrec_options['er_batch_size'] = $batchSize;
		}

		update_option( 'expertrec_options', $expertrec_options );

		foreach ( $docTypes as $docType ) {
			$this->stop_indexing = expertrec_option_nocache( 'expertrec_stop_indexing' );
			if ( $this->stop_indexing ) {
				$this->log->indexing( 'indexDocs', 'User stopped indexing' );
				break;
			}
			$this->log->indexing( 'indexDocs', 'Indexing for Doctype ' . $docType );

			$docCount = $this->getPostCount( $docType );
			$this->log->indexing( 'indexDocs', 'Doc Count for Doctype ' . $docType . ' is: ' . $docCount );
			$indexed_post_count                                        = 0;
			$expertrec_options['index_stats'][ $docType ]['indexable'] = (int) $docCount;
			$expertrec_options['index_stats'][ $docType ]['indexed']   = $indexed_post_count;
			$expertrec_options['index_stats']['currently_indexing']    = $docType;
			update_option( 'expertrec_options', $expertrec_options );
			$offset = 0;
			while ( $docCount-- ) {
				$this->stop_indexing = expertrec_option_nocache( 'expertrec_stop_indexing' );
				if ( $this->stop_indexing ) {
					$this->log->indexing( 'indexDocs', 'User stopped indexing' );
					break;
				}
				$this->log->indexing( 'indexDocs', 'Variable offset, batchsize, docType are: ' . $offset . ', ' . $batchSize . ', ' . $docType );
				$posts = get_posts(
					array(
						'posts_per_page' => $batchSize,
						'offset'         => $offset,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'fields'         => 'ids',
						'post_type'      => $docType,
						'post_status'    => 'publish',
					)
				);
				$this->log->indexing( 'indexDocs', 'got ' . count( $posts ) . ' Posts from DB' );
				if ( count( $posts ) < 1 ) {
					break;
				}
				$offset  = $offset + count( $posts );
				$docs    = array();
				$pdfDocs = array();
				foreach ( $posts as $post ) {
					$docs[] = array(
						'action' => 'addObject',
						'body'   => $this->createDoc( $post ),
					);
					$pdfs   = get_attached_media( 'application/pdf', $post );
					if ( count( $pdfs ) > 0 ) {
						$pdfDocs[] = $pdfs;
					}
				}
				try {
					$resp = $this->sendData( $url, 'POST', $docs, false, 'indexDocs' );
					if ( count( $pdfDocs ) > 0 ) {
						$pdf_list = array();
						foreach ( $pdfDocs as $pdf ) {
							$new_pdf  = $this->createPdfDoc( $pdf );
							$pdf_list = array_merge( $pdf_list, $new_pdf );
						}
						$resp = $this->sendData( $url, 'POST', $pdf_list, false, 'indexDocs' );
					}
				} catch ( Exception $e ) {
					$this->log->indexing( 'indexDocs', $e->getMessage() );
				}
				$indexed_post_count += count( $posts );
				$expertrec_options['index_stats'][ $docType ]['indexed'] = $indexed_post_count;
				update_option( 'expertrec_options', $expertrec_options );
			}
		}

		$expertrec_options                                      = get_option( 'expertrec_options' );
		$expertrec_options['last_successful_sync']              = time();
		$expertrec_options['first_sync_done']                   = true;
		$expertrec_options['index_stats']['currently_indexing'] = 'NA';
		update_option( 'expertrec_options', $expertrec_options );
		$this->log->indexing( 'indexDocs', 'Indexing completed' );
	}

	public function getPostTypes() {
		$post_types = array( 'product', 'post', 'page' );
		$available_post_types = get_post_types(
			array(
				'public'              => true,
				'_builtin'            => false,
				'exclude_from_search' => false,
			),
			'names',
			'and'
		);
		$remove_post_types = array( 'scheduled-action', 'scheduled_action', 'nav_menu_item' );

		foreach ( $available_post_types as $post_type ) {
			if ( in_array( $post_type, $remove_post_types ) ) {
				continue;
			} elseif ( array_search( $post_type, $post_types ) === false ) {
				array_push( $post_types, $post_type );
			}
		}

		return $post_types;
	}

	public function getPostCount( $post_type ) {
		$post_count = wp_count_posts( $post_type );
		if ( ! isset( $post_count->publish ) ) {
			return 0;
		}
		$count = $post_count->publish;
		if ( null == $count ) {
			return 0;
		}
		return $count + 0;
	}

	public function exp_count_pdfs_in_media_library() {
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'application/pdf',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
		);

		$pdf_query = new WP_Query($args);

		$pdf_count = $pdf_query->found_posts;

		wp_reset_postdata();

		return $pdf_count;
	}

	public function extract_text_from_pdf($pdf_url) {
		$text = '';


		$original_memory_limit = ini_get('memory_limit');
		ini_set('memory_limit', '1G');

		try {
			$pdf_path = str_replace(home_url('/'), ABSPATH, $pdf_url);

			$parser = new Parser();

			$pdf = $parser->parseFile($pdf_path);

			$pages = $pdf->getPages();
			foreach ($pages as $page) {
				$text .= $page->getText();
				unset($page);
				gc_collect_cycles();
			}

			unset($pages);
			unset($pdf);
			unset($parser);
			gc_collect_cycles();
		} catch (Throwable $e) {
			$text = '';
		} finally {
			ini_set('memory_limit', $original_memory_limit);
		}

		return $text;
	}

	public function getPdfMeta( $pdf_meta, $post_type = 'document' ) {
		$temp                   = array();
		$temp['title']          = $pdf_meta->post_title;
		$temp['id']             = $pdf_meta->ID;
		$temp['author']         = $pdf_meta->post_author;
		$temp['published_date'] = $this->convert_to_tz_format( $pdf_meta->post_date );
		$temp['post_status']    = $pdf_meta->post_status;
		$parent_id              = $pdf_meta->post_parent;
		if ( 0 !== $parent_id ) {
			$temp['parent_id'] = $pdf_meta->post_parent;
		}
		$temp['url']            = wp_get_attachment_url($pdf_meta->ID);
		$temp['post_type']      = $post_type;
		$temp['post_mime_type'] = $pdf_meta->post_mime_type;
		$temp['content']        = $this->extract_text_from_pdf($temp['url']);

		return $temp;
	}


	public function createPdfDoc( $pdfID ) {
		$doc = array();
		foreach ( $pdfID as $pdf_meta ) {
			$temp  = array();
			$temp  = $this->getPdfMeta( $pdf_meta );
			$doc[] = array(
				'action' => 'addObject',
				'body'   => $temp,
			);
		}
		return $doc;
	}

	public function getAllPostCount() {
		return $this->getPostCount( 'post' ) + $this->getPostCount( 'page' ) + $this->getPostCount( 'product' );
	}

	public function start_sync() {
		$this->log->truncate_log_file( 'expertrec_indexing' );
		update_option( 'expertrec_indexing_status', 'indexing' );
		$url     = $this->searchIndexUrl . "indexes/{$this->siteId}/start_sync";
		$payload = null;
		return $this->sendData( $url, 'POST', $payload, false, 'start_sync' );
	}

	public function new_start_sync() {
		$this->log->truncate_log_file( 'expertrec_indexing' );
		update_option( 'expertrec_indexing_status', 'indexing' );
		$url     = $this->searchIndexUrl . "indexes/{$this->siteId}/start_sync";
		$payload = '{}';
		$headers = array(
			'X-Expertrec-API-Key' => $this->write_api_key,
			'Content-type'        => 'application/json',
		);
		$res     = call_expertrec_api( $url, 'POST', $headers, $payload );
		$result = array(
			'status'   => false,
			'batch_id' => null,
			'code'     => 401,
			'comment'  => 'default comment',
		);
		if ( is_array( $res ) ) {
			$resjson = json_decode( $res['body'], true );
			if ( 200 == $res['response']['code'] ) {
				if ( isset( $resjson['batch_id'] ) ) {
					$result['batch_id'] = $resjson['batch_id'];
					$result['status']   = true;

					return $result;
				} else {
					$result['comment'] = 'batch_id not found in response';
				}
			} else {
				$result['comment'] = $resjson['comment'];
				$result['code']    = $res['response']['code'];
			}
		}
		return $result;
	}
	public function end_sync( $prefix = '' ) {
		$url     = $this->searchIndexUrl . "indexes/{$this->siteId}/end_sync";
		$payload = null;
		return $this->sendData( $url, 'POST', $payload, false, 'end_sync', $prefix );
	}

	public function index_category( $batchId, $categories ) {
		$this->log->indexing( 'index_category', 'got ' . count( $categories ) . ' Categories from DB' );
		$numberOfCategories = count( $categories );
		if ( $numberOfCategories < 1 ) {
			return 0;
		}
		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/batch";

		foreach ( $categories as $cat ) {
			$docs[] = array(
				'action' => 'addObject',
				'body'   => $cat,
			);
		}
		try {
			$resp = $this->sendData( $url, 'POST', $docs, false, 'index_categories', $batchId );
		} catch ( Exception $e ) {
			$this->log->indexing( 'index_category', 'indexing category failed, with the below error' );
			$this->log->indexing( 'index_category', $e->getMessage() );
		}
		return $numberOfCategories;
	}

	public function index_pdf($batchSize, $batchId, $offset ) {

		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/batch";

		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'application/pdf',
			'post_status'    => 'inherit',
			'posts_per_page' => $batchSize, // Number of PDFs to fetch per batch
			'offset'         => $offset,    // Offset to start from
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',      // Retrieve only post IDs for performance
		);

		$pdf_query = new WP_Query($args);

		$pdf_ids = $pdf_query->posts;

		$this->log->indexing( 'index_post_type', 'got count' . count( $pdf_ids ) . ' PDFs from media library' );

		if ( count( $pdf_ids ) < 1 ) {
			return 0;
		}

		foreach ( $pdf_ids as $pdf_id ) {
			$pdf_meta = get_post($pdf_id);
			$pdfData = $this->getPdfMeta($pdf_meta, 'pdf');

			$doc[] = array(
				'action' => 'addObject',
				'body'   => $pdfData,
			);
		}

		try {
			$resp   = $this->sendData( $url, 'POST', $doc, false, 'index_post_type', $batchId );
			$offset = $offset + count( $pdf_ids );
		} catch ( Exception $e ) {
			$this->log->indexing( 'index_post_type', 'indexing pdf failed, with the below error' );
			$this->log->indexing( 'index_post_type', $e->getMessage() );
		}

		wp_reset_postdata();

		return $offset;
	}


	public function index_post_type( $docType, $batchSize, $batchId, $offset ) {
		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/batch";
		$posts = get_posts(
			array(
				'posts_per_page' => $batchSize,
				'offset'         => $offset,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'post_type'      => $docType,
				'post_status'    => 'publish',
			)
		);
		$this->log->indexing( 'index_post_type', 'got ' . count( $posts ) . ' ' . $docType . ' from DB' );
		if ( count( $posts ) < 1 ) {
			return 0;
		}
		$offset  = $offset + count( $posts );
		$docs    = array();
		$pdfDocs = array();
		foreach ( $posts as $post ) {
			$docs[] = array(
				'action' => 'addObject',
				'body'   => $this->createDoc( $post ),
			);
			$pdfs   = get_attached_media( 'application/pdf', $post );
			if ( count( $pdfs ) > 0 ) {
				$pdfDocs[] = $pdfs;
			}
		}
		try {
			$resp = $this->sendData( $url, 'POST', $docs, false, 'index_post_type', $batchId );


		} catch ( Exception $e ) {
			$this->log->indexing( 'index_post_type', 'indexing post failed, with the below error' );
			$this->log->indexing( 'index_post_type', $e->getMessage() );
		}
		return $offset;
	}

	public function getValues( array $categories ) {
		$category_values = array();
		foreach ( $categories as $category ) {
			$category_values[] = $category->name;
		}

		return $category_values;
	}
}
