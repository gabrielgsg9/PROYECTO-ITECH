<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['tecnico'], '../login.php');
require_once __DIR__ . '/../../Repository/AppointmentRepository.php';
require_once __DIR__ . '/../../Repository/ReportRepository.php';
require_once __DIR__ . '/../../Service/StudyWorkflowService.php';

$userRepository = new UserRepository();
$users = $userRepository->all();
$search = trim((string) ($_GET['q'] ?? ''));
$patients = $search === ''
    ? array_values(array_filter($users, static fn($user) => ($user['role'] ?? '') === 'paciente'))
    : $userRepository->searchPatients($search);
$appointments = (new AppointmentRepository())->all();
$reports = new ReportRepository();
$studyWorkflow = new StudyWorkflowService(new AppointmentRepository(), $reports);

$patientId = filter_var($_GET['patient'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$selectedPatient = null;
foreach ($users as $u) {
    if ((int)($u['id'] ?? 0) === $patientId && ($u['role'] ?? '') === 'paciente') {
        $selectedPatient = $u;
    }
}

$patientAppointments = $selectedPatient
    ? array_values(array_filter($appointments, static fn($a) => (int)($a['patient_id'] ?? 0) === $patientId))
    : [];

$token = csrf_token();

function te($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function initial($v): string
{
    return mb_substr(trim((string)$v) ?: '?', 0, 1, 'UTF-8');
}

function status_slug($v): string
{
    return preg_replace('/[^a-z]/', '', strtolower((string)$v));
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel técnico — Clínica Imagen</title>
  <link rel="stylesheet" href="../STYLES/dashboard.css">
  <link rel="stylesheet" href="../STYLES/pages/users.css">
  <link rel="stylesheet" href="../STYLES/pages/technical-dashboard.css">
</head>
<body>
  <input type="checkbox" id="menu-toggle" class="backoffice__menu-toggle" aria-hidden="true">

  <aside class="sidebar">
    <a class="sidebar__brand" href="dashboard.php">
      <span class="sidebar__brand-mark">CI</span>Clínica Imagen
    </a>
    <nav class="sidebar__nav">
      <a class="sidebar__link sidebar__link--active" href="dashboard.php" aria-current="page">Pacientes e informes</a>
      <a class="sidebar__link" href="equipment.php">Equipos por sucursal</a>
    </nav>
    <a class="sidebar__logout" href="../logout.php">Cerrar sesión</a>
  </aside>

  <main class="dashboard">
    <header class="dashboard__header">
      <div>
        <p class="dashboard__eyebrow">Área técnica</p>
        <h1>Pacientes e informes</h1>
        <p class="dashboard__subtitle">Buscá un paciente para revisar sus citas y gestionar informes PDF.</p>
      </div>
      <label for="menu-toggle" class="dashboard__menu-button" aria-label="Abrir menú">☰</label>
    </header>

    <?php if (isset($_GET['uploaded'])): ?>
      <p class="alert alert--success" role="status">Informe cargado. Confirmá explícitamente el estudio cuando haya finalizado.</p>
    <?php elseif (isset($_GET['completed'])): ?>
      <p class="alert alert--success" role="status">Estudio confirmado como realizado.</p>
    <?php elseif (isset($_GET['saved'])): ?>
      <p class="alert alert--success" role="status">Informe guardado correctamente.</p>
    <?php elseif (isset($_GET['error'])): ?>
      <p class="alert alert--error" role="alert">No se pudo procesar el informe. Revisá el archivo o recargá la página.</p>
    <?php endif; ?>

    <section class="dashboard-section">
      <form method="get" class="search-form">
        <div class="search-form__field">
          <label for="q">Buscar por nombre, email o identificador</label>
          <input class="input" id="q" name="q" value="<?= te($search) ?>" placeholder="Ej: María, maria@correo.com o 12">
        </div>
        <button class="btn btn--primary" type="submit">Buscar</button>
        <?php if ($search !== ''): ?>
          <a class="search-form__clear" href="?">Limpiar</a>
        <?php endif; ?>
      </form>

      <hr class="section-divider">

      <div class="section-heading">
        <div>
          <p class="section-heading__eyebrow">Directorio</p>
          <h2>Pacientes registrados</h2>
        </div>
        <span class="section-count"><?= count($patients) ?></span>
      </div>

      <?php if (!$patients): ?>
        <p class="empty-state">No se encontraron pacientes.</p>
      <?php else: ?>
        <ul class="patient-list">
          <?php foreach ($patients as $p): ?>
            <li>
              <a class="patient-item<?= (int)$p['id'] === $patientId ? ' patient-item--active' : '' ?>"
                 href="?patient=<?= (int)$p['id'] ?>&amp;q=<?= rawurlencode($search) ?>">
                <span class="patient-item__avatar"><?= te(initial($p['name'] ?? '')) ?></span>
                <span class="patient-item__info">
                  <strong><?= te($p['name'] ?? 'Paciente') ?></strong>
                  <small><?= te($p['email'] ?? '') ?></small>
                </span>
                <span class="patient-item__id">ID <?= (int)$p['id'] ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <?php if ($selectedPatient): ?>
      <section class="dashboard-section">
        <div class="section-heading">
          <div>
            <p class="section-heading__eyebrow">Historial</p>
            <h2>Citas de <?= te($selectedPatient['name'] ?? 'Paciente') ?></h2>
          </div>
          <span class="section-count"><?= count($patientAppointments) ?></span>
        </div>

        <?php if (!$patientAppointments): ?>
          <p class="empty-state">Este paciente todavía no tiene citas.</p>
        <?php else: ?>
          <div class="data-table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Fecha y hora</th>
                  <th>Servicio</th>
                  <th>Sucursal</th>
                  <th>Profesional</th>
                  <th>Estado</th>
                  <th>Informe</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($patientAppointments as $a):
                  $report = $reports->findByAppointment((int)$a['id']);
                  $status = (string)($a['status'] ?? '');
                  $workflowPhase = $studyWorkflow->phaseFor($a, $report);
                ?>
                  <tr>
                    <td class="data-table__id"><?= (int)$a['id'] ?></td>
                    <td class="data-table__date"><?= te(($a['date'] ?? '') . ' ' . ($a['time'] ?? '')) ?></td>
                    <td><?= te($a['service_name'] ?? '') ?></td>
                    <td><?= te($a['branch_name'] ?? '') ?></td>
                    <td><?= te($a['professional_name'] ?? '') ?></td>
                    <td><span class="status-badge status-badge--<?= te(status_slug($status)) ?>"><?= te($status) ?></span></td>
                    <td class="report-cell">
                      <?php if ($report): ?>
                        <a class="report-link" href="report.php?id=<?= (int)$a['id'] ?>">Ver/descargar PDF</a>
                        <small class="report-file"><?= te($report['original_name']) ?></small>
                        <?php if ($workflowPhase === 'confirm_completion'): ?>
                          <form class="upload-form" method="post" action="upload-report.php" onsubmit="return confirm('¿Confirmás que el estudio de esta cita finalizó?');">
                            <input type="hidden" name="csrf_token" value="<?= te($token) ?>">
                            <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                            <input type="hidden" name="return_patient_id" value="<?= (int)$patientId ?>">
                            <input type="hidden" name="action" value="complete">
                            <button class="btn btn--primary" type="submit">Confirmar estudio realizado</button>
                          </form>
                        <?php elseif ($workflowPhase === 'waiting_for_appointment'): ?>
                          <small class="report-file">El informe queda pendiente hasta el horario de la cita.</small>
                        <?php elseif ($workflowPhase === 'waiting_confirmation'): ?>
                          <small class="report-file">Esperando confirmación de la cita para validar el estudio.</small>
                        <?php elseif ($workflowPhase === 'completed'): ?>
                          <small class="report-file">Estudio confirmado como realizado.</small>
                        <?php else: ?>
                          <small class="report-file">La cita está cancelada o figura como no asistida.</small>
                        <?php endif; ?>
                      <?php else: ?>
                        <?php if ($workflowPhase === 'ready_for_report'): ?>
                          <form class="upload-form" method="post" action="upload-report.php" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= te($token) ?>">
                            <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                            <input type="hidden" name="return_patient_id" value="<?= (int)$patientId ?>">
                            <input type="hidden" name="action" value="upload">
                            <input class="file-input" type="file" name="pdf" accept="application/pdf,.pdf" required>
                            <button class="btn btn--primary" type="submit">Subir PDF</button>
                          </form>
                        <?php elseif ($workflowPhase === 'not_started'): ?>
                          <small class="report-file">El informe estará disponible desde el horario de la cita.</small>
                        <?php elseif ($workflowPhase === 'not_ready'): ?>
                          <small class="report-file">Disponible cuando recepción confirme la cita.</small>
                        <?php else: ?>
                          <small class="report-file">No se puede cargar un informe para una cita cancelada o no asistida.</small>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
