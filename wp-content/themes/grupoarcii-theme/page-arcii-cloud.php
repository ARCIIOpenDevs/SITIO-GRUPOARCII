<?php 
/*
Template Name: ARCII Cloud - Infraestructura
*/
get_header(); ?>

<main id="main" class="site-main division-page arcii-cloud-page">
    
    <!-- Hero Split Section -->
    <section class="division-hero-section">
        <div class="hero-background">
            <div class="hero-glow arcii-cloud-glow"></div>
        </div>
        
        <div class="container">
            <div class="hero-split">
                <!-- Lado Izquierdo - Contenido -->
                <div class="hero-content">
                    <div class="division-logo">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/logos/arcii-cloud-logo.png" alt="ARCII Cloud" />
                    </div>
                    
                    <h1 class="division-title">
                        <span class="title-main">INFRAESTRUCTURA DE</span>
                        <span class="title-highlight arcii-cloud-text">CÓMPUTO SOBERANA</span>
                    </h1>
                    
                    <p class="division-description">
                        La <strong>columna vertebral del procesamiento de datos</strong>. 
                        Proveemos potencia de cómputo bruta, entornos virtualizados y 
                        colocación para operaciones de <span class="text-highlight">misión crítica.</span>
                    </p>
                    
                    <div class="division-stats">
                        <div class="stat-item">
                            <span class="stat-number">99.99%</span>
                            <span class="stat-label">Uptime SLA</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">24/7</span>
                            <span class="stat-label">Monitoreo NOC</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">Tier III</span>
                            <span class="stat-label">Data Center</span>
                        </div>
                    </div>
                </div>
                
                <!-- Lado Derecho - Visual -->
                <div class="hero-visual">
                    <div class="visual-container">
                        <div class="server-illustration">
                            <i class="fas fa-server"></i>
                            <div class="pulse-animation arcii-cloud-pulse"></div>
                        </div>
                        <div class="floating-elements">
                            <div class="floating-icon" style="--delay: 0s;">
                                <i class="fas fa-cloud"></i>
                            </div>
                            <div class="floating-icon" style="--delay: 1s;">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="floating-icon" style="--delay: 2s;">
                                <i class="fas fa-shield-alt"></i>
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
                    <span class="title-highlight arcii-cloud-text">ESPECIALIZADOS</span>
                </h2>
                <p class="section-description">
                    Infraestructura de clase empresarial con la flexibilidad 
                    y potencia que demandan las operaciones modernas.
                </p>
            </div>
            
            <div class="capabilities-grid">
                <!-- Cloud VPS -->
                <div class="capability-card arcii-cloud-card">
                    <div class="card-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3 class="card-title">Cloud VPS</h3>
                    <p class="card-description">
                        Instancias escalables con despliegue en segundos. 
                        Recursos garantizados y aislamiento completo.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Escalado automático</li>
                        <li><i class="fas fa-check"></i> SSD NVMe</li>
                        <li><i class="fas fa-check"></i> IPv4 + IPv6</li>
                        <li><i class="fas fa-check"></i> Snapshots incluidos</li>
                    </ul>
                </div>
                
                <!-- Bare Metal -->
                <div class="capability-card arcii-cloud-card">
                    <div class="card-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3 class="card-title">Bare Metal</h3>
                    <p class="card-description">
                        Servidores dedicados de tenencia única (Single-tenant). 
                        Hardware exclusivo sin virtualización.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> AMD EPYC/Intel Xeon</li>
                        <li><i class="fas fa-check"></i> Hasta 1TB RAM</li>
                        <li><i class="fas fa-check"></i> RAID configurado</li>
                        <li><i class="fas fa-check"></i> IPMI remoto</li>
                    </ul>
                </div>
                
                <!-- Colocation -->
                <div class="capability-card arcii-cloud-card">
                    <div class="card-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 class="card-title">Colocation</h3>
                    <p class="card-description">
                        Espacio en rack con energía y refrigeración redundante. 
                        Tu hardware, nuestra infraestructura.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Racks estándar 42U</li>
                        <li><i class="fas fa-check"></i> UPS N+1</li>
                        <li><i class="fas fa-check"></i> Conectividad múltiple</li>
                        <li><i class="fas fa-check"></i> Acceso 24/7</li>
                    </ul>
                </div>
                
                <!-- Servicios Gestionados -->
                <div class="capability-card arcii-cloud-card">
                    <div class="card-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="card-title">Servicios Gestionados</h3>
                    <p class="card-description">
                        Administración completa de tu infraestructura. 
                        Nos encargamos de todo el stack técnico.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Monitoreo proactivo</li>
                        <li><i class="fas fa-check"></i> Backups automáticos</li>
                        <li><i class="fas fa-check"></i> Actualizaciones</li>
                        <li><i class="fas fa-check"></i> Soporte especializado</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Bloque de Autoridad -->
    <section class="authority-section arcii-cloud-authority">
        <div class="container">
            <div class="authority-content">
                <div class="authority-icon">
                    <i class="fas fa-award"></i>
                </div>
                <div class="authority-text">
                    <h3 class="authority-title">+99.99% Uptime Garantizado por SLA</h3>
                    <p class="authority-description">
                        Infraestructura certificada Tier III con redundancia total en energía, 
                        refrigeración y conectividad. Respaldado por acuerdos de nivel de servicio 
                        que garantizan disponibilidad máxima para operaciones críticas.
                    </p>
                </div>
                <div class="authority-badges">
                    <div class="badge-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>ISO 27001</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-bolt"></i>
                        <span>Tier III</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-leaf"></i>
                        <span>Green IT</span>
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
                    ¿Listo para <span class="arcii-cloud-text">Operar?</span>
                </h2>
                <p class="cta-description">
                    Acelera tu transformación digital con infraestructura soberana. 
                    Desde VPS escalables hasta servidores dedicados de alto rendimiento.
                </p>
                
                <div class="cta-buttons">
                    <a href="https://arciicloud.com" target="_blank" class="btn btn-primary btn-large arcii-cloud-btn">
                        <i class="fas fa-external-link-alt"></i>
                        Visitar Sitio Oficial de ARCII Cloud
                    </a>
                    <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-secondary btn-large">
                        <i class="fas fa-calculator"></i>
                        Cotizar como Corporativo
                    </a>
                </div>
                
                <div class="cta-info">
                    <div class="info-item">
                        <i class="fas fa-headset"></i>
                        <span>Soporte técnico especializado 24/7</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>Despliegue en menos de 15 minutos</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Facturación flexible mensual o anual</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main><!-- #main -->

<style>
/* ARCII Cloud Specific Styles */
.arcii-cloud-text {
    color: #00E5FF !important;
}

.arcii-cloud-glow {
    background: radial-gradient(circle at top right, rgba(0, 229, 255, 0.3) 0%, transparent 70%);
}

.arcii-cloud-pulse {
    background: #00E5FF;
}

.arcii-cloud-card:hover {
    border-color: #00E5FF;
    box-shadow: 0 10px 30px rgba(0, 229, 255, 0.2);
}

.arcii-cloud-authority {
    background: linear-gradient(135deg, rgba(0, 229, 255, 0.1) 0%, transparent 100%);
}

.arcii-cloud-btn {
    background: linear-gradient(135deg, #00E5FF 0%, #0091EA 100%);
    border: none;
}

.arcii-cloud-btn:hover {
    background: linear-gradient(135deg, #0091EA 0%, #00E5FF 100%);
    transform: translateY(-2px);
}
</style>

<?php get_footer(); ?>