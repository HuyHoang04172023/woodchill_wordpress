<?php
/**
 * The template for displaying [vc_video] shortcode output of 'Video Player' element.
 *
 * This template can be overridden by copying it to yourtheme/vc_templates/vc_video.php.
 *
 * @see https://kb.wpbakery.com/docs/developers-how-tos/change-shortcodes-html-output
 *
 * Shortcode attributes
 * @var $atts
 * @var $title
 * @var $link
 * @var $el_class
 * @var $el_id
 * @var $css
 * @var $css_animation
 * @var $el_width
 * @var $el_aspect
 * @var $align
 * Shortcode class
 * @var WPBakeryShortCode_Vc_Video $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$title = $link = $el_class = $el_id = $css = $css_animation = $el_width = $el_aspect = $align = '';
$atts = vc_map_get_attributes( $this->getShortcode(), $atts );
extract( $atts );

if ( '' === $link ) {
	return null;
}
$el_class = $this->getExtraClass( $el_class ) . $this->getCSSAnimation( $css_animation );

$video_w = 500;
$video_h = $video_w / 1.61; // 1.61 golden ratio
// @var WP_Embed $wp_embed
global $wp_embed;
$embed = '';
if ( is_object( $wp_embed ) ) {
	$embed = $wp_embed->run_shortcode( '[embed width="' . $video_w . '" height="' . $video_h . '"]' . $link . '[/embed]' );
}
$element_class = empty( $this->settings['element_default_class'] ) ? '' : $this->settings['element_default_class'];
$el_classes = [
	'wpb_video_widget',
	$element_class,
	'vc_clearfix',
	$el_class,
	vc_shortcode_custom_css_class( $css, ' ' ),
	'vc_video-aspect-ratio-' . esc_attr( $el_aspect ),
	'vc_video-el-width-' . esc_attr( $el_width ),
	'vc_video-align-' . esc_attr( $align ),
];
$css_class = implode( ' ', $el_classes );
$css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, $css_class, $this->getShortcode(), $atts );
$wrapper_attributes = [];
if ( ! empty( $el_id ) ) {
	$wrapper_attributes[] = 'id="' . esc_attr( $el_id ) . '"';
}
$output = '
	<div class="' . esc_attr( $css_class ) . '" ' . implode( ' ', $wrapper_attributes ) . '>
		<div class="wpb_wrapper">
			' . wpb_widget_title( [
	'title' => $title,
	'extraclass' => 'wpb_video_heading',
] ) . '
			<div class="wpb_video_wrapper">' . $embed . '</div>
		</div>
	</div>
';

return $output;
