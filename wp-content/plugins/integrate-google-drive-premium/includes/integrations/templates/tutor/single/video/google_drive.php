<?php
/**
 * Display Video Google Drive
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$video_info = tutor_utils()->get_video_info();
$poster     = tutor_utils()->avalue_dot( 'poster', $video_info );
$poster_url = $poster ? wp_get_attachment_url( $poster ) : '';

do_action( 'tutor_lesson/single/before/video/google_drive' );


?>

<?php if ( $video_info ) {

    $url = tutor_utils()->array_get( 'source_google_drive', $video_info );

    $is_preview_url = strpos( $url, 'direct_file' ) !== false;
    $is_stream_url  = strpos( $url, 'igd_stream' ) !== false;

    if ( $is_preview_url ) { ?>

        <div class="tutor-video-player">
            <input type="hidden" id="tutor_video_tracking_information"
                   value="<?php echo esc_attr( json_encode( $jsonData ?? null ) ); ?>">
            <div class="loading-spinner" aria-hidden="true"></div>
            <div class="tutor-ratio tutor-ratio-16x9">
                <?php

                $id         = tutor_utils()->array_get( 'id_google_drive', $video_info );
                $account_id = tutor_utils()->array_get( 'account_id_google_drive', $video_info );

                if ( $is_preview_url ) {
                    parse_str( parse_url( $url, PHP_URL_QUERY ), $queryParams );

                    if ( isset( $queryParams['direct_file'] ) ) {
                        // Decode the base64-encoded JSON string
                        $decodedJson = base64_decode( $queryParams['direct_file'] );

                        // Decode the JSON string to an associative array
                        $data = json_decode( $decodedJson, true );

                        $id         = $data['id'] ?? $id;
                        $account_id = $data['account_id'] ?? $account_id;
                    }
                }

                $secure_video_playback = igd_get_settings( 'secureVideoPlayback' );

                if ( $secure_video_playback ) {
                    $url = add_query_arg( array(
                            'secure_embed' => 1,
                            'id'           => base64_encode( $id ),
                            'account_id'   => base64_encode( $account_id ),
                            'nonce'        => wp_create_nonce( 'igd' ),
                    ), home_url() );
                } else {
                    $url = "https://drive.google.com/file/d/$id/preview";
                }

                $embed_code = '<iframe src="' . $url . '" width="640" height="480" allowfullscreen="allowfullscreen"  allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';

                echo wp_kses(
                        $embed_code,
                        array(
                                'iframe' => array(
                                        'src'             => true,
                                        'title'           => true,
                                        'height'          => true,
                                        'width'           => true,
                                        'frameborder'     => true,
                                        'allowfullscreen' => true,
                                        'allow'           => true,
                                        'style'           => true,
                                        'sandbox'         => true,
                                ),
                        )
                );
                ?>
            </div>
        </div>
        <?php

    } else {

        if ( ! empty( tutor_utils()->array_get( 'id_google_drive', $video_info ) ) ) {

            $id         = tutor_utils()->array_get( 'id_google_drive', $video_info );
            $account_id = tutor_utils()->array_get( 'account_id_google_drive', $video_info );

            $url = add_query_arg( array(
                    'igd_stream'   => 1,
                    'id'           => $id,
                    'account_id'   => $account_id,
                    'ignore_limit' => 1,
            ), home_url() );

        }

        ?>

        <div class="tutor-video-player">
            <input type="hidden" id="tutor_video_tracking_information"
                   value="<?php echo esc_attr( json_encode( $jsonData ?? null ) ); ?>">

            <div class="loading-spinner" aria-hidden="true"></div>

            <video
                    poster="<?php echo esc_url( $poster_url ); ?>"
                    class="tutorPlayer"
                    preload="metadata"
                    playsinline
                    controls
            >
                <source src="<?php echo esc_url( $url ); ?>"
                        type="<?php echo esc_attr( tutor_utils()->avalue_dot( 'type', $video_info ) ); ?>">
            </video>
        </div>
    <?php }
}

?>

<?php do_action( 'tutor_lesson/single/after/video/google_drive' ); ?>
