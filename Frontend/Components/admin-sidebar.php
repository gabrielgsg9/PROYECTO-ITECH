<?php
$adminSidebarBasePath = $adminSidebarBasePath ?? '../';
$adminSidebarActive = $adminSidebarActive ?? '';
$adminSidebarLink = static function (string $key, string $label, string $href) use ($adminSidebarActive): string {
    $active = $adminSidebarActive === $key;
    return '<a class="sidebar__link' . ($active ? ' sidebar__link--active' : '') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . ($active ? ' aria-current="page"' : '') . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};
?>
<input type="checkbox" id="backoffice-menu" class="backoffice__menu-toggle" aria-hidden="true">
<aside class="sidebar" aria-label="Navegación administrativa">
  <a class="sidebar__brand" href="<?= htmlspecialchars($adminSidebarBasePath . 'admin/dashboard.php', ENT_QUOTES, 'UTF-8') ?>">
    <span class="sidebar__brand-mark">CI</span>Clínica Imagen
  </a>
  <nav class="sidebar__nav">
    <?= $adminSidebarLink('dashboard', 'Panel', $adminSidebarBasePath . 'admin/dashboard.php') ?>
    <?= $adminSidebarLink('usuarios', 'Usuarios', $adminSidebarBasePath . 'admin/users.php') ?>
    <?= $adminSidebarLink('sucursales', 'Sucursales', $adminSidebarBasePath . 'admin/branches.php') ?>
    <?= $adminSidebarLink('asignaciones', 'Asignaciones de técnicos', $adminSidebarBasePath . 'admin/technician-assignments.php') ?>
  </nav>
  <a class="sidebar__logout" href="<?= htmlspecialchars($adminSidebarBasePath . 'logout.php', ENT_QUOTES, 'UTF-8') ?>">Cerrar sesión</a>
</aside>
