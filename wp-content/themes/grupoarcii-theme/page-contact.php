<?php 
/*
Template Name: Contacto Corporativo
*/
get_header(); ?>

<main id="main" class="site-main contact-page">
    
    <!-- Hero Section Minimalista -->
    <section class="contact-hero-section">
        <div class="hero-background">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container">
            <div class="hero-content-center">
                <div class="section-badge">Contacto Corporativo</div>
                <h1 class="hero-title">
                    <span class="title-main">INICIEMOS</span>
                    <span class="title-highlight">LA CONVERSACIÓN</span>
                </h1>
                <p class="hero-description">
                    Para proyectos de alto nivel, alianzas estratégicas y relaciones con inversionistas. 
                    <strong>Nuestras puertas están abiertas.</strong>
                </p>
            </div>
        </div>
    </section>

    <!-- Contenedor Principal - Split Layout -->
    <section class="contact-main-section">
        <div class="container">
            <div class="contact-split">
                
                <!-- Columna Izquierda - El Mundo Físico -->
                <div class="contact-info">
                    <div class="info-header">
                        <h2 class="section-title">
                            <span class="title-highlight">NUESTRAS</span>
                            <span class="title-main">SEDES</span>
                        </h2>
                    </div>
                    
                    <!-- Oficinas Corporativas CDMX -->
                    <div class="office-card">
                        <div class="office-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="office-details">
                            <h3 class="office-title">CORPORATIVO CDMX</h3>
                            <div class="office-address">
                                <p>Av. Insurgentes Sur No. 1079, Piso 05</p>
                                <p>Col. Nochebuena, Benito Juárez</p>
                                <p>C.P. 03720, Ciudad de México</p>
                            </div>
                            <div class="office-function">
                                <em>"Estrategia, Legal y Alianzas Comerciales."</em>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Centro de Operaciones Puebla -->
                    <div class="office-card">
                        <div class="office-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="office-details">
                            <h3 class="office-title">OPERACIONES PUEBLA</h3>
                            <div class="office-address">
                                <p>Centro de Operaciones & NOC</p>
                                <p>Tehuacán, Puebla, México</p>
                            </div>
                            <div class="office-function">
                                <em>"NOC, Infraestructura y Desarrollo."</em>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contacto Directo -->
                    <div class="direct-contact">
                        <h4 class="contact-subtitle">Contacto Directo</h4>
                        <div class="contact-methods">
                            <div class="contact-method">
                                <i class="fas fa-envelope"></i>
                                <span>contacto@grupoarcii.com</span>
                            </div>
                            <div class="contact-method">
                                <i class="fas fa-phone"></i>
                                <span><?php echo get_theme_mod('grupoarcii_phone', '+52 (55) 5555-5555'); ?></span>
                            </div>
                            <div class="contact-method">
                                <i class="fas fa-clock"></i>
                                <span>Lunes a Viernes: 9:00 AM - 6:00 PM</span>
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <div class="contact-social">
                            <h5>Síguenos</h5>
                            <div class="social-links">
                                <a href="#" class="social-link" aria-label="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-link" aria-label="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-link" aria-label="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Columna Derecha - El Formulario de Poder -->
                <div class="contact-form">
                    <div class="form-wrapper">
                        <div class="form-header">
                            <h3 class="form-title">Formulario Ejecutivo</h3>
                            <p class="form-description">
                                Comparte los detalles de tu proyecto y nos pondremos en contacto 
                                en un plazo máximo de 24 horas hábiles.
                            </p>
                        </div>
                        
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="corporate-contact-form" id="corporateContactForm">
                            <input type="hidden" name="action" value="process_corporate_contact">
                            <?php wp_nonce_field('corporate_contact_nonce', 'contact_nonce'); ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fullname" class="form-label">Nombre Completo *</label>
                                    <input type="text" id="fullname" name="fullname" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="company" class="form-label">Empresa / Organización *</label>
                                    <input type="text" id="company" name="company" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email" class="form-label">Correo Corporativo *</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone" class="form-label">Teléfono</label>
                                    <input type="tel" id="phone" name="phone" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject" class="form-label">Motivo del Contacto *</label>
                                <select id="subject" name="subject" class="form-control" required>
                                    <option value="">Selecciona una opción</option>
                                    <option value="Proyecto Corporativo">Proyecto Corporativo / Licitación</option>
                                    <option value="Alianza Estratégica">Alianza Estratégica</option>
                                    <option value="Relación Inversionistas">Relación con Inversionistas</option>
                                    <option value="Prensa Medios">Prensa / Medios</option>
                                    <option value="Otro Administrativo">Otro Asunto Administrativo</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="message" class="form-label">Mensaje *</label>
                                <textarea id="message" name="message" class="form-control" rows="5" required 
                                    placeholder="Describe tu proyecto, necesidades específicas, timeline estimado y presupuesto aproximado..."></textarea>
                            </div>
                            
                            <div class="form-group form-checkbox">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="privacy_accepted" required>
                                    <span class="checkmark"></span>
                                    Acepto el <a href="<?php echo esc_url(home_url('/aviso-privacidad')); ?>" target="_blank">Aviso de Privacidad</a> 
                                    y autorizo el tratamiento de mis datos personales.
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-large btn-block">
                                <i class="fas fa-paper-plane"></i>
                                Enviar Mensaje Corporativo
                            </button>
                        </form>
                        
                        <!-- Mensaje de confirmación -->
                        <div id="formMessage" class="form-message" style="display: none;"></div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Directorio de Soporte - El Desvío -->
    <section class="support-filter-section">
        <div class="container">
            <div class="support-filter-content">
                <div class="filter-text">
                    <h4>¿Eres cliente y necesitas soporte técnico?</h4>
                    <p>Para asistencia técnica especializada, contacta directamente con nuestras divisiones:</p>
                </div>
                <div class="filter-buttons">
                    <a href="https://arciicloud.com/soporte" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-cloud"></i>
                        Soporte ARCII Cloud
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <a href="https://intaxweb.com/contacto" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-code"></i>
                        Mesa IntaxWeb
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <a href="https://brintel.mx/contacto" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-broadcast-tower"></i>
                        Mesa BRINTEL
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <a href="https://cernodes.com/soporte" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-gamepad"></i>
                        Soporte CerNodes
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Mapa Oscuro -->
    <section class="map-section">
        <div class="container">
            <div class="map-header">
                <h3 class="map-title">Encuéntranos</h3>
                <p class="map-description">
                    Visítanos en nuestras oficinas corporativas en el corazón de la Ciudad de México.
                </p>
            </div>
        </div>
        
        <div class="map-container">
            <!-- Google Maps Embed - Insurgentes Sur 1079 -->
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3764.8765456789!2d-99.1709!3d19.3650!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85d1ff35f1234567%3A0x1234567890abcdef!2sAv%20Insurgentes%20Sur%201079%2C%20Nochebuena%2C%2003720%20Ciudad%20de%20M%C3%A9xico%2C%20CDMX!5e0!3m2!1ses!2smx!4v1234567890123!5m2!1ses!2smx" 
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                class="dark-map">
            </iframe>
            
            <!-- Overlay de información -->
            <div class="map-overlay">
                <div class="overlay-content">
                    <div class="overlay-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="overlay-info">
                        <h5>Oficinas Corporativas</h5>
                        <p>Av. Insurgentes Sur 1079, Piso 05<br>Col. Nochebuena, CDMX</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main><!-- #main -->

<style>
/* Contact Page Specific Styles */
.contact-hero-section {
    background: linear-gradient(135deg, #121212 0%, #1a1a1a 100%);
    padding: 8rem 0 4rem;
    position: relative;
}

.contact-main-section {
    padding: 6rem 0;
    background: #0a0a0a;
}

.contact-split {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 4rem;
    align-items: start;
}

@media (max-width: 768px) {
    .contact-split {
        grid-template-columns: 1fr;
        gap: 3rem;
    }
}

/* Office Cards */
.office-card {
    display: flex;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border-left: 4px solid var(--arcii-dorado);
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.office-card:hover {
    background: rgba(197, 160, 89, 0.1);
    transform: translateX(5px);
}

.office-icon {
    font-size: 2.5rem;
    color: var(--arcii-dorado);
    margin-right: 1.5rem;
    flex-shrink: 0;
}

.office-title {
    color: #fff;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.office-address p {
    color: #ccc;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.office-function {
    color: var(--arcii-dorado);
    font-style: italic;
    margin-top: 1rem;
}

/* Contact Methods */
.direct-contact {
    margin-top: 3rem;
}

.contact-subtitle {
    color: #fff;
    font-size: 1.2rem;
    margin-bottom: 1.5rem;
}

.contact-method {
    display: flex;
    align-items: center;
    color: #ccc;
    margin-bottom: 1rem;
}

.contact-method i {
    color: var(--arcii-dorado);
    width: 20px;
    margin-right: 1rem;
}

/* Form Styles */
.form-wrapper {
    background: rgba(255, 255, 255, 0.05);
    padding: 3rem;
    border-radius: 16px;
    border: 1px solid rgba(197, 160, 89, 0.2);
}

.form-title {
    color: #fff;
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.form-description {
    color: #ccc;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    color: var(--arcii-dorado);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    width: 100%;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid #333;
    border-radius: 8px;
    color: #fff;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--arcii-dorado);
    background: rgba(0, 0, 0, 0.7);
    box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.1);
}

.form-control::placeholder {
    color: #666;
}

/* Checkbox */
.form-checkbox {
    margin: 2rem 0;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    color: #ccc;
    line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
    opacity: 0;
    position: absolute;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #666;
    border-radius: 4px;
    margin-right: 1rem;
    flex-shrink: 0;
    position: relative;
    transition: all 0.3s ease;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background: var(--arcii-dorado);
    border-color: var(--arcii-dorado);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark:after {
    content: "✓";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #000;
    font-weight: bold;
}

/* Support Filter */
.support-filter-section {
    padding: 3rem 0;
    background: #1a1a1a;
    border-top: 1px solid #333;
}

.support-filter-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 3rem;
}

@media (max-width: 768px) {
    .support-filter-content {
        flex-direction: column;
        text-align: center;
    }
}

.filter-text h4 {
    color: #fff;
    margin-bottom: 0.5rem;
}

.filter-text p {
    color: #ccc;
    margin: 0;
}

.filter-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .filter-buttons {
        justify-content: center;
    }
}

.btn-outline-secondary {
    background: transparent;
    border: 1px solid #666;
    color: #ccc;
    padding: 0.7rem 1.2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;
    white-space: nowrap;
}

.btn-outline-secondary:hover {
    background: rgba(197, 160, 89, 0.1);
    border-color: var(--arcii-dorado);
    color: var(--arcii-dorado);
    text-decoration: none;
}

/* Map Section */
.map-section {
    position: relative;
    background: #000;
}

.map-header {
    padding: 4rem 0 2rem;
    text-align: center;
}

.map-title {
    color: #fff;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.map-description {
    color: #ccc;
    margin: 0;
}

.map-container {
    position: relative;
    width: 100%;
    height: 450px;
}

.dark-map {
    filter: grayscale(100%) invert(92%) contrast(83%) hue-rotate(30deg);
}

.map-overlay {
    position: absolute;
    top: 2rem;
    right: 2rem;
    background: rgba(0, 0, 0, 0.9);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid var(--arcii-dorado);
    max-width: 280px;
}

.overlay-content {
    display: flex;
    align-items: center;
}

.overlay-icon {
    font-size: 1.5rem;
    color: var(--arcii-dorado);
    margin-right: 1rem;
}

.overlay-info h5 {
    color: #fff;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.overlay-info p {
    color: #ccc;
    font-size: 0.9rem;
    margin: 0;
}

/* Contact Social */
.contact-social {
    margin-top: 3rem;
}

.contact-social h5 {
    color: #fff;
    margin-bottom: 1rem;
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-link {
    width: 45px;
    height: 45px;
    background: rgba(197, 160, 89, 0.1);
    border: 1px solid var(--arcii-dorado);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--arcii-dorado);
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: var(--arcii-dorado);
    color: #000;
    transform: translateY(-3px);
    text-decoration: none;
}

/* Form Messages */
.form-message {
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
    text-align: center;
}

.form-message.success {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid #4CAF50;
    color: #4CAF50;
}

.form-message.error {
    background: rgba(244, 67, 54, 0.1);
    border: 1px solid #f44336;
    color: #f44336;
}
</style>

<script>
// Form handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('corporateContactForm');
    const messageDiv = document.getElementById('formMessage');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        submitBtn.disabled = true;
        
        // Collect form data
        const formData = new FormData(form);
        
        // Send via AJAX
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.style.display = 'block';
            
            if (data.success) {
                messageDiv.className = 'form-message success';
                messageDiv.innerHTML = '<i class="fas fa-check"></i> ' + data.message;
                form.reset();
            } else {
                messageDiv.className = 'form-message error';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + data.message;
            }
            
            // Scroll to message
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })
        .catch(error => {
            messageDiv.style.display = 'block';
            messageDiv.className = 'form-message error';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error de conexión. Inténtalo de nuevo.';
        })
        .finally(() => {
            // Restore button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Hide message when user starts typing
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            messageDiv.style.display = 'none';
        });
    });
});
</script>

<?php get_footer(); ?>