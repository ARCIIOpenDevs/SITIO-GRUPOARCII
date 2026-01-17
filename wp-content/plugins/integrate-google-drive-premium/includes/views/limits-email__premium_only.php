<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$user_name = sprintf( '<a style="text-decoration: none;color:#fff;" href="%s">%s</a>', get_edit_user_link( $user->ID ), $user->display_name );

$site_link = sprintf( '<a style="text-decoration: none; color:#fff;" href="%s">%s</a>', esc_url( home_url() ), esc_html( get_bloginfo( 'name' ) ) );

$title = __( 'Hi There', 'integrate-google-drive' );

$limit_text = __( 'download limits', 'integrate-google-drive' );

if ( $limit_type == 'downloadLimits' ) {
	$limit_text = __( 'downloads limitation per day', 'integrate-google-drive' );
} elseif ( $limit_type == 'downloadsPerFile' ) {
	$limit_text = __( 'downloads per file limitation', 'integrate-google-drive' );
} elseif ( $limit_type == 'bandwidthLimits' ) {
	$limit_text = __( 'bandwidth usage limitation', 'integrate-google-drive' );
} elseif ( $limit_type == 'zipDownloadLimits' ) {
	$limit_text = __( 'zip downloads limitation', 'integrate-google-drive' );
}

// Translators: %1$s is the username, %2$s is the site link
$text = __( sprintf( 'User %s has encountered the %s.', $user_name, $limit_text ), 'integrate-google-drive' );

if ( $is_user_recipient ) {
	$title = "Hi $user->display_name";
	$text  = __( sprintf( 'You have encountered the %s.', $limit_text ), 'integrate-google-drive' );
}

$primary_color = igd_get_settings( 'primaryColor', '#3C82F6' );

?>

<!DOCTYPE html>
<html lang="<?php echo get_bloginfo( 'language' ); ?>">
<head>
    <meta charset="<?php echo get_bloginfo( 'charset' ); ?>">
    <title><?php echo $subject; ?></title>
</head>
<body>

<!-- Email Container -->
<table width="100%" border="0" cellspacing="0" cellpadding="0"
       style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; max-width: 600px; margin: auto; text-align: center;">

    <!-- Header -->
    <tr>
        <td style="background: <?php echo esc_attr( $primary_color ); ?>; padding: 20px;">
            <h2 style="color: #FFFFFF; margin: 0;"><?php echo esc_html( $title ); ?></h2>
            <p style="color: #FFFFFF; margin: 0;"><?php echo $text; ?></p>
        </td>
    </tr>

    <!-- Usage Limits -->
    <tr>
        <td style="background: #FFFFFF; padding: 15px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="7" style="text-align: left;">

                <tr>
                    <td style="margin-bottom: 15px;">
                        <strong><?php echo 'global' == $this->limits_type ? esc_html__( 'Global Usage Limits : ', 'integrate-google-drive' ) : esc_html__( 'Module Usage Limits : ', 'integrate-google-drive' ); ?></strong>
                    </td>
                </tr>

                <tr>
                    <td style="">
						<?php echo esc_html( sprintf( __( 'Downloads Per Day : %s', 'integrate-google-drive' ), ! empty( $this->limits_data['downloadLimits'] ) ? $this->limits_data['downloadLimits'] : __( 'Unlimited', 'integrate-google-drive' ) ) ); ?>
                    </td>
                </tr>
                <tr>

                    <td style="">
						<?php echo esc_html( sprintf( __( 'Downloads Per File : %s', 'integrate-google-drive' ), ! empty( $this->limits_data['downloadsPerFile'] ) ? $this->limits_data['downloadsPerFile'] : __( 'Unlimited', 'integrate-google-drive' ) ) ); ?>
                    </td>
                </tr>
                <tr>
                    <td style="">
						<?php echo esc_html( sprintf( __( 'Zip Downloads Per Day : %s', 'integrate-google-drive' ), ! empty( $this->limits_data['zipDownloadLimits'] ) ? $this->limits_data['zipDownloadLimits'] : __( 'Unlimited', 'integrate-google-drive' ) ) ); ?>
                    </td>
                </tr>
                <tr>
                    <td style="">
						<?php echo esc_html( sprintf( __( 'Bandwidth Usage Per Day : %s', 'integrate-google-drive' ), ! empty( $this->limits_data['bandwidthLimits'] ) ? $this->limits_data['bandwidthLimits'] . 'MB' : __( 'Unlimited', 'integrate-google-drive' ) ) ); ?>
                    </td>

                </tr>
            </table>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background: #EAEAEA; padding: 20px;">
            <p style="color: #333333; margin: 0;"><?php echo esc_html__( 'Best Regards,', 'integrate-google-drive' ); ?></p>
            <h3 style="color: <?php echo esc_attr( $primary_color ); ?>; margin: 0;"><?php bloginfo( 'name' ); ?></h3>
        </td>
    </tr>

    <!-- Additional Footer -->
    <tr>
        <td style="text-align:center; padding: 20px;">
            <p style="color: #777; margin: 0; font-size: 14px;">
				<?php echo esc_html__( 'This email has been generated from Integrate Google Drive at', 'integrate-google-drive' ) . ' '; ?>
                <a href="<?php echo esc_url( home_url() ); ?>" target="_blank"
                   style="color: <?php echo esc_attr( $primary_color ); ?>; margin: 0; text-decoration: none;"><?php bloginfo( 'name' ); ?></a>.
            </p>
        </td>
    </tr>

</table>
</body>
</html>

