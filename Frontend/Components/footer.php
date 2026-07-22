<?php

$anio_actual = date('Y');

$links_paciente = [
    ['label' => 'Agendar Cita', 'href' => 'agenda.php'],
    ['label' => 'Mis Citas', 'href' => 'mis-citas.php'],
    ['label' => 'Historial Clínico', 'href' => 'historial.php'],
    ['label' => 'Mi Perfil', 'href' => 'perfil.php'],
];

$links_info = [
    ['label' => 'Servicios', 'href' => '#servicios'],
    ['label' => 'Preguntas Frecuentes', 'href' => '#faq'],
    ['label' => 'Privacidad', 'href' => 'privacidad.php'],
    ['label' => 'Términos', 'href' => 'terminos.php'],
];
?>

<footer class="site-footer">
    <div class="footer__body">
        <div class="footer__grid">

            <!-- Marca -->
            <div class="footer__col footer__col--brand">
                <a href="dashboard.php" class="footer__logo">
                    <div class="footer__logo-icon">🦷</div>
                    <div class="footer__logo-text">
                        OdontoClinic
                        <span>Portal del Paciente</span>
                    </div>
                </a>
                <p class="footer__tagline">
                    Salud bucal con tecnología de vanguardia en cuatro sucursales en Montevideo.
                </p>
                <div class="footer__social">
                    <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                            <circle cx="12" cy="12" r="4" />
                            <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                        </svg>
                    </a>
                    <a href="https://wa.me/59899123456" target="_blank" rel="noopener" aria-label="WhatsApp">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                            <path
                                d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.826L0 24l6.338-1.498A11.96 11.96 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.808 9.808 0 01-5.006-1.373l-.36-.213-3.732.882.931-3.618-.234-.372A9.808 9.808 0 012.182 12C2.182 6.58 6.58 2.182 12 2.182c5.42 0 9.818 4.398 9.818 9.818 0 5.42-4.398 9.818-9.818 9.818z" />
                        </svg>
                    </a>
                    <a href="mailto:info@odontoclinic.com.uy" aria-label="Email">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Mi Portal -->
            <div class="footer__col">
                <h4 class="footer__col-title">Mi Portal</h4>
                <ul class="footer__links">
                    <?php foreach ($links_paciente as $lnk): ?>
                        <li><a class="links_patient" href="<?= htmlspecialchars($lnk['href']) ?>"><?= htmlspecialchars($lnk['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Información -->
            <div class="footer__col">
                <h4 class="footer__col-title">Información</h4>
                <ul class="footer__links">
                    <?php foreach ($links_info as $lnk): ?>
                        <li><a href="<?= htmlspecialchars($lnk['href']) ?>"><?= htmlspecialchars($lnk['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contacto -->
            <div class="footer__col">
                <h4 class="footer__col-title">Contacto</h4>
                <a href="tel:+59829001234" class="footer__contact-item">📞 2900 1234</a>
                <p class="footer__contact-note">Urgencias 24 hs · 365 días</p>
            </div>

        </div>
    </div>

    <div class="footer__legal">
        <div class="footer__legal-inner">
            <p>&copy; <?= $anio_actual ?> OdontoClinic S.A. &middot; Habilitación MSP N.º 12.345</p>
            <div class="footer__legal-links">
                <a href="privacidad.php">Privacidad</a>
                <span>·</span>
                <a href="terminos.php">Términos</a>
            </div>
        </div>
    </div>
</footer>