<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;

use FluentForm\App\Helpers\Helper;
use FluentForm\App\Modules\Form\FormFieldsParser;
use FluentForm\App\Services\FormBuilder\BaseFieldManager;
use FluentForm\Framework\Helpers\ArrayHelper;

class FluentForms_Field extends BaseFieldManager {

	public $field_type = 'integrate_google_drive';

	public function __construct() {

		parent::__construct( $this->field_type, 'Google Drive Upload', [
			'cloud',
			'google drive',
			'drive',
			'files',
			'upload',
		], 'general' );

		// Data render
		add_filter( 'fluentform/response_render_' . $this->key, [ $this, 'renderResponse' ], 10, 3 );

		// Validation
		add_filter( 'fluentform/validate_input_item_' . $this->key, [ $this, 'validateInput' ], 10, 5 );

		// After form submission
		add_action( 'fluentform/submission_inserted', [ $this, 'may_create_entry_folder' ], 10, 3 );

		// Add to conversational form accepted elements and types
		add_filter( 'fluentform/conversational_accepted_field_elements', function ( $elements ) {
			$elements[] = $this->field_type;

			return $elements;
		} );

		// Convert the field type to a conversational type
		add_filter( 'fluentform/conversational_field_types', function ( $types ) {
			$types[ $this->field_type ] = 'FlowFormTextType';

			return $types;
		} );

		// Load IGD scripts for conversational forms shortcode
		add_filter( 'do_shortcode_tag', [ $this, 'load_conversational_shortcode_scripts' ], 10, 3 );

		// Load IGD scripts for conversational forms URL
		add_filter( 'fluentform/rendering_form', [ $this, 'load_conversational_url_scripts' ] );

		// Enqueue scripts in admin fluentforms page
		add_action( 'fluentform/editor_script_loaded', [ $this, 'enqueue_scripts' ] );

	}

	public function enqueue_scripts() {
		Enqueue::instance()->frontend_scripts();

		wp_enqueue_style( 'igd-fluentforms', IGD_ASSETS . '/css/fluentforms.css', [ 'igd-frontend' ], IGD_VERSION );
		wp_enqueue_script( 'igd-fluentforms', IGD_ASSETS . '/js/fluentforms.js', [ 'igd-frontend' ], IGD_VERSION, true );
	}

	public function load_conversational_url_scripts( $form ) {

		if ( empty( $_GET['fluent-form'] ) || ( method_exists( 'FluentForm\App\Helpers\Helper', 'isConversionForm' ) && ! Helper::isConversionForm( $form->id ) ) ) {
			return $form;
		}


		$fields = FormFieldsParser::getFields( $form, true );

		$igd_fields = [];

		foreach ( $fields as $field ) {
			if ( ! empty( $field['element'] ) && $field['element'] === 'integrate_google_drive' ) {
				$igd_fields[] = $field;
			}
		}

		if ( empty( $igd_fields ) ) {
			return $form;
		}

		// Print fields JS data
		$this->print_fields_js_data( $igd_fields );

		$this->enqueue_scripts();

		global $wp_scripts, $wp_styles;

		$handle            = 'igd-fluentforms';
		$collected_scripts = [];
		$collected_styles  = [];

		// Recursively collect script dependencies
		$collect_script_deps = function ( $handle ) use ( &$collect_script_deps, &$collected_scripts, $wp_scripts ) {

			if ( isset( $collected_scripts[ $handle ] ) || ! isset( $wp_scripts->registered[ $handle ] ) ) {
				return;
			}

			$script = $wp_scripts->registered[ $handle ];

			foreach ( $script->deps as $dep ) {
				$collect_script_deps( $dep );
			}

			$collected_scripts[ $handle ] = $script;

		};

		// Recursively collect style dependencies
		$collect_style_deps = function ( $handle ) use ( &$collect_style_deps, &$collected_styles, $wp_styles ) {
			if ( isset( $collected_styles[ $handle ] ) || ! isset( $wp_styles->registered[ $handle ] ) ) {
				return;
			}

			$style = $wp_styles->registered[ $handle ];

			foreach ( $style->deps as $dep ) {
				$collect_style_deps( $dep );
			}

			$collected_styles[ $handle ] = $style;
		};

		// Collect dependencies
		$collect_script_deps( $handle );
		$collect_style_deps( $handle );

		// Print styles
		foreach ( $collected_styles as $style_handle => $style ) {
			if ( empty( $style->src ) ) {
				continue;
			}
			$href = add_query_arg( 'ver', $style->ver, $style->src );
			printf(
				"<link rel='stylesheet' id='%s-css' href='%s' type='text/css' media='all' />\n",
				esc_attr( $style_handle ),
				esc_url( $href )
			);
		}

		// Print custom styles
		printf( '<style>%s</style>', Enqueue::instance()->get_custom_css() );

		// Print scripts
		foreach ( $collected_scripts as $script_handle => $script ) {
			if ( empty( $script->src ) ) {
				continue;
			}

			if ( ! empty( $script->extra['data'] ) ) {
				printf(
					"<script type='text/javascript' id='%s-js-extra'>\n%s\n</script>\n",
					esc_attr( $script_handle ),
					$script->extra['data']
				);
			}

			$src = add_query_arg( 'ver', $script->ver, $script->src );
			printf(
				"<script type='text/javascript' id='%s-js' src='%s'></script>\n",
				esc_attr( $script_handle ),
				esc_url( $src )
			);
		}

		return $form;
	}

	public function load_conversational_shortcode_scripts( $output, $tag, $attr ) {

		if ( $tag !== 'fluentform' ) {
			return $output;
		}

		if ( empty( $attr['type'] ) || $attr['type'] !== 'conversational' ) {
			return $output;
		}

		$form_id = $attr['id'] ?? null;

		if ( ! $form_id ) {
			return $output;
		}

		$form = Helper::getForm( $form_id );

		if ( ! $form ) {
			return $output;
		}

		$fields = FormFieldsParser::getFields( $form, true );

		$igd_fields = array_filter( $fields, function ( $field ) {
			return isset( $field['element'] ) && $field['element'] === 'integrate_google_drive';
		} );

		if ( empty( $igd_fields ) ) {
			return $output;
		}

		ob_start();

		// Print fields JS data
		$this->print_fields_js_data( $igd_fields );

		$scripts_content = ob_get_clean();

		$output = $scripts_content . $output;

		// Enqueue IGD scripts
		$this->enqueue_scripts();

		return $output;
	}

	public function print_fields_js_data( $fields ) { ?>
        <script>
            var igdFields = <?php echo wp_json_encode( array_values( $fields ) ); ?>;
            var igdShortcodes = <?php echo wp_json_encode( Shortcode::get_shortcodes() ); ?>;
        </script>
	<?php }

	public function may_create_entry_folder( $insertId, $formData, $form ) {
		$igd_fields = [];

		foreach ( $formData as $key => $value ) {
			if ( strpos( $key, 'integrate_google_drive' ) === 0 ) {
				$igd_fields[ $key ] = $value;
			}
		}

		if ( ! empty( $igd_fields ) ) {

			foreach ( $igd_fields as $key => $value ) {

				if ( empty( $value ) ) {
					continue;
				}

				$files = json_decode( $value, true );


				if ( empty( $files ) ) {
					continue;
				}

				$form_fields = FormFieldsParser::getFields( $form, true );

				$field = array_filter( $form_fields, function ( $item ) use ( $key ) {
					return $item['attributes']['name'] === $key;
				} );

				$field = array_shift( $field );

				// IGD field config
				$field_value = $field['attributes']['value'];

				$shortcode_data = Shortcode::get_shortcode( $field_value )['config'] ?? [];

				$tag_data = [
					'form' => [
						'form_title' => $form->title,
						'form_id'    => $form->id,
						'entry_id'   => $insertId,
					]
				];

				$upload_folder = ! empty( $shortcode_data['folders'] ) && is_array( $shortcode_data['folders'] ) ? reset( $shortcode_data['folders'] ) : [
					'id'        => 'root',
					'accountId' => '',
				];

				// Rename files
				$file_name_template = ! empty( $shortcode_data['uploadFileName'] ) ? $shortcode_data['uploadFileName'] : '%file_name%%file_extension%';

				// Check if the file name template contains dynamic tags
				if ( igd_contains_tags( 'field', $file_name_template ) ) {

					// Get dynamic tags by filtering the form data
					$extra_tags = $this->handle_form_field_tags( $file_name_template, $formData );

					$rename_files = [];
					foreach ( $files as $file ) {
						// We will rename the file name
						$tag_data['name'] = $file['name'];

						$name = igd_replace_template_tags( $tag_data, $extra_tags );

						$rename_files[] = [
							'id'   => $file['id'],
							'name' => $name,
						];
					}

					if ( ! empty( $rename_files ) ) {
						App::instance( $upload_folder['accountId'] )->rename_files( $rename_files );
					}

				}

				// Create Entry Folder
				$create_entry_folder   = ! empty( $shortcode_data['createEntryFolders'] );
				$create_private_folder = ! empty( $shortcode_data['createPrivateFolder'] );

				if ( ! $create_entry_folder && ! $create_private_folder ) {
					continue;
				}

				$entry_folder_name_template = ! empty( $shortcode_data['entryFolderNameTemplate'] ) ? $shortcode_data['entryFolderNameTemplate'] : 'Entry (%entry_id%) - %form_title%';

				if ( igd_contains_tags( 'user', $entry_folder_name_template ) ) {
					if ( is_user_logged_in() ) {
						$tag_data['user'] = get_userdata( get_current_user_id() );
					}
				}

				if ( igd_contains_tags( 'post', $entry_folder_name_template ) ) {
					$referrer = wp_get_referer();

					if ( ! empty( $referrer ) ) {
						$post_id = url_to_postid( $referrer );
						if ( ! empty( $post_id ) ) {
							$tag_data['post'] = get_post( $post_id );
							if ( $tag_data['post']->post_type == 'product' ) {
								$tag_data['wc_product'] = wc_get_product( $post_id );
							}
						}
					}
				}

				// Dynamic tags
				$extra_tags = [];
				if ( igd_contains_tags( 'field', $entry_folder_name_template ) ) {
					$extra_tags = $this->handle_form_field_tags( $entry_folder_name_template, $formData );
				}

				$tag_data['name'] = $entry_folder_name_template;
				$folder_name      = igd_replace_template_tags( $tag_data, $extra_tags );

				// Check Private Folders
				$private_folders = ! empty( $shortcode_data['privateFolders'] );
				if ( $private_folders && is_user_logged_in() ) {
					$folders = get_user_meta( get_current_user_id(), 'igd_folders', true );

					if ( ! empty( $folders ) ) {
						$folders = array_values( array_filter( (array) $folders, function ( $item ) {
							return igd_is_dir( $item );
						} ) );
					} elseif ( $create_private_folder ) {
						$folders = Private_Folders::instance()->create_user_folder( get_current_user_id(), $shortcode_data );
					}

					if ( ! empty( $folders ) ) {
						$shortcode_data['folders'] = $folders;
					}
				}

				$merge_folders = isset( $shortcode_data['mergeFolders'] ) ? filter_var( $shortcode_data['mergeFolders'], FILTER_VALIDATE_BOOLEAN ) : false;

				Uploader::instance( $upload_folder['accountId'] )->create_entry_folder_and_move( $files, $folder_name, $upload_folder, $merge_folders, $create_entry_folder );

			}

		}
	}

	private function handle_form_field_tags( $name_template, $formData ) {
		$extra_tags = [];

		// get %field_{key}% from the file name template
		preg_match_all( '/%field_([^%]+)%/', $name_template, $matches );
		$field_keys = $matches[1];

		if ( ! empty( $field_keys ) ) {
			foreach ( $formData as $field_key => $field_value ) {
				if ( ! in_array( $field_key, $field_keys ) ) {
					continue;
				}

				// Handle array values, such as checkboxes
				if ( is_array( $field_value ) ) {
					$field_value = implode( ', ', $field_value );
				}

				$extra_tags[ '%field_' . $field_key . '%' ] = $field_value;
			}

		}

		return $extra_tags;
	}

	public function getComponent() {

		return [
			'index'          => 99,
			'element'        => $this->key,
			'attributes'     => [
				'name'        => $this->key,
				'type'        => 'text',
				'value'       => '',
				'class'       => 'upload-file-list igd-hidden',
				'placeholder' => 'igd-field-preview',
			],
			'settings'       => [
				'container_class'    => '',
				'label'              => esc_html__( 'Attach your documents', 'integrate-google-drive' ),
				'label_placement'    => 'top',
				'value'              => '',
				'configure'          => '',
				'help_message'       => '',
				'admin_field_label'  => '',
				'validation_rules'   => [
					'required' => [
						'value'   => false,
						'message' => esc_html__( 'This field is required', 'integrate-google-drive' ),
					],
				],
				'conditional_logics' => [],
			],
			'editor_options' => [
				'title'      => $this->title,
				'icon_class' => 'ff-edit-files',
				'template'   => 'inputText',
			],
		];
	}

	public function getGeneralEditorElements() {
		return [
			'label',
			'admin_field_label',
			'class',
			'label_placement',
			'validation_rules',
		];
	}

	public function generalEditorElement() {

		$shortcodes = Shortcode::get_shortcodes();

		$shortcodes = array_filter( $shortcodes, function ( $shortcode ) {
			return 'browser' === $shortcode['type'] || 'uploader' === $shortcode['type'];
		} );

		$options = [
			[
				'label' => __( 'Select Shortcode', 'integrate-google-drive' ),
				'value' => '',
			]
		];

		if ( ! empty( $shortcodes ) ) {
			foreach ( $shortcodes as $shortcode ) {
				$options[] = [
					'label' => $shortcode['title'],
					'value' => $shortcode['id'],
				];
			}
		}

		return [

			'value' => [
				'template'  => 'select',
				'label'     => __( 'Select Module', 'integrate-google-drive' ),
				'help_text' => __( 'Select an existing module or create a new module.', 'integrate-google-drive' ),
				'css_class' => 'igd-uploader-data',
				'options'   => $options,
			],

			'configure' => [
				'template'         => 'inputTextarea',
				'inline_help_text' => '<div class="igd-form-uploader-config"><button type="button" class="igd-form-uploader-trigger igd-form-uploader-trigger-fluentforms igd-btn btn-primary"><i class="dashicons dashicons-admin-generic"></i><span>Configure</span></button></div>',
			],

		];

	}

	public function getAdvancedEditorElements() {
		return [
			'name',
			'help_message',
			'container_class',
			'class',
			'conditional_logics',
		];
	}

	public function render( $element, $form ) {

		$elementName = $element['element'];

		$element = apply_filters( 'fluenform_rendering_field_data_' . $elementName, $element, $form );

		$field_value = ! empty( $element['attributes']['value'] ) ? trim( $element['attributes']['value'] ) : '';

		$shortcode_data = Shortcode::get_shortcode( $field_value )['config'] ?? [];

		$default_data = [
			'type'           => 'uploader',
			'isFormUploader' => 'fluentforms',
		];

		$parsed_data = wp_parse_args( $shortcode_data, $default_data );

		if ( method_exists( 'FluentForm\App\Helpers\Helper', 'isMultiStepForm' ) && Helper::isMultiStepForm( $form->id ) ) {
			$parsed_data['uploadImmediately'] = true;
		}

		$shortcode_render = Shortcode::instance()->render_shortcode( [], $parsed_data );

		$field_id = $this->makeElementId( $element, $form ) . '_' . Helper::$formInstance;
		$prefill  = ( isset( $_REQUEST[ $field_id ] ) ? stripslashes( $_REQUEST[ $field_id ] ) : '' );

		$element['attributes']['type']  = 'text';
		$element['attributes']['id']    = $field_id;
		$element['attributes']['class'] = 'upload-file-list igd-hidden';
		$element['attributes']['value'] = $prefill;

		$elMarkup = "%s <input %s>";
		$elMarkup = sprintf( $elMarkup, $shortcode_render, $this->buildAttributes( $element['attributes'], $form ) );

		$html = $this->buildElementMarkup( $elMarkup, $element, $form );

		$this->printContent( 'fluentform_rendering_field_html_' . $elementName, $html, $element, $form );

	}

	public function renderResponse( $response, $field, $form_id ) {
		return apply_filters( 'igd_render_form_field_data', $response, true, $this );
	}

	public function validateInput( $errors, $field, $formData, $fields, $form ) {
		$fieldName = $field['name'];

		$value = $formData[ $fieldName ]; // This is the user input value

		$uploaded_files = json_decode( $value, true );

		$is_required = ! empty( $field['rules']['required']['value'] );
		if ( $is_required && ( empty( $uploaded_files ) || ( 0 === count( (array) $uploaded_files ) ) ) ) {
			return [ ArrayHelper::get( $field, 'raw.settings.validation_rules.required.message' ) ];
		}

		// Validate minFiles
		$field_value    = $field['raw']['attributes']['value'];
		$shortcode_data = Shortcode::get_shortcode( $field_value )['config'] ?? [];

		$minFiles = isset( $shortcode_data['minFiles'] ) ? (int) $shortcode_data['minFiles'] : 0;

		if ( ! empty( $minFiles ) && ( count( (array) $uploaded_files ) < $minFiles ) ) {
			return [ sprintf( __( 'Please upload at least %d files.', 'integrate-google-drive' ), $minFiles ) ];
		}

		return $errors;
	}

}

new FluentForms_Field();