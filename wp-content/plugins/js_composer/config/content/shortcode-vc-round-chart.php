<?php
/**
 * Configuration file for [vc_round_chart] shortcode of 'Round Chart' element.
 *
 * @see https://kb.wpbakery.com/docs/inner-api/vc_map/ for more detailed information about element attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

return [
	'name' => esc_html__( 'Round Chart', 'js_composer' ),
	'base' => 'vc_round_chart',
	'class' => '',
	'icon' => 'icon-wpb-vc-round-chart',
	'element_default_class' => 'wpb_content_element',
	'category' => esc_html__( 'Content', 'js_composer' ),
	'description' => esc_html__( 'Pie and Doughnut charts', 'js_composer' ),
	'params' => [
		[
			'type' => 'textfield',
			'heading' => esc_html__( 'Widget title', 'js_composer' ),
			'param_name' => 'title',
			'description' => esc_html__( 'Enter text used as widget title (Note: located above content element).', 'js_composer' ),
			'admin_label' => true,
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Design', 'js_composer' ),
			'param_name' => 'type',
			'value' => [
				esc_html__( 'Pie', 'js_composer' ) => 'pie',
				esc_html__( 'Doughnut', 'js_composer' ) => 'doughnut',
			],
			'description' => esc_html__( 'Select type of chart.', 'js_composer' ),
			'admin_label' => true,
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Style', 'js_composer' ),
			'description' => esc_html__( 'Select chart color style.', 'js_composer' ),
			'param_name' => 'style',
			'value' => [
				esc_html__( 'Flat', 'js_composer' ) => 'flat',
				esc_html__( 'Modern', 'js_composer' ) => 'modern',
				esc_html__( 'Custom', 'js_composer' ) => 'custom',
			],
			'dependency' => [
				'callback' => 'vcChartCustomColorDependency',
			],
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Gap', 'js_composer' ),
			'param_name' => 'stroke_width',
			'value' => [
				0 => 0,
				1 => 1,
				2 => 2,
				5 => 5,
			],
			'description' => esc_html__( 'Select gap size.', 'js_composer' ),
			'std' => 2,
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Outline color', 'js_composer' ),
			'param_name' => 'stroke_color',
			'value' => vc_get_shared( 'colors-dashed' ) + [ esc_html__( 'Custom', 'js_composer' ) => 'custom' ],
			'description' => esc_html__( 'Select outline color.', 'js_composer' ),
			'param_holder_class' => 'vc_colored-dropdown',
			'std' => 'white',
			'dependency' => [
				'element' => 'stroke_width',
				'value_not_equal_to' => '0',
			],
		],
		[
			'type' => 'colorpicker',
			'heading' => esc_html__( 'Custom outline color', 'js_composer' ),
			'param_name' => 'custom_stroke_color',
			'description' => esc_html__( 'Select custom outline color.', 'js_composer' ),
			'default_colorpicker_color' => '#FFFFFF',
			'dependency' => [
				'element' => 'stroke_color',
				'value' => [ 'custom' ],
			],
		],
		[
			'type' => 'checkbox',
			'heading' => esc_html__( 'Show legend?', 'js_composer' ),
			'param_name' => 'legend',
			'description' => esc_html__( 'If checked, chart will have legend.', 'js_composer' ),
			'value' => [ esc_html__( 'Yes', 'js_composer' ) => 'yes' ],
			'std' => 'yes',
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Legend color', 'js_composer' ),
			'param_name' => 'legend_color',
			'value' => vc_get_shared( 'colors-dashed' ) + [ esc_html__( 'Custom', 'js_composer' ) => 'custom' ],
			'description' => esc_html__( 'Select legend color.', 'js_composer' ),
			'param_holder_class' => 'vc_colored-dropdown',
			'std' => 'black',
			'dependency' => [
				'element' => 'legend',
				'value' => 'yes',
			],
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Legend position', 'js_composer' ),
			'param_name' => 'legend_position',
			'value' => [
				esc_html__( 'Top', 'js_composer' ) => 'top',
				esc_html__( 'Left', 'js_composer' ) => 'left',
				esc_html__( 'Bottom', 'js_composer' ) => 'bottom',
				esc_html__( 'Right', 'js_composer' ) => 'right',
			],
			'description' => esc_html__( 'Select legend position.', 'js_composer' ),
			'std' => 'left',
			'dependency' => [
				'element' => 'legend',
				'value' => 'yes',
			],
		],
		[
			'type' => 'colorpicker',
			'heading' => esc_html__( 'Custom legend color', 'js_composer' ),
			'default_colorpicker_color' => '#2a2a2a',
			'param_name' => 'custom_legend_color',
			'description' => esc_html__( 'Select custom legend color.', 'js_composer' ),
			'dependency' => [
				'element' => 'legend_color',
				'value' => [ 'custom' ],
			],
		],
		[
			'type' => 'checkbox',
			'heading' => esc_html__( 'Show hover values?', 'js_composer' ),
			'param_name' => 'tooltips',
			'description' => esc_html__( 'If checked, chart will show values on hover.', 'js_composer' ),
			'value' => [ esc_html__( 'Yes', 'js_composer' ) => 'yes' ],
			'std' => 'yes',
		],
		[
			'type' => 'param_group',
			'heading' => esc_html__( 'Values', 'js_composer' ),
			'param_name' => 'values',
			'value' => rawurlencode( wp_json_encode( [
				[
					'title' => esc_html__( 'One', 'js_composer' ),
					'value' => '60',
					'color' => 'blue',
				],
				[
					'title' => esc_html__( 'Two', 'js_composer' ),
					'value' => '40',
					'color' => 'pink',
				],
			] ) ),
			'params' => [
				[
					'type' => 'textfield',
					'heading' => esc_html__( 'Title', 'js_composer' ),
					'param_name' => 'title',
					'description' => esc_html__( 'Enter title for chart area.', 'js_composer' ),
					'admin_label' => true,
				],
				[
					'type' => 'textfield',
					'heading' => esc_html__( 'Value', 'js_composer' ),
					'param_name' => 'value',
					'description' => esc_html__( 'Enter value for area.', 'js_composer' ),
				],
				[
					'type' => 'dropdown',
					'heading' => esc_html__( 'Color', 'js_composer' ),
					'param_name' => 'color',
					'value' => vc_get_shared( 'colors-dashed' ),
					'description' => esc_html__( 'Select area color.', 'js_composer' ),
					'param_holder_class' => 'vc_colored-dropdown',
				],
				[
					'type' => 'colorpicker',
					'heading' => esc_html__( 'Custom color', 'js_composer' ),
					'default_colorpicker_color' => '#E8E8E8',
					'param_name' => 'custom_color',
					'description' => esc_html__( 'Select custom area color.', 'js_composer' ),
				],
			],
			'callbacks' => [
				'after_add' => 'vcChartParamAfterAddCallback',
			],
		],
		[
			'type' => 'dropdown',
			'heading' => esc_html__( 'Animation', 'js_composer' ),
			'description' => esc_html__( 'Select animation style.', 'js_composer' ),
			'param_name' => 'animation',
			'value' => vc_get_shared( 'animation styles' ),
			'std' => 'easeInOutCubic',
		],
		vc_map_add_css_animation(),
		[
			'type' => 'el_id',
			'heading' => esc_html__( 'Element ID', 'js_composer' ),
			'param_name' => 'el_id',
			'description' => sprintf( esc_html__( 'Enter element ID (Note: make sure it is unique and valid according to %1$sw3c specification%2$s).', 'js_composer' ), '<a href="https://www.w3schools.com/tags/att_global_id.asp" target="_blank">', '</a>' ),
		],
		[
			'type' => 'textfield',
			'heading' => esc_html__( 'Extra class name', 'js_composer' ),
			'param_name' => 'el_class',
			'description' => esc_html__( 'Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer' ),
		],
		[
			'type' => 'css_editor',
			'heading' => esc_html__( 'CSS box', 'js_composer' ),
			'param_name' => 'css',
			'group' => esc_html__( 'Design Options', 'js_composer' ),
			'value' => [
				'margin-bottom' => '35px',
			],
		],
	],
];
