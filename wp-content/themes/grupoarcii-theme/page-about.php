<?php 
/*
Template Name: About Page - Nosotros
*/
get_header(); ?>

<main id="main" class="site-main about-page">
    
    <!-- Hero Section Interna -->
    <section class="about-hero-section">
        <div class="hero-background">
            <div class="hero-image" style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/about-hero-bg.jpg')"></div>
            <div class="hero-overlay"></div>
        </div>
        
        <div class="hero-content">
            <div class="container">
                <div class="hero-text-container">
                    <div class="hero-badge">
                        <span class="badge-text">Grupo Empresarial</span>
                        <span class="badge-year">Est. 2015</span>
                    </div>
                    
                    <h1 class="hero-title">
                        <span class="title-main">UNA DÉCADA</span>
                        <span class="title-highlight">CONSTRUYENDO EL</span>
                        <span class="title-sub">FUTURO DIGITAL</span>
                    </h1>
                    
                    <p class="hero-description">
                        La historia de cómo pasamos de un servidor local a un 
                        <strong>ecosistema tecnológico integral</strong> que transforma 
                        la infraestructura digital de México y Latinoamérica.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- El Manifiesto (Misión y Visión) -->
    <section class="manifesto-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">El Manifiesto</div>
                <h2 class="section-title">
                    <span class="title-main">Nuestra Razón</span>
                    <span class="title-highlight">de Existir</span>
                </h2>
            </div>
            
            <div class="manifesto-grid">
                <!-- Misión -->
                <div class="manifesto-card">
                    <div class="card-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <h3 class="card-title">NUESTRA MISIÓN</h3>
                    <div class="card-content">
                        <p>
                            Proveer <strong>infraestructura tecnológica soberana</strong> y soluciones 
                            digitales integrales que permitan a las organizaciones operar con 
                            <span class="text-highlight">seguridad, velocidad e independencia técnica.</span>
                        </p>
                    </div>
                </div>
                
                <!-- Visión -->
                <div class="manifesto-card">
                    <div class="card-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="card-title">VISIÓN 2030</h3>
                    <div class="card-content">
                        <p>
                            Consolidarnos como el <strong>ecosistema tecnológico de capital mexicano</strong> 
                            más robusto para la transformación digital, liderando el mercado de 
                            <span class="text-highlight">infraestructura crítica y entretenimiento.</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Historia - Timeline -->
    <section class="history-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Nuestra Historia</div>
                <h2 class="section-title">
                    <span class="title-main">El Camino</span>
                    <span class="title-highlight">Hacia el Liderazgo</span>
                </h2>
                <p class="section-description">
                    Una década de crecimiento orgánico, innovación constante y 
                    consolidación como referente tecnológico en México.
                </p>
            </div>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-year">2015</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h3 class="timeline-title">El Origen</h3>
                        <p class="timeline-description">
                            Nace la primera iniciativa de hosting <strong>(ARCII Networks)</strong> 
                            con un solo servidor dedicado. La semilla que germinaría 
                            en un ecosistema completo.
                        </p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2018</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <h3 class="timeline-title">Expansión</h3>
                        <p class="timeline-description">
                            Se consolida la división de desarrollo de software <strong>(IntaxWeb)</strong> 
                            para atender la demanda creciente de clientes que requerían 
                            soluciones personalizadas y desarrollo a medida.
                        </p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2021</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <h3 class="timeline-title">Infraestructura Física</h3>
                        <p class="timeline-description">
                            Surge <strong>BRINTEL Redes</strong> para cubrir la necesidad de 
                            conectividad física y fibra óptica en el sector industrial, 
                            completando la cadena de valor tecnológica.
                        </p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2024</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h3 class="timeline-title">Consolidación del Grupo</h3>
                        <p class="timeline-description">
                            Se estructura formalmente <strong>"Grupo ARCII"</strong> como la 
                            matriz administrativa que controla las 4 verticales, 
                            optimizando sinergias y gobierno corporativo.
                        </p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-year">2026</div>
                    <div class="timeline-content">
                        <div class="timeline-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3 class="timeline-title">Nueva Era</h3>
                        <p class="timeline-description">
                            Lanzamiento de la nueva identidad corporativa y 
                            <strong>expansión de nodos en LATAM</strong>. El futuro 
                            comienza ahora con infraestructura de clase mundial.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gobierno Corporativo -->
    <section class="leadership-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Gobierno Corporativo</div>
                <h2 class="section-title">
                    <span class="title-main">LIDERAZGO</span>
                    <span class="title-highlight">ESTRATÉGICO</span>
                </h2>
                <p class="section-description">
                    El equipo directivo que guía la visión estratégica y operacional 
                    del conglomerado más sólido de tecnología en México.
                </p>
            </div>
            
            <div class="leadership-grid">
                <?php
                $args = array(
                    'post_type' => 'team_member',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_team_position',
                            'compare' => 'EXISTS'
                        )
                    )
                );
                $team_query = new WP_Query($args);
                
                if ($team_query->have_posts()) :
                    while ($team_query->have_posts()) : $team_query->the_post();
                        $cargo = get_post_meta(get_the_ID(), '_team_position', true);
                        $linkedin = get_post_meta(get_the_ID(), '_team_linkedin', true);
                        $email = get_post_meta(get_the_ID(), '_team_email', true);
                ?>
                    <div class="team-card">
                        <div class="team-photo-wrapper">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium', array('class' => 'team-photo')); ?>
                            <?php else : ?>
                                <div class="team-photo-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="team-overlay">
                                <div class="team-social">
                                    <?php if ($linkedin) : ?>
                                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="social-link">
                                            <i class="fab fa-linkedin-in"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($email) : ?>
                                        <a href="mailto:<?php echo esc_attr($email); ?>" class="social-link">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="team-info">
                            <h3 class="team-name"><?php the_title(); ?></h3>
                            <p class="team-position"><?php echo esc_html($cargo); ?></p>
                            
                            <?php if (get_the_content()) : ?>
                                <div class="team-bio">
                                    <?php echo wp_trim_words(get_the_content(), 25); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                ?>
                    <!-- Fallback si no hay miembros del equipo -->
                    <div class="team-placeholder">
                        <div class="placeholder-content">
                            <i class="fas fa-users"></i>
                            <h3>Consejo Directivo</h3>
                            <p>La información del equipo directivo se está actualizando. 
                               Próximamente conocerás a los líderes que impulsan la 
                               transformación digital de Grupo ARCII.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Nuestras Sedes -->
    <section class="locations-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Presencia Nacional</div>
                <h2 class="section-title">
                    <span class="title-main">NUESTRAS</span>
                    <span class="title-highlight">SEDES</span>
                </h2>
            </div>
            
            <div class="locations-grid">
                <div class="locations-info">
                    <!-- CDMX Corporativo -->
                    <div class="location-item">
                        <div class="location-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="location-details">
                            <h3 class="location-title">CDMX (Corporativo)</h3>
                            <p class="location-address">
                                Av. Insurgentes Sur No. 1079, Piso 05<br>
                                Col. Nochebuena, Benito Juárez<br>
                                Ciudad de México, CDMX
                            </p>
                            <p class="location-description">
                                <em>"Donde se toman las decisiones."</em><br>
                                Centro neurálgico de operaciones estratégicas y administración corporativa.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Puebla Operaciones -->
                    <div class="location-item">
                        <div class="location-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="location-details">
                            <h3 class="location-title">Puebla (Operaciones)</h3>
                            <p class="location-address">
                                Tehuacán, Puebla<br>
                                Centro de Datos Principal
                            </p>
                            <p class="location-description">
                                <em>"Donde vive la infraestructura."</em><br>
                                Data center de alta disponibilidad con conectividad redundante.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Soporte 24/7 -->
                    <div class="location-item">
                        <div class="location-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="location-details">
                            <h3 class="location-title">NOC 24/7</h3>
                            <p class="location-address">
                                Network Operations Center<br>
                                Monitoreo Continuo
                            </p>
                            <p class="location-description">
                                <em>"Donde nunca descansamos."</em><br>
                                Soporte técnico especializado disponible las 24 horas del día.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Mapa/Imagen -->
                <div class="locations-visual">
                    <div class="map-container">
                        <div class="map-placeholder">
                            <i class="fas fa-map-marked-alt"></i>
                            <p>Mapa Interactivo</p>
                            <small>Ubicaciones estratégicas en México</small>
                        </div>
                        <!-- Aquí se puede integrar Google Maps o Mapbox -->
                        <!--
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=..." 
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                        -->
                    </div>
                </div>
            </div>
            
            <div class="locations-cta">
                <div class="cta-content">
                    <h3 class="cta-title">¿Necesitas visitarnos?</h3>
                    <p class="cta-description">
                        Programa una cita en nuestras oficinas corporativas 
                        o visita nuestro centro de datos en Puebla.
                    </p>
                    <div class="cta-buttons">
                        <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i>
                            Agenda una Visita
                        </a>
                        <a href="tel:+525512345678" class="btn btn-secondary">
                            <i class="fas fa-phone"></i>
                            Contactar Ahora
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Certificaciones y Reconocimientos -->
    <section class="certifications-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-main">CERTIFICACIONES</span>
                    <span class="title-highlight">Y RECONOCIMIENTOS</span>
                </h2>
            </div>
            
            <div class="certifications-grid">
                <div class="cert-item">
                    <div class="cert-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="cert-info">
                        <h4>ISO 27001</h4>
                        <p>Gestión de Seguridad de la Información</p>
                    </div>
                </div>
                
                <div class="cert-item">
                    <div class="cert-logo">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="cert-info">
                        <h4>ISO 9001</h4>
                        <p>Gestión de Calidad</p>
                    </div>
                </div>
                
                <div class="cert-item">
                    <div class="cert-logo">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="cert-info">
                        <h4>Green IT</h4>
                        <p>Tecnología Sustentable</p>
                    </div>
                </div>
                
                <div class="cert-item">
                    <div class="cert-logo">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="cert-info">
                        <h4>Partner Oficial</h4>
                        <p>Microsoft, AWS, Google Cloud</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main><!-- #main -->

<?php get_footer(); ?>