<?php

namespace MainWP\Child;

class MainWP_Client_Report {

	public static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_filter( 'wp_mainwp_stream_current_agent', array( $this, 'current_agent' ), 10, 1 );
	}

	public function init() {
		add_filter( 'mainwp_site_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_child_log', array( 'MainWP_Client_Report', 'do_reports_log' ) );
	}

	public function current_agent( $agent ) {
		if ( isset( $_POST['function'] ) && isset( $_POST['mainwpsignature'] ) ) {
			$agent = '';
		}
		return $agent;
	}

	public function sync_others_data( $information, $data = array() ) {
		if ( isset( $data['syncClientReportData'] ) && $data['syncClientReportData'] ) {
			$creport_sync_data = array();
			$firsttime         = get_option( 'mainwp_creport_first_time_activated' );
			if ( false !== $firsttime ) {
				$creport_sync_data['firsttime_activated'] = $firsttime;
			}
			if ( ! empty( $creport_sync_data ) ) {
				$information['syncClientReportData'] = $creport_sync_data;
			}
		}
		return $information;
	}

	public static function do_reports_log( $ext = '' ) {
		switch ( $ext ) {
			case 'backupbuddy':
				\MainWP_Child_Back_Up_Buddy::instance()->do_reports_log( $ext );
				break;
			case 'backupwordpress':
				\MainWP_Child_Back_Up_WordPress::instance()->do_reports_log( $ext );
				break;
			case 'backwpup':
				\MainWP_Child_Back_WP_Up::instance()->do_reports_log( $ext );
				break;
			case 'wordfence':
				\MainWP_Child_Wordfence::instance()->do_reports_log( $ext );
				break;
			case 'wptimecapsule':
				\MainWP_Child_Timecapsule::instance()->do_reports_log( $ext );
				break;
		}
	}

	public function action() {

		$information = array();

		if ( ! function_exists( 'wp_mainwp_stream_get_instance' ) ) {
			$information['error'] = __( 'No MainWP Child Reports plugin installed.', 'mainwp-child' );
			mainwp_child_helper()->write( $information );
		}

		if ( isset( $_POST['mwp_action'] ) ) {
			switch ( $_POST['mwp_action'] ) {
				case 'save_sucuri_stream':
					$information = $this->save_sucuri_stream();
					break;
				case 'save_backup_stream':
					$information = $this->save_backup_stream();
					break;
				case 'get_stream':
					$information = $this->get_stream();
					break;
				case 'set_showhide':
					$information = $this->set_showhide();
					break;
			}
		}
		mainwp_child_helper()->write( $information );
	}

	public function save_sucuri_stream() {
		$scan_data = isset( $_POST['scan_data'] ) ? $_POST['scan_data'] : '';
		do_action( 'mainwp_reports_sucuri_scan', $_POST['result'], $_POST['scan_status'], $scan_data, isset( $_POST['scan_time'] ) ? $_POST['scan_time'] : 0 );
		return true;
	}

	public function save_backup_stream() {
		do_action( 'mainwp_backup', $_POST['destination'], $_POST['message'], $_POST['size'], $_POST['status'], $_POST['type'] );

		return true;
	}

	public function is_backup_action( $action ) {
		if ( in_array( $action, array( 'mainwp_backup', 'backupbuddy_backup', 'backupwordpress_backup', 'backwpup_backup', 'updraftplus_backup', 'wptimecapsule_backup', 'wpvivid_backup' ) ) ) {
			return true;
		}
		return false;
	}

	public function get_compatible_context( $context ) {
		// convert context name of tokens to context name saved in child report.
		// some context are not difference.
		$mapping_contexts = array(
			'comment'     => 'comments', // actual context values: post, page.
			'plugin'      => 'plugins',
			'users'       => 'profiles',
			'user'        => 'profiles',
			'session'     => 'sessions',
			'setting'     => 'settings',
			'theme'       => 'themes',
			'posts'       => 'post',
			'pages'       => 'page',
			'widgets'     => 'widgets',
			'widget'      => 'widgets',
			'menu'        => 'menus',
			'backups'     => 'backups',
			'backup'      => 'backups',
			'sucuri'      => 'sucuri_scan',
			'maintenance' => 'mainwp_maintenance',
			'wordfence'   => 'wordfence_scan',
			'backups'     => 'backups',
			'backup'      => 'backups',
			'media'       => 'media',
		);

		$context = isset( $mapping_contexts[ $context ] ) ? $mapping_contexts[ $context ] : $context;
		return strtolower( $context );
	}


	public function get_connector_by_compatible_context( $context ) {

		$connector = '';
		
		$mapping_connectors = array(
			'plugins' => 'installer',
			'themes' => 'installer',
			'WordPress' => 'installer',
			'profiles' => 'users',
			'comments' => 'comments',
			'settings' => 'settings',
			'post' => 'posts',
			'page' => 'posts',
			'widgets' => 'widgets',
			'menus' => 'menus',
			'backups' => 'mainwp_backups',
			'sucuri_scan' => 'mainwp_sucuri',
			'mainwp_maintenance' => 'mainwp_maintenance',			
			'wordfence_scan' => 'mainwp_wordfence',
			'media' => 'media'
		);
		
		if ( isset( $mapping_connectors[ $context ] ) )
			$connector = $mapping_connectors[ $context ];
		
		return $connector;
	}

	public function get_compatible_action( $action, $context = '' ) {

		$mapping_actions = array(
			'restored' => 'untrashed',
			'spam'     => 'spammed',
		);

		if ( isset( $mapping_actions[ $action ] ) ) {
			return $mapping_actions[ $action ];
		}

		if ( 'mainwp_maintenance' == $context ) {
			if ( 'process' == $action ) {
				$action = 'maintenance';
			}
		} elseif ( 'sucuri_scan' == $context ) {
			if ( 'checks' == $action ) {
				$action = 'sucuri_scan';
			}
		} elseif ( 'wordfence_scan' == $context ) {
			if ( 'scan' == $action ) {
				$action = 'wordfence_scan';
			}
		}
		return $action;
	}

	public function get_stream() {
		
		$allowed_params = array(
			'connector',
			'context',
			'action',
			'author',
			'author_role',
			'object_id',
			'search',
			'date',
			'date_from',
			'date_to',
			'record__in',
			'blog_id',
			'ip',
		);

		$sections = isset( $_POST['sections'] ) ? maybe_unserialize( base64_decode( $_POST['sections'] ) ) : array(); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode function is used for begin reasons.
		if ( ! is_array( $sections ) ) {
			$sections = array();
		}

		$other_tokens = isset( $_POST['other_tokens'] ) ? maybe_unserialize( base64_decode( $_POST['other_tokens'] ) ) : array(); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode function is used for begin reasons.
		if ( ! is_array( $other_tokens ) ) {
			$other_tokens = array();
		}

		unset( $_POST['sections'] );
		unset( $_POST['other_tokens'] );

		$args = array();
		foreach ( $allowed_params as $param ) {
			$paramval = wp_mainwp_stream_filter_input( INPUT_POST, $param );
			if ( $paramval || '0' === $paramval ) {
				$args[ $param ] = $paramval;
			}
		}

		foreach ( $args as $arg => $val ) {
			if ( ! in_array( $arg, $allowed_params ) ) {
				unset( $args[ $arg ] );
			}
		}

		$exclude_connector_posts = true;
		if ( isset( $sections['body'] ) && isset( $sections['body']['section_token'] ) && is_array( $sections['body']['section_token'] ) ) {
			foreach ( $sections['body']['section_token'] as $sec ) {
				if ( false !== strpos( $sec, '[section.posts' ) || false !== strpos( $sec, '[section.pages' ) ) {
					$exclude_connector_posts = false;
					break;
				}
			}
		}
		if ( $exclude_connector_posts ) {
			if ( isset( $sections['header'] ) && isset( $sections['header']['section_token'] ) && is_array( $sections['header']['section_token'] ) ) {
				foreach ( $sections['header']['section_token'] as $sec ) {
					if ( false !== strpos( $sec, '[section.posts' ) || false !== strpos( $sec, '[section.pages' ) ) {
						$exclude_connector_posts = false;
						break;
					}
				}
			}
		}
		if ( $exclude_connector_posts ) {
			if ( isset( $sections['footer'] ) && isset( $sections['footer']['section_token'] ) && is_array( $sections['footer']['section_token'] ) ) {
				foreach ( $sections['footer']['section_token'] as $sec ) {
					if ( false !== strpos( $sec, '[section.posts' ) || false !== strpos( $sec, '[section.pages' ) ) {
						$exclude_connector_posts = false;
						break;
					}
				}
			}
		}
		if ( $exclude_connector_posts ) {
			if ( isset( $other_tokens['body'] ) && is_array( $other_tokens['body'] ) ) {
				foreach ( $other_tokens['body'] as $sec ) {
					if ( false !== strpos( $sec, '[post.' ) || false !== strpos( $sec, '[page.' ) ) {
						$exclude_connector_posts = false;
						break;
					}
				}
			}
		}
		if ( $exclude_connector_posts ) {
			if ( isset( $other_tokens['header'] ) && is_array( $other_tokens['header'] ) ) {
				foreach ( $other_tokens['header'] as $sec ) {
					if ( false !== strpos( $sec, '[post.' ) || false !== strpos( $sec, '[page.' ) ) {
						$exclude_connector_posts = false;
						break;
					}
				}
			}
		}
		if ( $exclude_connector_posts ) {
			if ( isset( $other_tokens['footer'] ) && is_array( $other_tokens['footer'] ) ) {
				foreach ( $other_tokens['footer'] as $sec ) {
					if ( false !== strpos( $sec, '[post.' ) || false !== strpos( $sec, '[page.' ) ) {
						$exclude_connector_posts = false;
						break;
					}
				}
			}
		}
		if ( $exclude_connector_posts ) {
			$args['connector__not_in'] = array( 'posts' );
		}

		$args['action__not_in'] = array( 'login' );

		$args['with-meta'] = 1;

		if ( isset( $args['date_from'] ) ) {
			$args['date_from'] = date( 'Y-m-d', $args['date_from'] ); // phpcs:ignore -- local time.
		}

		if ( isset( $args['date_to'] ) ) {
			$args['date_to'] = date( 'Y-m-d', $args['date_to'] ); // phpcs:ignore -- local time.
		}

		if ( MainWP_Child_Branding::instance()->is_branding() ) {
			$args['hide_child_reports'] = 1;
		}

		$args['records_per_page'] = 9999;

		$records = wp_mainwp_stream_get_instance()->db->query( $args );

		if ( ! is_array( $records ) ) {
			$records = array();
		}

		// fix invalid data, or skip records!
		$skip_records = array();

		// fix for incorrect posts created logs!
		// query created posts from WP posts data to simulate records logging for created posts.
		if ( isset( $_POST['direct_posts'] ) && ! empty( $_POST['direct_posts'] ) ) {

			$args = array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'date_query'  => array(
					'column'    => 'post_date',
					'after'     => $args['date_from'],
					'before'    => $args['date_to'],
				),
			);

			$result                = new \WP_Query( $args );
			$records_created_posts = $result->posts;

			if ( $records_created_posts ) {

				$count_records = count( $records );
				for ( $i = 0; $i < $count_records; $i++ ) {
					$record = $records[ $i ];
					if ( 'posts' == $record->connector && 'post' == $record->context && 'created' == $record->action ) {
						if ( ! in_array( $record->ID, $skip_records ) ) {
							$skip_records[] = $record->ID; // so avoid this created logging, will use logging query from posts data.
						}
					}
				}

				$post_authors = array();

				foreach ( $records_created_posts as $_post ) {
					$au_id = $_post->post_author;
					if ( ! isset( $post_authors[ $au_id ] ) ) {
						$au                     = get_user_by( 'id', $au_id );
						$post_authors[ $au_id ] = $au->display_name;
					}
					$au_name = $post_authors[ $au_id ];

					// simulate logging created posts record.
					$stdObj            = new \stdClass();
					$stdObj->ID        = 0; // simulate ID value.
					$stdObj->connector = 'posts';
					$stdObj->context   = 'post';
					$stdObj->action    = 'created';
					$stdObj->created   = $_post->post_date;
					$stdObj->meta      = array(
						'post_title' => array( $_post->post_title ),
						'user_meta'  => array( $au_name ),
					);

					$records[] = $stdObj;
				}
			}
		}

		if ( isset( $other_tokens['header'] ) && is_array( $other_tokens['header'] ) ) {
			$other_tokens_data['header'] = $this->get_other_tokens_data( $records, $other_tokens['header'], $skip_records );
		}

		if ( isset( $other_tokens['body'] ) && is_array( $other_tokens['body'] ) ) {
			$other_tokens_data['body'] = $this->get_other_tokens_data( $records, $other_tokens['body'], $skip_records );
		}

		if ( isset( $other_tokens['footer'] ) && is_array( $other_tokens['footer'] ) ) {
			$other_tokens_data['footer'] = $this->get_other_tokens_data( $records, $other_tokens['footer'], $skip_records );
		}

		$sections_data = array();

		if ( isset( $sections['header'] ) && is_array( $sections['header'] ) && ! empty( $sections['header'] ) ) {
			foreach ( $sections['header']['section_token'] as $index => $sec ) {
				$tokens                            = $sections['header']['section_content_tokens'][ $index ];
				$sections_data['header'][ $index ] = $this->get_section_loop_data( $records, $tokens, $sec, $skip_records );
			}
		}
		if ( isset( $sections['body'] ) && is_array( $sections['body'] ) && ! empty( $sections['body'] ) ) {
			foreach ( $sections['body']['section_token'] as $index => $sec ) {
				$tokens                          = $sections['body']['section_content_tokens'][ $index ];
				$sections_data['body'][ $index ] = $this->get_section_loop_data( $records, $tokens, $sec, $skip_records );
			}
		}
		if ( isset( $sections['footer'] ) && is_array( $sections['footer'] ) && ! empty( $sections['footer'] ) ) {
			foreach ( $sections['footer']['section_token'] as $index => $sec ) {
				$tokens                            = $sections['footer']['section_content_tokens'][ $index ];
				$sections_data['footer'][ $index ] = $this->get_section_loop_data( $records, $tokens, $sec, $skip_records );
			}
		}

		$information = array(
			'other_tokens_data' => $other_tokens_data,
			'sections_data'     => $sections_data,
		);

		return $information;
	}

	public function get_other_tokens_data( $records, $tokens, &$skip_records ) {

		$token_values = array();

		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}

		$backups_created_time_to_fix = array();
		foreach ( $tokens as $token ) {

			if ( isset( $token_values[ $token ] ) ) {
				continue;
			}

			$str_tmp   = str_replace( array( '[', ']' ), '', $token );
			$array_tmp = explode( '.', $str_tmp );

			if ( is_array( $array_tmp ) ) {
				$context = '';
				$action  = '';
				$data    = '';
				if ( 2 === count( $array_tmp ) ) {
					list( $context, $data ) = $array_tmp;
				} elseif ( 3 === count( $array_tmp ) ) {
					list( $context, $action, $data ) = $array_tmp;
				}

				$context = $this->get_compatible_context( $context );

				// to compatible with new version of child report.
				// to check condition for grabbing report data.
				$connector = $this->get_connector_by_compatible_context( $context );

				$action = $this->get_compatible_action( $action, $context );

				// custom values.
				if ( 'profiles' == $context ) {
					if ( 'created' == $action || 'deleted' == $action ) {
						$context = 'users'; // see class-connector-user.php.
					}
				}

				switch ( $data ) {
					case 'count':
						$count = 0;
						foreach ( $records as $record ) {

							// check connector.
							if ( 'editor' == $record->connector ) {
								if ( ! in_array( $context, array( 'plugins', 'themes' ) ) || 'updated' !== $action ) {
									continue;
								}
							} elseif ( $connector !== $record->connector ) {
								continue;
							}

							$valid_context = false;
							// check context.
							if ( 'comments' == $context ) { // multi values.
								$comment_contexts = array( 'post', 'page' );
								if ( ! in_array( $record->context, $comment_contexts ) ) {
									continue;
								}
								$valid_context = true;
							} elseif ( 'post' === $context && 'created' === $action ) {
								if ( in_array( $record->ID, $skip_records ) ) {
									continue;
								}
								$valid_context = true;
							} elseif ( 'menus' == $context ) {
								$valid_context = true; // ok, pass, don't check context.
							} elseif ( 'editor' == $record->connector ) {
								$valid_context = true; // ok, pass, checked above.
							} elseif ( 'media' == $connector && 'media' == $record->connector ) {
								$valid_context = true; // ok, pass, do not check context.
							} elseif ( 'widgets' == $connector && 'widgets' == $record->connector ) {
								$valid_context = true; // ok, pass, don't check context.
							}

							if ( ! $valid_context || strtolower( $record->context ) !== $context ) {
								continue;
							}

							// custom action value.
							if ( 'widgets' == $connector ) {
								if ( 'deleted' == $action ) {
									$action = 'removed'; // action saved in database.
								}
							}

							// check action.
							if ( 'backups' === $context ) {
								if ( ! $this->is_backup_action( $record->action ) ) {
									continue;
								}
								$created = strtotime( $record->created );
								if ( in_array( $created, $backups_created_time_to_fix ) ) {
									if ( ! in_array( $record->ID, $skip_records ) ) {
										$skip_records[] = $record->ID;
									}
									continue;
								} else {
									$backups_created_time_to_fix[] = $created;
								}
							} else {
								if ( $action !== $record->action ) {
									continue;
								}

								if ( 'updated' === $action && ( 'post' === $context || 'page' === $context ) ) {
									$new_status = $this->get_stream_meta_data( $record, 'new_status' );
									if ( 'draft' === $new_status ) {
										continue;
									}
								} elseif ( 'updated' === $action && ( 'themes' === $context || 'plugins' === $context ) ) {
									$name = $this->get_stream_meta_data( $record, 'name' );
									if ( empty( $name ) ) {
										if ( ! in_array( $record->ID, $skip_records ) ) {
											$skip_records[] = $record->ID;
										}
										continue;
									} else {
										$old_version = $this->get_stream_meta_data( $record, 'old_version' );
										$version     = $this->get_stream_meta_data( $record, 'version' );
										if ( version_compare( $version, $old_version, '<=' ) ) {
											if ( ! in_array( $record->ID, $skip_records ) ) {
												$skip_records[] = $record->ID;
											}
											continue;
										}
									}
								}
							}
							$count ++;
						}
						$token_values[ $token ] = $count;
						break;
				}
			}
		}

		return $token_values;
	}

	public function get_section_loop_data( $records, $tokens, $section, $skip_records = array() ) {

		$context = '';
		$action  = '';

		$str_tmp   = str_replace( array( '[', ']' ), '', $section );
		$array_tmp = explode( '.', $str_tmp );
		if ( is_array( $array_tmp ) ) {
			if ( 2 === count( $array_tmp ) ) {
				list( $str1, $context ) = $array_tmp;
			} elseif ( 3 === count( $array_tmp ) ) {
				list( $str1, $context, $action ) = $array_tmp;
			}
		}

		// get db $context value by mapping.
		$context = $this->get_compatible_context( $context );
		// to compatible with new version of child report.
		// to check condition for grabbing report data.
		$connector = $this->get_connector_by_compatible_context( $context );

		$action = $this->get_compatible_action( $action, $context );

		if ( 'profiles' == $context ) {
			if ( 'created' == $action || 'deleted' == $action ) {
				$context = 'users'; // see class-connector-user.php.
			}
		}

		return $this->get_section_loop_records( $records, $tokens, $connector, $context, $action, $skip_records );
	}

	public function get_section_loop_records( $records, $tokens, $connector, $context, $action, $skip_records ) {

		$maintenance_details = array(
			'revisions'     => __( 'Delete all post revisions', 'mainwp-child' ),
			'revisions_max' => __( 'Delete all post revisions, except for the last:', 'mainwp-child' ),
			'autodraft'     => __( 'Delete all auto draft posts', 'mainwp-child' ),
			'trashpost'     => __( 'Delete trash posts', 'mainwp-child' ),
			'spam'          => __( 'Delete spam comments', 'mainwp-child' ),
			'pending'       => __( 'Delete pending comments', 'mainwp-child' ),
			'trashcomment'  => __( 'Delete trash comments', 'mainwp-child' ),
			'tags'          => __( 'Delete tags with 0 posts associated', 'mainwp-child' ),
			'categories'    => __( 'Delete categories with 0 posts associated', 'mainwp-child' ),
			'optimize'      => __( 'Optimize database tables', 'mainwp-child' ),
		);

		$loops      = array();
		$loop_count = 0;
		foreach ( $records as $record ) {

			if ( in_array( $record->ID, $skip_records ) ) {
				continue;
			}

			if ( 'editor' == $record->connector ) {
				if ( ! in_array( $context, array( 'plugins', 'themes' ) ) || 'updated' !== $action ) {
					continue;
				}
			} elseif ( $connector !== $record->connector ) {
				continue;
			}

			$valid_context = false;

			if ( 'comments' == $context ) {
				$comment_contexts = array( 'post', 'page' );
				if ( ! in_array( $record->context, $comment_contexts ) ) {
					continue;
				}
				$valid_context = true;
			} elseif ( 'menus' == $context ) {
				$valid_context = true; // ok, pass, don't check context.
			} elseif ( 'editor' == $record->connector ) {
				$valid_context = true; // ok, pass, checked above.
			} elseif ( 'media' == $connector && 'media' == $record->connector ) {
				$valid_context = true; // ok, pass, do not check context.
			} elseif ( 'widgets' == $connector && 'widgets' == $record->connector ) {
				$valid_context = true; // ok, pass, don't check context.
			}

			if ( ! $valid_context || strtolower( $record->context ) !== $context ) {
				continue;
			}

			// custom action value!
			if ( 'widgets' == $connector ) {
				if ( 'deleted' == $action ) {
					$action = 'removed'; // action saved in database!
				}
			}

			if ( 'backups' == $context ) {
				if ( ! $this->is_backup_action( $record->action ) ) {
					continue;
				}
			} elseif ( $action !== $record->action ) {
				continue;
			}

			if ( 'updated' === $action && ( 'post' === $context || 'page' === $context ) ) {
				$new_status = $this->get_stream_meta_data( $record, 'new_status' );
				if ( 'draft' === $new_status ) { // avoid auto save post!
					continue;
				}
			}

			$token_values = array();

			foreach ( $tokens as $token ) {

				$data       = '';
				$token_name = str_replace( array( '[', ']' ), '', $token );
				$array_tmp  = explode( '.', $token_name );

				if ( 'user.name' === $token_name ) {
					$data = 'display_name';
				} else {
					if ( 1 === count( $array_tmp ) ) {
						list( $data ) = $array_tmp;
					} elseif ( 2 === count( $array_tmp ) ) {
						list( $str1, $data ) = $array_tmp;
					} elseif ( 3 === count( $array_tmp ) ) {
						list( $str1, $str2, $data ) = $array_tmp;
					}

					if ( 'version' === $data ) {
						if ( 'old' === $str2 ) {
							$data = 'old_version';
						} elseif ( 'current' === $str2 && 'WordPress' === $str1 ) {
							$data = 'new_version';
						}
					}
				}

				if ( 'role' === $data ) {
					$data = 'roles';
				}

				$tok_value = $this->get_section_loop_token_value( $record, $data, $context, $token );

				$token_values[ $token ] = $tok_value;

				if ( empty( $tok_value ) ) {
					$msg = 'MainWP Child Report:: skip empty value :: token :: ' . $token . ' :: record :: ' . print_r( $record, true );  // phpcs:ignore -- debug mode only.
					MainWP_Helper::log_debug( $msg );
				}
			}

			if ( ! empty( $token_values ) ) {
				$loops[ $loop_count ] = $token_values;
				$loop_count ++;
			}
		}
		return $loops;
	}

	public function get_section_loop_token_value( $record, $data, $context, $token ) {

		$tok_value = '';

		switch ( $data ) {
			case 'ID':
				$tok_value = $record->ID;
				break;
			case 'date':
				$tok_value = MainWP_Helper::format_date( MainWP_Helper::get_timestamp( strtotime( $record->created ) ) );
				break;
			case 'time':
				$tok_value = MainWP_Helper::format_time( MainWP_Helper::get_timestamp( strtotime( $record->created ) ) );
				break;
			case 'area':
				$data      = 'sidebar_name';
				$tok_value = $this->get_stream_meta_data( $record, $data );
				break;
			case 'name':
			case 'version':
			case 'old_version':
			case 'new_version':
			case 'display_name':
			case 'roles':
				if ( 'name' == $data ) {
					if ( 'profiles' == $context ) {
						$data = 'display_name';
					}
				}
					$tok_value = $this->get_stream_meta_data( $record, $data );
				break;
			case 'title':
				if ( 'comments' === $context ) {
					$tok_value = $record->summary;
				} else {
					if ( 'page' === $context || 'post' === $context ) {
						$data = 'post_title';
					} elseif ( 'menus' === $record->connector ) {
						$data = 'name';
					}
					$tok_value = $this->get_stream_meta_data( $record, $data );
				}
				break;
			case 'author':
				if ( 'comment' == $connector ) {
					$data = 'user_name';
				} else {
					$data = 'user_meta';
				}

				$value = $this->get_stream_meta_data( $record, $data );

				if ( empty( $value ) && 'comments' === $context ) {
					$value = __( 'Guest', 'mainwp-child' );
				}

				// check compatibility with old meta data.
				if ( empty( $value ) ) {
					$value = $this->get_stream_meta_data( $record, 'author_meta' );
				}

				$tok_value = $value;
				break;
			case 'status':
			case 'webtrust':
				if ( 'sucuri_scan' === $context ) {
					$tok_value = $this->get_sucuri_scan_token_value( $record, $data );					
				} else {
					$tok_value = $value;
				}
				break;
			case 'details':
			case 'result':
				if ( 'mainwp_maintenance' === $context && 'details' == $data ) {
					$tok_value = $this->get_mainwp_maintenance_token_value( $record, $data );
				} elseif ( 'wordfence_scan' === $context || 'mainwp_maintenance' === $context ) {
					$meta_value = $this->get_stream_meta_data( $record, $data );
					if ( 'wordfence_scan' === $context && 'result' == $data ) {
						// SUM_FINAL:Scan complete. You have xxx new issues to fix. See below.
						// SUM_FINAL:Scan complete. Congratulations, no new problems found.
						if ( stripos( $meta_value, 'Congratulations' ) ) {
							$meta_value = 'No issues detected';
						} elseif ( stripos( $meta_value, 'You have' ) ) {
							$meta_value = 'Issues Detected';
						} else {
							$meta_value = '';
						}
					}
					$tok_value = $meta_value;					
				}
				break;
			case 'type':
				if ( 'backups' === $context ) {
					$tok_value = $this->get_stream_meta_data( $record, $data );
				} else {
					$tok_value = $token;
				}
				break;
			default:
				$tok_value = 'N/A';
				break;
		}

		return $tok_value;
	}

	public function get_stream_meta_data( $record, $data ) {

		if ( empty( $record ) ) {
			return '';
		}

		$meta_key = $data;

		$value = '';

		if ( isset( $record->meta ) ) {
			$meta = $record->meta;

			if ( isset( $meta[ $meta_key ] ) ) {
				$value = $meta[ $meta_key ];
				$value = ( 'user_meta' == $meta_key && isset( $value[1] ) ) ? $value[1] : current( $value );

				if ( 'author_meta' === $meta_key ) {
					$value = maybe_unserialize( $value );
					if ( is_array( $value ) ) {
						$value = $value['display_name'];
						// fix empty author value!
						if ( empty( $value ) ) {
							if ( isset( $value['agent'] ) && ! empty( $value['agent'] ) ) {
								$value = $value['agent'];
							}
						}
					}
					if ( ! is_string( $value ) ) {
						$value = '';
					}
				}
			}
		}

		return $value;
	}

	private function get_sucuri_scan_token_value( $record, $data ) {
		$scan_data = $this->get_stream_meta_data( $record, 'scan_data' );					
		if ( ! empty( $scan_data ) ) {
			$scan_data = maybe_unserialize( base64_decode( $scan_data ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode function is used for begin reasons.
			if ( is_array( $scan_data ) ) {

				$blacklisted    = $scan_data['blacklisted'];
				$malware_exists = $scan_data['malware_exists'];

				$status = array();
				if ( $blacklisted ) {
					$status[] = __( 'Site Blacklisted', 'mainwp-child' ); }
				if ( $malware_exists ) {
					$status[] = __( 'Site With Warnings', 'mainwp-child' ); }

				if ( 'status' == $data ) {
					$tok_value = count( $status ) > 0 ? implode( ', ', $status ) : __( 'Verified Clear', 'mainwp-child' );
				} elseif ( 'webtrust' == $data ) {
					$tok_value = $blacklisted ? __( 'Site Blacklisted', 'mainwp-child' ) : __( 'Trusted', 'mainwp-child' );
				}
			}
		} else {
			$tok_value = $this->get_stream_meta_data( $record, $data );
		}
		return $tok_value;
	}
	
	private function get_mainwp_maintenance_token_value( $record, $data ) {
		
		$meta_value = $this->get_stream_meta_data( $record, $data );
		$meta_value = explode( ',', $meta_value );

		$details = array();

		if ( is_array( $meta_value ) ) {
			foreach ( $meta_value as $mt ) {
				if ( isset( $maintenance_details[ $mt ] ) ) {
					if ( 'revisions_max' == $mt ) {
						$max_revisions = $this->get_stream_meta_data( $record, 'revisions' );
						$dtl           = $maintenance_details['revisions_max'] . ' ' . $max_revisions;
					} else {
						$dtl = $maintenance_details[ $mt ];
					}
					$details[] = $dtl;
				}
			}
		}
		$tok_value = implode( ', ', $details );
		return $tok_value;
	}

	public function set_showhide() {
		$hide = isset( $_POST['showhide'] ) && ( 'hide' === $_POST['showhide'] ) ? 'hide' : '';
		MainWP_Child_Branding::instance()->save_branding_options( 'hide_child_reports', $hide );
		$information['result'] = 'SUCCESS';

		return $information;
	}

	public function creport_init() {

		$branding_opts = MainWP_Child_Branding::instance()->get_branding_options();
		$hide_nag      = false;

		if ( isset( $branding_opts['hide_child_reports'] ) && 'hide' == $branding_opts['hide_child_reports'] ) {
			add_filter( 'all_plugins', array( $this, 'creport_branding_plugin' ) );
			add_action( 'admin_menu', array( $this, 'creport_remove_menu' ) );
			$hide_nag = true;
		}

		if ( ! $hide_nag ) {
			// check child branding settings!
			if ( MainWP_Child_Branding::instance()->is_branding() ) {
				$hide_nag = true;
			}
		}

		if ( $hide_nag ) {
			add_filter( 'site_transient_update_plugins', array( &$this, 'remove_update_nag' ) );
			add_filter( 'mainwp_child_hide_update_notice', array( &$this, 'hide_update_notice' ) );
		}
	}

	public function hide_update_notice( $slugs ) {
		$slugs[] = 'mainwp-child-reports/mainwp-child-reports.php';
		return $slugs;
	}

	public function remove_update_nag( $value ) {
		if ( isset( $_POST['mainwpsignature'] ) ) {
			return $value;
		}

		if ( ! MainWP_Helper::is_screen_with_update() ) {
			return $value;
		}

		if ( isset( $value->response['mainwp-child-reports/mainwp-child-reports.php'] ) ) {
			unset( $value->response['mainwp-child-reports/mainwp-child-reports.php'] );
		}

		return $value;
	}


	public function creport_branding_plugin( $plugins ) {
		foreach ( $plugins as $key => $value ) {
			$plugin_slug = basename( $key, '.php' );
			if ( 'mainwp-child-reports' === $plugin_slug ) {
				unset( $plugins[ $key ] );
			}
		}

		return $plugins;
	}

	public function creport_remove_menu() {
		remove_menu_page( 'mainwp_wp_stream' );
	}
}
