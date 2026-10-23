<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['administrador'], '../login.php');

$repo = new UserRepository();
$roles = ['paciente', 'recepcionista', 'tecnico', 'administrador'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!valid_csrf($_POST['csrf_token'] ?? null)) {
    $error = 'Solicitud inválida. Recargá la página e intentá nuevamente.';
  } else {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $target = $id ? $repo->findById($id) : null;
    $action = (string) ($_POST['action'] ?? '');

    if (!$target) {
      $error = 'El usuario indicado no existe.';
    } elseif ((int) $id === (int) $_SESSION['user_id']) {
      $error = 'No podés modificar ni eliminar tu propia cuenta desde esta pantalla.';
    } elseif ($action === 'role') {
      $role = (string) ($_POST['role'] ?? '');
      if (!in_array($role, $roles, true)) {
        $error = 'El rol seleccionado no existe.';
      } elseif ($repo->updateRole((int) $id, $role)) {
        $message = 'Rol actualizado correctamente.';
      } else {
        $error = 'No se pudo cambiar el rol; comprobá la regla del último administrador.';
      }
    } elseif ($action === 'delete') {
      if ($repo->delete((int) $id)) {
        $message = 'Usuario eliminado. Las citas históricas conservarán los datos del paciente.';
      } else {
        $error = 'No se pudo eliminar; comprobá que no sea el último administrador.';
      }
    } else {
      $error = 'Acción no válida.';
    }
  }
}

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$allUsers = $repo->all();
$users = $searchQuery === '' ? $allUsers : $repo->search($searchQuery);
$token = csrf_token();

$counts = ['administrador' => 0, 'recepcionista' => 0, 'tecnico' => 0, 'paciente' => 0];
foreach ($allUsers as $u) {
  $r = (string) ($u['role'] ?? '');
  if (isset($counts[$r])) {
    $counts[$r]++;
  }
}

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
  <title>Usuarios — Clínica Imagen</title>
  <link rel="stylesheet" href="../STYLES/dashboard.css">
  <link rel="stylesheet" href="../STYLES/pages/users.css">
</head>

<body>
  <?php $adminSidebarBasePath = '../'; $adminSidebarActive = 'usuarios'; require __DIR__ . '/../Components/admin-sidebar.php'; ?>

  <main class="dashboard">
    <header class="dashboard__header">
      <div>
        <p class="dashboard__eyebrow">Administración</p>
        <h1>Gestión de usuarios</h1>
        <p class="dashboard__subtitle">Administrá los roles y las cuentas registradas en el sistema.</p>
      </div>
      <label for="backoffice-menu" class="dashboard__menu-button" aria-label="Abrir menú">☰</label>
    </header>

    <?php if ($message): ?>
      <p class="alert alert--success" role="status"><?= e($message) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
      <p class="alert alert--error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <section class="stats-grid" aria-label="Resumen de usuarios">
      <article class="stat-card">
        <span>01</span>
        <div>
          <p>Total de usuarios</p><strong><?= count($users) ?></strong>
        </div>
      </article>
      <article class="stat-card">
        <span>02</span>
        <div>
          <p>Administradores</p><strong><?= $counts['administrador'] ?></strong>
        </div>
      </article>
      <article class="stat-card">
        <span>03</span>
        <div>
          <p>Recepcionistas</p><strong><?= $counts['recepcionista'] ?></strong>
        </div>
      </article>
      <article class="stat-card">
        <span>04</span>
        <div>
          <p>Pacientes</p><strong><?= $counts['paciente'] ?></strong>
        </div>
      </article>
    </section>

    <section class="dashboard-section users">
      <div class="section-heading">
        <div>
          <p class="section-heading__eyebrow">Cuentas</p>
          <h2>Usuarios registrados</h2>
        </div>
      </div>

      <form class="users-search" method="get" role="search">
        <label class="users-search__label" for="user-search">Buscar usuarios</label>
        <div class="users-search__controls">
          <input class="input users-search__input" type="search" id="user-search" name="q"
            value="<?= e($searchQuery) ?>" placeholder="Buscar usuario por ID o nombre...">
          <button class="btn btn--primary" type="submit">Buscar</button>
          <?php if ($searchQuery !== ''): ?>
            <a class="btn" href="users.php">Limpiar</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if (!$users): ?>
        <p class="users__empty" role="status">
          <?= $searchQuery !== '' ? 'No se encontraron usuarios para esa búsqueda.' : 'No hay usuarios registrados.' ?>
        </p>
      <?php else: ?>
        <div class="users__table-wrap">
          <table class="users-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u):
                $name = (string) ($u['name'] ?? '');
                $role = (string) ($u['role'] ?? '');
                ?>
                <tr>
                  <td class="users-table__id"><?= (int) $u['id'] ?></td>
                  <td>
                    <div class="user-cell">
                      <span class="user-cell__avatar"><?= e(mb_substr($name, 0, 1, 'UTF-8')) ?></span>
                      <?= e($name) ?>
                    </div>
                  </td>
                  <td class="users-table__email"><?= e($u['email'] ?? '') ?></td>
                  <td><span class="role-badge role-badge--<?= e($role) ?>"><?= e($role) ?></span></td>
                  <td class="users-table__actions">
                    <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
                      <div class="user-actions">
                        <form method="post">
                          <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                          <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                          <input type="hidden" name="action" value="role">
                          <select class="select" name="role" aria-label="Rol">
                            <?php foreach ($roles as $r): ?>
                              <option value="<?= e($r) ?>" <?= $role === $r ? 'selected' : '' ?>><?= e(ucfirst($r)) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <button class="btn btn--primary" type="submit">Guardar rol</button>
                        </form>
                        <form method="post"
                          onsubmit="return confirm('¿Eliminar esta cuenta? Las citas históricas se conservan.');">
                          <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                          <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                          <input type="hidden" name="action" value="delete">
                          <button class="btn btn--danger" type="submit">Eliminar</button>
                        </form>
                      </div>
                    <?php else: ?>
                      <span class="user-actions__current">Cuenta actual</span>
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