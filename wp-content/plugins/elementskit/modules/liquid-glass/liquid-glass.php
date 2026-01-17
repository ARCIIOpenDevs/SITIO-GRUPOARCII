<?php
namespace Elementor;

use Elementor\Controls_Manager;

defined('ABSPATH') || die();

class ElementsKit_Liquid_Glass {
	public  function __construct() {
		add_action( 'elementor/element/column/section_advanced/after_section_end', [ $this, 'register_liquid_glass_section' ], 10 );
		add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'register_liquid_glass_section' ], 10 );
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_liquid_glass_section' ], 10 );

		// Flexbox Container support
		add_action( 'elementor/element/container/section_layout/after_section_end', array( $this, 'register_liquid_glass_section' ) );
	}

	public function enqueue_frontend_scripts() {
		return [
			'scripts' => [
				[
					'name' => 'ekit-liquid-glass',
					'conditions' => [
						'terms' => [
							[
								'name' => 'ekit_liquid_glass_enable',
								'operator' => '===',
								'value' => 'yes',
							],
						],
					],
				],
			],
			'styles' => [
				[
					'name' => 'ekit-liquid-glass',
					'conditions' => [
						'terms' => [
							[
								'name' => 'ekit_liquid_glass_enable',
								'operator' => '===',
								'value' => 'yes',
							],
						],
					],
				],
			],
		];
	}

	public function register_liquid_glass_section($element) {
		$element->start_controls_section(
			'elementskit_liquid_glass_section',
			[
				'label' => esc_html__( 'Elementskit Liquid Glass', 'elementskit' ),
				'tab' => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'ekit_liquid_glass_enable',
			[
				'label' => esc_html__('Enable Liquid Glass', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'assets' => $this->enqueue_frontend_scripts(),
			]
		);

		$element->add_control(
			'ekit_liquid_glass_preset',
			[
				'label' => esc_html__( 'Liquid Glass Effect', 'elementskit' ),
				'description' => esc_html__( 'Tip: Use a semi-transparent background to see the effect clearly.', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'preset2',
				'options' => [
					'preset1'  => esc_html__( 'Soft Ripple', 'elementskit' ),
					'preset2'  => esc_html__( 'Deep Glass', 'elementskit' ),
					'preset3'  => esc_html__( 'Crystal Flow', 'elementskit' ),
					'preset4'  => esc_html__( 'Heavy Distortion', 'elementskit' ),
					'preset5'  => esc_html__( 'Liquid Mist', 'elementskit' ),
					'preset6'  => esc_html__( 'Vertical Wave', 'elementskit' ),
					'preset7'  => esc_html__( 'Horizontal Flow', 'elementskit' ),
					'preset8'  => esc_html__( 'Balanced Blur', 'elementskit' ),
					'preset9'  => esc_html__( 'Glass Storm', 'elementskit' ),
					'preset10' => esc_html__( 'Molten Glass', 'elementskit' ),
				],
				'render_type' => 'template',
				'prefix_class' => 'ekit-liquid-glass-',
				'condition' => [
					'ekit_liquid_glass_enable' => 'yes',
				],
			]
		);

		$element->add_control(
			'ekit_liquid_glass_blur',
			[
				'label' => esc_html__('Blur Strength', 'elementskit'),
				'description' => esc_html__('Leave empty to use the default value of the selected preset.', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--ekit-liquid-glass-blur: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'ekit_liquid_glass_enable' => 'yes',
				],
			]
		);

		$element->add_control(
			'ekit_liquid_glass_shadow',
			[
				'label' => esc_html__( 'Shadow', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'shadow1',
				'options' => [
					'none' => esc_html__( 'None', 'elementskit' ),
					'shadow1' => esc_html__( 'Shadow Preset 01', 'elementskit' ),
					'shadow2' => esc_html__( 'Shadow Preset 02', 'elementskit' ),
					'shadow3' => esc_html__( 'Shadow Preset 03', 'elementskit' ),
					'shadow4' => esc_html__( 'Shadow Preset 04', 'elementskit' ),
					'shadow5' => esc_html__( 'Shadow Preset 05', 'elementskit' ),
					'shadow6' => esc_html__( 'Shadow Preset 06', 'elementskit' ),
					'shadow7' => esc_html__( 'Shadow Preset 07', 'elementskit' ),
					'shadow8' => esc_html__( 'Shadow Preset 08', 'elementskit' ),
					'shadow9' => esc_html__( 'Shadow Preset 09', 'elementskit' ),
					'shadow10' => esc_html__( 'Shadow Preset 10', 'elementskit' ),
					'custom' => esc_html__( 'Custom Shadow', 'elementskit' ),
				],
				// 'render_type' => 'template',
				'prefix_class' => 'ekit-liquid-glass-',
				'condition' => [
					'ekit_liquid_glass_enable' => 'yes',
				],
				'selectors_dictionary' => [
					'none' => '',
					'shadow1' => 'box-shadow: 0 0 15px 0 rgba(255, 255 ,255, 0.6) inset;',
					'shadow1' => 'box-shadow: 0 0 15px 0 rgba(255, 255 ,255, 0.6) inset;',
					'shadow2' => 'box-shadow: 0 0 20px 0 rgba(255, 255, 255, 0.65) inset;',
					'shadow3' => 'box-shadow: 0 0 15px 0 rgba(255, 255, 255, 0.7) inset;',
					'shadow4' => 'box-shadow: 0 20px 15px -5px rgba(255, 255, 255, 0.5) inset;',
					'shadow5' => 'box-shadow: 0 0 30px 1px rgba(255, 255, 255, 0.7) inset;',
					'shadow6' => 'box-shadow: 0 -20px 25px -15px rgba(255, 255, 255, 0.5) inset;',
					'shadow7' => 'box-shadow: 0 10px 25px -10px rgba(255, 255, 255, 0.4) inset;',
					'shadow8' => 'box-shadow: 0 -10px 20px -5px rgba(255, 255, 255, 0.55) inset;',
					'shadow9' => 'box-shadow: 0 0 40px 5px rgba(255, 255, 255, 0.6) inset;',
					'shadow10' => 'box-shadow: 0 15px 15px -10px rgba(255, 255, 255, 0.45) inset;',
					'custom' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '{{VALUE}};',
				],
			]
		);

		$element->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_liquid_glass_shadow_custom',
				'label' => esc_html__( 'Custom Shadow', 'elementskit' ),
				'fields_options' => [
					'box_shadow' => [
						'default'	=> [
							'color' => 'rgba(255, 255, 255, 0.5)',
							'horizontal' => 0,
							'vertical' => 0,
							'blur' => 15,
							'spread' => 0,
							'position' => 'inset',
						],
					],
				],
				'condition' => [
					'ekit_liquid_glass_enable' => 'yes',
					'ekit_liquid_glass_shadow' => 'custom',
				],
				'selector' => '{{WRAPPER}}',
			]
		);

		$element->end_controls_section();
	}
}
