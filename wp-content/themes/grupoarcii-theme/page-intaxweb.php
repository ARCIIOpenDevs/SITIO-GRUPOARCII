<?php 
/*
Template Name: IntaxWeb - Desarrollo
*/
get_header(); ?>

<main id="main" class="site-main division-page intaxweb-page">
    
    <!-- Hero Split Section -->
    <section class="division-hero-section">
        <div class="hero-background">
            <div class="hero-glow intaxweb-glow"></div>
        </div>
        
        <div class="container">
            <div class="hero-split">
                <!-- Lado Izquierdo - Contenido -->
                <div class="hero-content">
                    <div class="division-logo">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/logos/intaxweb-logo.png" alt="IntaxWeb" />
                    </div>
                    
                    <h1 class="division-title">
                        <span class="title-main">INGENIERÍA DE SOFTWARE &</span>
                        <span class="title-highlight intaxweb-text">EXPERIENCIA DIGITAL</span>
                    </h1>
                    
                    <p class="division-description">
                        Transformamos <strong>lógica de negocios compleja</strong> en interfaces 
                        funcionales. Desarrollamos el software, las apps y la identidad que tu 
                        marca necesita para <span class="text-highlight">liderar.</span>
                    </p>
                    
                    <div class="division-stats">
                        <div class="stat-item">
                            <span class="stat-number">150+</span>
                            <span class="stat-label">Proyectos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">8</span>
                            <span class="stat-label">Años Experiencia</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">OWASP</span>
                            <span class="stat-label">Seguridad</span>
                        </div>
                    </div>
                </div>
                
                <!-- Lado Derecho - Visual -->
                <div class="hero-visual">
                    <div class="visual-container">
                        <div class="code-illustration">
                            <i class="fas fa-code"></i>
                            <div class="pulse-animation intaxweb-pulse"></div>
                        </div>
                        <div class="floating-elements">
                            <div class="floating-icon" style="--delay: 0s;">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="floating-icon" style="--delay: 1s;">
                                <i class="fas fa-palette"></i>
                            </div>
                            <div class="floating-icon" style="--delay: 2s;">
                                <i class="fas fa-rocket"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Grid de Capacidades -->
    <section class="capabilities-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-main">SERVICIOS</span>
                    <span class="title-highlight intaxweb-text">ESPECIALIZADOS</span>
                </h2>
                <p class="section-description">
                    Desde concepto hasta producción. Creamos experiencias digitales 
                    que convierten usuarios en embajadores de tu marca.
                </p>
            </div>
            
            <div class="capabilities-grid">
                <!-- Desarrollo a Medida -->
                <div class="capability-card intaxweb-card">
                    <div class="card-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="card-title">Desarrollo a Medida</h3>
                    <p class="card-description">
                        Sistemas Web, CRM y ERPs personalizados. 
                        Arquitectura escalable con tecnologías modernas.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> React/Vue.js + Node.js</li>
                        <li><i class="fas fa-check"></i> API REST y GraphQL</li>
                        <li><i class="fas fa-check"></i> Bases de datos optimizadas</li>
                        <li><i class="fas fa-check"></i> DevOps integrado</li>
                    </ul>
                </div>
                
                <!-- Apps Móviles -->
                <div class="capability-card intaxweb-card">
                    <div class="card-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="card-title">Apps Móviles</h3>
                    <p class="card-description">
                        Soluciones nativas para iOS y Android. 
                        Experiencias fluidas con rendimiento optimizado.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Swift/Kotlin nativo</li>
                        <li><i class="fas fa-check"></i> React Native híbrido</li>
                        <li><i class="fas fa-check"></i> Integración con APIs</li>
                        <li><i class="fas fa-check"></i> App Store deployment</li>
                    </ul>
                </div>
                
                <!-- Diseño UI/UX -->
                <div class="capability-card intaxweb-card">
                    <div class="card-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3 class="card-title">Diseño UI/UX</h3>
                    <p class="card-description">
                        Interfaces centradas en la conversión y usabilidad. 
                        Diseño que vende y usuarios que regresan.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Research de usuarios</li>
                        <li><i class="fas fa-check"></i> Prototipos interactivos</li>
                        <li><i class="fas fa-check"></i> Design Systems</li>
                        <li><i class="fas fa-check"></i> Testing A/B</li>
                    </ul>
                </div>
                
                <!-- Transformación Digital -->
                <div class="capability-card intaxweb-card">
                    <div class="card-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3 class="card-title">Transformación Digital</h3>
                    <p class="card-description">
                        Modernización de sistemas legacy y procesos. 
                        De papel a digital sin interrupciones.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Migración de datos</li>
                        <li><i class="fas fa-check"></i> Integraciones complejas</li>
                        <li><i class="fas fa-check"></i> Automatización</li>
                        <li><i class="fas fa-check"></i> Capacitación incluida</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Bloque de Autoridad -->
    <section class="authority-section intaxweb-authority">
        <div class="container">
            <div class="authority-content">
                <div class="authority-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="authority-text">
                    <h3 class="authority-title">Desarrollos Auditados con Estándares OWASP</h3>
                    <p class="authority-description">
                        Cada línea de código pasa por rigurosas pruebas de seguridad siguiendo 
                        los estándares internacionales OWASP. Tu software estará protegido contra 
                        las vulnerabilidades más críticas desde el primer día.
                    </p>
                </div>
                <div class="authority-badges">
                    <div class="badge-item">
                        <i class="fas fa-bug"></i>
                        <span>OWASP Top 10</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-code-branch"></i>
                        <span>Clean Code</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-rocket"></i>
                        <span>CI/CD</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Saliente -->
    <section class="division-cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">
                    ¿Listo para <span class="intaxweb-text">Innovar?</span>
                </h2>
                <p class="cta-description">
                    Convierte tu visión en software funcional. Desde MVPs ágiles 
                    hasta plataformas empresariales de misión crítica.
                </p>
                
                <div class="cta-buttons">
                    <a href="https://intaxweb.com" target="_blank" class="btn btn-primary btn-large intaxweb-btn">
                        <i class="fas fa-external-link-alt"></i>
                        Visitar Sitio Oficial de IntaxWeb
                    </a>
                    <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-secondary btn-large">
                        <i class="fas fa-calculator"></i>
                        Cotizar como Corporativo
                    </a>
                </div>
                
                <div class="cta-info">
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span>Equipo multidisciplinario especializado</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-sync"></i>
                        <span>Metodologías ágiles Scrum/Kanban</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-handshake"></i>
                        <span>Garantía de satisfacción 100%</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main><!-- #main -->

<style>
/* IntaxWeb Specific Styles */
.intaxweb-text {
    background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.intaxweb-glow {
    background: radial-gradient(circle at top right, rgba(142, 45, 226, 0.3) 0%, rgba(74, 0, 224, 0.2) 50%, transparent 70%);
}

.intaxweb-pulse {
    background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%);
}

.intaxweb-card:hover {
    border-color: #8E2DE2;
    box-shadow: 0 10px 30px rgba(142, 45, 226, 0.2);
}

.intaxweb-authority {
    background: linear-gradient(135deg, rgba(142, 45, 226, 0.1) 0%, rgba(74, 0, 224, 0.05) 100%);
}

.intaxweb-btn {
    background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%);
    border: none;
}

.intaxweb-btn:hover {
    background: linear-gradient(135deg, #4A00E0 0%, #8E2DE2 100%);
    transform: translateY(-2px);
}
</style>

<?php get_footer(); ?>