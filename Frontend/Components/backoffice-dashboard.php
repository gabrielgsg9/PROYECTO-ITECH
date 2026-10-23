<?php
require_once __DIR__ . '/../../Data/clinicData.php';
require_once __DIR__ . '/../../Repository/DashboardRepositoryFactory.php';
require_once __DIR__ . '/../../Service/DashboardMetricsService.php';
$dashboardBasePath = $dashboardBasePath ?? '../';
$dashboardUserName = htmlspecialchars((string) ($_SESSION['user_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$dashboardRole = (string) ($_SESSION['role'] ?? '');
$isAdministrator = $dashboardRole === 'administrador';
$adminSidebarBasePath = $dashboardBasePath;
$adminSidebarActive = 'dashboard';
$registeredPatients = count(array_filter(
    get_auth_users(),
    static fn(array $user): bool => ($user['role'] ?? '') === 'paciente'
));
$activeProfessionals = count($profesionales ?? []);
$dashboardMetrics = null;
$dashboardHasWarnings = false;
try {
    $dashboardStores = DashboardRepositoryFactory::create();
    $dashboardMetrics = (new DashboardMetricsService(
        $dashboardStores['appointments'],
        $dashboardStores['studies']
    ))->forToday();
    $dashboardHasWarnings = $dashboardMetrics['warnings'] !== [];
    foreach ($dashboardMetrics['warnings'] as $warning) {
        error_log('Inconsistencia en datos del dashboard: ' . $warning);
    }
} catch (Throwable $exception) {
    error_log('No se pudieron cargar los datos del dashboard: ' . $exception->getMessage());
}
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
    <div class="backoffice">
        <?php if ($isAdministrator):
            require __DIR__ . '/admin-sidebar.php'; else: ?>
            <input class="backoffice__menu-toggle" type="checkbox" id="backoffice-menu">
            <aside class="sidebar" aria-label="Navegacion principal"><a class="sidebar__brand"
                    href="<?= $dashboardBasePath ?>home.php"><span class="sidebar__brand-mark">CI</span>Clinica Imagen</a>
                <nav class="sidebar__nav"><a class="sidebar__link sidebar__link--active"
                        href="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">Inicio</a><?php if ($dashboardRole === 'recepcionista'): ?><a
                            class="sidebar__link"
                            href="<?= $dashboardBasePath ?>recepcion/appointments.php">Turnos</a><?php endif; ?><?php if ($isAdministrator): ?><a
                            class="sidebar__link"
                            href="<?= $dashboardBasePath ?>admin/branches.php">Sucursales</a><?php else: ?><span
                            class="sidebar__link sidebar__link--disabled"
                            title="Modulo aun no disponible">Sucursales</span><?php endif; ?><?php if ($isAdministrator): ?><a
                            class="sidebar__link" href="<?= $dashboardBasePath ?>admin/users.php">Gestionar usuarios</a><a
                            class="sidebar__link" href="<?= $dashboardBasePath ?>admin/branches.php">Gestionar
                            sucursales</a><?php endif; ?><?php if ($dashboardRole === 'recepcionista'): ?><a
                            class="sidebar__link" href="<?= $dashboardBasePath ?>recepcion/appointments.php">Gestionar
                            citas</a><?php endif; ?></nav><a class="sidebar__logout"
                    href="<?= $dashboardBasePath ?>logout.php">Cerrar sesion</a>
            </aside><?php endif; ?>
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
            <?php if ($dashboardHasWarnings): ?>
                <p class="dashboard-data-warning" role="status">Se detectaron inconsistencias en algunos turnos o informes.
                    Algunos registros pueden haberse omitido de los indicadores; el detalle quedó registrado para revisión.
                </p><?php endif; ?>
            <?php if ($dashboardMetrics === null): ?>
                <p class="dashboard-data-warning" role="alert">No fue posible consultar los datos de turnos e informes. Los
                    indicadores no están disponibles.</p><?php endif; ?>
            <section class="stats-grid">
                <article class="stat-card"><span>Pacientes</span>
                    <div>
                        <p>Pacientes registrados</p><strong><?= $registeredPatients ?></strong><small>Registros
                            disponibles en el sistema</small>
                    </div>
                </article>
                <article class="stat-card"><span>Turnos</span>
                    <div>
                        <p>Turnos programados hoy</p>
                        <strong><?= $dashboardMetrics === null ? '—' : (int) $dashboardMetrics['scheduled_today'] ?></strong><small><?php if ($dashboardMetrics === null): ?>No
                                se pudieron consultar los
                                turnos<?php else: ?><?= (int) $dashboardMetrics['pending_today'] ?> pendientes ·
                                <?= (int) $dashboardMetrics['attended_today'] ?> atendidos<?php endif; ?></small>
                    </div>
                </article>
                <article class="stat-card"><span>Estudios</span>
                    <div>
                        <p>Estudios realizados</p>
                        <strong><?= $dashboardMetrics === null ? '—' : (int) $dashboardMetrics['studies_completed'] ?></strong><small><?= $dashboardMetrics === null ? 'No se pudieron consultar los informes' : 'Informes de turnos atendidos' ?></small>
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
                <div class="quick-actions__grid<?= $dashboardRole === 'recepcionista' ? ' quick-actions__grid--reception' : '' ?>"><?php if ($dashboardRole === 'recepcionista'): ?><a
                            class="quick-action" href="<?= $dashboardBasePath ?>recepcion/new-patient.php"><b>+</b>Nuevo
                            paciente<small>Registrar en recepción</small></a><a class="quick-action"
                            href="<?= $dashboardBasePath ?>appointments.php"><b>+</b>Nueva cita<small>Agendar para un
                                paciente</small></a><?php else: ?><span class="quick-action quick-action--disabled"
                            title="Modulo aun no disponible"><b>+</b>Nuevo
                            paciente<small>Proximamente</small></span><?php endif; ?><a class="quick-action"
                        href="<?= $dashboardRole === 'recepcionista' ? $dashboardBasePath . 'recepcion/appointments.php' : $dashboardBasePath . 'admin/users.php' ?>"><b>+</b><?= $dashboardRole === 'recepcionista' ? 'Gestionar citas' : 'Gestionar usuarios' ?>
                        <small><?= $dashboardRole === 'recepcionista' ? 'Confirmar o cancelar citas' : 'Roles y cuentas' ?></small></a><?php if ($isAdministrator): ?><a
                            class="quick-action" href="<?= $dashboardBasePath ?>admin/branches.php"><b>+</b>Gestionar
                            sucursales<small>Altas, edicion y estado</small></a><a class="quick-action"
                            href="<?= $dashboardBasePath ?>admin/technician-assignments.php"><b>+</b>Asignar sucursales a
                            técnicos<small>Accesos del panel técnico</small></a><?php endif; ?>
                </div>
            </section>
            <section class="dashboard-section appointments">
                <div class="section-heading">
                    <div>
                        <p class="section-heading__eyebrow">Agenda diaria</p>
                        <h2>Turnos de
                            hoy<?= $dashboardMetrics !== null ? ' · ' . htmlspecialchars($dashboardMetrics['date'], ENT_QUOTES, 'UTF-8') : '' ?>
                        </h2>
                    </div><?php if ($dashboardRole === 'recepcionista'): ?><a
                            href="<?= $dashboardBasePath ?>recepcion/appointments.php">Ir a citas</a><?php endif; ?>
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
                        <tbody><?php if ($dashboardMetrics === null): ?>
                                <tr class="appointments__empty">
                                    <td colspan="6"><strong>No se pudieron cargar los turnos.</strong>
                                        <p>Intentá nuevamente más tarde.</p>
                                    </td>
                                </tr>
                            <?php elseif (!$dashboardMetrics['today_appointments']): ?>
                                <tr class="appointments__empty">
                                    <td colspan="6"><strong>No hay turnos para hoy.</strong></td>
                                </tr>
                            <?php else:
                            foreach ($dashboardMetrics['today_appointments'] as $appointment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($appointment['time'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($appointment['patient_name'] ?? 'Paciente sin nombre'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($appointment['service_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($appointment['professional_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($appointment['branch_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($appointment['status'] ?? 'Sin estado'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
