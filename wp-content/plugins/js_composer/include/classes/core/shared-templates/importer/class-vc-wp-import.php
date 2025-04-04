<?php
/**
 * Modified by WPBakery Page Builder team
 * WordPress Importer
 * http://wordpress.org/extend/plugins/wordpress-importer/
 * Description: Import posts, pages, comments, custom fields, categories, tags and more from a WordPress export file.
 * Author: wordpressdotorg
 * Author URI: http://wordpress.org/
 * Version: 0.6.3 with fixes and enchancements from WPBakery Page Builder
 * Text Domain: js_composer
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}


// Load Importer API.
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
		require $class_wp_importer;
	}
}

// include WXR file parsers.
require_once __DIR__ . '/class-vc-wxr-parser.php';

/**
 * WordPress Importer class for managing the import process of a WXR file
 *
 * @package WordPress
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
	/**
	 * Class Vc_WP_Import
	 */
	class Vc_WP_Import extends WP_Importer {
		/**
		 * Max supported WXR version.
		 *
		 * @var float
		 */
		public $max_wxr_version = 1.2;
		/**
		 * File id.
		 *
		 * @var int
		 */
		public $id;

		/**
		 * Information to import from WXR file.
		 *
		 * @var string
		 */
		public $version;

		/**
		 * Posts list.
		 *
		 * @var array
		 */
		public $posts = [];

		/**
		 * Base url.
		 *
		 * @var string
		 */
		public $base_url = '';

		/**
		 * Processed posts.
		 * Mappings from old information to new.
		 *
		 * @var array
		 */
		public $processed_posts = [];

		/**
		 * List of processed attachments.
		 *
		 * @var array
		 */
		public $processed_attachments = [];

		/**
		 * List of post orphans.
		 *
		 * @var array
		 */
		public $post_orphans = [];

		/**
		 * Whether to fetch attachments.
		 *
		 * @var bool
		 */
		public $fetch_attachments = true;

		/**
		 * URL remap.
		 *
		 * @var array
		 */
		public $url_remap = [];

		/**
		 * Featured images.
		 *
		 * @var array
		 */
		public $featured_images = [];

		/**
		 * The main controller for the actual import stage.
		 *
		 * @param string $file Path to the WXR file for importing.
		 */
		public function import( $file ) {
			add_filter( 'vc_import_post_meta_key', [
				$this,
				'is_valid_meta_key',
			] );
			add_filter( 'http_request_timeout', [
				$this,
				'bump_request_timeout',
			] );

			$this->import_start( $file );

			wp_suspend_cache_invalidation( true );
			$this->process_posts();
			wp_suspend_cache_invalidation( false );

			// update incorrect/missing information in the DB.
			$this->backfill_parents();
			$this->backfill_attachment_urls();
			$this->remap_featured_images();
			do_action( 'vc_import_pre_end', $this );
			$this->import_end();
		}

		/**
		 * Parses the WXR file and prepares us for the task of processing parsed data
		 *
		 * @param string $file Path to the WXR file for importing.
		 */
		public function import_start( $file ) {
			if ( ! is_file( $file ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'js_composer' ) . '</strong><br />';
				echo esc_html__( 'The file does not exist, please try again.', 'js_composer' ) . '</p>';
				die();
			}

			$import_data = $this->parse( $file );

			if ( is_wp_error( $import_data ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'js_composer' ) . '</strong><br />';
				// WP_Error $import_data.
				echo esc_html( $import_data->get_error_message() ) . '</p>';
				die();
			}

			$this->version = $import_data['version'];
			$this->posts = $import_data['posts'];
			$this->base_url = esc_url( $import_data['base_url'] );

			wp_defer_term_counting( true );
			wp_defer_comment_counting( true );

			do_action( 'vc_import_start' );
		}

		/**
		 * Performs post-import cleanup of files and the cache
		 */
		public function import_end() {
			wp_import_cleanup( $this->id );

			wp_cache_flush();
			foreach ( get_taxonomies() as $tax ) {
				delete_option( "{$tax}_children" );
				_get_term_hierarchy( $tax );
			}

			wp_defer_term_counting( false );
			wp_defer_comment_counting( false );

			do_action( 'vc_import_end' );

			return true;
		}

		/**
		 * Handles the WXR upload and initial parsing of the file to prepare for
		 * displaying author import options
		 *
		 * @return bool False if error uploading or invalid file, true otherwise
		 */
		public function handle_upload() {
			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'js_composer' ) . '</strong><br />';
				echo esc_html( $file['error'] ) . '</p>';

				return false;
			} elseif ( ! file_exists( $file['file'] ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'js_composer' ) . '</strong><br />';
				printf( esc_html__( 'The export file could not be found at %s. It is likely that this was caused by a permissions problem.', 'js_composer' ), '<code>' . esc_html( $file['file'] ) . '</code>' );
				echo '</p>';

				return false;
			}

			$this->id = (int) $file['id'];
			$import_data = $this->parse( $file['file'] );
			if ( is_wp_error( $import_data ) ) {
				echo '<p><strong>' . esc_html__( 'Sorry, there has been an error.', 'js_composer' ) . '</strong><br />';
				// WP_Error $import_data - error holder.
				echo esc_html( $import_data->get_error_message() ) . '</p>';

				return false;
			}

			$this->version = $import_data['version'];
			if ( $this->version > $this->max_wxr_version ) {
				echo '<div class="error"><p><strong>';
				printf( esc_html__( 'This WXR file (version %s) may not be supported by this version of the importer. Please consider updating.', 'js_composer' ), esc_html( $import_data['version'] ) );
				echo '</strong></p></div>';
			}

			return true;
		}

		/**
		 * Create new posts based on import information
		 *
		 * Posts marked as having a parent which doesn't exist will become top level items.
		 * Doesn't create a new post if: the post type doesn't exist, the given post ID
		 * is already noted as imported or a post with the same title and date already exists.
		 * Note that new/updated terms, comments and meta are imported for the last of the above.
		 */
		public function process_posts() {
			$status = [];
			$this->posts = apply_filters( 'vc_import_posts', $this->posts );
			if ( is_array( $this->posts ) && ! empty( $this->posts ) ) {
				foreach ( $this->posts as $post ) {
					$post = apply_filters( 'vc_import_post_data_raw', $post );

					if ( ! post_type_exists( $post['post_type'] ) ) {
						$status[] = [
							'success' => false,
							'code' => 'invalid_post_type',
							'post' => $post,
						];
						do_action( 'vc_import_post_exists', $post );
						continue;
					}

					if ( isset( $this->processed_posts[ $post['post_id'] ] ) && ! empty( $post['post_id'] ) ) {
						continue;
					}

					if ( 'auto-draft' === $post['status'] ) {
						continue;
					}

					$post_parent = (int) $post['post_parent'];
					if ( $post_parent ) {
						// if we already know the parent, map it to the new local ID.
						if ( isset( $this->processed_posts[ $post_parent ] ) ) {
							$post_parent = $this->processed_posts[ $post_parent ];
							// otherwise record the parent for later.
						} else {
							$this->post_orphans[ intval( $post['post_id'] ) ] = $post_parent;
							$post_parent = 0;
						}
					}

					// map the post author.
					$author = (int) get_current_user_id();

					$postdata = [
						'post_author' => $author,
						'post_date' => $post['post_date'],
						'post_date_gmt' => $post['post_date_gmt'],
						'post_content' => $post['post_content'],
						'post_excerpt' => $post['post_excerpt'],
						'post_title' => $post['post_title'],
						'post_status' => $post['status'],
						'post_name' => $post['post_name'],
						'comment_status' => $post['comment_status'],
						'ping_status' => $post['ping_status'],
						'guid' => $post['guid'],
						'post_parent' => $post_parent,
						'menu_order' => $post['menu_order'],
						'post_type' => $post['post_type'],
						'post_password' => $post['post_password'],
					];

					$original_post_ID = $post['post_id'];
					$postdata = apply_filters( 'vc_import_post_data_processed', $postdata, $post, $this );

					$postdata = wp_slash( $postdata );

					if ( 'attachment' === $postdata['post_type'] ) {
						$remote_url = ! empty( $post['attachment_url'] ) ? $post['attachment_url'] : $post['guid'];

						// try to use _wp_attached file for upload folder placement to ensure the same location as the export site.
						// e.g. location is 2003/05/image.jpg but the attachment post_date is 2010/09, see media_handle_upload().
						$postdata['upload_date'] = $post['post_date'];
						if ( isset( $post['postmeta'] ) ) {
							foreach ( $post['postmeta'] as $meta ) {
								if ( '_wp_attached_file' === $meta['key'] ) {
									if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) ) {
										$postdata['upload_date'] = $matches[0];
									}
									break;
								}
							}
						}

						$post_id = $this->process_attachment( $postdata, $remote_url, $original_post_ID );
					} else {
						$post_id = wp_insert_post( $postdata, true );
						do_action( 'vc_import_insert_post', $post_id, $original_post_ID, $postdata, $post );
						// map pre-import ID to local ID.
						$this->processed_posts[ intval( $post['post_id'] ) ] = (int) $post_id;
					}

					if ( is_wp_error( $post_id ) ) {
						$status[] = [
							'success' => false,
							'code' => 'wp_error',
							'post' => $post_id,
						];
						continue;
					}

					if ( true === $post['is_sticky'] ) {
						stick_post( $post_id );
					}

					if ( ! isset( $post['postmeta'] ) ) {
						$post['postmeta'] = [];
					}

					$post['postmeta'] = apply_filters( 'vc_import_post_meta', $post['postmeta'], $post_id, $post );

					// add/update post meta.
					if ( ! empty( $post['postmeta'] ) ) {
						foreach ( $post['postmeta'] as $meta ) {
							$key = apply_filters( 'vc_import_post_meta_key', $meta['key'], $post_id, $post );
							$value = false;

							if ( '_edit_last' === $key ) {
								$key = false;
							}

							if ( $key ) {
								// export gets meta straight from the DB so could have a serialized string.
								if ( ! $value ) {
									$value = maybe_unserialize( $meta['value'] );
								}

								add_post_meta( $post_id, $key, $value );
								do_action( 'vc_import_post_meta', $post_id, $key, $value );

								// if the post has a featured image, take note of this in case of remap.
								if ( '_thumbnail_id' === $key ) {
									$this->featured_images[ $post_id ] = (int) $value;
								}
							}
						}
					}
				}
			}
			unset( $this->posts );

			return $status;
		}

		/**
		 * If fetching attachments is enabled then attempt to create a new attachment
		 *
		 * @param array $post Attachment post details from WXR.
		 * @param string $url URL to fetch attachment from.
		 * @param int $original_post_ID
		 * @return int|\WP_Error Post ID on success, WP_Error otherwise.
		 */
		public function process_attachment( $post, $url, $original_post_ID ) {
			if ( ! $this->fetch_attachments ) {
				return new WP_Error( 'attachment_processing_error', esc_html__( 'Fetching attachments is not enabled', 'js_composer' ) );
			}

			// if the URL is absolute, but does not contain address, then upload it assuming base_site_url.
			if ( preg_match( '|^/[\w\W]+$|', $url ) ) {
				$url = rtrim( $this->base_url, '/' ) . $url;
			}

			$upload = $this->fetch_remote_file( $url, $post );
			if ( is_wp_error( $upload ) ) {
				return $upload;
			}

			$info = wp_check_filetype( $upload['file'] );
			if ( $info ) {
				$post['post_mime_type'] = $info['type'];
			} else {
				return new WP_Error( 'attachment_processing_error', esc_html__( 'Invalid file type', 'js_composer' ) );
			}

			$post['guid'] = $upload['url'];

			// as per wp-admin/includes/upload.php.
			$post_id = wp_insert_attachment( $post, $upload['file'] );
			wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );

			// remap resized image URLs, works by stripping the extension and remapping the URL stub.
			if ( preg_match( '!^image/!', $info['type'] ) ) {
				$parts = pathinfo( $url );
				$name = basename( $parts['basename'], ".{$parts['extension']}" ); // PATHINFO_FILENAME in PHP 5.2.

				$parts_new = pathinfo( $upload['url'] );
				$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );

				$this->url_remap[ $parts['dirname'] . '/' . $name ] = $parts_new['dirname'] . '/' . $name_new;
			}
			$this->processed_attachments[ intval( $original_post_ID ) ] = (int) $post_id;

			return $post_id;
		}

		/**
		 * Process url.
		 *
		 * @param string $url
		 * @param bool $file_path
		 * @return array|bool|\Requests_Utility_CaseInsensitiveDictionary
		 */
		private function wp_get_http( $url, $file_path = false ) {
			set_time_limit( 60 );

			$options = [];
			$options['redirection'] = 5;

			$options['method'] = 'GET';

			$response = wp_safe_remote_request( $url, $options );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$headers = wp_remote_retrieve_headers( $response );
			$headers['response'] = wp_remote_retrieve_response_code( $response );

			if ( ! $file_path ) {
				return $headers;
			}

			// GET request - write it to the supplied filename
			// @codingStandardsIgnoreLine
			$out_fp = fopen( $file_path, 'w' );
			if ( ! $out_fp ) {
				return $headers;
			}

			// @codingStandardsIgnoreLine
			fwrite( $out_fp, wp_remote_retrieve_body( $response ) );
			// @codingStandardsIgnoreLine
			fclose( $out_fp );
			clearstatcache();

			return $headers;
		}

		/**
		 * Attempt to download a remote file attachment
		 *
		 * @param string $url URL of item to fetch.
		 * @param array $post Attachment details.
		 * @return array|WP_Error Local file location details on success, WP_Error otherwise.
		 */
		public function fetch_remote_file( $url, $post ) {
			// extract the file name and extension from the url.
			$file_name = basename( $url );

			// get placeholder file in the upload dir with a unique, sanitized filename.
			$upload = wp_upload_bits( $file_name, null, '', $post['upload_date'] );
			if ( $upload['error'] ) {
				return new WP_Error( 'upload_dir_error', $upload['error'] );
			}

			// fetch the remote url and write it to the placeholder file.
			$headers = $this->wp_get_http( $url, $upload['file'] );

			// request failed.
			if ( ! $headers ) {
				// @codingStandardsIgnoreLine
				@unlink( $upload['file'] );

				return new WP_Error( 'import_file_error', esc_html__( 'Remote server did not respond', 'js_composer' ) );
			}

			// make sure the fetch was successful.
			if ( intval( $headers['response'] ) !== 200 ) {
				// @codingStandardsIgnoreLine
				@unlink( $upload['file'] );

				return new WP_Error( 'import_file_error', sprintf( esc_html__( 'Remote server returned error response %1$d %2$s', 'js_composer' ), esc_html( $headers['response'] ), get_status_header_desc( $headers['response'] ) ) );
			}

			$filesize = filesize( $upload['file'] );

			if ( isset( $headers['content-length'] ) && intval( $headers['content-length'] ) !== $filesize ) {
				// @codingStandardsIgnoreLine
				@unlink( $upload['file'] );

				return new WP_Error( 'import_file_error', esc_html__( 'Remote file is incorrect size', 'js_composer' ) );
			}

			if ( ! $filesize ) {
				// @codingStandardsIgnoreLine
				@unlink( $upload['file'] );

				return new WP_Error( 'import_file_error', esc_html__( 'Zero size file downloaded', 'js_composer' ) );
			}

			$max_size = (int) $this->max_attachment_size();
			if ( ! empty( $max_size ) && $filesize > $max_size ) {
				// @codingStandardsIgnoreLine
				@unlink( $upload['file'] );

				return new WP_Error( 'import_file_error', sprintf( esc_html__( 'Remote file is too large, limit is %s', 'js_composer' ), size_format( $max_size ) ) );
			}

			// keep track of the old and new urls so we can substitute them later.
			$this->url_remap[ $url ] = $upload['url'];
			$this->url_remap[ $post['guid'] ] = $upload['url']; // r13735, really needed?.
			// keep track of the destination if the remote url is redirected somewhere else.
			if ( isset( $headers['x-final-location'] ) && $headers['x-final-location'] !== $url ) {
				$this->url_remap[ $headers['x-final-location'] ] = $upload['url'];
			}

			return $upload;
		}

		/**
		 * Attempt to associate posts and menu items with previously missing parents
		 *
		 * An imported post's parent may not have been imported when it was first created
		 * so try again. Similarly for child menu items and menu items which were missing
		 * the object (e.g. post) they represent in the menu
		 */
		public function backfill_parents() {
			global $wpdb;

			// find parents for post orphans.
			foreach ( $this->post_orphans as $child_id => $parent_id ) {
				$local_child_id = false;
				$local_parent_id = false;
				if ( isset( $this->processed_posts[ $child_id ] ) ) {
					$local_child_id = $this->processed_posts[ $child_id ];
				}
				if ( isset( $this->processed_posts[ $parent_id ] ) ) {
					$local_parent_id = $this->processed_posts[ $parent_id ];
				}

				if ( $local_child_id && $local_parent_id ) {
					// @codingStandardsIgnoreLine
					$wpdb->update( $wpdb->posts, array( 'post_parent' => $local_parent_id ), array( 'ID' => $local_child_id ), '%d', '%d' );
				}
			}
		}

		/**
		 * Use stored mapping information to update old attachment URLs
		 */
		public function backfill_attachment_urls() {
			global $wpdb;
			// make sure we do the longest urls first, in case one is a substring of another.
			uksort( $this->url_remap, [
				$this,
				'cmpr_strlen',
			] );

			foreach ( $this->url_remap as $from_url => $to_url ) {
				// remap urls in post_content.
				// @codingStandardsIgnoreLine
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $from_url, $to_url ) );
				// remap enclosure urls.
				// @codingStandardsIgnoreLine
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key='enclosure'", $from_url, $to_url ) );
			}
		}

		/**
		 * Update _thumbnail_id meta to new, imported attachment IDs
		 */
		public function remap_featured_images() {
			// cycle through posts that have a featured image.
			foreach ( $this->featured_images as $post_id => $value ) {
				if ( isset( $this->processed_posts[ $value ] ) ) {
					$new_id = $this->processed_posts[ $value ];
					// only update if there's a difference.
					if ( $new_id !== $value ) {
						update_post_meta( $post_id, '_thumbnail_id', $new_id );
					}
				}
			}
		}

		/**
		 * Parse a WXR file
		 *
		 * @param string $file Path to WXR file for parsing.
		 * @return array Information gathered from the WXR file
		 */
		public function parse( $file ) {
			$parser = new Vc_WXR_Parser();

			return $parser->parse( $file );
		}

		/**
		 * Decide if the given meta key maps to information we will want to import
		 *
		 * @param string $key The meta key to check.
		 * @return string|bool The key if we do want to import, false if not
		 */
		public function is_valid_meta_key( $key ) {
			// skip attachment metadata since we'll regenerate it from scratch.
			// skip _edit_lock as not relevant for import.
			if ( in_array( $key, [
				'_wp_attached_file',
				'_wp_attachment_metadata',
				'_edit_lock',
			], true ) ) {
				return false;
			}

			return $key;
		}

		/**
		 * Decide whether or not the importer is allowed to create users.
		 * Default is true, can be filtered via import_allow_create_users
		 *
		 * @return bool True if creating users is allowed
		 */
		public function allow_create_users() {
			return false;
		}

		/**
		 * Decide whether or not the importer should attempt to download attachment files.
		 * Default is true, can be filtered via import_allow_fetch_attachments. The choice
		 * made at the import options screen must also be true, false here hides that checkbox.
		 *
		 * @return bool True if downloading attachments is allowed
		 */
		public function allow_fetch_attachments() {
			return apply_filters( 'vc_import_allow_fetch_attachments', true );
		}

		/**
		 * Decide what the maximum file size for downloaded attachments is.
		 * Default is 0 (unlimited), can be filtered via import_attachment_size_limit
		 *
		 * @return int Maximum attachment file size to import
		 */
		public function max_attachment_size() {
			return apply_filters( 'vc_import_attachment_size_limit', 0 );
		}

		/**
		 * Return the difference in length between two strings.
		 *
		 * @param string $a
		 * @param string $b
		 * @return int
		 */
		public function cmpr_strlen( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	}
}
