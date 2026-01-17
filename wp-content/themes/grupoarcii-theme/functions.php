<?php
/**
 * Grupo ARCII Corporate Theme Functions
 * Version: 2026.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme setup
function grupoarcii_theme_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form', 
        'comment-list',
        'gallery',
        'caption'
    ));
    add_theme_support('custom-logo');
    add_theme_support('customize-selective-refresh-widgets');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => 'Menú Principal',
        'footer' => 'Menú Footer'
    ));
}
add_action('after_setup_theme', 'grupoarcii_theme_setup');

// Enqueue styles and scripts
function grupoarcii_theme_scripts() {
    // Main stylesheet
    wp_enqueue_style('grupoarcii-style', get_stylesheet_uri(), array(), '1.0.0');
    
    // Google Fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap', array(), null);
    
    // Font Awesome for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
    
    // Custom JavaScript
    wp_enqueue_script('grupoarcii-main', get_template_directory_uri() . '/js/main.js', array('jquery'), '1.0.0', true);
    
    // Smooth scroll and animations
    wp_enqueue_script('grupoarcii-animations', get_template_directory_uri() . '/js/animations.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'grupoarcii_theme_scripts');

// Custom post types for Grupo ARCII
function grupoarcii_register_post_types() {
    // Press Releases (Comunicados)
    register_post_type('comunicado', array(
        'labels' => array(
            'name' => 'Comunicados',
            'singular_name' => 'Comunicado',
            'add_new' => 'Agregar Comunicado',
            'add_new_item' => 'Agregar Nuevo Comunicado',
            'edit_item' => 'Editar Comunicado',
            'new_item' => 'Nuevo Comunicado',
            'view_item' => 'Ver Comunicado',
            'search_items' => 'Buscar Comunicados',
            'not_found' => 'No se encontraron comunicados',
        ),
        'public' => true,
        'has_archive' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'show_in_rest' => true,
    ));
    
    // Team Members
    register_post_type('team_member', array(
        'labels' => array(
            'name' => 'Equipo Directivo',
            'singular_name' => 'Miembro del Equipo',
            'add_new' => 'Agregar Miembro',
            'add_new_item' => 'Agregar Nuevo Miembro',
            'edit_item' => 'Editar Miembro',
        ),
        'public' => true,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'grupoarcii_register_post_types');

// Add custom fields support
function grupoarcii_add_meta_boxes() {
    add_meta_box(
        'team_member_details',
        'Detalles del Miembro',
        'grupoarcii_team_member_callback',
        'team_member',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'grupoarcii_add_meta_boxes');

function grupoarcii_team_member_callback($post) {
    $position = get_post_meta($post->ID, '_team_position', true);
    $linkedin = get_post_meta($post->ID, '_team_linkedin', true);
    $email = get_post_meta($post->ID, '_team_email', true);
    
    echo '<table class="form-table">';
    echo '<tr><th><label for="team_position">Cargo:</label></th><td><input type="text" id="team_position" name="team_position" value="' . esc_attr($position) . '" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="team_linkedin">LinkedIn:</label></th><td><input type="url" id="team_linkedin" name="team_linkedin" value="' . esc_attr($linkedin) . '" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="team_email">Email:</label></th><td><input type="email" id="team_email" name="team_email" value="' . esc_attr($email) . '" class="regular-text" /></td></tr>';
    echo '</table>';
}

// Save custom fields
function grupoarcii_save_team_member_fields($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['team_position'])) {
        update_post_meta($post_id, '_team_position', sanitize_text_field($_POST['team_position']));
    }
    if (isset($_POST['team_linkedin'])) {
        update_post_meta($post_id, '_team_linkedin', esc_url_raw($_POST['team_linkedin']));
    }
    if (isset($_POST['team_email'])) {
        update_post_meta($post_id, '_team_email', sanitize_email($_POST['team_email']));
    }
}
add_action('save_post', 'grupoarcii_save_team_member_fields');

// Contact form handler
function grupoarcii_handle_contact_form() {
    if (isset($_POST['grupoarcii_contact_form'])) {
        $nombre = sanitize_text_field($_POST['nombre']);
        $empresa = sanitize_text_field($_POST['empresa']);
        $cargo = sanitize_text_field($_POST['cargo']);
        $motivo = sanitize_text_field($_POST['motivo']);
        $mensaje = sanitize_textarea_field($_POST['mensaje']);
        $email = sanitize_email($_POST['email']);
        
        $to = get_option('admin_email');
        $subject = '[Grupo ARCII] Nuevo contacto: ' . $motivo;
        $body = "Nombre: $nombre\n";
        $body .= "Empresa: $empresa\n";
        $body .= "Cargo: $cargo\n";
        $body .= "Email: $email\n";
        $body .= "Motivo: $motivo\n\n";
        $body .= "Mensaje:\n$mensaje";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        if (wp_mail($to, $subject, $body, $headers)) {
            wp_redirect(add_query_arg('contact', 'success', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('contact', 'error', wp_get_referer()));
        }
        exit;
    }
}
add_action('wp', 'grupoarcii_handle_contact_form');

// Corporate contact form handler
function grupoarcii_handle_corporate_contact() {
    if (!isset($_POST['contact_nonce']) || !wp_verify_nonce($_POST['contact_nonce'], 'corporate_contact_nonce')) {
        wp_die('Security check failed');
    }
    
    $fullname = sanitize_text_field($_POST['fullname']);
    $company = sanitize_text_field($_POST['company']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $subject = sanitize_text_field($_POST['subject']);
    $message = sanitize_textarea_field($_POST['message']);
    $privacy_accepted = isset($_POST['privacy_accepted']);
    
    // Validation
    $errors = array();
    
    if (empty($fullname)) $errors[] = 'El nombre es requerido';
    if (empty($company)) $errors[] = 'La empresa es requerida';
    if (empty($email) || !is_email($email)) $errors[] = 'Email válido es requerido';
    if (empty($subject)) $errors[] = 'El motivo es requerido';
    if (empty($message)) $errors[] = 'El mensaje es requerido';
    if (!$privacy_accepted) $errors[] = 'Debe aceptar el aviso de privacidad';
    
    // Response
    $response = array();
    
    if (!empty($errors)) {
        $response['success'] = false;
        $response['message'] = implode('. ', $errors);
    } else {
        // Send email
        $to = get_option('admin_email');
        $email_subject = '[CONTACTO CORPORATIVO] ' . $subject . ' - ' . $company;
        $email_body = "CONTACTO CORPORATIVO - GRUPO ARCII\n";
        $email_body .= "================================\n\n";
        $email_body .= "Nombre: $fullname\n";
        $email_body .= "Empresa: $company\n";
        $email_body .= "Email: $email\n";
        $email_body .= "Teléfono: $phone\n";
        $email_body .= "Motivo: $subject\n\n";
        $email_body .= "Mensaje:\n$message\n\n";
        $email_body .= "================================\n";
        $email_body .= "Enviado desde: " . home_url() . "\n";
        $email_body .= "Fecha: " . current_time('Y-m-d H:i:s') . "\n";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $email
        );
        
        if (wp_mail($to, $email_subject, $email_body, $headers)) {
            $response['success'] = true;
            $response['message'] = 'Mensaje enviado exitosamente. Nos pondremos en contacto en un plazo máximo de 24 horas hábiles.';
            
            // Log the contact for future reference
            error_log("Corporate Contact: $company - $fullname - $email - $subject");
        } else {
            $response['success'] = false;
            $response['message'] = 'Error al enviar el mensaje. Por favor, intenta de nuevo o contacta directamente por teléfono.';
        }
    }
    
    // Return JSON response
    wp_send_json($response);
}
add_action('wp_ajax_process_corporate_contact', 'grupoarcii_handle_corporate_contact');
add_action('wp_ajax_nopriv_process_corporate_contact', 'grupoarcii_handle_corporate_contact');

// Customizer settings
function grupoarcii_customize_register($wp_customize) {
    // Company info section
    $wp_customize->add_section('grupoarcii_company', array(
        'title' => 'Información Corporativa',
        'priority' => 30,
    ));
    
    // Phone number
    $wp_customize->add_setting('grupoarcii_phone', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('grupoarcii_phone', array(
        'label' => 'Teléfono Corporativo',
        'section' => 'grupoarcii_company',
        'type' => 'text',
    ));
    
    // Address
    $wp_customize->add_setting('grupoarcii_address', array(
        'default' => 'Av. Insurgentes Sur No. 1079, Piso 05, Col. Nochebuena, Benito Juárez, CDMX',
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    
    $wp_customize->add_control('grupoarcii_address', array(
        'label' => 'Dirección Corporativa',
        'section' => 'grupoarcii_company',
        'type' => 'textarea',
    ));
}
add_action('customize_register', 'grupoarcii_customize_register');

// Security enhancements
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');

?>