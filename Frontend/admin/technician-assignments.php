<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['administrador'], '../login.php');
require_once __DIR__ . '/../../Service/TechnicianBranchService.php';

$service = new TechnicianBranchService();
$error = '';
$notice = isset($_GET['saved']) ? 'Asignaciones guardadas correctamente.' : '';
$selectedTechnicianId = filter_var($_GET['tecnico_id'] ?? $_POST['tecnico_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$submittedBranchIds = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $rawIds = $_POST['sucursal_ids'] ?? [];
    $submittedBranchIds = is_array($rawIds) ? $rawIds : [];
    try {
        if (!valid_csrf($_POST['csrf_token'] ?? null)) throw new DomainException('La solicitud venció o no es válida. Recargá la página e intentá nuevamente.');
        if (($_POST['action'] ?? '') !== 'replace') throw new DomainException('La operación solicitada no es válida.');
        $id = filter_var($_POST['tecnico_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 1) throw new InvalidArgumentException('Seleccioná un técnico válido.');
        $selectedTechnicianId = $id;
        $service->save((int) ($_SESSION['user_id'] ?? 0), $id, $submittedBranchIds);
        header('Location: technician-assignments.php?tecnico_id=' . $id . '&saved=1');
        exit;
    } catch (InvalidArgumentException | DomainException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('Error al guardar asignaciones de técnicos: ' . $exception->getMessage());
        $error = 'No se pudieron guardar las asignaciones. Intentá nuevamente.';
    }
}

try {
    $technicians = $service->technicians();
    $branches = $service->branches();
    $assignments = $service->assignments();
} catch (Throwable $exception) {
    error_log('Error al cargar asignaciones de técnicos: ' . $exception->getMessage());
    $error = $error ?: 'No se pudo cargar la configuración de asignaciones.';
    $technicians = $branches = $assignments = [];
}

$selectedTechnician = null;
foreach ($technicians as $technician) {
    if ((int) $technician['id'] === $selectedTechnicianId) { $selectedTechnician = $technician; break; }
}
$selectedBranchIds = $submittedBranchIds ?? ($assignments[$selectedTechnicianId] ?? []);
$selectedBranchIds = array_values(array_filter(array_map(static function ($id) {
    return is_scalar($id) ? filter_var($id, FILTER_VALIDATE_INT) : false;
}, $selectedBranchIds), static fn($id) => $id !== false && $id !== null && $id > 0));
$token = csrf_token();

function technician_assignment_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function technician_branch_names(array $ids, array $branches): array
{
    $names = [];
    foreach ($branches as $branch) if (in_array((int) $branch['id'], $ids, true)) $names[] = (string) $branch['nombre'];
    return $names;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Asignaciones de técnicos — Clínica Imagen</title>
  <link rel="stylesheet" href="../STYLES/dashboard.css">
  <link rel="stylesheet" href="../STYLES/pages/users.css">
  <link rel="stylesheet" href="../STYLES/pages/technician-assignments.css">
</head>
<body>
  <?php $adminSidebarBasePath = '../'; $adminSidebarActive = 'asignaciones'; require __DIR__ . '/../Components/admin-sidebar.php'; ?>
  <main class="dashboard">
    <header class="dashboard__header">
      <div><p class="dashboard__eyebrow">Administración</p><h1>Asignaciones de técnicos</h1><p class="dashboard__subtitle">Definí a qué sucursales puede acceder cada técnico.</p></div>
      <label for="backoffice-menu" class="dashboard__menu-button" aria-label="Abrir menú">☰</label>
    </header>

    <?php if ($notice): ?><p class="alert alert--success" role="status"><?= technician_assignment_e($notice) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert--error" role="alert"><?= technician_assignment_e($error) ?></p><?php endif; ?>

    <section class="dashboard-section users">
      <div class="section-heading"><div><p class="section-heading__eyebrow">Personal técnico</p><h2>Técnicos registrados</h2></div><span class="section-count"><?= count($technicians) ?></span></div>
      <?php if (!$technicians): ?>
        <p class="users__empty">No hay usuarios con rol técnico. Asigná ese rol desde la gestión de usuarios.</p>
      <?php else: ?>
        <div class="users__table-wrap"><table class="users-table technician-assignment-table">
          <thead><tr><th>ID</th><th>Técnico</th><th>Sucursales asignadas</th><th>Acción</th></tr></thead>
          <tbody><?php foreach ($technicians as $technician):
            $technicianId = (int) $technician['id'];
            $names = technician_branch_names($assignments[$technicianId] ?? [], $branches);
          ?>
            <tr>
              <td class="users-table__id"><?= $technicianId ?></td>
              <td><strong><?= technician_assignment_e($technician['name'] ?? $technician['username'] ?? 'Técnico') ?></strong><small class="technician-assignment-email"><?= technician_assignment_e($technician['email'] ?? '') ?></small></td>
              <td><?= $names ? technician_assignment_e(implode(', ', $names)) : '<span class="technician-assignment-empty">Sin sucursales asignadas</span>' ?></td>
              <td><a class="btn <?= $selectedTechnicianId === $technicianId ? 'btn--primary' : '' ?>" href="technician-assignments.php?tecnico_id=<?= $technicianId ?>#assignment-editor"><?= $selectedTechnicianId === $technicianId ? 'Editando' : 'Gestionar' ?></a></td>
            </tr>
          <?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>

    <?php if ($selectedTechnician): ?>
    <section class="dashboard-section users technician-assignment-editor" id="assignment-editor">
      <div class="section-heading"><div><p class="section-heading__eyebrow">Técnico #<?= (int) $selectedTechnician['id'] ?></p><h2>Asignar sucursales a <?= technician_assignment_e($selectedTechnician['name'] ?? $selectedTechnician['username'] ?? 'Técnico') ?></h2><p class="dashboard__subtitle">Las opciones marcadas serán el conjunto final de accesos.</p></div></div>
      <?php if (!$branches): ?><p class="users__empty">No hay sucursales registradas para asignar.</p><?php else: ?>
      <form method="post" class="technician-assignment-form" onsubmit="return confirm('Se reemplazará el conjunto completo de sucursales asignadas a este técnico por las opciones marcadas. ¿Confirmás el cambio?');">
        <input type="hidden" name="csrf_token" value="<?= technician_assignment_e($token) ?>">
        <input type="hidden" name="action" value="replace">
        <input type="hidden" name="tecnico_id" value="<?= (int) $selectedTechnician['id'] ?>">
        <fieldset class="technician-assignment-options">
          <legend>Sucursales disponibles</legend>
          <?php foreach ($branches as $branch): $branchId = (int) $branch['id']; $active = !array_key_exists('activo', $branch) || (bool) $branch['activo']; ?>
          <label class="technician-assignment-option <?= $active ? '' : 'technician-assignment-option--inactive' ?>">
            <input type="checkbox" name="sucursal_ids[]" value="<?= $branchId ?>" <?= in_array($branchId, $selectedBranchIds, true) ? 'checked' : '' ?>>
            <span><strong><?= technician_assignment_e($branch['nombre'] ?? 'Sucursal') ?></strong><small><?= technician_assignment_e($branch['direccion'] ?? '') ?><?= $active ? '' : ' · Inactiva' ?></small></span>
          </label>
          <?php endforeach; ?>
        </fieldset>
        <div class="technician-assignment-actions"><button class="btn btn--primary" type="submit">Guardar asignaciones</button><a class="btn" href="technician-assignments.php">Cancelar</a></div>
      </form>
      <?php endif; ?>
    </section>
    <?php elseif ($selectedTechnicianId > 0 && !$error): ?>
      <p class="alert alert--error" role="alert">El usuario solicitado no existe o no tiene rol técnico.</p>
    <?php endif; ?>
  </main>
</body>
</html>
