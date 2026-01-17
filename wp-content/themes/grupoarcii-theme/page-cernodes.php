<?php 
/*
Template Name: CerNodes - Gaming
*/
get_header(); ?>

<main id="main" class="site-main division-page cernodes-page">
    
    <!-- Hero Split Section -->
    <section class="division-hero-section">
        <div class="hero-background">
            <div class="hero-glow cernodes-glow"></div>
        </div>
        
        <div class="container">
            <div class="hero-split">
                <!-- Lado Izquierdo - Contenido -->
                <div class="hero-content">
                    <div class="division-logo">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/logos/cernodes-logo.png" alt="CerNodes" />
                    </div>
                    
                    <h1 class="division-title">
                        <span class="title-main">RENDIMIENTO EXTREMO &</span>
                        <span class="title-highlight cernodes-text">BAJA LATENCIA</span>
                    </h1>
                    
                    <p class="division-description">
                        Hosting especializado para <strong>entornos de alta demanda</strong>. 
                        Servidores optimizados para gaming, voz (VoIP) y aplicaciones en 
                        <span class="text-highlight">tiempo real.</span>
                    </p>
                    
                    <div class="division-stats">
                        <div class="stat-item">
                            <span class="stat-number">&lt;5ms</span>
                            <span class="stat-label">Latencia LATAM</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">100%</span>
                            <span class="stat-label">NVMe SSD</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">10Gbps</span>
                            <span class="stat-label">Anti-DDoS</span>
                        </div>
                    </div>
                </div>
                
                <!-- Lado Derecho - Visual -->
                <div class="hero-visual">
                    <div class="visual-container">
                        <div class="gaming-illustration">
                            <i class="fas fa-gamepad"></i>
                            <div class="pulse-animation cernodes-pulse"></div>
                        </div>
                        <div class="floating-elements">
                            <div class="floating-icon" style="--delay: 0s;">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div class="floating-icon" style="--delay: 1s;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="floating-icon" style="--delay: 2s;">
                                <i class="fas fa-bolt"></i>
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
                    <span class="title-highlight cernodes-text">ESPECIALIZADOS</span>
                </h2>
                <p class="section-description">
                    Hardware de última generación optimizado para el rendimiento extremo 
                    que demandan los juegos y aplicaciones en tiempo real.
                </p>
            </div>
            
            <div class="capabilities-grid">
                <!-- Game Hosting -->
                <div class="capability-card cernodes-card">
                    <div class="card-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3 class="card-title">Game Hosting</h3>
                    <p class="card-description">
                        Minecraft, GTA V, Rust con panel Pterodactyl. 
                        Servidores de juego profesionales.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Panel Pterodactyl incluido</li>
                        <li><i class="fas fa-check"></i> Mods y plugins pre-instalados</li>
                        <li><i class="fas fa-check"></i> Backups automáticos</li>
                        <li><i class="fas fa-check"></i> Soporte de juegos especializado</li>
                    </ul>
                </div>
                
                <!-- Protección DDoS -->
                <div class="capability-card cernodes-card">
                    <div class="card-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="card-title">Protección DDoS</h3>
                    <p class="card-description">
                        Mitigación de ataques en capas 3, 4 y 7. 
                        Protección transparente siempre activa.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Hasta 100 Gbps de protección</li>
                        <li><i class="fas fa-check"></i> Detección automática &lt;3s</li>
                        <li><i class="fas fa-check"></i> Sin falsos positivos</li>
                        <li><i class="fas fa-check"></i> Dashboard en tiempo real</li>
                    </ul>
                </div>
                
                <!-- Alto Rendimiento -->
                <div class="capability-card cernodes-card">
                    <div class="card-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <h3 class="card-title">Alto Rendimiento</h3>
                    <p class="card-description">
                        Procesadores Ryzen 9 y almacenamiento NVMe. 
                        Hardware extremo para cargas intensivas.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> AMD Ryzen 9 5950X</li>
                        <li><i class="fas fa-check"></i> Hasta 128GB DDR4</li>
                        <li><i class="fas fa-check"></i> NVMe Gen4 Enterprise</li>
                        <li><i class="fas fa-check"></i> Red 10 Gbps</li>
                    </ul>
                </div>
                
                <!-- VoIP y Streaming -->
                <div class="capability-card cernodes-card">
                    <div class="card-icon">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                    <h3 class="card-title">VoIP y Streaming</h3>
                    <p class="card-description">
                        Infraestructura optimizada para comunicaciones. 
                        Audio/video sin cortes ni delays.
                    </p>
                    <ul class="card-features">
                        <li><i class="fas fa-check"></i> Latencia &lt;5ms LATAM</li>
                        <li><i class="fas fa-check"></i> Jitter controlado</li>
                        <li><i class="fas fa-check"></i> QoS garantizado</li>
                        <li><i class="fas fa-check"></i> CDN integrado</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Bloque de Autoridad -->
    <section class="authority-section cernodes-authority">
        <div class="container">
            <div class="authority-content">
                <div class="authority-icon">
                    <i class="fas fa-globe-americas"></i>
                </div>
                <div class="authority-text">
                    <h3 class="authority-title">Ping Optimizado para LATAM y USA</h3>
                    <p class="authority-description">
                        Nuestros nodos están estratégicamente ubicados para minimizar la latencia 
                        hacia los principales mercados de gaming en Latinoamérica y Estados Unidos. 
                        Conectividad premium con carriers de primer nivel.
                    </p>
                </div>
                <div class="authority-badges">
                    <div class="badge-item">
                        <i class="fas fa-clock"></i>
                        <span>&lt;5ms LATAM</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-network-wired"></i>
                        <span>Tier-1 ISPs</span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-gamepad"></i>
                        <span>Gaming Grade</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gaming Portfolio -->
    <section class="gaming-portfolio-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-main">JUEGOS</span>
                    <span class="title-highlight cernodes-text">SOPORTADOS</span>
                </h2>
                <p class="section-description">
                    Especialistas en los títulos más demandantes del mercado gaming.
                </p>
            </div>
            
            <div class="games-grid">
                <div class="game-item">
                    <div class="game-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <h4>Minecraft</h4>
                    <p>Java/Bedrock, Bukkit, Spigot, Paper</p>
                </div>
                
                <div class="game-item">
                    <div class="game-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h4>GTA V</h4>
                    <p>FiveM, alt:V, RageMP</p>
                </div>
                
                <div class="game-item">
                    <div class="game-icon">
                        <i class="fas fa-hammer"></i>
                    </div>
                    <h4>Rust</h4>
                    <p>Vanilla, Modded, Oxide</p>
                </div>
                
                <div class="game-item">
                    <div class="game-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Counter-Strike</h4>
                    <p>CS:GO, CS2, SourceMod</p>
                </div>
                
                <div class="game-item">
                    <div class="game-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h4>ARK</h4>
                    <p>Survival Evolved, Ascended</p>
                </div>
                
                <div class="game-item">
                    <div class="game-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h4>Más Títulos</h4>
                    <p>Solicita soporte personalizado</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Saliente -->
    <section class="division-cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">
                    ¿Listo para <span class="cernodes-text">Dominar?</span>
                </h2>
                <p class="cta-description">
                    Hardware extremo y latencia mínima. La infraestructura que necesitas 
                    para liderar el gaming y las comunicaciones en tiempo real.
                </p>
                
                <div class="cta-buttons">
                    <a href="https://cernodes.com" target="_blank" class="btn btn-primary btn-large cernodes-btn">
                        <i class="fas fa-external-link-alt"></i>
                        Visitar Sitio Oficial de CerNodes
                    </a>
                    <a href="<?php echo esc_url(home_url('/contacto')); ?>" class="btn btn-secondary btn-large">
                        <i class="fas fa-calculator"></i>
                        Cotizar como Corporativo
                    </a>
                </div>
                
                <div class="cta-info">
                    <div class="info-item">
                        <i class="fas fa-gamepad"></i>
                        <span>Soporte especializado en gaming</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-rocket"></i>
                        <span>Despliegue instantáneo con templates</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users-cog"></i>
                        <span>Comunidad de desarrolladores activa</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main><!-- #main -->

<style>
/* CerNodes Specific Styles */
.cernodes-text {
    color: #FF0000 !important;
    text-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
}

.cernodes-glow {
    background: radial-gradient(circle at top right, rgba(255, 0, 0, 0.3) 0%, transparent 70%);
}

.cernodes-pulse {
    background: #FF0000;
    box-shadow: 0 0 20px rgba(255, 0, 0, 0.5);
}

.cernodes-card:hover {
    border-color: #FF0000;
    box-shadow: 0 10px 30px rgba(255, 0, 0, 0.2);
}

.cernodes-authority {
    background: linear-gradient(135deg, rgba(255, 0, 0, 0.1) 0%, transparent 100%);
}

.cernodes-btn {
    background: linear-gradient(135deg, #FF0000 0%, #CC0000 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(255, 0, 0, 0.3);
}

.cernodes-btn:hover {
    background: linear-gradient(135deg, #CC0000 0%, #FF0000 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 0, 0, 0.4);
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.game-item {
    text-align: center;
    padding: 2rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.game-item:hover {
    background: rgba(255, 0, 0, 0.1);
    transform: translateY(-5px);
}

.game-icon {
    font-size: 3rem;
    color: #FF0000;
    margin-bottom: 1rem;
}

.game-item h4 {
    color: #fff;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.game-item p {
    color: #ccc;
    font-size: 0.9rem;
}
</style>

<?php get_footer(); ?>