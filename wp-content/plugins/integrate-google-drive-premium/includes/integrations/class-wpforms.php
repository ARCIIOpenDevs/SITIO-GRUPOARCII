<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;

// Check if class exists
if ( ! class_exists( 'WPForms_Field' ) ) {
	return;
}

class WPForms extends \WPForms_Field {

	public function __construct() {
		parent::__construct();

		// Add Integrate Google Drive Group
		add_filter( 'wpforms_builder_fields_buttons', [ $this, 'add_group' ], 8 );
	}

	public function add_group( $fields ) {
		$tmp = [
			'integrate_google_drive' => [
				'group_name' => 'Integrate Google Drive',
				'fields'     => [],
			],
		];

		return array_slice( $fields, 0, 1, true ) + $tmp + array_slice( $fields, 1, count( $fields ) - 1, true );
	}

	public function init() {

		// Define field type information.
		$this->name  = 'Google Drive';
		$this->type  = 'igd-uploader';
		$this->group = 'integrate_google_drive';
		$this->icon  = 'fa-cloud-upload fa-lg';
		$this->order = 3;

		add_action( 'wpforms_builder_enqueues', [ $this, 'enqueue_scripts' ] );

		add_filter( 'wpforms_process_filter', [ $this, 'validation' ], 10, 4 );

		add_action( 'wpforms_process_complete', [ $this, 'may_create_entry_folder' ], 10, 4 );

	}

	public function may_create_entry_folder( $fields, $entry, $form_data, $entry_id ) {

		$igd_fields = [];

		foreach ( $fields as $field ) {
			if ( $field['type'] == $this->type ) {
				$igd_fields[ $field['id'] ] = $field;
			}
		}

		if ( empty( $igd_fields ) ) {
			return;
		}

		foreach ( $igd_fields as $id => $field ) {
			$value = $field['value'];

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

			$shortcode_data = Shortcode::get_shortcode( $form_data['fields'][ $id ]['module_id'] )['config'] ?? [];

			$tag_data = [
				'form' => [
					'form_title' => $form_data['settings']['form_title'],
					'form_id'    => $form_data['id'],
					'entry_id'   => $entry_id,
				]
			];

			$upload_folder = ! empty( $shortcode_data['folders'] ) && is_array( $shortcode_data['folders'] ) ? reset( $shortcode_data['folders'] )
				: [ 'id' => 'root', 'accountId' => '', ];

			// Rename files
			$file_name_template = ! empty( $shortcode_data['uploadFileName'] ) ? $shortcode_data['uploadFileName'] : '%file_name%%file_extension%';

			// Check if the file name template contains dynamic tags
			if ( igd_contains_tags( 'field_id', $file_name_template ) ) {

				// Get dynamic tags by filtering the form data
				$extra_tags = $this->handle_form_field_tags( $file_name_template, $fields );

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

			// Add user and post tags
			if ( igd_contains_tags( 'user', $entry_folder_name_template ) ) {
				if ( is_user_logged_in() ) {
					$tag_data['user'] = get_userdata( get_current_user_id() );
				}
			}

			if ( igd_contains_tags( 'post', $entry_folder_name_template ) ) {
				$referrer = wp_get_referer();

				if ( ! empty( $referrer ) ) {
					// Get the post ID from the referrer URL
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
			if ( igd_contains_tags( 'field_id', $entry_folder_name_template ) ) {
				$extra_tags = $this->handle_form_field_tags( $entry_folder_name_template, $fields );
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

	private function handle_form_field_tags( $name_template, $form_fields ) {
		$extra_tags = [];

		// get %field_{key}% from the file name template
		preg_match_all( '/%field_id_([^%]+)%/', $name_template, $matches );
		$field_ids = $matches[1];

		if ( ! empty( $field_ids ) ) {
			foreach ( $form_fields as $tagField ) {
				$field_id = $tagField['id'];

				if ( ! in_array( $field_id, $field_ids ) ) {
					continue;
				}

				$field_value = $tagField['value'];

				// Handle array values, such as checkboxes
				if ( is_array( $field_value ) ) {
					$field_value = implode( ', ', $field_value );
				}

				$extra_tags[ '%field_id_' . $field_id . '%' ] = $field_value;
			}

		}

		return $extra_tags;
	}

	public function validation( $fields, $entry, $form_data ) {
		foreach ( $fields as $field_id => $field ) {

			// Check if the field type is 'file-upload'
			if ( 'igd-uploader' !== $field['type'] ) {
				continue;
			}

			$shortcode_data = Shortcode::get_shortcode( $form_data['fields'][ $field_id ]['module_id'] )['config'] ?? [];

			// Get the minimum file uploads setting
			$min_file_uploads = isset( $shortcode_data['minFiles'] ) ? (int) $shortcode_data['minFiles'] : 0;

			// If the minimum file uploads is not set or is zero, no validation is needed
			if ( $min_file_uploads <= 0 ) {
				continue;
			}

			// Count the number of uploaded files
			$value          = $entry['fields'][ $field_id ] ?? '';
			$uploaded_files = explode( ' ),', $value );

			$uploaded_files_count = count( $uploaded_files );

			// Check if the number of uploaded files is less than the minimum requirement
			if ( $uploaded_files_count < $min_file_uploads ) {
				// Add a validation error message
				wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = sprintf(
					__( 'Please upload at least %d files.', 'integrate-google-drive' ),
					$min_file_uploads
				);
			}
		}

		return $fields;
	}

	// Frontend - Field display on the form front-end.
	public function field_display( $field, $deprecated, $form_data ) {

		$module_id      = $field['module_id'] ?? '';
		$shortcode_data = Shortcode::get_shortcode( $module_id )['config'] ?? [];

		$default_data = [
			'type'           => 'uploader',
			'isFormUploader' => 'wpforms',
		];

		$data = wp_parse_args( $shortcode_data, $default_data );

		if ( ! empty( $field['required'] ) ) {
			$data['isRequired'] = true;
		}

		echo Shortcode::instance()->render_shortcode( [], $data );

		$field_id = sprintf( 'wpforms-%d-field_%d', $form_data['id'], $field['id'] );
		printf( '<input type="text" name="wpforms[fields][%d]" id="%s" class="upload-file-list igd-hidden">', $field['id'], $field_id );
	}

	/**
	 * Admin
	 * -----------------------------------------------------------------------------------------------------------------
	 * Format field value which is stored.
	 *
	 * @param int $field_id field ID
	 * @param mixed $field_submit field value that was submitted
	 * @param array $form_data form data and settings
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		if ( $this->type !== $form_data['fields'][ $field_id ]['type'] ) {
			return;
		}

		$name = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] ) : '';

		wpforms()->process->fields[ $field_id ] = [
			'name'  => $name,
			'value' => $field_submit,
			'id'    => absint( $field_id ),
			'type'  => $this->type,
		];
	}

	// Enqueue scripts
	public function enqueue_scripts() {

		if ( empty( wp_styles()->registered['wp-components'] ) ) {
			wp_register_style( 'wp-components', includes_url( 'css/dist/components/style.css' ) );
		}

		Enqueue::instance()->admin_scripts( '', false );

	}

	// Field options panel inside the builder
	public function field_options( $field ) {
		// Options open markup.
		$this->field_option( 'basic-options', $field, [ 'markup' => 'open', ] );

		// Label
		$this->field_option( 'label', $field );

		// Description.
		$this->field_option( 'description', $field );

		// Module ID Label
		$lbl = $this->field_element(
			'label',
			$field,
			[
				'slug'    => 'module_id',
				'value'   => esc_attr__( 'Module ID', 'integrate-google-drive' ),
				'tooltip' => 'Edit and existing module ID or create a new one.',
			],
			false
		);

		// Configure button.
		ob_start(); ?>

        <button data-id="<?php echo esc_attr( $field['id'] ); ?>" id="igd-form-uploader-config-wpforms" type="button"
                class="igd-form-uploader-trigger igd-form-uploader-trigger-wpforms igd-btn btn-primary">
            <i class="dashicons dashicons-admin-generic"></i>
            <span><?php esc_html_e( 'Configure Uploader', 'integrate-google-drive' ); ?></span>
        </button>

		<?php

		$shortcodes = Shortcode::get_shortcodes();

		$shortcodes = array_filter( $shortcodes, function ( $shortcode ) {
			return 'browser' === $shortcode['type'] || 'uploader' === $shortcode['type'];
		} );

		$options = [ '' => __( 'Select Shortcode', 'integrate-google-drive' ) ];

		if ( ! empty( $shortcodes ) ) {
			foreach ( $shortcodes as $shortcode ) {
				$options[$shortcode['id']] = $shortcode['title'];
			}
		}

		$btn_container = ob_get_clean();

		$fld = $this->field_element(
			'select',
			$field,
			[
				'class' => 'igd-uploader-data',
				'slug'  => 'module_id',
				'name'  => __( 'Select Module', 'integrate-google-drive' ),
				'value' => $field['module_id'] ?? '',
				'choicesjs' => true,
				'options' => $options,
			],
			false
		);

		$args = [
			'slug'    => 'module_id',
			'content' => $lbl . $fld . $btn_container,
		];

		$this->field_element( 'row', $field, $args );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Options close markup.
		$this->field_option(
			'basic-options', $field, [ 'markup' => 'close', ]
		);

		// Advanced field options

		// Options open markup.
		$this->field_option(
			'advanced-options',
			$field,
			[ 'markup' => 'open', ]
		);

		// Hide label.
		$this->field_option( 'label_hide', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Options close markup.
		$this->field_option(
			'advanced-options',
			$field,
			[ 'markup' => 'close', ]
		);
	}

	// Field preview inside the builder.
	public function field_preview( $field ) {

		// Label.
		$this->field_preview_option( 'label', $field );

		// Description.
		$this->field_preview_option( 'description', $field );

		$default_data = [
			'id'             => $field['id'],
			'type'           => 'uploader',
			'isFormUploader' => 'wpforms',
			'isRequired'     => ! empty( $field['required'] ),
		];

		$shortcode_data = [];

		if ( ! empty( $field['module_id'] ) ) {
			$shortcode_data = Shortcode::get_shortcode( $field['module_id'] )['config'] ?? [];
		}

		$data = wp_parse_args( $shortcode_data, $default_data );

		echo Shortcode::instance()->render_shortcode( [], $data );

	}

}

new WPForms();