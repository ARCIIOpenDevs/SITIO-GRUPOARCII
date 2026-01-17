<?php 
/*
Template Name: Home Page
*/
get_header(); ?>

<main id="main" class="site-main home-page">
    
    <!-- Hero Section (Pantalla Principal) -->
    <section class="hero-section">
        <div class="hero-video-container">
            <video class="hero-video" autoplay muted loop playsinline>
                <source src="<?php echo get_template_directory_uri(); ?>/assets/videos/arcii-hero-bg.mp4" type="video/mp4">
            </video>
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
                        <span class="title-main">Transformamos</span>
                        <span class="title-highlight">el Futuro Digital</span>
                        <span class="title-sub">de tu Empresa</span>
                    </h1>
                    
                    <p class="hero-description">
                        Somos el conglomerado empresarial líder en México especializado en 
                        <strong>transformación digital integral</strong>, combinando consultoría estratégica, 
                        desarrollo tecnológico y soluciones innovadoras para impulsar el crecimiento de tu negocio.
                    </p>
                    
                    <div class="hero-cta">
                        <a href="#ecosistema" class="btn btn-primary btn-large">
                            <i class="fas fa-rocket"></i>
                            Descubre Nuestro Ecosistema
                        </a>
                        <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-secondary btn-large">
                            <i class="fas fa-calendar-check"></i>
                            Agenda una Consulta
                        </a>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number" data-count="500">0</span>
                            <span class="stat-label">Empresas Transformadas</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-count="9">0</span>
                            <span class="stat-label">Años de Experiencia</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-count="50">0</span>
                            <span class="stat-label">Expertos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-count="4">0</span>
                            <span class="stat-label">Divisiones Especializadas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll indicator -->
        <div class="scroll-indicator">
            <div class="scroll-text">Desliza para explorar</div>
            <div class="scroll-arrow">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </section>

    <!-- Ecosistema Grid -->
    <section id="ecosistema" class="ecosystem-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Nuestro Ecosistema</div>
                <h2 class="section-title">
                    <span class="title-main">Cuatro Divisiones</span>
                    <span class="title-highlight">Una Visión Integral</span>
                </h2>
                <p class="section-description">
                    Nuestro conglomerado empresarial integra cuatro divisiones especializadas, 
                    cada una líder en su sector, trabajando en sinergia para ofrecer 
                    soluciones completas de transformación digital.
                </p>
            </div>
            
            <div class="ecosystem-grid">
                <!-- ARCII Cloud -->
                <div class="ecosystem-card" data-division="arcii-cloud">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <div class="card-badge">Infraestructura</div>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">ARCII Cloud</h3>
                        <p class="card-description">
                            Soluciones de infraestructura en la nube, ciberseguridad avanzada 
                            y gestión integral de servicios IT empresariales.
                        </p>
                        
                        <div class="card-features">
                            <span class="feature-tag">Cloud Computing</span>
                            <span class="feature-tag">Ciberseguridad</span>
                            <span class="feature-tag">DevOps</span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="<?php echo esc_url(home_url('/arcii-cloud')); ?>" class="card-link">
                            Explorar Servicios <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <!-- IntaxWeb -->
                <div class="ecosystem-card" data-division="intaxweb">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="card-badge">Desarrollo</div>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">IntaxWeb</h3>
                        <p class="card-description">
                            Desarrollo de software a medida, aplicaciones web y móviles, 
                            e integración de sistemas empresariales complejos.
                        </p>
                        
                        <div class="card-features">
                            <span class="feature-tag">Desarrollo Web</span>
                            <span class="feature-tag">Apps Móviles</span>
                            <span class="feature-tag">Integraciones</span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="<?php echo esc_url(home_url('/intaxweb')); ?>" class="card-link">
                            Ver Proyectos <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <!-- BRINTEL -->
                <div class="ecosystem-card" data-division="brintel">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <div class="card-badge">Telecomunicaciones</div>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">BRINTEL</h3>
                        <p class="card-description">
                            Infraestructura de telecomunicaciones, despliegue de fibra óptica 
                            y conectividad de alta velocidad para el sector empresarial e industrial.
                        </p>
                        
                        <div class="card-features">
                            <span class="feature-tag">Telecomunicaciones</span>
                            <span class="feature-tag">Fibra Óptica</span>
                            <span class="feature-tag">Redes Empresariales</span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="<?php echo esc_url(home_url('/brintel')); ?>" class="card-link">
                            Conocer Servicios <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <!-- CerNodes -->
                <div class="ecosystem-card" data-division="cernodes">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="card-badge">Gaming</div>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="card-title">CerNodes</h3>
                        <p class="card-description">
                            Hosting especializado para gaming, servidores de ultra baja latencia 
                            y protección avanzada contra ataques DDoS para entretenimiento digital.
                        </p>
                        
                        <div class="card-features">
                            <span class="feature-tag">Gaming Hosting</span>
                            <span class="feature-tag">Baja Latencia</span>
                            <span class="feature-tag">Protección DDoS</span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="<?php echo esc_url(home_url('/cernodes')); ?>" class="card-link">
                            Explorar Soluciones <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="ecosystem-cta">
                <div class="cta-content">
                    <h3 class="cta-title">¿Listo para transformar tu empresa?</h3>
                    <p class="cta-description">
                        Nuestro equipo de expertos está preparado para diseñar 
                        la solución perfecta para tus necesidades específicas.
                    </p>
                    <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-primary btn-large">
                        Solicita una Consultoría Gratuita
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Authority Counters -->
    <section class="authority-section">
        <div class="container">
            <div class="authority-grid">
                <div class="authority-item">
                    <div class="authority-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="authority-number" data-count="15">0</div>
                    <div class="authority-label">Certificaciones Internacionales</div>
                </div>
                
                <div class="authority-item">
                    <div class="authority-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="authority-number" data-count="98">0</div>
                    <div class="authority-label">% Satisfacción del Cliente</div>
                </div>
                
                <div class="authority-item">
                    <div class="authority-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="authority-number" data-count="24">0</div>
                    <div class="authority-label">Horas de Soporte</div>
                </div>
                
                <div class="authority-item">
                    <div class="authority-icon">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                    <div class="authority-number" data-count="12">0</div>
                    <div class="authority-label">Países Atendidos</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Press Feed (Comunicados) -->
    <section class="press-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Comunicados</div>
                <h2 class="section-title">
                    <span class="title-main">Últimas</span>
                    <span class="title-highlight">Noticias Corporativas</span>
                </h2>
            </div>
            
            <div class="press-grid">
                <?php
                $comunicados = new WP_Query(array(
                    'post_type' => 'comunicado',
                    'posts_per_page' => 3,
                    'post_status' => 'publish'
                ));
                
                if ($comunicados->have_posts()) :
                    while ($comunicados->have_posts()) : $comunicados->the_post();
                ?>
                    <article class="press-card">
                        <div class="press-date">
                            <?php echo get_the_date('d M Y'); ?>
                        </div>
                        <h3 class="press-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <div class="press-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="press-link">
                            Leer completo <i class="fas fa-arrow-right"></i>
                        </a>
                    </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                ?>
                    <div class="no-press">
                        <p>Próximamente comunicados corporativos...</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="press-cta">
                <a href="<?php echo esc_url(home_url('/comunicados')); ?>" class="btn btn-outline-primary">
                    Ver Todos los Comunicados
                </a>
            </div>
        </div>
    </section>

    <!-- Contact CTA -->
    <section class="contact-cta-section">
        <div class="container">
            <div class="cta-content-center">
                <h2 class="cta-title-large">
                    ¿Tienes un Proyecto en Mente?
                </h2>
                <p class="cta-description-large">
                    Nuestro equipo de expertos está listo para ayudarte a transformar 
                    tus ideas en soluciones digitales exitosas. Contacta con nosotros 
                    y descubre cómo podemos impulsar tu negocio al siguiente nivel.
                </p>
                <div class="cta-buttons">
                    <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Agenda una Reunión
                    </a>
                    <a href="tel:+525512345678" class="btn btn-secondary btn-large">
                        <i class="fas fa-phone"></i>
                        Llama Ahora
                    </a>
                </div>
            </div>
        </div>
    </section>

</main><!-- #main -->

<script>
// Counter animation
function animateCounters() {
    const counters = document.querySelectorAll('[data-count]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.dataset.count);
        const increment = target / 50;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 40);
    });
}

// Intersection Observer for counter animation
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.disconnect();
            }
        });
    });
    
    const authoritySection = document.querySelector('.authority-section');
    if (authoritySection) {
        observer.observe(authoritySection);
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php get_footer(); ?>