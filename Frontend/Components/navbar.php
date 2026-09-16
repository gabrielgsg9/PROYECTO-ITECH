<nav class="navbar">
    <a href="#" class="navbar__brand">
        <div class="navbar__logo">🦷</div>
        <div class="navbar__name">
            OdontoClinic
            <span>Portal del Paciente</span>
        </div>
    </a>

    <button class="navbar__toggle" id="navbarToggle" aria-label="Abrir menú">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="navbar__menu" id="navbarMenu">
        <ul class="navbar__links">
            <li><a href="home.php">Inicio</a></li>
            <li><a href="agenda.php" class="active">Mis Citas</a></li>
            <li><a href="#">Historial</a></li>
            <li><a href="#">Estudios</a></li>
            <li><a href="#">Mi Perfil</a></li>
            <li><a href="logout.php">Salir</a></li>
        </ul>

        <?php if (isset($patient) && $patient !== null): ?>
            <div class="navbar__user">
                <div class="navbar__avatar">
                    <?= htmlspecialchars(strtoupper(substr($patient['nombre'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <span class="navbar__user-name"><?= htmlspecialchars($patient['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>
    </div>
</nav>
