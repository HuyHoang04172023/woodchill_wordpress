<?php
/**
 * Configuration file for [vc_tour] shortcode of 'Old Tour' element.
 *
 * @see https://kb.wpbakery.com/docs/inner-api/vc_map/ for more detailed information about element attributes.
 * @depreacted 4.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

return [
	'name' => esc_html__( 'Old Tour', 'js_composer' ),
	'base' => 'vc_tour',
	'show_settings_on_create' => false,
	'is_container' => true,
	'container_not_allowed' => true,
	'deprecated' => '4.6',
	'icon' => 'icon-wpb-ui-tab-content-vertical',
	'category' => esc_html__( 'Content', 'js_composer' ),
	'wrapper_class' => 'vc_clearfix',
	'description' => esc_html__( 'Vertical tabbed content', 'js_composer' ),
	'params' => [
		[
			'type' => 'textfield',
			'heading' => esc_html__( 'Widget title', 'js_composer' ),
			'param_name' => 'title',
			'description' => esc_html__( 'Enter text used as widget title (Note: located above content element).', 'js_composer' ),
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Auto rotate slides', 'js_composer' ),
			'param_name' => 'interval',
			'value' => [
				esc_html__( 'Disable', 'js_composer' ) => 0,
				3,
				5,
				10,
				15,
			],
			'std' => 0,
			'description' => esc_html__( 'Auto rotate slides each X seconds.', 'js_composer' ),
		],
		[
			'type' => 'textfield',
			'heading' => esc_html__( 'Extra class name', 'js_composer' ),
			'param_name' => 'el_class',
			'description' => esc_html__( 'Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer' ),
		],
	],
	'custom_markup' => '
<div class="wpb_tabs_holder wpb_holder vc_clearfix vc_container_for_children">
<ul class="tabs_controls">
</ul>
%content%
</div>',
	'default_content' => '
[vc_tab title="' . esc_html__( 'Tab 1', 'js_composer' ) . '" tab_id=""][/vc_tab]
[vc_tab title="' . esc_html__( 'Tab 2', 'js_composer' ) . '" tab_id=""][/vc_tab]
',
	'js_view' => 'VcTabsView',
];
