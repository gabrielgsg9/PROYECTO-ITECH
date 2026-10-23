<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['administrador'], '../login.php');
require_once __DIR__ . '/../../Service/BranchService.php';

$service = new BranchService();
$message = '';
$error = '';
$formName = '';
$formAddress = '';
$editId = null;
$showCreate = ($_GET['new'] ?? '') === '1';

$notices = [
    'created' => 'Sucursal creada correctamente.',
    'updated' => 'Sucursal actualizada correctamente.',
    'activated' => 'Sucursal activada correctamente.',
    'deactivated' => 'Sucursal desactivada correctamente.',
];
$message = $notices[(string) ($_GET['notice'] ?? '')] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'update' && $id !== false && $id !== null && $id > 0) {
        $editId = $id;
        $formName = trim((string) ($_POST['nombre'] ?? ''));
        $formAddress = trim((string) ($_POST['direccion'] ?? ''));
    } elseif ($action === 'create') {
        $showCreate = true;
        $formName = trim((string) ($_POST['nombre'] ?? ''));
        $formAddress = trim((string) ($_POST['direccion'] ?? ''));
    }

    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Solicitud inválida. Recargá la página e intentá nuevamente.';
    } else {
        try {
            if ($action === 'create') {
                $service->create((string) ($_POST['nombre'] ?? ''), (string) ($_POST['direccion'] ?? ''));
                header('Location: branches.php?notice=created');
                exit;
            }
            if ($action === 'update') {
                if ($id === false || $id === null || $id < 1) {
                    throw new InvalidArgumentException('La sucursal indicada no es válida.');
                }
                $updated = $service->update($id, (string) ($_POST['nombre'] ?? ''), (string) ($_POST['direccion'] ?? ''));
                if ($updated === null) {
                    throw new InvalidArgumentException('La sucursal indicada ya no existe.');
                }
                header('Location: branches.php?notice=updated');
                exit;
            }
            if ($action === 'toggle') {
                if ($id === false || $id === null || $id < 1 || !in_array($_POST['activo'] ?? null, ['0', '1'], true)) {
                    throw new InvalidArgumentException('La solicitud de estado no es válida.');
                }
                $active = $_POST['activo'] === '1';
                if (!$service->setActive($id, $active)) {
                    throw new InvalidArgumentException('La sucursal indicada ya no existe.');
                }
                header('Location: branches.php?notice=' . ($active ? 'activated' : 'deactivated'));
                exit;
            }
            if ($action !== 'create' && $action !== 'update' && $action !== 'toggle') {
                $error = 'La operación solicitada no es válida.';
            }
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (DomainException $exception) {
            $error = 'Ya existe una sucursal con ese nombre.';
        } catch (Throwable $exception) {
            error_log('Error al gestionar sucursal: ' . $exception->getMessage());
            $error = 'No se pudo guardar el cambio. Intentá nuevamente.';
        }
    }
}

try {
    $branches = $service->all();
} catch (Throwable $exception) {
    error_log('Error al leer sucursales: ' . $exception->getMessage());
    http_response_code(500);
    $branches = [];
    $error = 'No se pudieron cargar las sucursales.';
}

$editingBranch = null;
if ($editId !== null) {
    foreach ($branches as $branch) {
        if ((int) ($branch['id'] ?? 0) === $editId) {
            $editingBranch = $branch;
            break;
        }
    }
} elseif (isset($_GET['edit'])) {
    $requestedId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($requestedId !== false && $requestedId !== null && $requestedId > 0) {
        foreach ($branches as $branch) {
            if ((int) ($branch['id'] ?? 0) === $requestedId) {
                $editId = $requestedId;
                $editingBranch = $branch;
                $formName = (string) ($branch['nombre'] ?? '');
                $formAddress = (string) ($branch['direccion'] ?? '');
                break;
            }
        }
    }
}
if ($editId !== null && $editingBranch === null && $error === '') {
    $error = 'La sucursal indicada no existe.';
    $editId = null;
}

$token = csrf_token();
function branch_e($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sucursales — Clínica Imagen</title>
  <link rel="stylesheet" href="../STYLES/dashboard.css">
  <link rel="stylesheet" href="../STYLES/pages/users.css">
</head>
<body>
  <?php $adminSidebarBasePath = '../'; $adminSidebarActive = 'sucursales'; require __DIR__ . '/../Components/admin-sidebar.php'; ?>
  <main class="dashboard">
    <header class="dashboard__header">
      <div><p class="dashboard__eyebrow">Administración</p><h1>Gestión de sucursales</h1>
        <p class="dashboard__subtitle">Consultá las ubicaciones, actualizá sus datos y administrá su disponibilidad.</p></div>
      <label for="backoffice-menu" class="dashboard__menu-button" aria-label="Abrir menú">☰</label>
    </header>

    <?php if ($message): ?><p class="alert alert--success" role="status"><?= branch_e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert--error" role="alert"><?= branch_e($error) ?></p><?php endif; ?>

    <?php if ($editingBranch !== null || ($editId !== null && $error !== '')): ?>
      <section class="dashboard-section users branch-editor" id="branch-form">
        <div class="section-heading"><div><p class="section-heading__eyebrow">Sucursal #<?= (int) $editId ?></p><h2>Editar sucursal</h2></div>
          <a class="btn" href="branches.php">Cancelar</a></div>
        <form method="post" class="branch-form">
          <input type="hidden" name="csrf_token" value="<?= branch_e($token) ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= (int) $editId ?>">
          <div class="form-group"><label for="edit-nombre">Nombre</label>
            <input class="form-control" id="edit-nombre" name="nombre" type="text" maxlength="150" required value="<?= branch_e($formName) ?>"></div>
          <div class="form-group"><label for="edit-direccion">Dirección</label>
            <input class="form-control" id="edit-direccion" name="direccion" type="text" maxlength="255" required value="<?= branch_e($formAddress) ?>"></div>
          <button class="btn btn--primary" type="submit">Guardar cambios</button>
        </form>
      </section>
    <?php else: ?>
      <details class="dashboard-section users branch-create" id="new-branch-form" <?= $showCreate ? 'open' : '' ?>>
        <summary class="btn btn--primary">+ Nueva sucursal</summary>
        <div class="branch-create__body">
          <div class="section-heading"><div><p class="section-heading__eyebrow">Nueva ubicación</p><h2>Registrar sucursal</h2></div></div>
          <form method="post" class="branch-form">
            <input type="hidden" name="csrf_token" value="<?= branch_e($token) ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group"><label for="nombre">Nombre</label>
              <input class="form-control" id="nombre" name="nombre" type="text" maxlength="150" required value="<?= branch_e($formName) ?>"></div>
            <div class="form-group"><label for="direccion">Dirección</label>
              <input class="form-control" id="direccion" name="direccion" type="text" maxlength="255" required value="<?= branch_e($formAddress) ?>"></div>
            <button class="btn btn--primary" type="submit">Guardar sucursal</button>
          </form>
        </div>
      </details>
    <?php endif; ?>

    <section class="dashboard-section users">
      <div class="section-heading"><div><p class="section-heading__eyebrow">Ubicaciones</p><h2>Sucursales registradas</h2></div></div>
      <?php if (!$branches): ?>
        <p class="users__empty">No hay sucursales registradas. Usá «+ Nueva sucursal» para agregar la primera.</p>
      <?php else: ?>
        <div class="users__table-wrap"><table class="users-table branch-table">
          <thead><tr><th>ID</th><th>Nombre</th><th>Dirección</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody><?php foreach ($branches as $branch):
            $branchId = (int) ($branch['id'] ?? 0);
            $isActive = !array_key_exists('activo', $branch) || (bool) $branch['activo'];
          ?>
            <tr>
              <td class="users-table__id"><?= $branchId ?></td>
              <td><?= branch_e($branch['nombre'] ?? '') ?></td>
              <td><?= branch_e($branch['direccion'] ?? '') ?></td>
              <td><span class="branch-status <?= $isActive ? 'branch-status--active' : 'branch-status--inactive' ?>"><?= $isActive ? 'Activa' : 'Inactiva' ?></span></td>
              <td class="branch-table__actions">
                <div class="branch-actions">
                  <a class="btn" href="branches.php?edit=<?= $branchId ?>#branch-form">Editar</a>
                  <form method="post" action="branches.php" onsubmit="return <?= $isActive ? "confirm('¿Desactivar esta sucursal? Las citas históricas se conservarán.')" : 'true' ?>;">
                    <input type="hidden" name="csrf_token" value="<?= branch_e($token) ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $branchId ?>">
                    <input type="hidden" name="activo" value="<?= $isActive ? '0' : '1' ?>">
                    <button class="btn <?= $isActive ? 'btn--danger' : 'btn--primary' ?>" type="submit"><?= $isActive ? 'Desactivar' : 'Activar' ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
