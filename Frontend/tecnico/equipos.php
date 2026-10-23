<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['tecnico'], '../login.php');
require_once __DIR__ . '/../../Service/EquipmentService.php';

$token = csrf_token();
$error = '';
$success = isset($_GET['saved']) ? 'Equipo guardado correctamente.' : '';
$service = null;
$branches = [];
$selectedBranch = null;
$requestMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$branchInput = $requestMethod === 'POST' ? ($_POST['sucursal_id'] ?? null) : ($_GET['sucursal'] ?? null);
$selectedBranchId = filter_var($branchInput, FILTER_VALIDATE_INT) ?: 0;
$equipmentRows = [];
$editingId = filter_var($_GET['editar'] ?? $_POST['equipo_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$editing = null;
$form = [
    'nombre' => '', 'tipo' => '', 'marca' => '', 'modelo' => '', 'numero_serie' => '',
    'estado' => 'Operativo', 'fecha_incorporacion' => '', 'observaciones' => '',
];

try {
    $user = (new UserRepository())->findById((int) ($_SESSION['user_id'] ?? 0));
    if (!$user || ($user['role'] ?? '') !== 'tecnico') throw new RuntimeException('No se pudo validar la cuenta técnica.');
    $technicianId = (int) ($user['id'] ?? 0);
    if ($technicianId < 1) throw new RuntimeException('La cuenta técnica no tiene un identificador válido.');
    $service = new EquipmentService();
    $branches = $service->branchesForTechnician($technicianId);

    foreach ($branches as $branch) {
        if ((int) ($branch['id'] ?? 0) === $selectedBranchId) { $selectedBranch = $branch; break; }
    }
    if ($selectedBranchId > 0 && !$selectedBranch) throw new DomainException('No tenés acceso a esa sucursal.');

    if ($requestMethod === 'POST') {
        if (!valid_csrf($_POST['csrf_token'] ?? null)) {
            throw new DomainException('La solicitud venció o no es válida. Recargá la página e intentá nuevamente.');
        }
        if (($_POST['action'] ?? '') !== 'save') throw new DomainException('La operación solicitada no es válida.');
        $input = [
            'nombre' => $_POST['nombre'] ?? '', 'tipo' => $_POST['tipo'] ?? '',
            'marca' => $_POST['marca'] ?? '', 'modelo' => $_POST['modelo'] ?? '',
            'numero_serie' => $_POST['numero_serie'] ?? '', 'estado' => $_POST['estado'] ?? '',
            'fecha_incorporacion' => $_POST['fecha_incorporacion'] ?? '', 'observaciones' => $_POST['observaciones'] ?? '',
        ];
        $form = array_map(static fn($value) => is_scalar($value) ? (string) $value : '', $input);
        $postBranchId = filter_var($_POST['sucursal_id'] ?? null, FILTER_VALIDATE_INT);
        if ($postBranchId === false || $postBranchId === null || $postBranchId < 1) throw new DomainException('Seleccioná una sucursal válida.');
        $selectedBranchId = (int) $postBranchId;
        if ($editingId > 0) {
            if (!$service->update($technicianId, $selectedBranchId, $editingId, $input)) throw new DomainException('El equipo ya no existe en esa sucursal.');
        } else {
            $service->create($technicianId, $selectedBranchId, $input);
        }
        header('Location: equipos.php?sucursal=' . $selectedBranchId . '&saved=1');
        exit;
    }

} catch (InvalidArgumentException | DomainException $exception) {
    $error = $exception->getMessage();
} catch (Throwable $exception) {
    error_log('Error al cargar la gestión JSON de equipos: ' . $exception->getMessage());
    $error = 'No se pudo cargar el inventario de equipos. Verificá el archivo JSON y la configuración de sucursales autorizadas.';
}

if ($service && $selectedBranch) {
    try {
        $equipmentRows = $service->listForTechnicianBranch($technicianId, $selectedBranchId);
        if ($editingId > 0) {
            $editing = $service->findForTechnicianBranch($technicianId, $selectedBranchId, $editingId);
            if (!$editing) throw new DomainException('El equipo solicitado no existe en esa sucursal.');
            if ($requestMethod !== 'POST') {
                $form = [
                    'nombre' => (string) $editing['nombre'], 'tipo' => (string) $editing['tipo'],
                    'marca' => (string) ($editing['marca'] ?? ''), 'modelo' => (string) ($editing['modelo'] ?? ''),
                    'numero_serie' => (string) ($editing['numero_serie'] ?? ''), 'estado' => (string) $editing['estado'],
                    'fecha_incorporacion' => (string) ($editing['fecha_incorporacion'] ?? ''),
                    'observaciones' => (string) ($editing['observaciones'] ?? ''),
                ];
            }
        }
    } catch (Throwable $exception) {
        if ($error === '') {
            error_log('Error al consultar el inventario JSON de equipos: ' . $exception->getMessage());
            $error = $exception instanceof DomainException ? $exception->getMessage() : 'No se pudo consultar el inventario de esta sucursal.';
        }
    }
}

function equipment_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function equipment_status_class(string $status): string
{
    return match ($status) {
        'Operativo' => 'equipment-status--operativo',
        'Mantenimiento' => 'equipment-status--mantenimiento',
        'Fuera de servicio' => 'equipment-status--fuera-de-servicio',
        default => '',
    };
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Equipos por sucursal — Clínica Imagen</title>
  <link rel="stylesheet" href="../STYLES/dashboard.css">
  <link rel="stylesheet" href="../STYLES/pages/technical-dashboard.css">
</head>
<body>
  <input type="checkbox" id="menu-toggle" class="backoffice__menu-toggle" aria-hidden="true">
  <aside class="sidebar" aria-label="Navegación técnica">
    <a class="sidebar__brand" href="dashboard.php"><span class="sidebar__brand-mark">CI</span>Clínica Imagen</a>
    <nav class="sidebar__nav">
      <a class="sidebar__link" href="dashboard.php">Pacientes e informes</a>
      <a class="sidebar__link sidebar__link--active" href="equipos.php" aria-current="page">Equipos por sucursal</a>
    </nav>
    <a class="sidebar__logout" href="../logout.php">Cerrar sesión</a>
  </aside>
  <main class="dashboard">
    <header class="dashboard__header">
      <div><p class="dashboard__eyebrow">Área técnica</p><h1>Equipos por sucursal</h1><p class="dashboard__subtitle">Consultá y actualizá la maquinaria asignada a tus sucursales.</p></div>
      <label for="menu-toggle" class="dashboard__menu-button" aria-label="Abrir menú">☰</label>
    </header>

    <?php if ($success): ?><p class="alert alert--success" role="status"><?= equipment_e($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert--error" role="alert"><?= equipment_e($error) ?></p><?php endif; ?>

    <?php if (!$error || $branches): ?>
    <section class="dashboard-section">
      <div class="section-heading"><div><p class="section-heading__eyebrow">Ubicación</p><h2>Seleccionar sucursal</h2></div></div>
      <?php if (!$branches): ?>
        <p class="empty-state">Tu cuenta técnica todavía no tiene sucursales asignadas para gestionar.</p>
      <?php else: ?>
        <form method="get" class="equipment-branch-select">
          <label for="branch-select">Sucursal asignada</label>
          <select class="input" id="branch-select" name="sucursal" required>
            <option value="">Seleccioná una sucursal</option>
            <?php foreach ($branches as $branch): ?><option value="<?= (int) $branch['id'] ?>" <?= (int) $branch['id'] === $selectedBranchId ? 'selected' : '' ?>><?= equipment_e($branch['nombre']) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn--primary" type="submit">Ver equipos</button>
        </form>
      <?php endif; ?>
    </section>

    <?php if ($selectedBranch && $service): ?>
    <section class="dashboard-section">
      <div class="section-heading"><div><p class="section-heading__eyebrow">Inventario JSON</p><h2 id="equipment-form-title"><?= $editing ? 'Editar equipo' : 'Registrar equipo' ?></h2></div><?php if (!$editing): ?><a class="btn btn--primary" href="#equipment-form-title">Registrar nuevo equipo</a><?php endif; ?></div>
      <form method="post" action="equipos.php?sucursal=<?= $selectedBranchId ?>" class="equipment-form">
        <input type="hidden" name="csrf_token" value="<?= equipment_e($token) ?>">
        <input type="hidden" name="action" value="save">
        <?php if ($editing): ?><input type="hidden" name="equipo_id" value="<?= (int) $editingId ?>"><?php endif; ?>
        <div class="equipment-field"><label for="equipment-branch">Sucursal de destino *</label><?php if ($editing): ?><input type="hidden" name="sucursal_id" value="<?= $selectedBranchId ?>"><input class="input" id="equipment-branch" value="<?= equipment_e($selectedBranch['nombre']) ?>" disabled><?php else: ?><select class="input" id="equipment-branch" name="sucursal_id" required><?php foreach ($branches as $branch): ?><option value="<?= (int) $branch['id'] ?>" <?= (int) $branch['id'] === $selectedBranchId ? 'selected' : '' ?>><?= equipment_e($branch['nombre']) ?></option><?php endforeach; ?></select><?php endif; ?></div>
        <div class="equipment-field"><label for="equipment-name">Nombre *</label><input class="input" id="equipment-name" name="nombre" maxlength="150" value="<?= equipment_e($form['nombre']) ?>" required></div>
        <div class="equipment-field"><label for="equipment-type">Tipo o categoría *</label><input class="input" id="equipment-type" name="tipo" maxlength="100" value="<?= equipment_e($form['tipo']) ?>" placeholder="Ej.: Tomógrafo, rayos X" required></div>
        <div class="equipment-field"><label for="equipment-brand">Marca</label><input class="input" id="equipment-brand" name="marca" maxlength="100" value="<?= equipment_e($form['marca']) ?>"></div>
        <div class="equipment-field"><label for="equipment-model">Modelo</label><input class="input" id="equipment-model" name="modelo" maxlength="100" value="<?= equipment_e($form['modelo']) ?>"></div>
        <div class="equipment-field"><label for="equipment-serial">Número de serie</label><input class="input" id="equipment-serial" name="numero_serie" maxlength="100" value="<?= equipment_e($form['numero_serie']) ?>"></div>
        <div class="equipment-field"><label for="equipment-status">Estado operativo</label><select class="input" id="equipment-status" name="estado"><?php foreach (EquipmentService::STATUSES as $status): ?><option value="<?= equipment_e($status) ?>" <?= $form['estado'] === $status ? 'selected' : '' ?>><?= equipment_e($status) ?></option><?php endforeach; ?></select></div>
        <div class="equipment-field"><label for="equipment-date">Fecha de incorporación</label><input class="input" type="date" id="equipment-date" name="fecha_incorporacion" value="<?= equipment_e($form['fecha_incorporacion']) ?>"></div>
        <div class="equipment-field equipment-field--wide"><label for="equipment-notes">Observaciones</label><textarea class="input" id="equipment-notes" name="observaciones" rows="3" maxlength="5000"><?= equipment_e($form['observaciones']) ?></textarea></div>
        <div class="equipment-form__actions"><button class="btn btn--primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Registrar equipo' ?></button><?php if ($editing): ?><a class="btn" href="equipos.php?sucursal=<?= $selectedBranchId ?>">Cancelar edición</a><?php endif; ?></div>
      </form>
    </section>

    <section class="dashboard-section">
      <div class="section-heading"><div><p class="section-heading__eyebrow">Sucursal seleccionada</p><h2>Equipos registrados</h2><p class="dashboard__subtitle"><?= equipment_e($selectedBranch['nombre']) ?></p></div><span class="section-count"><?= count($equipmentRows) ?></span></div>
      <?php if (!$equipmentRows): ?><p class="empty-state">Esta sucursal todavía no tiene equipos registrados.</p><?php else: ?>
      <div class="data-table-wrap"><table class="data-table equipment-table"><thead><tr><th>ID</th><th>Sucursal</th><th>Nombre</th><th>Tipo</th><th>Marca / modelo</th><th>Número de serie</th><th>Estado</th><th>Incorporación</th><th>Observaciones</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($equipmentRows as $item): ?><tr>
          <td class="data-table__id"><?= (int) $item['id'] ?></td><td><?= equipment_e($selectedBranch['nombre']) ?></td><td><?= equipment_e($item['nombre']) ?></td><td><?= equipment_e($item['tipo']) ?></td>
          <td><?= equipment_e(trim(($item['marca'] ?? '') . ' ' . ($item['modelo'] ?? '')) ?: '—') ?></td>
          <td><?= equipment_e($item['numero_serie'] ?: '—') ?></td><td><span class="status-badge <?= equipment_e(equipment_status_class((string) $item['estado'])) ?>"><?= equipment_e($item['estado']) ?></span></td>
          <td><?= equipment_e($item['fecha_incorporacion'] ?: '—') ?></td>
          <td><?= equipment_e($item['observaciones'] ?: '—') ?></td>
          <td><a class="btn" href="equipos.php?sucursal=<?= $selectedBranchId ?>&amp;editar=<?= (int) $item['id'] ?>">Ver / editar</a></td>
        </tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </section>
    <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
