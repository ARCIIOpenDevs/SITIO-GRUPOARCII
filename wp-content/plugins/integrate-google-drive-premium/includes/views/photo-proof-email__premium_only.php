<?php

defined( 'ABSPATH' ) || exit;

$selected_files = array_slice( $selected, 0, 20 );

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $subject ); ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #333;
        }

        .container {
            max-width: 720px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .header {
            background-color: #4f5aba;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
        }

        .content {
            padding: 30px;
        }

        .content p {
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .file-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .file-table th,
        .file-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ececec;
        }

        .file-table th {
            background-color: #f0f3f9;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .cta-button {
            background-color: #4f5aba;
            color: #fff;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #888;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>
			<?php

			if ( ! $selection ) {
				echo esc_html__( 'New Proof Submission', 'integrate-google-drive' );
			} else {
				echo esc_html__( 'Proof Submission Updated', 'integrate-google-drive' );
			}

			?>
        </h1>
    </div>

    <div class="content">
        <p><?php esc_html_e( 'Hello Admin,', 'integrate-google-drive' ); ?></p>

        <p>
			<?php

			if ( ! $selection ) {
				esc_html_e( 'A user has submitted their proof selection for review:', 'integrate-google-drive' );
			} else {
				esc_html_e( 'A user has updated their proof selection:', 'integrate-google-drive' );
			}

			?>

            <br>

            <strong>
				<?php
				if ( ! empty( $user['name'] ) ) {
					echo esc_html( $user['name'] ) . ' (' . esc_html( $user['email'] ) . ')';
				} elseif ( ! empty( $user['email'] ) ) {
					echo esc_html( $user['email'] );
				} else {
					esc_html_e( 'Unidentified user', 'integrate-google-drive' );
				}
				?>
            </strong>
        </p>

		<?php if ( $shortcode_title ) : ?>
            <p>
				<?php esc_html_e( 'Module:', 'integrate-google-drive' ); ?>
                <a href="<?php echo esc_url( admin_url( "admin.php?page=integrate-google-drive-shortcode-builder&id=$shortcode_id" ) ); ?>">
					<?php echo esc_html( $shortcode_title ); ?>
                </a>
            </p>
		<?php endif; ?>



		<?php if ( ! empty( $message ) ) : ?>
            <p><strong><?php esc_html_e( 'User Message:', 'integrate-google-drive' ); ?></strong><br>
				<?php echo wp_kses_post( nl2br( esc_html( $message ) ) ); ?></p>
		<?php endif; ?>

        <p>
            <strong><?php printf( esc_html__( 'Selected Files (showing first %d of %d):', 'integrate-google-drive' ), count( $selected_files ), count( $selected ) ); ?></strong>
        </p>

        <table class="file-table">
            <thead>
            <tr>
                <th><?php esc_html_e( 'Thumbnail', 'integrate-google-drive' ); ?></th>
                <th><?php esc_html_e( 'Title', 'integrate-google-drive' ); ?></th>
                <th><?php esc_html_e( 'Tag', 'integrate-google-drive' ); ?></th>
                <th><?php esc_html_e( 'Link', 'integrate-google-drive' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $selected_files as $file ) :
				$icon_url = igd_get_thumbnail_url( $file, 'custom', [ 'width' => 64, 'height' => 64 ] );
				$file_url = sprintf( 'https://drive.google.com/file/d/%1$s/view', $file['id'] );
				?>
                <tr>
                    <td><img src="<?php echo esc_url( $icon_url ); ?>" width="50" height="50"
                             style="border-radius: 6px;"></td>
                    <td><?php echo esc_html( $file['name'] ); ?></td>
                    <td>
						<?php
						if ( ! empty( $file['reviewTag'] ) ) {
							$label = $file['reviewTag']['label'] ?? '';
							$color = $file['reviewTag']['color'] ?? '#000';
							echo sprintf( '<span style="color: %s; font-weight: 500;">%s</span>', esc_attr( $color ), esc_html( $label ) );
						} else {
							echo '&mdash;';
						}
						?>
                    </td>
                    <td><a href="<?php echo esc_url( $file_url ); ?>" class="cta-button" target="_blank"
                           style="background-color:#4CAF50;"><?php esc_html_e( 'View', 'integrate-google-drive' ); ?></a>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align: center">
			<?php if ( $selection_id ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'selection_id', $selection_id, admin_url( 'admin.php?page=integrate-google-drive-proof-selections' ) ) ); ?>"
                   class="cta-button" style="background-color: #999999;">
					<?php esc_html_e( 'View All Files', 'integrate-google-drive' ); ?>
                </a>
			<?php endif; ?>

            <a href="<?php echo esc_url( add_query_arg( [
				'action'       => 'igd_proof_download',
				'selection_id' => $selection_id,
				'nonce'        => wp_create_nonce( 'igd_proof_download' ),
			], admin_url( 'admin-ajax.php' ) ) ); ?>" class="cta-button">
				<?php esc_html_e( 'Download CSV', 'integrate-google-drive' ); ?>
            </a>
        </div>

    </div>

    <div class="footer">
		<?php
		printf(
			esc_html__( 'This email was generated by the Integrate Google Drive plugin at %s', 'integrate-google-drive' ),
			'<a href="' . esc_url( home_url() ) . '" style="color: inherit; text-decoration: underline;">' . esc_html( get_bloginfo( 'name' ) ) . '</a>'
		);
		?>
    </div>
</div>

</body>
</html>
