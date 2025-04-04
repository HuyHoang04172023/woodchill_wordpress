<?php
/**
 * Class that handles specific [vc_section] shortcode.
 *
 * @see js_composer/include/templates/shortcodes/vc_section.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * WPBakery Page Builder section
 *
 * @package WPBakeryPageBuilder
 */
class WPBakeryShortCode_Vc_Section extends WPBakeryShortCodesContainer {
	/**
	 * Add container classes.
	 *
	 * @param string $width
	 * @param int $i
	 * @return string
	 */
	public function containerHtmlBlockParams( $width, $i ) {
		return 'class="vc_section_container vc_container_for_children"';
	}

	/**
	 * WPBakeryShortCode_Vc_Section constructor.
	 *
	 * @param array $settings
	 */
	public function __construct( $settings ) {
		parent::__construct( $settings );
		$this->shortcodeScripts();
	}

	/**
	 * Register shortcode scripts.
	 */
	protected function shortcodeScripts() {
		wp_register_script( 'vc_jquery_skrollr_js', vc_asset_url( 'lib/vendor/node_modules/skrollr/dist/skrollr.min.js' ), [ 'jquery-core' ], WPB_VC_VERSION, true );
		wp_register_script( 'vc_youtube_iframe_api_js', 'https://www.youtube.com/iframe_api', [], WPB_VC_VERSION, true );
	}

	/**
	 * Additional CSS class for shortcode in admin.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function cssAdminClass() {
		$sortable = ( vc_user_access_check_shortcode_all( $this->shortcode ) ? ' wpb_sortable' : ' ' . $this->nonDraggableClass );

		return 'wpb_' . $this->settings['base'] . $sortable . '' . ( ! empty( $this->settings['class'] ) ? ' ' . $this->settings['class'] : '' );
	}

	/**
	 * Add column controls to shortcode output.
	 *
	 * @param string $controls
	 * @param string $extended_css
	 * @return string
	 * @throws \Exception
	 */
	public function getColumnControls( $controls = 'full', $extended_css = '' ) {
		$controls_start = '<div class="vc_controls vc_controls-visible controls_column' . ( ! empty( $extended_css ) ? " {$extended_css}" : '' ) . '">';

		$output = '<div class="vc_controls vc_controls-row controls_row vc_clearfix">';
		$controls_end = '</div>';
		// Create columns.
		$controls_move = ' <a class="vc_control column_move vc_column-move" href="#" title="' . esc_attr__( 'Drag row to reorder', 'js_composer' ) . '" data-vc-control="move"><i class="vc-composer-icon vc-c-icon-dragndrop"></i></a>';
		$moveAccess = vc_user_access()->part( 'dragndrop' )->checkStateAny( true, null )->get();
		if ( ! $moveAccess ) {
			$controls_move = '';
		}
		$controls_add = ' <a class="vc_control column_add vc_column-add" href="#" title="' . esc_attr__( 'Add column', 'js_composer' ) . '" data-vc-control="add"><i class="vc-composer-icon vc-c-icon-add"></i></a>';
		$controls_delete = '<a class="vc_control column_delete vc_column-delete" href="#" title="' . esc_attr__( 'Delete this row', 'js_composer' ) . '" data-vc-control="delete"><i class="vc-composer-icon vc-c-icon-delete_empty"></i></a>';
		$controls_edit = ' <a class="vc_control column_edit vc_column-edit" href="#" title="' . esc_attr__( 'Edit this row', 'js_composer' ) . '" data-vc-control="edit"><i class="vc-composer-icon vc-c-icon-mode_edit"></i></a>';
		$controls_clone = ' <a class="vc_control column_clone vc_column-clone" href="#" title="' . esc_attr__( 'Clone this row', 'js_composer' ) . '" data-vc-control="clone"><i class="vc-composer-icon vc-c-icon-clone"></i></a>';
		$controls_copy = ' <a class="vc_control column_copy vc_column-copy" href="#" title="' . esc_attr__( 'Copy this row', 'js_composer' ) . '" data-vc-control="copy"><i class="vc-composer-icon vc-c-icon-copy"></i></a>';
		$controls_paste = ' <a class="vc_control column_paste vc_column-paste" href="#" title="' . esc_attr__( 'Paste', 'js_composer' ) . '" data-vc-control="paste"><i class="vc-composer-icon vc-c-icon-paste"></i></a>';
		$editAccess = vc_user_access_check_shortcode_edit( $this->shortcode );
		$allAccess = vc_user_access_check_shortcode_all( $this->shortcode );
		$row_edit_clone_delete = '<span class="vc_row_edit_clone_delete">';

		if ( 'add' === $controls ) {
			return $controls_start . $controls_add . $controls_end;
		}
		if ( $allAccess ) {
			$row_edit_clone_delete .= $controls_delete . $controls_paste . $controls_copy . $controls_clone . $controls_edit;
		} elseif ( $editAccess ) {
			$row_edit_clone_delete .= $controls_edit;
		}
		$row_edit_clone_delete .= '</span>';

		if ( $allAccess ) {
			$output .= '<div>' . $controls_move . $controls_add . '</div>' . $row_edit_clone_delete . $controls_end;
		} elseif ( $editAccess ) {
			$output .= $row_edit_clone_delete . $controls_end;
		} else {
			$output .= $row_edit_clone_delete . $controls_end;
		}

		return $output;
	}

	/**
	 * Get admin output.
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function contentAdmin( $atts, $content = null ) {
		$width = '';
		$atts = shortcode_atts( $this->predefined_atts, $atts );

		$output = '';

		$column_controls = $this->getColumnControls();

		$output .= '<div data-element_type="' . $this->settings['base'] . '" class="' . $this->cssAdminClass() . '">';
		$output .= str_replace( '%column_size%', 1, $column_controls );
		$output .= '<div class="wpb_element_wrapper">';
		if ( isset( $this->settings['custom_markup'] ) && '' !== $this->settings['custom_markup'] ) {
			$markup = $this->settings['custom_markup'];
			$output .= $this->customMarkup( $markup );
		} else {
			$output .= '<div ' . $this->containerHtmlBlockParams( $width, 1 ) . '>';
			$output .= do_shortcode( shortcode_unautop( $content ) );
			$output .= '</div>';
		}
		if ( isset( $this->settings['params'] ) ) {
			$inner = '';
			foreach ( $this->settings['params'] as $param ) {
				if ( ! isset( $param['param_name'] ) ) {
					continue;
				}
				$param_value = isset( $atts[ $param['param_name'] ] ) ? $atts[ $param['param_name'] ] : '';
				if ( is_array( $param_value ) ) {
					// Get first element from the array.
					reset( $param_value );
					$first_key = key( $param_value );
					$param_value = $param_value[ $first_key ];
				}
				$inner .= $this->singleParamHtmlHolder( $param, $param_value );
			}
			$output .= $inner;
		}
		$output .= '</div>';
		if ( $this->backened_editor_prepend_controls ) {
			$output .= $this->getColumnControls( 'add', 'vc_section-bottom-controls bottom-controls' );
		}
		$output .= '</div>';

		return $output;
	}
}
