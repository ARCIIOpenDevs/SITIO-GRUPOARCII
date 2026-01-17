    </div><!-- #content -->

    <!-- Footer Global -->
    <footer class="site-footer" id="colophon">
        <div class="footer-main">
            <div class="container">
                <div class="footer-content">
                    <!-- Columna 1: Logo y descripción -->
                    <div class="footer-column footer-about">
                        <div class="footer-logo">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/logo-arcii-white.png" alt="Grupo ARCII" />
                        </div>
                        <p class="footer-description">
                            Grupo ARCII es un conglomerado empresarial líder en México, especializado en 
                            transformación digital, consultoría estratégica y desarrollo tecnológico integral.
                        </p>
                        <div class="footer-social">
                            <a href="#" aria-label="LinkedIn" class="social-link">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" aria-label="Twitter" class="social-link">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" aria-label="Facebook" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" aria-label="Instagram" class="social-link">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Columna 2: Ecosistema -->
                    <div class="footer-column footer-ecosystem">
                        <h3 class="footer-title">Ecosistema</h3>
                        <ul class="footer-menu">
                            <li><a href="<?php echo esc_url(home_url('/arcii-cloud')); ?>">ARCII Cloud</a></li>
                            <li><a href="<?php echo esc_url(home_url('/intaxweb')); ?>">IntaxWeb</a></li>
                            <li><a href="<?php echo esc_url(home_url('/brintel')); ?>">BRINTEL</a></li>
                            <li><a href="<?php echo esc_url(home_url('/cernodes')); ?>">CerNodes</a></li>
                        </ul>
                    </div>

                    <!-- Columna 3: Navegación -->
                    <div class="footer-column footer-navigation">
                        <h3 class="footer-title">Navegación</h3>
                        <ul class="footer-menu">
                            <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                            <li><a href="<?php echo esc_url(home_url('/nosotros')); ?>">Nosotros</a></li>
                            <li><a href="<?php echo esc_url(home_url('/talento')); ?>">Talento</a></li>
                            <li><a href="<?php echo esc_url(home_url('/contacto')); ?>">Contacto</a></li>
                            <li><a href="<?php echo esc_url(home_url('/comunicados')); ?>">Comunicados</a></li>
                        </ul>
                    </div>

                    <!-- Columna 4: Contacto -->
                    <div class="footer-column footer-contact">
                        <h3 class="footer-title">Contacto</h3>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div class="contact-details">
                                    <strong>Oficina Principal</strong>
                                    <p><?php echo get_theme_mod('grupoarcii_address', 'Av. Insurgentes Sur No. 1079, Piso 05<br>Col. Nochebuena, Benito Juárez<br>CDMX, México'); ?></p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <div class="contact-details">
                                    <strong>Teléfono</strong>
                                    <p><?php echo get_theme_mod('grupoarcii_phone', '+52 55 1234 5678'); ?></p>
                                </div>
                            </div>
                            
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <div class="contact-details">
                                    <strong>Email</strong>
                                    <p>contacto@grupoarcii.com</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <div class="footer-copyright">
                        <p>&copy; <?php echo date('Y'); ?> Grupo ARCII. Todos los derechos reservados.</p>
                    </div>
                    
                    <div class="footer-legal">
                        <ul class="legal-menu">
                            <li><a href="<?php echo esc_url(home_url('/aviso-privacidad')); ?>">Aviso de Privacidad</a></li>
                            <li><a href="<?php echo esc_url(home_url('/terminos-condiciones')); ?>">Términos y Condiciones</a></li>
                            <li><a href="<?php echo esc_url(home_url('/codigo-etica')); ?>">Código de Ética</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-certifications">
                        <div class="cert-item">
                            <span class="cert-text">ISO 27001</span>
                        </div>
                        <div class="cert-item">
                            <span class="cert-text">ISO 9001</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button class="back-to-top" id="backToTop" aria-label="Volver arriba">
            <i class="fas fa-chevron-up"></i>
        </button>
    </footer><!-- #colophon -->

</div><!-- #page -->

<!-- Cookie Notice -->
<div class="cookie-notice" id="cookieNotice" style="display: none;">
    <div class="container">
        <div class="cookie-content">
            <div class="cookie-text">
                <p>Utilizamos cookies para mejorar su experiencia en nuestro sitio web. Al continuar navegando, acepta nuestro uso de cookies.</p>
            </div>
            <div class="cookie-actions">
                <button class="btn btn-secondary btn-sm cookie-accept" onclick="acceptCookies()">Aceptar</button>
                <a href="<?php echo esc_url(home_url('/politica-cookies')); ?>" class="cookie-link">Más información</a>
            </div>
        </div>
    </div>
</div>

<script>
// Cookie notice functionality
function showCookieNotice() {
    if (!localStorage.getItem('cookiesAccepted')) {
        document.getElementById('cookieNotice').style.display = 'block';
    }
}

function acceptCookies() {
    localStorage.setItem('cookiesAccepted', 'true');
    document.getElementById('cookieNotice').style.display = 'none';
}

// Back to top functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTopBtn = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });
    
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // Show cookie notice
    showCookieNotice();
});

// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mobileOverlay = document.querySelector('.mobile-menu-overlay');
    const mobileClose = document.querySelector('.mobile-menu-close');
    const body = document.body;
    
    if (mobileToggle && mobileOverlay) {
        mobileToggle.addEventListener('click', function() {
            mobileOverlay.classList.add('active');
            body.classList.add('mobile-menu-open');
            mobileToggle.setAttribute('aria-expanded', 'true');
        });
        
        mobileClose.addEventListener('click', function() {
            mobileOverlay.classList.remove('active');
            body.classList.remove('mobile-menu-open');
            mobileToggle.setAttribute('aria-expanded', 'false');
        });
        
        // Close on overlay click
        mobileOverlay.addEventListener('click', function(e) {
            if (e.target === mobileOverlay) {
                mobileOverlay.classList.remove('active');
                body.classList.remove('mobile-menu-open');
                mobileToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>

<?php wp_footer(); ?>

</body>
</html>