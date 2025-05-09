<?php
/**
 * Name: Templates
 *
 * Menu Name: Pod Templates
 *
 * Description: An easy to use templating engine for Pods. Use {@field_name} magic tags to output values, within your HTML markup.
 *
 * Version: 2.3
 *
 * Category: Advanced
 *
 * Menu Page: edit.php?post_type=_pods_template
 * Menu Add Page: post-new.php?post_type=_pods_template
 *
 * @package    Pods\Components
 * @subpackage Templates
 */

// Pull in the functions
require_once plugin_dir_path( __FILE__ ) . '/includes/functions-view_template.php';
require_once plugin_dir_path( __FILE__ ) . '/includes/functions-pod_reference.php';

// Pull in the Frontier Template System
require_once plugin_dir_path( __FILE__ ) . 'class-pods_templates.php';

// Pull in Auto Template
require_once dirname( __FILE__ ) . '/includes/auto-template/Pods_Templates_Auto_Template_Settings.php';
new Pods_Templates_Auto_Template_Settings();

Pods_Templates_Frontier::get_instance();

use Pods\Whatsit\Template;

/**
 * Class Pods_Templates
 */
class Pods_Templates extends PodsComponent {

	/**
	 * Pods object
	 *
	 * @var object
	 *
	 * @since 2.0.0
	 */
	public static $obj = null;

	/**
	 * Whether to enable deprecated functionality based on old function usage
	 *
	 * @var bool
	 *
	 * @since 2.0.0
	 */
	public static $deprecated = false;

	/**
	 * Object type
	 *
	 * @var string
	 *
	 * @since 2.0.0
	 */
	private $object_type = '_pods_template';

	/**
	 * The capability type.
	 *
	 * @link https://codex.wordpress.org/Function_Reference/register_post_type
	 * @var string
	 */
	private $capability_type = 'pods_template';

	/**
	 * {@inheritdoc}
	 */
	public function init() {
		if ( ! did_action( 'init' ) ) {
			add_action( 'init', [ $this, 'register_config' ] );
		} else {
			$this->register_config();
		}

		if ( is_admin() ) {
			add_filter( 'post_updated_messages', array( $this, 'setup_updated_messages' ), 10, 1 );

			add_action( 'add_meta_boxes_' . $this->object_type, array( $this, 'edit_page_form' ) );

			add_filter( 'get_post_metadata', array( $this, 'get_meta' ), 10, 4 );
			add_filter( 'update_post_metadata', array( $this, 'save_meta' ), 10, 4 );

			add_action( 'pods_meta_save_pre_post__pods_template', array( $this, 'fix_filters' ), 10, 5 );
			add_action( 'post_updated', array( $this, 'clear_cache' ), 10, 3 );
			add_action( 'delete_post', array( $this, 'clear_cache' ), 10, 1 );
			add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 2 );
			add_filter( 'bulk_actions-edit-' . $this->object_type, array( $this, 'remove_bulk_actions' ) );

			add_filter( 'builder_layout_filter_non_layout_post_types', array( $this, 'disable_builder_layout' ) );
		}

		add_filter( 'members_get_capabilities', array( $this, 'get_capabilities' ) );
	}

	/**
	 * Register the configuration for this object.
	 *
	 * @since 2.9.9
	 */
	public function register_config() {
		$is_admin_user = pods_is_admin();

		$args = array(
			'label'            => 'Pod Templates',
			'labels'           => array( 'singular_name' => 'Pod Template' ),
			'public'           => false,
			'can_export'       => false,
			'show_ui'          => true,
			'show_in_menu'     => false,
			'query_var'        => false,
			'rewrite'          => false,
			'has_archive'      => false,
			'hierarchical'     => false,
			'supports'         => array( 'title', 'author', 'revisions' ),
			'menu_icon'        => pods_svg_icon( 'pods' ),
			'delete_with_user' => false,
		);

		if ( ! $is_admin_user ) {
			$args['capability_type'] = $this->capability_type;
		}

		$args = PodsInit::object_label_fix( $args, 'post_type', $this->object_type );

		register_post_type( $this->object_type, apply_filters( 'pods_internal_register_post_type_object_template', $args ) );

		$args = [
			'internal'           => true,
			'type'               => 'post_type',
			'storage'            => 'meta',
			'name'               => $this->object_type,
			'label'              => 'Pod Templates',
			'label_singular'     => 'Pod Template',
			'description'        => '',
			'public'             => 0,
			'show_ui'            => 1,
			'rest_enable'        => 0,
			'supports_title'     => 1,
			'supports_editor'    => 0,
			'supports_author'    => 1,
			'supports_revisions' => 1,
		];

		if ( ! $is_admin_user ) {
			$args['capability_type']        = 'custom';
			$args['capability_type_custom'] = $this->capability_type;
		}

		pods_register_type( 'post_type', $this->object_type, $args );

		$group = [
			'name'              => 'restrict-content',
			'label'             => __( 'Restrict Content', 'pods' ),
			'description'       => '',
			'weight'            => 0,
			'meta_box_context'  => 'normal',
			'meta_box_priority' => 'high',
		];

		$fields = [
			[
				'name'       => 'admin_only',
				'label'      => __( 'Show to Admins Only', 'pods' ),
				'default'    => 0,
				'type'       => 'boolean',
				'dependency' => true,
			],
			[
				'name'       => 'restrict_capability',
				'label'      => __( 'Restrict access by Capability', 'pods' ),
				'help'       => [
					__( '<h3>Capabilities</h3> Capabilities denote access to specific functionality in WordPress, and are assigned to specific User Roles. Please see the Roles and Capabilities component in Pods for an easy tool to add your own capabilities and roles.', 'pods' ),
					'http://codex.wordpress.org/Roles_and_Capabilities',
				],
				'default'    => 0,
				'type'       => 'boolean',
				'dependency' => true,
			],
			[
				'name'              => 'capability_allowed',
				'label'             => __( 'Capability Allowed', 'pods' ),
				'type'              => 'pick',
				'pick_object'       => 'capability',
				'pick_format_type'  => 'multi',
				'pick_format_multi' => 'autocomplete',
				'pick_ajax'         => false,
				'default'           => '',
				'depends-on'        => [
					'pods_meta_restrict_capability' => true,
				],
			],
			[
				'name'       => 'show_restrict_message',
				'label'      => __( 'Show no access message', 'pods' ),
				'default'    => 0,
				'type'       => 'boolean',
				'dependency' => true,
			],
			[
				'name'                  => 'restrict_message',
				'label'                 => __( 'No access message', 'pods' ),
				'type'                  => 'wysiwyg',
				'default'               => __( 'You do not have access to view this content.', 'pods' ),
				'wysiwyg_editor_height' => 200,
				'depends-on'            => [
					'pods_meta_show_restrict_message' => true,
				],
			],
		];

		pods_register_group( $group, $this->object_type, $fields );
	}

	/**
	 * @param $caps
	 *
	 * @return array
	 */
	public function get_capabilities( $caps ) {

		$caps = array_merge(
			$caps, array(
				'edit_' . $this->capability_type,
				'read_' . $this->capability_type,
				'delete_' . $this->capability_type,
				'edit_' . $this->capability_type . 's',
				'edit_others_' . $this->capability_type . 's',
				'publish_' . $this->capability_type . 's',
				'read_private_' . $this->capability_type . 's',
				'edit_' . $this->capability_type . 's',
			)
		);

		return $caps;
	}

	/**
	 * @param $post_types
	 *
	 * @return array
	 */
	public function disable_builder_layout( $post_types ) {

		$post_types[] = $this->object_type;

		return $post_types;
	}

	/**
	 * Update Post Type messages
	 *
	 * @param array $messages
	 *
	 * @return array
	 * @since 2.0.2
	 */
	public function setup_updated_messages( $messages ) {

		global $post, $post_ID;

		$post_type = get_post_type_object( $this->object_type );

		$labels = $post_type->labels;

		$messages[ $post_type->name ] = array(
			1  => sprintf( __( '%1$s updated. <a href="%2$s">%3$s</a>', 'pods' ), $labels->singular_name, esc_url( get_permalink( $post_ID ) ), $labels->view_item ),
			2  => __( 'Custom field updated.', 'pods' ),
			3  => __( 'Custom field deleted.', 'pods' ),
			4  => sprintf( __( '%s updated.', 'pods' ), $labels->singular_name ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s', 'pods' ), $labels->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( '%1$s published. <a href="%2$s">%3$s</a>', 'pods' ), $labels->singular_name, esc_url( get_permalink( $post_ID ) ), $labels->view_item ),
			7  => sprintf( __( '%s saved.', 'pods' ), $labels->singular_name ),
			8  => sprintf( __( '%1$s submitted. <a target="_blank" rel="noopener noreferrer" href="%2$s">Preview %3$s</a>', 'pods' ), $labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), $labels->singular_name ),
			9  => sprintf(
				__( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" rel="noopener noreferrer" href="%3$s">Preview %4$s</a>', 'pods' ), $labels->singular_name,
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ), $labels->singular_name
			),
			10 => sprintf( __( '%1$s draft updated. <a target="_blank" rel="noopener noreferrer" href="%2$s">Preview %3$s</a>', 'pods' ), $labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ), $labels->singular_name ),
		);

		if ( false === (boolean) $post_type->public ) {
			$messages[ $post_type->name ][1] = sprintf( __( '%s updated.', 'pods' ), $labels->singular_name );
			$messages[ $post_type->name ][6] = sprintf( __( '%s published.', 'pods' ), $labels->singular_name );
			$messages[ $post_type->name ][8] = sprintf( __( '%s submitted.', 'pods' ), $labels->singular_name );
			$messages[ $post_type->name ][9] = sprintf(
				__( '%1$s scheduled for: <strong>%2$s</strong>.', 'pods' ), $labels->singular_name,
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
			);
			$messages[ $post_type->name ][10] = sprintf( __( '%s draft updated.', 'pods' ), $labels->singular_name );
		}

		return $messages;
	}

	/**
	 * Enqueue styles
	 *
	 * @since 2.0.0
	 */
	public function admin_assets() {
		wp_enqueue_script( 'pods-dfv' );
		wp_enqueue_style( 'pods-styles' );
	}

	/**
	 * Fix filters, specifically removing balanceTags
	 *
	 * @since 2.0.1
	 *
	 * @param      $data
	 * @param null $pod
	 * @param null $id
	 * @param null $groups
	 * @param null $post
	 */
	public function fix_filters( $data, $pod = null, $id = null, $groups = null, $post = null ) {

		remove_filter( 'content_save_pre', 'balanceTags', 50 );
	}

	/**
	 * Remove unused row actions
	 *
	 * @since 2.0.5
	 *
	 * @param $actions
	 * @param $post
	 *
	 * @return
	 */
	public function remove_row_actions( $actions, $post ) {

		global $current_screen;

		if ( ! is_object( $current_screen ) || $this->object_type != $current_screen->post_type ) {
			return $actions;
		}

		if ( isset( $actions['view'] ) ) {
			unset( $actions['view'] );
		}

		if ( isset( $actions['inline hide-if-no-js'] ) ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		// W3 Total Cache
		if ( isset( $actions['pgcache_purge'] ) ) {
			unset( $actions['pgcache_purge'] );
		}

		return $actions;
	}

	/**
	 * Remove unused bulk actions
	 *
	 * @since 2.0.5
	 *
	 * @param $actions
	 *
	 * @return
	 */
	public function remove_bulk_actions( $actions ) {

		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		return $actions;
	}

	/**
	 * Clear cache on save
	 *
	 * @since 2.0.0
	 *
	 * @param      $data
	 * @param null $pod
	 * @param null $id
	 * @param null $groups
	 * @param null $post
	 */
	public function clear_cache( $data, $pod = null, $id = null, $groups = null, $post = null ) {

		$old_post = $id;

		if ( ! is_object( $id ) ) {
			$old_post = null;
		}

		if ( ! is_array( $data ) && 0 < $data ) {
			$post = $data;
			$post = get_post( $post );
		}

		if ( ! is_object( $post ) || $this->object_type !== $post->post_type ) {
			return;
		}

		pods_transient_clear( 'pods_object_templates' );

		pods_api()->cache_flush_pods( null, false );
	}

	/**
	 * Change post title placeholder text
	 *
	 * @since 2.0.0
	 *
	 * @param $text
	 * @param $post
	 *
	 * @return string
	 */
	public function set_title_text( $text, $post ) {
		return __( 'Enter template name here', 'pods' );
	}

	/**
	 * Edit page form
	 *
	 * @since 2.0.0
	 */
	public function edit_page_form() {

		global $post_type;

		if ( $this->object_type !== $post_type ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ), 21 );
		add_filter( 'enter_title_here', array( $this, 'set_title_text' ), 10, 2 );
	}

	/**
	 * Get the fields
	 *
	 * @param null   $_null
	 * @param int    $post_ID
	 * @param string $meta_key
	 * @param bool   $single
	 *
	 * @return array|bool|int|mixed|null|string|void
	 */
	public function get_meta( $_null, $post_ID = null, $meta_key = null, $single = false ) {
		if ( 'code' !== $meta_key ) {
			return $_null;
		}

		$post = get_post( $post_ID );

		if ( ! is_object( $post ) || $this->object_type !== $post->post_type ) {
			return $_null;
		}

		return $post->post_content;
	}

	/**
	 * Save the fields
	 *
	 * @param        $_null
	 * @param int    $post_ID
	 * @param string $meta_key
	 * @param string $meta_value
	 *
	 * @return bool|int|null
	 */
	public function save_meta( $_null, $post_ID = null, $meta_key = null, $meta_value = null ) {
		if ( 'code' !== $meta_key ) {
			return $_null;
		}

		$post = get_post( $post_ID );

		if ( ! is_object( $post ) || $this->object_type !== $post->post_type ) {
			return $_null;
		}

		$postdata = array(
			'ID'           => $post_ID,
			'post_content' => $meta_value,
		);

		remove_filter( current_filter(), array( $this, __FUNCTION__ ) );

		$revisions = false;

		if ( has_action( 'pre_post_update', 'wp_save_post_revision' ) ) {
			remove_action( 'pre_post_update', 'wp_save_post_revision' );

			$revisions = true;
		}

		wp_update_post( (object) $postdata );

		// Flush the find posts cache.
		pods_cache_clear( true, 'pods_post_type_storage_' . $this->object_type );

		// objects will be automatically sanitized
		if ( $revisions ) {
			add_action( 'pre_post_update', [ $this, 'save_post_revision_for_post' ] );
		}

		return true;
	}

	/**
	 * Save post revision for a post without a return.
	 *
	 * @since 3.2.8
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_post_revision_for_post( $post_id ) {
		wp_save_post_revision( $post_id );
	}

	/**
	 * Display the page template
	 *
	 * @param string $template_name The template name
	 * @param string $code          Custom template code to use instead
	 * @param object $obj           The Pods object
	 * @param bool   $deprecated    Whether to use deprecated functionality based on old function usage
	 * @param bool   $check_access  Whether to check access for Posts that are Password-protected.
	 *
	 * @return mixed|string|void
	 * @since 2.0.0
	 */
	public static function template( $template_name, $code = null, $obj = null, $deprecated = false, $check_access = false ) {

		if ( ! empty( $obj ) ) {
			self::$obj =& $obj;
		} else {
			$obj =& self::$obj;
		}

		self::$deprecated = $deprecated;

		if ( empty( $obj ) || ! is_object( $obj ) ) {
			return '';
		}

		$has_code_override = ! empty( $code );

		/** @var Pods $obj */

		$template = array(
			'id'      => 0,
			'name'    => $template_name,
			'slug'    => $template_name,
			'code'    => $code,
			'options' => array(),
		);

		if ( empty( $code ) && ! empty( $template_name ) ) {
			// Check for an ID in the template name.
			if ( is_int( $template_name ) ) {
				$template_obj = $obj->api->load_template( [ 'id' => $template_name ] );
			} else {
				// First check by title.
				$template_obj = $obj->api->load_template( [ 'title' => $template_name ] );

				// Then check by slug.
				if ( ! $template_obj ) {
					$template_obj = $obj->api->load_template( [ 'slug' => $template_name ] );
				}

				// Then check by ID.
				if ( ! $template_obj && is_numeric( $template_name ) ) {
					$template_obj = $obj->api->load_template( [ 'id' => (int) $template_name ] );
				}
			}

			if ( ! empty( $template_obj ) ) {
				$template = $template_obj;

				if ( ! empty( $template['code'] ) ) {
					$code = $template['code'];
				}

				if ( $template instanceof Template ) {
					$options = $template;
				} else {
					$options = pods_v( 'options', $template );
				}

				$permission = pods_permission( $template );

				$permission = (boolean) apply_filters( 'pods_templates_permission', $permission, $code, $template, $obj );

				if ( ! $permission ) {
					if ( 1 === (int) pods_v( 'show_restrict_message', $options, 1 ) ) {
						$message = pods_v( 'restrict_message', $options, __( 'You do not have access to view this content.', 'pods' ), true );
						$message = PodsForm::field_method( 'wysiwyg', 'display', $message, 'restrict_message', $options );

						return apply_filters( 'pods_templates_permission_denied', $message, $code, $template, $obj );
					}

					return '';
				}
			}
		}

		$slug = $template['slug'];

		$code = apply_filters( 'pods_templates_pre_template', $code, $template, $obj );
		$code = apply_filters( "pods_templates_pre_template_{$slug}", $code, $template, $obj );

		$info = $check_access ? pods_info_from_args( [ 'pods' => $obj ] ) : [];

		$process_magic_tags = false;

		ob_start();

		// @todo If the template exists, uses that, otherwise use the $code.

		$template_file = null;

		if ( $template_name && $template_name === trim( preg_replace( '/[^a-zA-Z0-9_\-\/]/', '', (string) $template_name ), ' /-' ) ) {
			$default_templates   = self::get_templates_for_pod_template( $template, $obj );
			$template_files_info = self::get_template_files_info( $default_templates );

			$template_file = array_key_first( $template_files_info );
		}

		if ( $has_code_override || ( ! $template_file && ! empty( $code ) ) ) {
			// Only detail templates need $this->id
			if ( empty( $obj->id ) ) {
				$obj->reset();

				while ( $obj->fetch() ) {
					$info['item_id'] = $obj->id();

					// Ensure the post is not password protected.
					if (
						$check_access
						&& (
							pods_access_bypass_post_with_password( $info )
							|| pods_access_bypass_private_post( $info )
						)
					) {
						continue;
					}

					echo self::do_template( $code, $obj, true );
				}
			} else {
				$info['item_id'] = $obj->id();

				if (
					! $check_access
					|| (
						! pods_access_bypass_post_with_password( $info )
						&& ! pods_access_bypass_private_post( $info )
					)
				) {
					echo self::do_template( $code, $obj, true );
				}
			}
		} elseif ( $template_file ) {
			if ( $template_files_info[ $template_file ]['MagicTags'] ) {
				$process_magic_tags = true;
			}

			if ( empty( $obj->id ) ) {
				while ( $obj->fetch() ) {
					$info['item_id'] = $obj->id();

					// Ensure the post is not password protected.
					if (
						$check_access
						&& (
							pods_access_bypass_post_with_password( $info )
							|| pods_access_bypass_private_post( $info )
						)
					) {
						continue;
					}

					$template_output = pods_template_part( $template_file, compact( array_keys( get_defined_vars() ) ), true );

					if ( $process_magic_tags ) {
						$template_output = self::do_template( $template_output, $obj );
					}

					echo $template_output;
				}
			} else {
				$info['item_id'] = $obj->id();

				if (
					! $check_access
					|| (
						! pods_access_bypass_post_with_password( $info )
						&& ! pods_access_bypass_private_post( $info )
					)
				) {
					$template_output = pods_template_part( $template_file, compact( array_keys( get_defined_vars() ) ), true );

					if ( $process_magic_tags ) {
						$template_output = self::do_template( $template_output, $obj );
					}

					echo $template_output;
				}
			}
		}//end if

		$out = ob_get_clean();

		$out = apply_filters( 'pods_templates_post_template', $out, $code, $template, $obj );
		$out = apply_filters( "pods_templates_post_template_{$slug}", $out, $code, $template, $obj );

		return $out;
	}

	/**
	 * Get templates for pod page.
	 *
	 * @since 3.2.8
	 *
	 * @param array|Template $template The pod template data.
	 * @param Pods|null      $obj      The Pods object.
	 *
	 * @return array The list of templates for the pod template.
	 */
	public static function get_templates_for_pod_template( $template, $obj = null ): array {
		$template_name = $template instanceof Template ? $template->get_name() : ( $template['slug'] ?? $template['name'] );

		if ( empty( $template_name ) ) {
			return [];
		}

		$template_name = trim( preg_replace( '/[^a-zA-Z0-9_\-\/]/', '', (string) $template_name ), ' /-' );

		$default_templates = array(
			'pods/templates/' . $template_name . '.php',
			'pods/' . $template_name . '.php',
			'pods-' . $template_name . '.php',
			$template_name . '.php',
		);

		/**
		 * Allow filtering the list of default theme templates to check for a template.
		 *
		 * @since unknown
		 *
		 * @param string[]       $default_templates The list of default theme templates to check for a template.
		 * @param string         $template_name     The template name.
		 * @param array|Template $template          The template information.
		 * @param Pods           $obj               The Pods object.
		 */
		return (array) apply_filters( 'pods_template_default_templates', $default_templates, $template_name, $template, $obj );
	}

	/**
	 * Get the list of template header information for each of the template files.
	 *
	 * @since 3.2.8
	 *
	 * @param array<int,string> $template_files The list of template files.
	 *
	 * @return array The list of template header information for each of the template files.
	 */
	public static function get_template_files_info( array $template_files ): array {
		$template_files_info = [];

		foreach ( $template_files as $template_file ) {
			$file_path = locate_template( $template_file );

			// Skip if template was not found.
			if ( '' === $file_path ) {
				continue;
			}

			$data = get_file_data( $file_path, [
				'PodTemplate'  => 'Pod Template',
				'MagicTags' => 'Magic Tags',
			] );

			$data['MagicTags'] = pods_is_truthy( $data['MagicTags'] );

			$template_files_info[ $template_file ] = $data;
		}

		return $template_files_info;
	}

	/**
	 * Parse a template string
	 *
	 * @param string $code        The template string to parse
	 * @param object $obj         The Pods object
	 * @param bool   $process_php Whether to process PHP -- this will be removed in Pods 3.3+.
	 *
	 * @since 1.8.5
	 * @return mixed|string|void
	 */
	public static function do_template(
		$code,
		$obj = null,
		$process_php = false
	) {
		if ( ! empty( $obj ) ) {
			self::$obj =& $obj;
		} else {
			$obj =& self::$obj;
		}

		if ( empty( $obj ) || ! is_object( $obj ) || ! is_string( $code ) ) {
			return '';
		}

		$out = '';

		if ( false !== strpos( $code, '<?' ) ) {
			$code = str_replace( '$this->', '$obj->', $code );

			ob_start();

			/**
			 * Allow evaluating Pod Template content by custom code snippet if needed.
			 *
			 * @since 3.3.0
			 *
			 * @param string $code        The template string to parse.
			 * @param object $obj         The Pods object.
			 * @param bool   $process_php Whether to process PHP.
			 */
			do_action( 'pods_templates_eval_content', $code, $obj, $process_php );

			$out = (string) ob_get_clean();
		} else {
			$out = $code;
		}

		if ( '' !== trim( $out ) ) {
			$out = $obj->do_magic_tags( $out );
		}

		// Prevent blank whitespace from being output if nothing came through.
		if ( '' === trim( $out ) ) {
			$out = '';
		}

		return apply_filters( 'pods_templates_do_template', $out, $code, $obj );
	}

	/**
	 * Get the object and possibly use the current object context if available.
	 *
	 * @param string     $pod_name The pod name.
	 * @param int|string $item_id  The item ID.
	 *
	 * @return Pods|false The Pods object or false if Pod not valid.
	 */
	public static function get_obj( $pod_name, $item_id ) {
		if ( ! empty( self::$obj ) && self::$obj->pod === $pod_name && self::$obj->id() == $item_id ) {
			return self::$obj;
		}

		return pods( $pod_name, $item_id, true );
	}

}
