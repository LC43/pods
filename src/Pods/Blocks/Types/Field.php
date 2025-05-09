<?php

namespace Pods\Blocks\Types;

use WP_Block;

/**
 * Field block functionality class.
 *
 * @since 2.8.0
 */
class Field extends Base {

	/**
	 * Which is the name/slug of this block
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function slug() {
		return 'pods-block-field';
	}

	/**
	 * Get block configuration to register with Pods.
	 *
	 * @since 2.8.0
	 *
	 * @return array Block configuration.
	 */
	public function block() {
		return [
			'internal'        => true,
			'label'           => __( 'Pods Field Value', 'pods' ),
			'description'     => __( 'Display a single Pod item\'s field value (custom fields).', 'pods' ),
			'namespace'       => 'pods',
			'category'        => 'pods',
			'icon'            => 'pods',
			'renderType'      => 'php',
			'render_callback' => [ $this, 'safe_render' ],
			'keywords'        => [
				'pods',
				'field',
				'value',
				'custom',
				'meta',
			],
			'uses_context'    => [
				'postType',
				'postId',
			],
			'transforms'      => [
				'from' => [
					[
						'type'       => 'shortcode',
						'tag'        => 'pods',
						'attributes' => [
							'name'  => [
								'type'      => 'object',
								'source'    => 'shortcode',
								'attribute' => 'name',
							],
							'slug'  => [
								'type'      => 'string',
								'source'    => 'shortcode',
								'attribute' => 'slug',
							],
							'field' => [
								'type'      => 'string',
								'source'    => 'shortcode',
								'attribute' => 'field',
							],
							'link_field' => [
								'type'      => 'string',
								'source'    => 'shortcode',
								'attribute' => 'link_field',
							],
						],
						'isMatchConfig' => [
							[
								'name'     => 'field',
								'required' => true,
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Get list of Field configurations to register with Pods for the block.
	 *
	 * @since 2.8.0
	 *
	 * @return array List of Field configurations.
	 */
	public function fields() {
		return [
			[
				'name'        => 'name',
				'label'       => __( 'Pod Name', 'pods' ),
				'type'        => 'pick',
				'data'        => [ $this, 'callback_get_all_pods' ],
				'default'     => '',
				'description' => __( 'Choose the pod to reference, or reference the Pod in the current context of this block.', 'pods' ),
			],
			[
				'name'    => 'access_rights_help',
				'label'   => __( 'Access Rights', 'pods' ),
				'type'    => 'html',
				'default' => '',
				'html_content' => sprintf(
					// translators: %s is the Read Documentation link.
					esc_html__( 'Read about how access rights control what can be displayed to other users: %s', 'pods' ),
					'<a href="https://docs.pods.io/displaying-pods/access-rights-in-pods/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'pods' ) . '</a>'
				),
			],
			[
				'name'        => 'slug',
				'label'       => __( 'Slug or ID', 'pods' ),
				'type'        => 'text',
				'description' => __( 'Defaults to using the current pod item.', 'pods' ),
			],
			[
				'name'        => 'field',
				'label'       => __( 'Field Name', 'pods' ),
				'type'        => 'text',
				'description' => __( 'This is the field name you want to display.', 'pods' ),
			],
			[
				'name'        => 'link_field',
				'label'       => __( 'Link Field Name (optional)', 'pods' ),
				'type'        => 'text',
				'description' => __( 'You can specify a field to link the output to. Like "permalink" or "related_field.permalink".', 'pods' ),
			],
		];
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it.
	 *
	 * @since 2.8.0
	 *
	 * @param array         $attributes The block attributes.
	 * @param string        $content    The block default content.
	 * @param WP_Block|null $block      The block instance.
	 *
	 * @return string The block content to render.
	 */
	public function render( $attributes = [], $content = '', $block = null ) {
		// If the feature is disabled then return early.
		if ( ! pods_can_use_dynamic_feature( 'display' ) ) {
			return '';
		}

		$attributes = $this->attributes( $attributes );
		$attributes = array_map( 'pods_trim', $attributes );

		$attributes['source']  = __METHOD__;
		$attributes['context'] = 'field';

		if ( empty( $attributes['field'] ) ) {
			if ( $this->in_editor_mode( $attributes ) ) {
				return $this->render_placeholder(
					'<i class="pods-block-placeholder_error"></i>' . esc_html__( 'Pods Field Value', 'pods' ),
					esc_html__( 'Please specify a "Field Name" under "More Settings" to configure this block.', 'pods' )
				);
			}

			return '';
		}

		// Check whether we should preload the block.
		if ( $this->is_preloading_block() && ! $this->should_preload_block( $attributes, $block ) ) {
			return '';
		}

		// Use current if no pod name / slug provided.
		if ( empty( $attributes['name'] ) || empty( $attributes['slug'] ) ) {
			$attributes['use_current'] = true;
		} elseif ( ! isset( $attributes['use_current'] ) ) {
			$attributes['use_current'] = false;
		}

		$provided_post_id = $this->in_editor_mode( $attributes ) ? pods_v( 'post_id', 'get', 0, true ) : get_the_ID();
		$provided_post_id = absint( pods_v( '_post_id', $attributes, $provided_post_id, true ) );

		if ( $attributes['use_current'] && $block instanceof WP_Block && ! empty( $block->context['postType'] ) ) {
			// Detect post type / ID from context.
			$attributes['name'] = $block->context['postType'];

			if ( ! empty( $block->context['postId'] ) ) {
				$attributes['slug'] = $block->context['postId'];

				unset( $attributes['use_current'] );
			}
		} elseif (
			$attributes['use_current']
			&& 0 !== $provided_post_id
			&& $this->in_editor_mode( $attributes )
		) {
			$attributes['slug'] = $provided_post_id;

			if ( empty( $attributes['name'] ) ) {
				$attributes['name'] = get_post_type( $attributes['slug'] );
			}

			unset( $attributes['use_current'] );
		}

		pods_set_render_is_in_block( true );

		$content = pods_shortcode( $attributes );

		pods_set_render_is_in_block( false );

		if ( $this->should_autop( $content, $attributes ) ) {
			$content = wpautop( $content );
		}

		return $content;
	}
}
