<?php
/**
 * Class that handles specific [vc_text_separator] shortcode.
 *
 * @see js_composer/include/templates/shortcodes/vc_text_separator.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Class WPBakeryShortCode_Vc_Text_separator
 */
class WPBakeryShortCode_Vc_Text_Separator extends WPBakeryShortCode {

	/**
	 * Override default title.
	 *
	 * @param string $title
	 * @return string
	 */
	public function outputTitle( $title ) {
		return '';
	}

	/**
	 * Get plugin icon.
	 *
	 * @param array $atts
	 * @return string
	 * @throws \Exception
	 */
	public function getVcIcon( $atts ) {

		if ( empty( $atts['i_type'] ) ) {
			$atts['i_type'] = 'fontawesome';
		}
		$data = vc_map_integrate_parse_atts( $this->shortcode, 'vc_icon', $atts, 'i_' );
		if ( $data ) {
			$icon = wpbakery()->getShortCode( 'vc_icon' );
			if ( is_object( $icon ) ) {
				return $icon->render( array_filter( $data ) );
			}
		}

		return '';
	}
}
