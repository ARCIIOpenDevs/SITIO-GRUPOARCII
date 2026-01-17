<?php
/**
 * Plugin Name:       Benchmark Carousel
 * Description:       Muestra las campañas de Benchmark Email en un carrusel mediante un shortcode.
 * Version:           3.0
 * Author:            ARCII Development Team
 * Author URI:        https://dev.grupoarcii.com
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. CREAR LA PÁGINA DE AJUSTES EN EL ADMIN
add_action( 'admin_menu', 'bch_create_settings_page' );
add_action( 'admin_init', 'bch_register_settings' );

function bch_create_settings_page() {
    add_options_page(
        'Benchmark Carousel',
        'Benchmark Carousel',
        'manage_options',
        'benchmark-carousel',
        'bch_render_settings_page_html'
    );
}

function bch_register_settings() {
    register_setting( 'bch_settings_group', 'bch_accounts' ); // Cambiado a 'bch_accounts'
}

function bch_render_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>Configuración de Benchmark Carousel</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'bch_settings_group' );
            do_settings_sections( 'bch_settings_group' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Cuentas de Benchmark</th>
                    <td>
                        <textarea name="bch_accounts" rows="10" cols="50" class="large-text"><?php echo esc_attr( get_option( 'bch_accounts' ) ); ?></textarea>
                        <p class="description">
                            Pega una cuenta por línea, separando el email de login y la Clave API v1.0 con una coma.
                        </p>
                        <p><strong>Ejemplo:</strong> <code>tu-email@dominio.com,tu-clave-api-12345</code></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// 2. ENCOLAR (CARGAR) NUESTRO CSS
add_action( 'wp_enqueue_scripts', 'bch_enqueue_styles' );
function bch_enqueue_styles() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'benchmark_carousel' ) ) {
        wp_enqueue_style(
            'bch-styles',
            plugin_dir_url( __FILE__ ) . 'assets/styles.css',
            [],
            '3.0' // Versión actualizada
        );
    }
}

// 3. LA FUNCIÓN DEL SHORTCODE [benchmark_carousel]
add_shortcode( 'benchmark_carousel', 'bch_display_carousel' );

function bch_display_carousel() {
    
    $accounts_string = get_option( 'bch_accounts' );
    if ( empty( $accounts_string ) ) {
        if ( current_user_can('manage_options') ) {
            return '<p style="color:red; font-weight:bold;">Benchmark Carousel: No hay cuentas configuradas. <a href="'.admin_url('options-general.php?page=benchmark-carousel').'">Configurar ahora</a>.</p>';
        }
        return '';
    }

    $accounts_list = explode( "\n", $accounts_string );
    $all_campaigns = [];
    $debug_messages = ''; 

    foreach ( $accounts_list as $account_line ) {
        $account_line = trim( $account_line );
        if ( empty( $account_line ) ) continue;
        
        $parts = explode( ',', $account_line, 2 ); // Limitar a 2 partes
        if ( count( $parts ) !== 2 ) {
            $debug_messages .= '<li><strong>Error de formato:</strong> La línea "' . esc_html($account_line) . '" no tiene el formato email,clave.</li>';
            continue;
        }

        $username = trim( $parts[0] );
        $password = trim( $parts[1] ); // Esta es la "Clave API"

        // --- INICIO DE CACHÉ ---
        // La caché ahora se basa en el username
        $transient_key = 'bch_campaigns_v3_0_' . substr( md5( $username ), 0, 10 );
        $campaign_data = get_transient( $transient_key );

        if ( false === $campaign_data ) {
            $campaign_data = bch_fetch_benchmark_campaigns_v1_0( $username, $password ); // Llamar a la nueva función
            set_transient( $transient_key, $campaign_data, 3600 ); 
        }
        // --- FIN DE CACHÉ ---

        // --- Revisar el resultado ---
        if ( isset( $campaign_data['error'] ) ) {
            $debug_messages .= '<li><strong>Cuenta (' . esc_html( $username ) . '):</strong> ' . $campaign_data['error'] . '</li>';
        } elseif ( is_array( $campaign_data ) && ! empty( $campaign_data ) ) {
            $all_campaigns = array_merge( $all_campaigns, $campaign_data );
        } else {
            $debug_messages .= '<li><strong>Cuenta (' . esc_html( $username ) . '):</strong> Conexión exitosa, pero no se encontraron campañas "Sent".</li>';
        }
    }

    // --- Si no hay campañas, mostrar mensaje (con depuración) ---
    if ( empty( $all_campaigns ) ) {
        $output = '<p>No se encontraron campañas recientes.</p>';
        
        if ( ! empty( $debug_messages ) && current_user_can('manage_options') ) {
            $output .= '<div class="bch-debug-info">';
            $output .= '<strong>[Información de depuración (solo admin)]</strong>';
            $output .= '<ul>' . $debug_messages . '</ul>';
            $output .= '<p>Para forzar una nueva comprobación, desactiva y vuelve a activar el plugin para limpiar la caché.</p>';
            $output .= '</div>';
        }
        return $output;
    }

    // --- Ordenar todas las campañas (de todas las cuentas) por fecha ---
    usort( $all_campaigns, function( $a, $b ) {
        return strtotime( $b['modifiedDate'] ) - strtotime( $a['modifiedDate'] );
    });

    // --- Construir el HTML de salida ---
    $output = '<div class="bch-carousel-wrapper">';
    $output .= '<div class="bch-carousel-track">';

    foreach ( $all_campaigns as $campaign ) {
        $fecha = date_i18n( 'j \d\e F, Y', strtotime( $campaign['modifiedDate'] ) );

        $output .= '<article class="bch-email-card">';
        $output .= '<div class="bch-card-contenido">';
        $output .= '<p class="bch-card-fecha">' . esc_html( $fecha ) . '</p>';
        $output .= '<h3 class="bch-card-asunto">' . esc_html( $campaign['subject'] ) . '</h3>';
        $output .= '<p class="bch-card-descripcion">' . esc_html( $campaign['name'] ) . '</p>';
        $output .= '<a href="' . esc_url( $campaign['webpageURL'] ) . '" class="bch-card-link" target="_blank" rel="noopener noreferrer">';
        $output .= 'Ver emailing completo';
        $output .= '</a>';
        $output .= '</div>';
        $output .= '</article>';
    }

    $output .= '</div>'; // Fin de .bch-carousel-track
    $output .= '</div>'; // Fin de .bch-carousel-wrapper

    return $output;
}


// 4. FUNCIÓN AUXILIAR REESCRITA PARA API V1.0 (Login y después GetList)
function bch_fetch_benchmark_campaigns_v1_0( $username, $password ) {
    
    // Cargar la librería XML-RPC de WordPress
    if ( ! class_exists( 'IXR_Client' ) ) {
        include_once( ABSPATH . WPINC . '/class-IXR.php' );
    }

    // ¡ENDPOINT CORRECTO!
    $client = new IXR_Client( 'http://api.benchmarkemail.com/1.0/' );
    $client->timeout = 20; 
    $client->useragent = 'Benchmark-Carousel-WordPress-Plugin'; 

    // --- PASO 1: LOGIN PARA OBTENER EL TOKEN ---
    if ( ! $client->query( 'login', $username, $password ) ) {
        return ['error' => 'Error de API v1.0 (Login): ' . esc_html( $client->getErrorMessage() )];
    }

    $token = $client->getResponse();
    
    // Verificar si el login devolvió un error (ej. credenciales inválidas)
    if ( isset( $token['status'] ) && $token['status'] === 'error' ) {
         $error_msg = isset($token['msg']) ? $token['msg'] : 'Credenciales inválidas.';
        return ['error' => 'Error de API v1.0 (Login): ' . esc_html( $error_msg )];
    }
    
    // Verificar que el token sea un string
    if ( ! is_string( $token ) || empty( $token ) ) {
        return ['error' => 'Error de API v1.0 (Login): No se recibió un token válido.'];
    }

    // --- PASO 2: USAR EL TOKEN PARA OBTENER CAMPAÑAS ---
    
    $method_name = 'campaignGetList'; // El método que probamos en v1.7
    $filter = ['status' => 'Sent'];
    $pageNumber = 1;
    $pageSize = 50;
    $sortby = 'date';
    $sortOrder = 'desc';

    if ( ! $client->query(
        $method_name,
        $token, // ¡Usamos el token obtenido!
        $filter,            
        $pageNumber,        
        $pageSize,          
        $sortby,            
        $sortOrder          
    ) ) {
        return ['error' => 'Error de API v1.0 (campaignGetList): ' . esc_html( $client->getErrorMessage() )];
    }

    $data = $client->getResponse();

    // Error devuelto por la función campaignGetList
    if ( isset( $data['status'] ) && $data['status'] === 'error' ) {
         $error_msg = isset($data['msg']) ? $data['msg'] : 'Respuesta desconocida.';
        return ['error' => 'Error de API v1.0 (campaignGetList): ' . esc_html( $error_msg )];
    }

    // Respuesta inesperada
    if ( ! is_array( $data ) ) {
         return ['error' => 'Respuesta inesperada de API v1.0 (campaignGetList). Se esperaba un array.'];
    }

    // Si la API no devuelve NINGÚN item
    if ( empty( $data ) ) {
        return []; // Éxito, pero 0 campañas
    }

    // Convertir la respuesta de la API v1 a nuestro formato interno
    $sent_campaigns = [];
    foreach ( $data as $item ) {
        // Asegurarnos de que tiene los campos que necesitamos
        if ( isset($item['name']) && isset($item['subject']) && isset($item['modifiedDate']) && ! empty( $item['webpageURL'] ) ) {
            $sent_campaigns[] = $item; // El formato ya es el que necesitamos
        }
    }

    if ( empty($sent_campaigns) && ! empty($data) ) {
        return ['error' => 'Conexión exitosa. Se encontraron ' . count($data) . ' campañas "Sent", pero ninguna tenía una "webpageURL" pública.'];
    }

    return $sent_campaigns; // Éxito
}
