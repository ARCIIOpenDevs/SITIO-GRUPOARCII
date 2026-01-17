<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Grupo ARCII - Líderes en transformación digital empresarial con soluciones integrales en tecnología, consultoría y desarrollo.">
    <meta name="keywords" content="Grupo ARCII, transformación digital, consultoría empresarial, desarrollo tecnológico, CDMX">
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri(); ?>/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri(); ?>/assets/favicon-16x16.png">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <!-- Header Global -->
    <header class="site-header" id="masthead">
        <div class="header-container">
            <!-- Top Bar -->
            <div class="header-top">
                <div class="container">
                    <div class="header-top-content">
                        <div class="header-contact">
                            <span class="header-phone">
                                <i class="fas fa-phone"></i>
                                <?php echo get_theme_mod('grupoarcii_phone', '+52 55 1234 5678'); ?>
                            </span>
                            <span class="header-address">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo get_theme_mod('grupoarcii_address', 'Av. Insurgentes Sur No. 1079, Piso 05, Col. Nochebuena, Benito Juárez, CDMX'); ?>
                            </span>
                        </div>
                        <div class="header-social">
                            <a href="#" aria-label="LinkedIn" class="social-link">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" aria-label="Twitter" class="social-link">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" aria-label="Facebook" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Navigation -->
            <nav class="main-navigation" role="navigation">
                <div class="container">
                    <div class="nav-wrapper">
                        <!-- Logo -->
                        <div class="site-branding">
                            <?php if (has_custom_logo()) : ?>
                                <?php the_custom_logo(); ?>
                            <?php else : ?>
                                <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/logo-arcii.png" alt="Grupo ARCII" />
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Navigation Menu -->
                        <div class="main-menu">
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'primary',
                                'menu_id' => 'primary-menu',
                                'container' => false,
                                'menu_class' => 'nav-menu',
                                'fallback_cb' => 'grupoarcii_fallback_menu'
                            ));
                            ?>
                        </div>
                        
                        <!-- CTA Button -->
                        <div class="header-cta">
                            <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-primary">
                                Contactar Ahora
                            </a>
                        </div>
                        
                        <!-- Mobile Menu Toggle -->
                        <button class="mobile-menu-toggle" aria-label="Menú" aria-expanded="false">
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                        </button>
                    </div>
                </div>
            </nav>
            
            <!-- Mobile Menu -->
            <div class="mobile-menu-overlay">
                <div class="mobile-menu-content">
                    <div class="mobile-menu-header">
                        <div class="site-branding">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/logo-arcii-white.png" alt="Grupo ARCII" />
                        </div>
                        <button class="mobile-menu-close" aria-label="Cerrar menú">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <nav class="mobile-navigation">
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'container' => false,
                            'menu_class' => 'mobile-nav-menu',
                            'fallback_cb' => 'grupoarcii_fallback_menu'
                        ));
                        ?>
                    </nav>
                    
                    <div class="mobile-menu-footer">
                        <div class="mobile-contact">
                            <p class="mobile-phone">
                                <i class="fas fa-phone"></i>
                                <?php echo get_theme_mod('grupoarcii_phone', '+52 55 1234 5678'); ?>
                            </p>
                            <p class="mobile-address">
                                <i class="fas fa-map-marker-alt"></i>
                                CDMX, México
                            </p>
                        </div>
                        
                        <div class="mobile-social">
                            <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        </div>
                        
                        <div class="mobile-cta">
                            <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-primary btn-block">
                                Contactar Ahora
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header><!-- #masthead -->

    <div id="content" class="site-content">

<?php
// Fallback menu function
function grupoarcii_fallback_menu() {
    echo '<ul class="nav-menu">';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/')) . '">HOME</a></li>';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/nosotros')) . '">NOSOTROS</a></li>';
    echo '<li class="menu-item menu-item-has-children">';
    echo '<a href="' . esc_url(home_url('/ecosistema')) . '">ECOSISTEMA</a>';
    echo '<ul class="sub-menu">';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/arcii-cloud')) . '">ARCII Cloud</a></li>';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/intaxweb')) . '">IntaxWeb</a></li>';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/brintel')) . '">BRINTEL</a></li>';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/cernodes')) . '">CerNodes</a></li>';
    echo '</ul>';
    echo '</li>';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/talento')) . '">TALENTO</a></li>';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/contacto')) . '">CONTACTO</a></li>';
    echo '</ul>';
}
?>