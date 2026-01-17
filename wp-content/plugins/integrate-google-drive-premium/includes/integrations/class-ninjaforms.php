<?php

namespace IGD;

use NF_Abstracts_Field;

defined( 'ABSPATH' ) || exit;

// Register Field
add_filter( 'ninja_forms_register_fields', function ( $fields ) {
	$fields['integrate_google_drive'] = new NinjaForms();

	return $fields;
} );

// Load Field Templates
add_filter( 'ninja_forms_field_template_file_paths', function ( $file_paths ) {
	$file_paths[] = IGD_INCLUDES . '/integrations/templates/';

	return $file_paths;
} );

// Enqueue Scripts
add_action( 'ninja_forms_enqueue_scripts', function ( $data ) {

	$form_id = $data['form_id'];

	$fields = Ninja_Forms()->form( $form_id )->get_fields();

	foreach ( $fields as $field ) {
		if ( $field->get_setting( 'type' ) == 'integrate_google_drive' ) {

			// Print global nonce to be used in the template file
			if ( ! is_user_logged_in() ) {
				$nonce = wp_create_nonce( 'igd-shortcode-nonce' );
				printf( '<script>var igd_shortcode_nonce = "%s";</script>', htmlspecialchars( $nonce, ENT_QUOTES, 'UTF-8' ) );
			}

			wp_enqueue_style( 'igd-frontend' );
			wp_enqueue_script( 'igd-frontend' );

			break;
		}
	}

} );

// Create entry folder if needed
add_action( 'ninja_forms_after_submission', function ( $data ) {

	$igd_fields = [];

	foreach ( $data['fields'] as $field ) {
		if ( $field['settings']['type'] == 'integrate_google_drive' ) {
			$igd_fields[] = $field;
		}
	}

	if ( ! empty( $igd_fields ) ) {

		function igd_ninja_forms_handle_form_field_tags( $name_template, $form_fields ) {
			$extra_tags = [];

			// get %field_{key}% from the file name template
			preg_match_all( '/%field_([^%]+)%/', $name_template, $matches );
			$field_keys = $matches[1];

			if ( ! empty( $field_keys ) ) {
				foreach ( $form_fields as $tagField ) {
					$key = $tagField['settings']['key'];

					if ( ! in_array( $key, $field_keys ) ) {
						continue;
					}

					$field_value = $tagField['settings']['value'];

					// Handle array values, such as checkboxes
					if ( is_array( $field_value ) ) {
						$field_value = implode( ', ', $field_value );
					}

					$extra_tags[ '%field_' . $key . '%' ] = $field_value;
				}

			}

			return $extra_tags;
		}

		foreach ( $igd_fields as $field ) {
			$value = $field['settings']['value'];

			if ( empty( $value ) ) {
				continue;
			}

			$files = [];

			// Fetch file ids from the value text
			preg_match_all( '/file\/d\/(.*?)\/view/', $value, $matches );

			$file_ids = $matches[1];

			if ( empty( $file_ids ) ) {
				continue;
			}

			foreach ( $file_ids as $file_id ) {
				$files[] = App::instance()->get_file_by_id( $file_id );
			}

			if ( empty( $files ) ) {
				continue;
			}

			$shortcode_data = Shortcode::get_shortcode( $field['settings']['module_id'] )['config'] ?? [];

			$tag_data = [
				'form' => [
					'form_title' => $data['settings']['title'],
					'form_id'    => $data['form_id'],
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
				$extra_tags = igd_ninja_forms_handle_form_field_tags( $file_name_template, $data['fields'] );

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

			if ( false !== strpos( $entry_folder_name_template, '%entry_id%' ) ) {
				$entry_post_id = $data['actions']['save']['sub_id'];

				$entry_id                     = get_post_meta( $entry_post_id, '_seq_num', true );
				$tag_data['form']['entry_id'] = $entry_id;
			}

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
				$extra_tags = igd_ninja_forms_handle_form_field_tags( $entry_folder_name_template, $data['fields'] );
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

} );

class NinjaForms extends NF_Abstracts_Field {
	protected $_name = 'integrate_google_drive';
	protected $_type = 'integrate_google_drive';
	protected $_nicename = 'Google Drive';
	protected $_parent_type = 'textbox';
	protected $_section = 'common';
	protected $_templates = 'integrate_google_drive';
	protected $_icon = 'cloud-upload';
	protected $_test_value = false;

	protected $_settings_all_fields = [
		'key',
		'label',
		'label_pos',
		'required',
		'classes',
		'manual_key',
		'help',
		'description',
	];

	public function __construct() {
		parent::__construct();

		add_action( 'nf_admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		//load template in editor
		add_action( 'ninja_forms_builder_templates', [ $this, 'maybe_include_template' ] );

		$this->register_custom_settings();

		add_filter( 'igd_localize_data', [ $this, 'localize_data' ] );
	}

	public function localize_data( $data ) {
		$data['shortcodes'] = Shortcode::instance()->get_shortcodes();

		return $data;
	}

	public function maybe_include_template() {
		$template_path = IGD_INCLUDES . '/integrations/templates/fields-integrate_google_drive.html';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}

	}

	protected function register_custom_settings() {

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

		$this->_settings = array_merge( $this->_settings, [
			'module_id'     => [
				'name'    => 'module_id',
				'type'    => 'select',
				'value'   => '',
				'options' => $options,
				'label'   => __( 'Select Module', 'integrate-google-drive' ),
				'group'   => 'primary',
				'help'    => __( 'Select an existing Google Drive module or create a new one.', 'integrate-google-drive' ),
			],
			'igd_configure' => [
				'name'  => 'igd_configure',
				'type'  => 'html',
				'value' => sprintf(
					'<div class="igd-form-uploader-config">
						<button type="button" class="igd-form-uploader-trigger igd-form-uploader-trigger-ninjaforms igd-btn btn-primary">
							<i class="dashicons dashicons-admin-generic"></i>
							<span>%s</span>
						</button>
					</div>',
					__( 'Configure', 'integrate-google-drive' )
				),
				'group' => 'primary',
				'width' => 'full',
			],
		] );
	}

	public function admin_enqueue_scripts() {
		if ( class_exists( '\IGD\Enqueue' ) ) {
			Enqueue::instance()->admin_scripts( '', false );
		}
	}

	public function validate( $field, $data ) {
		$errors = parent::validate( $field, $data );

		if ( ! empty( $errors ) ) {
			return $errors;
		}


		$shortcode_data = Shortcode::get_shortcode( $field['settings']['module_id'] )['config'] ?? [];

		$min_files = isset( $shortcode_data['minFiles'] ) ? absint( $shortcode_data['minFiles'] ) : 0;
		$files     = ! empty( $field['value'] ) ? array_filter( array_map( 'trim', explode( '),', $field['value'] ) ) ) : [];

		if ( $min_files && count( $files ) < $min_files ) {
			$errors['slug']    = 'min-files-error';
			$errors['message'] = sprintf(
			/* translators: %s: minimum number of files */
				__( 'Please upload at least %s file(s).', 'integrate-google-drive' ),
				$min_files
			);
		}

		return $errors;
	}


}
