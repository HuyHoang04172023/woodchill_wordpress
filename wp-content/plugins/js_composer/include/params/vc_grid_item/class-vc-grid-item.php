<?php
/**
 * Basic class for vc_grid_item param.
 *
 * @see https://kb.wpbakery.com/docs/inner-api/vc_map/#vc_map()-ParametersofparamsArray
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Class Vc_Grid_Item to build grid item.
 */
class Vc_Grid_Item {
	/**
	 * Template for grid item.
	 *
	 * @var string
	 */
	protected $template = '';
	/**
	 * Html template for grid item.
	 *
	 * @var bool
	 */
	protected $html_template = false;
	/**
	 * Post object.
	 *
	 * @var bool|WP_Post
	 */
	protected $post = false;
	/**
	 * Grid item attributes.
	 *
	 * @var array
	 */
	protected $grid_atts = [];
	/**
	 * Is end of grid.
	 *
	 * @var bool
	 */
	protected $is_end = false;
	/**
	 * Shortcodes for grid item.
	 *
	 * @var bool
	 */
	protected $shortcodes = false;
	/**
	 * Found variables in template.
	 *
	 * @var bool
	 */
	protected $found_variables = false;
	/**
	 * Predefined templates.
	 *
	 * @var bool
	 */
	protected static $predefined_templates = false;
	/**
	 * Template id.
	 *
	 * @var bool|int
	 */
	protected $template_id = false;

	/**
	 * Get shortcodes to build vc grid item templates.
	 *
	 * @return bool|mixed
	 */
	public function shortcodes() {
		if ( false === $this->shortcodes ) {
			$this->shortcodes = include vc_path_dir( 'PARAMS_DIR', 'vc_grid_item/shortcodes.php' );
			$this->shortcodes = apply_filters( 'vc_grid_item_shortcodes', $this->shortcodes );
		}
		add_filter( 'vc_shortcode_set_template_vc_icon', [
			$this,
			'addVcIconShortcodesTemplates',
		] );
		add_filter( 'vc_shortcode_set_template_vc_button2', [
			$this,
			'addVcButton2ShortcodesTemplates',
		] );
		add_filter( 'vc_shortcode_set_template_vc_single_image', [
			$this,
			'addVcSingleImageShortcodesTemplates',
		] );
		add_filter( 'vc_shortcode_set_template_vc_custom_heading', [
			$this,
			'addVcCustomHeadingShortcodesTemplates',
		] );
		add_filter( 'vc_shortcode_set_template_vc_btn', [
			$this,
			'addVcBtnShortcodesTemplates',
		] );

		return $this->shortcodes;
	}

	/**
	 * Used by filter vc_shortcode_set_template_vc_icon to set custom template for vc_icon shortcode.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function addVcIconShortcodesTemplates( $template ) {
		if ( Vc_Grid_Item_Editor::postType() === WPBMap::getScope() ) {
			$file = vc_path_dir( 'TEMPLATES_DIR', 'params/vc_grid_item/shortcodes/vc_icon.php' );
			if ( is_file( $file ) ) {
				return $file;
			}
		}

		return $template;
	}

	/**
	 * Used by filter vc_shortcode_set_template_vc_button2 to set custom template for vc_button2 shortcode.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function addVcButton2ShortcodesTemplates( $template ) {
		if ( Vc_Grid_Item_Editor::postType() === WPBMap::getScope() ) {
			$file = vc_path_dir( 'TEMPLATES_DIR', 'params/vc_grid_item/shortcodes/vc_button2.php' );
			if ( is_file( $file ) ) {
				return $file;
			}
		}

		return $template;
	}

	/**
	 * Used by filter vc_shortcode_set_template_vc_single_image to set custom template for vc_single_image shortcode.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function addVcSingleImageShortcodesTemplates( $template ) {
		if ( Vc_Grid_Item_Editor::postType() === WPBMap::getScope() ) {
			$file = vc_path_dir( 'TEMPLATES_DIR', 'params/vc_grid_item/shortcodes/vc_single_image.php' );
			if ( is_file( $file ) ) {
				return $file;
			}
		}

		return $template;
	}

	/**
	 * Used by filter vc_shortcode_set_template_vc_custom_heading to set custom template for vc_custom_heading
	 * shortcode.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function addVcCustomHeadingShortcodesTemplates( $template ) {
		if ( Vc_Grid_Item_Editor::postType() === WPBMap::getScope() ) {
			$file = vc_path_dir( 'TEMPLATES_DIR', 'params/vc_grid_item/shortcodes/vc_custom_heading.php' );
			if ( is_file( $file ) ) {
				return $file;
			}
		}

		return $template;
	}

	/**
	 * Used by filter vc_shortcode_set_template_vc_button2 to set custom template for vc_button2 shortcode.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function addVcBtnShortcodesTemplates( $template ) {
		if ( Vc_Grid_Item_Editor::postType() === WPBMap::getScope() ) {
			$file = vc_path_dir( 'TEMPLATES_DIR', 'params/vc_grid_item/shortcodes/vc_btn.php' );
			if ( is_file( $file ) ) {
				return $file;
			}
		}

		return $template;
	}

	/**
	 * Map shortcodes for vc_grid_item param type.
	 *
	 * @throws \Exception
	 */
	public function mapShortcodes() {
		// @kludge
		// TODO: refactor with with new way of roles for shortcodes.
		// NEW ROLES like post_type for shortcode and access policies.
		$shortcodes = $this->shortcodes();
		foreach ( $shortcodes as $shortcode_settings ) {
			vc_map( $shortcode_settings );
		}
	}

	/**
	 * Get list of predefined templates.
	 *
	 * @return bool|mixed
	 */
	public static function predefinedTemplates() {
		if ( false === self::$predefined_templates ) {
			self::$predefined_templates = apply_filters( 'vc_grid_item_predefined_templates', include vc_path_dir( 'PARAMS_DIR', 'vc_grid_item/templates.php' ) );
		}

		return self::$predefined_templates;
	}

	/**
	 * Get predefined template by id.
	 *
	 * @param int $id - Predefined templates id.
	 *
	 * @return array|bool
	 */
	public static function predefinedTemplate( $id ) {
		$predefined_templates = self::predefinedTemplates();
		if ( isset( $predefined_templates[ $id ]['template'] ) ) {
			return $predefined_templates[ $id ];
		}

		return false;
	}

	/**
	 * Set template which should grid used when vc_grid_item param value is rendered.
	 *
	 * @param int $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function setTemplateById( $id ) {
		require_once vc_path_dir( 'PARAMS_DIR', 'vc_grid_item/templates.php' );
		if ( 0 === strlen( $id ) ) {
			return false;
		}
		if ( preg_match( '/^\d+$/', $id ) ) {
			$post = get_post( (int) $id );
			if ( $post ) {
				$this->setTemplate( $post->post_content, $post->ID );
			}

			return true;
		} else {
			$predefined_template = $this->predefinedTemplate( $id );
			if ( $predefined_template ) {
				$this->setTemplate( $predefined_template['template'], $id );

				return true;
			}
		}

		return false;
	}

	/**
	 * Setter for template attribute.
	 *
	 * @param string $template
	 * @param int $template_id
	 * @throws \Exception
	 */
	public function setTemplate( $template, $template_id ) {
		$this->template = $template;
		$this->template_id = $template_id;
		$this->parseTemplate( $template );
	}

	/**
	 * Getter for template attribute.
	 *
	 * @return string
	 */
	public function template() {
		return $this->template;
	}

	/**
	 * Add custom css from shortcodes that were mapped for vc grid item.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function addShortcodesCustomCss() {
		$output = $shortcodes_custom_css = '';
		$id = $this->template_id;
		if ( preg_match( '/^\d+$/', $id ) ) {
			$shortcodes_custom_css = get_post_meta( $id, '_wpb_shortcodes_custom_css', true );
		} else {
			$predefined_template = $this->predefinedTemplate( $id );
			if ( $predefined_template ) {
				$shortcodes_custom_css = wpbakery()->parseShortcodesCss( $predefined_template['template'], 'custom' );
			}
		}
		if ( ! empty( $shortcodes_custom_css ) ) {
			$shortcodes_custom_css = wp_strip_all_tags( $shortcodes_custom_css );
			$first_tag = 'style';
			$output .= '<' . $first_tag . ' data-type="vc_shortcodes-custom-css">';
			$output .= $shortcodes_custom_css;
			$output .= '</' . $first_tag . '>';
		}

		return $output;
	}

	/**
	 * Generates html with template's variables.
	 *
	 * @param string $template
	 * @return string
	 * @since 7.6
	 */
	public function getParseTemplate( $template ) {
		$this->mapShortcodes();
		WPBMap::addAllMappedShortcodes();
		$attr = ' width="' . $this->gridAttribute( 'element_width', 12 ) . '" is_end="' . ( 'true' === $this->isEnd() ? 'true' : '' ) . '"';
		$template = preg_replace( '/(\[(\[?)vc_gitem\b)/', '$1' . $attr, $template );
		$template = str_replace( [
			'<p>[vc_gitem',
			'[/vc_gitem]</p>',
		], [
			'[vc_gitem',
			'[/vc_gitem]',
		], $template );

		return do_shortcode( trim( $template ) );
	}

	/**
	 * Set parsed template to html_template attribute.
	 *
	 * @param string $template
	 * @throws \Exception
	 */
	public function parseTemplate( $template ) {
		$this->html_template .= $this->getParseTemplate( $template );
	}

	/**
	 * Regexp for variables.
	 *
	 * @return string
	 */
	public function templateVariablesRegex() {
        // phpcs:ignore:Generic.Strings.UnnecessaryStringConcat.Found
		return '/\{\{' . '\{?' . '\s*' . '([^\}\:]+)(\:([^\}]+))?' . '\s*' . '\}\}' . '\}?/';
	}

	/**
	 * Get default variables.
	 *
	 * @return array|bool
	 */
	public function getTemplateVariables() {
		if ( ! is_array( $this->found_variables ) ) {
			preg_match_all( $this->templateVariablesRegex(), $this->html_template, $this->found_variables, PREG_SET_ORDER );
		}

		return $this->found_variables;
	}

	/**
	 * Render item by replacing template variables for exact post.
	 *
	 * @param WP_Post $post
	 *
	 * @return mixed
	 */
	public function renderItem( WP_Post $post ) {
		$pattern = [];
		$replacement = [];
		$this->addAttributesFilters();
		foreach ( $this->getTemplateVariables() as $var ) {
			$pattern[] = '/' . preg_quote( $var[0], '/' ) . '/';
			$replacement[] = preg_replace( '/\\$/', '\\\$', $this->attribute( $var[1], $post, isset( $var[3] ) ? trim( $var[3] ) : '' ) );
		}

		return preg_replace( $pattern, $replacement, do_shortcode( $this->html_template ) );
	}

	/**
	 * Adds filters to build templates variables values.
	 */
	public function addAttributesFilters() {
		require_once vc_path_dir( 'PARAMS_DIR', 'vc_grid_item/attributes.php' );
	}

	/**
	 * Getter for Grid shortcode attributes.
	 *
	 * @param array $grid_atts
	 */
	public function setGridAttributes( $grid_atts ) {
		$this->grid_atts = $grid_atts;
	}

	/**
	 * Setter for Grid shortcode attributes.
	 *
	 * @param string $name
	 * @param string $initial
	 *
	 * @return string
	 */
	public function gridAttribute( $name, $initial = '' ) {
		return isset( $this->grid_atts[ $name ] ) ? $this->grid_atts[ $name ] : $initial;
	}

	/**
	 * Get attribute value for WP_post object.
	 *
	 * @param string $name
	 * @param WP_Post $post
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function attribute( $name, $post, $data = '' ) {
		$data = html_entity_decode( $data );

		return apply_filters( 'vc_gitem_template_attribute_' . trim( $name ), ( isset( $post->$name ) ? $post->$name : '' ), [
			'post' => $post,
			'data' => $data,
		] );
	}

	/**
	 * Set that this is last items in the grid. Used for load more button and lazy loading.
	 *
	 * @param bool $is_end
	 */
	public function setIsEnd( $is_end = true ) {
		$this->is_end = $is_end;
	}

	/**
	 * Checks is the end.
	 *
	 * @return bool
	 */
	public function isEnd() {
		return $this->is_end;
	}
}
