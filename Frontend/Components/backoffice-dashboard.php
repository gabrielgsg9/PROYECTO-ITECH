<?php
require_once __DIR__ . '/../../Data/dataClinica.php';
$dashboardBasePath = $dashboardBasePath ?? '../';
$dashboardUserName = htmlspecialchars((string) ($_SESSION['user_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$dashboardRole = (string) ($_SESSION['role'] ?? '');
$isAdministrator = $dashboardRole === 'administrador';
$registeredPatients = count(array_filter(
    get_auth_users(),
    static fn (array $user): bool => ($user['role'] ?? '') === 'paciente'
));
$activeProfessionals = count($profesionales ?? []);
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel principal - Clinica Imagen</title>
    <link rel="stylesheet" href="<?= $dashboardBasePath ?>STYLES/dashboard.css">
</head>

<body>
    <div class="backoffice"><input class="backoffice__menu-toggle" type="checkbox" id="backoffice-menu">
        <aside class="sidebar" aria-label="Navegacion principal"><a class="sidebar__brand"
                href="<?= $dashboardBasePath ?>home.php"><span class="sidebar__brand-mark">CI</span>Clinica Imagen</a>
            <nav class="sidebar__nav"><a class="sidebar__link sidebar__link--active"
                    href="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">Inicio</a><span
                    class="sidebar__link sidebar__link--disabled" title="Modulo aun no disponible">Pacientes</span><a
                    class="sidebar__link" href="<?= $dashboardBasePath ?>agenda.php">Turnos</a><span
                    class="sidebar__link sidebar__link--disabled"
                    title="Modulo aun no disponible">Sucursales</span><span
                    class="sidebar__link sidebar__link--disabled"
                    title="Modulo aun no disponible">Profesionales</span><span
                    class="sidebar__link sidebar__link--disabled"
                    title="Modulo aun no disponible">Estudios</span><?php if ($isAdministrator): ?><span
                        class="sidebar__link sidebar__link--disabled" title="Modulo aun no disponible">Usuarios
                        internos</span><?php endif; ?><span class="sidebar__link sidebar__link--disabled"
                    title="Modulo aun no disponible">Configuracion</span></nav><a class="sidebar__logout"
                href="<?= $dashboardBasePath ?>logout.php">Cerrar sesion</a>
        </aside>
        <main class="dashboard">
            <header class="dashboard__header"><label class="dashboard__menu-button" for="backoffice-menu"
                    aria-label="Mostrar navegacion">Menu</label>
                <div>
                    <p class="dashboard__eyebrow">Clinica Imagen</p>
                    <h1>Panel principal</h1>
                    <p class="dashboard__subtitle">Resumen de actividad de Clinica Imagen</p>
                </div>
                <div class="user-summary"><span
                        class="user-summary__avatar"><?= htmlspecialchars(strtoupper(substr($dashboardUserName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                    <div>
                        <strong><?= $dashboardUserName ?></strong><small><?= htmlspecialchars(ucfirst($dashboardRole), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>
            </header>
            <section class="stats-grid">
                <article class="stat-card"><span>Pacientes</span>
                    <div>
                        <p>Pacientes registrados</p><strong><?= $registeredPatients ?></strong><small>Registros
                            disponibles en el sistema</small>
                    </div>
                </article>
                <article class="stat-card"><span>Turnos</span>
                    <div>
                        <p>Turnos de hoy</p><strong>0</strong><small>Sin turnos registrados para hoy</small>
                    </div>
                </article>
                <article class="stat-card"><span>Estudios</span>
                    <div>
                        <p>Estudios realizados</p><strong>0</strong><small>No hay estudios registrados</small>
                    </div>
                </article>
                <article class="stat-card"><span>Equipo</span>
                    <div>
                        <p>Profesionales activos</p><strong><?= $activeProfessionals ?></strong><small>Profesionales
                            cargados actualmente</small>
                    </div>
                </article>
            </section>
            <section class="dashboard-section">
                <div class="section-heading">
                    <div>
                        <p class="section-heading__eyebrow">Accesos directos</p>
                        <h2>Acciones rapidas</h2>
                    </div>
                </div>
                <div class="quick-actions__grid"><span class="quick-action quick-action--disabled"
                        title="Modulo aun no disponible"><b>+</b>Nuevo paciente<small>Proximamente</small></span><a
                        class="quick-action" href="<?= $dashboardBasePath ?>agenda.php"><b>+</b>Nuevo
                        turno<small>Agendar una cita</small></a><span class="quick-action quick-action--disabled"
                        title="Modulo aun no disponible"><b>Buscar</b>Buscar
                        paciente<small>Proximamente</small></span><span class="quick-action quick-action--disabled"
                        title="Modulo aun no disponible"><b>+</b>Registrar estudio<small>Proximamente</small></span>
                </div>
            </section>
            <section class="dashboard-section appointments">
                <div class="section-heading">
                    <div>
                        <p class="section-heading__eyebrow">Agenda diaria</p>
                        <h2>Turnos de hoy</h2>
                    </div><a href="<?= $dashboardBasePath ?>agenda.php">Ir a agenda</a>
                </div>
                <div class="appointments__table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Servicio / estudio</th>
                                <th>Profesional</th>
                                <th>Sucursal</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="appointments__empty">
                                <td colspan="6"><strong>No hay turnos registrados para hoy.</strong>
                                    <p>Las nuevas citas apareceran aqui cuando el sistema cuente con almacenamiento de
                                        turnos.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
