<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['recepcionista'], '../login.php');
require_once __DIR__ . '/../../Repository/AppointmentRepository.php';
$repo = new AppointmentRepository();
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf($_POST['csrf_token'] ?? null))
        $error = 'Solicitud inválida. Recargá la página e intentá nuevamente.';
    else {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $action = (string) ($_POST['action'] ?? '');
        $status = match ($action) { 'confirm' => 'Confirmada', 'cancel' => 'Cancelada', default => ''};
        if (!$id)
            $error = 'Identificador de cita no válido.';
        elseif ($status === '')
            $error = 'Acción no válida.';
        elseif ($repo->setStatus((int) $id, $status))
            $message = 'La cita fue ' . strtolower($status) . '.';
        else
            $error = 'La cita no existe o su estado ya no permite este cambio.';
    }
}
$appointments = $repo->all();
$users = (new UserRepository())->all();
$byId = [];
foreach ($users as $u)
    $byId[(string) $u['id']] = $u;
$token = csrf_token();

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Citas — Clínica Imagen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../STYLES/dashboard.css">
    <link rel="stylesheet" href="../STYLES/pages/appointments.css">
</head>

<body>
    <nav class="breadcrumb" aria-label="Ruta de navegación">
        <a href="dashboard.php">Panel</a>
        <span class="sep">/</span>
        <span>Gestión de citas</span>
    </nav>

    <header class="page-hero">
        <div class="page-hero__content">
            <span class="page-hero__eyebrow">Recepción</span>
            <h1>Gestión de citas</h1>
            <p>Confirmá o cancelá las citas de los pacientes de la clínica.</p>
        </div>
    </header>

    <main class="main">
        <section class="form-card">
            <div class="card-head">
                <div>
                    <h2 class="section-title">Listado de citas</h2>
                    <p class="section-subtitle">Las citas pendientes requieren tu confirmación.</p>
                </div>
                <span class="count-pill"><?= count($appointments) ?>
                    <?= count($appointments) === 1 ? 'cita' : 'citas' ?></span>
            </div>

            <?php if ($message): ?>
                <p class="alert alert--ok" role="status">✓ <?= e($message) ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="alert alert--error" role="alert">⚠ <?= e($error) ?></p>
            <?php endif; ?>

            <?php if (!$appointments): ?>
                <div class="empty">
                    <div class="empty__icon">📅</div>
                    <p>No hay citas registradas.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Paciente</th>
                                <th>Fecha y hora</th>
                                <th>Servicio</th>
                                <th>Profesional</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a):
                                $patient = (string) ($a['patient_name'] ?? $byId[(string) ($a['patient_id'] ?? '')]['name'] ?? 'Usuario eliminado');
                                $st = (string) ($a['status'] ?? '');
                                $stClass = match ($st) {
                                    'Pendiente' => 'badge--pendiente',
                                    'Confirmada' => 'badge--confirmada',
                                    'Cancelada' => 'badge--cancelada',
                                    default => ''
                                };
                                $initial = mb_strtoupper(mb_substr($patient, 0, 1, 'UTF-8'), 'UTF-8');
                                ?>
                                <tr>
                                    <td class="cell-id">#<?= (int) $a['id'] ?></td>
                                    <td>
                                        <div class="patient">
                                            <span class="avatar" aria-hidden="true"><?= e($initial) ?></span>
                                            <span><?= e($patient) ?></span>
                                        </div>
                                    </td>
                                    <td><?= e(trim((string) ($a['date'] ?? '') . ' ' . (string) ($a['time'] ?? ''))) ?></td>
                                    <td><?= e($a['service_name'] ?? '') ?></td>
                                    <td class="cell-muted"><?= e($a['professional_name'] ?? 'Sin preferencia') ?></td>
                                    <td><span class="badge <?= $stClass ?>"><?= e($st) ?></span></td>
                                    <td>
                                        <?php if ($st === 'Pendiente'): ?>
                                            <form method="post" class="actions">
                                                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                                <button class="btn btn--turquesa" name="action" value="confirm">Confirmar</button>
                                                <button class="btn btn--outline" name="action" value="cancel"
                                                    onclick="return confirm('¿Cancelar esta cita?')">Cancelar</button>
                                            </form>
                                        <?php elseif ($st === 'Confirmada'): ?>
                                            <form method="post" class="actions" onsubmit="return confirm('¿Cancelar esta cita?')">
                                                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                                <button class="btn btn--outline" name="action" value="cancel">Cancelar</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="cell-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>