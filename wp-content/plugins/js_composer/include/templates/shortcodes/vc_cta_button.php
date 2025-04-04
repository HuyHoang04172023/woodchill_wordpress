<?php
/**
 * The template for displaying [vc_cta_button] shortcode output.
 *
 * This template can be overridden by copying it to yourtheme/vc_templates/vc_cta_button.php.
 *
 * @see https://kb.wpbakery.com/docs/developers-how-tos/change-shortcodes-html-output
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Shortcode attributes
 *
 * @var $atts
 * @var $color
 * @var $icon
 * @var $size
 * @var $target
 * @var $href
 * @var $title
 * @var $call_text
 * @var $position
 * @var $el_class
 * @var $css_animation
 * Shortcode class
 * @var WPBakeryShortCode_Vc_Cta_button $this
 */
$color = $icon = $size = $target = $href = $target = $call_text = $position = $el_class = $css_animation = '';
$output = '';
$atts = vc_map_get_attributes( $this->getShortcode(), $atts );
extract( $atts );

$el_class = $this->getExtraClass( $el_class );

if ( 'same' === $target || '_self' === $target ) {
	$target = '';
}
if ( '' !== $target ) {
	$target = ' target="' . esc_attr( $target ) . '"';
}

$icon = ( '' !== $icon && 'none' !== $icon ) ? ' ' . $icon : '';
$i_icon = ( '' !== $icon ) ? ' <i class="icon"> </i>' : '';

$color = ( '' !== $color ) ? ' wpb_' . $color : '';
$size = ( '' !== $size && 'wpb_regularsize' !== $size ) ? ' wpb_' . $size : ' ' . $size;

$a_class = '';

if ( '' !== $href ) {
	$button = '<span class="wpb_button ' . esc_attr( $color . $size . $icon ) . '">' . $title . $i_icon . '</span>';
	$button = '<a class="wpb_button_a' . esc_attr( $a_class ) . '" href="' . esc_url( $href ) . '"' . $target . '>' . $button . '</a>';
} else {
	$button = '';
	$el_class .= ' cta_no_button';
}
$css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, 'wpb_call_to_action wpb_content_element vc_clearfix ' . $position . $el_class, $this->settings['base'], $atts );
$css_class .= $this->getCSSAnimation( $css_animation );

$output .= '<div class="' . esc_attr( $css_class ) . '">';
if ( 'cta_align_bottom' !== $position ) {
	$output .= $button;
}
$output .= apply_filters( 'wpb_cta_text', '<h2 class="wpb_call_text">' . $call_text . '</h2>', [ 'content' => $call_text ] );
if ( 'cta_align_bottom' === $position ) {
	$output .= $button;
}
$output .= '</div>';

return $output;
