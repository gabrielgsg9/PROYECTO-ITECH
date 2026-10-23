<?php
require_once __DIR__ . '/Helpers/auth.php';

require_role(['paciente', 'recepcionista'], 'login.php');
$receptionMode = (string) ($_SESSION['role'] ?? '') === 'recepcionista';
$patient = $receptionMode ? null : get_current_patient();
if (!$receptionMode && $patient === null) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../Data/dataClinica.php';

require_once 'Helpers/agenda.helper.php';
require_once __DIR__ . '/../Repository/AppointmentRepository.php';
require_once __DIR__ . '/../Service/AppointmentBookingService.php';

$patientOptions = $receptionMode ? array_values(array_filter((new UserRepository())->all(), static fn(array $user): bool => ($user['role'] ?? '') === 'paciente')) : [];
$serviceDurations = [];
foreach ($servicios as $serviceRow) $serviceDurations[(int)$serviceRow['id']] = (int)$serviceRow['duracion'];
$activeAppointments = array_values(array_map(static fn(array $row): array => ['branch_id'=>(int)($row['branch_id']??0),'professional_id'=>(int)($row['professional_id']??0),'date'=>(string)($row['date']??''),'time'=>(string)($row['time']??''),'duration'=>(int)($row['duration_minutes']??$serviceDurations[(int)($row['service_id']??0)]??30)], array_filter((new AppointmentRepository())->all(), static fn(array $row): bool => !in_array((string)($row['status']??''), ['Cancelada','No asistio'], true))));
$appointmentPatient = $patient;
if ($receptionMode && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPatientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
    if ($selectedPatientId && $selectedPatientId > 0) {
        $selectedUser = (new UserRepository())->findById((int) $selectedPatientId);
        if ($selectedUser && ($selectedUser['role'] ?? '') === 'paciente') $appointmentPatient = ['id' => (int) $selectedUser['id'], 'nombre' => (string) $selectedUser['name'], 'email' => (string) ($selectedUser['email'] ?? '')];
    }
}

$resultado = procesar_formulario_cita($sucursales, $servicios, $profesionales);
$confirmacion = $resultado['confirmacion'];
$errores = $resultado['errores'];
$keepFormValues = $confirmacion === null && $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];

if ($confirmacion !== null) {
    try {
        $targetPatientId = $receptionMode ? (int) ($appointmentPatient['id'] ?? 0) : (int) $patient['id'];
        if ($targetPatientId < 1) throw new DomainException('SeleccionÃ¡ un paciente registrado.');
        (new AppointmentBookingService())->book($targetPatientId, ['branch_id'=>$confirmacion['sucursal_id'],'service_id'=>$confirmacion['servicio_id'],'professional_id'=>$confirmacion['profesional_id'],'date'=>$confirmacion['fecha'],'time'=>$confirmacion['hora'],'notes'=>$confirmacion['notas']], $sucursales, $servicios, $profesionales, array_column(generar_fechas_disponibles(14), 'valor'));
    } catch (Throwable $exception) { error_log('No se pudo guardar la cita: '. $exception->getMessage()); $confirmacion=null; $errores[] = $exception instanceof DomainException || $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'No se pudo guardar la cita. Intentá nuevamente.'; }
}

$fechas_disponibles = generar_fechas_disponibles(14);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita â€” Portal del Paciente</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="STYLES/agenda.css">
</head>

<body>

    <?php require_once __DIR__ . '/Components/navbar.php'; ?>

    <?php if ($confirmacion): ?>
        <div class="page-hero">
            <div class="page-hero__content">
                <div class="page-hero__eyebrow">âœ… Solicitud enviada</div>
                <h1>Â¡Cita Agendada!</h1>
                <p>Solicitud recibida. Se enviarÃ¡ la confirmaciÃ³n al correo del paciente.</p>
            </div>
        </div>

        <div class="confirmation-wrap">
            <div class="confirmation-icon">âœ…</div>
            <div class="confirmation-number"><?= htmlspecialchars($confirmacion['numero']) ?></div>
            <p style="color:var(--gris-texto); margin-bottom: 1rem;">NÃºmero de confirmaciÃ³n de la cita</p>

            <div class="email-notice">
                ðŸ“§ Se enviarÃ¡ un correo de confirmaciÃ³n a <strong><?= htmlspecialchars($appointmentPatient['email'] ?? '') ?></strong>
            </div>

            <div class="confirmation-card">
                <dl>
                    <div class="conf-row">
                        <dt>Sucursal</dt>
                        <dd><?= htmlspecialchars($confirmacion['sucursal']) ?></dd>
                    </div>
                    <div class="conf-row">
                        <dt>Servicio</dt>
                        <dd><?= htmlspecialchars($confirmacion['servicio']) ?></dd>
                    </div>
                    <div class="conf-row">
                        <dt>Profesional</dt>
                        <dd><?= htmlspecialchars($confirmacion['profesional']) ?></dd>
                    </div>
                    <div class="conf-row">
                        <dt>Fecha</dt>
                        <dd><?= htmlspecialchars(date('d/m/Y', strtotime($confirmacion['fecha']))) ?></dd>
                    </div>
                    <div class="conf-row">
                        <dt>Hora</dt>
                        <dd><?= htmlspecialchars($confirmacion['hora']) ?></dd>
                    </div>
                    <?php if ($confirmacion['notas']): ?>
                        <div class="conf-row">
                            <dt>Notas</dt>
                            <dd><?= htmlspecialchars($confirmacion['notas']) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="confirmation-actions">
                <?php if ($receptionMode): ?><a href="recepcion/citas.php" class="btn btn--primary">Ver citas</a><a href="agenda.php" class="btn btn--outline">Agendar otra cita</a><a href="recepcion/dashboard.php" class="btn btn--outline">Volver al panel</a><?php else: ?>
                <a href="mis-citas.php" class="btn btn--primary">ðŸ“… Ver citas</a>
                <a href="agenda.php" class="btn btn--outline">âž• Agendar otra cita</a>
                <a href="dashboard.php" class="btn btn--outline">ðŸ  Ir al inicio</a><?php endif; ?>
            </div>
        </div>

    <?php else: ?>

        <div class="page-hero">
            <div class="page-hero__content">
                <div class="page-hero__eyebrow">ðŸ“… Nueva cita</div>
                <h1><?= $receptionMode ? 'Agendar cita para un paciente' : 'Agendar una Cita' ?></h1>
                <p>Seleccionar sucursal, servicio, profesional y el horario que mejor se adapte al paciente.</p>
            </div>
        </div>

        <div class="stepper-wrapper">
            <div class="stepper">
                <div class="step active" id="step-tab-1" onclick="goToStep(1)">
                    <div class="step__connector"></div>
                    <div class="step__bubble" id="bubble-1">1</div>
                    <div class="step__label">Sucursal & Servicio</div>
                </div>
                <div class="step" id="step-tab-2" onclick="goToStep(2)">
                    <div class="step__connector"></div>
                    <div class="step__bubble" id="bubble-2">2</div>
                    <div class="step__label">Profesional</div>
                </div>
                <div class="step" id="step-tab-3" onclick="goToStep(3)">
                    <div class="step__connector"></div>
                    <div class="step__bubble" id="bubble-3">3</div>
                    <div class="step__label">Fecha & Hora</div>
                </div>
                <div class="step" id="step-tab-4" onclick="goToStep(4)">
                    <div class="step__connector"></div>
                    <div class="step__bubble" id="bubble-4">4</div>
                    <div class="step__label">Confirmar</div>
                </div>
            </div>
        </div>

        <div class="main">
            <div>
                <?php if (!empty($errores)): ?>
                    <div class="alert alert--error">
                        <span>âš ï¸</span>
                        <div>
                            <strong>Por favor corregir los siguientes errores:</strong>
                            <ul>
                                <?php foreach ($errores as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="agenda.php" id="formAgenda" novalidate>
                    <!--token improvisado contra ataques csrf -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="confirmar_cita" value="1">

                    <!-- Campos de selecciÃ³n (actualizados por JS) -->
                    <input type="hidden" name="sucursal_id" id="inp_sucursal_id" value="">
                    <input type="hidden" name="servicio_id" id="inp_servicio_id" value="">
                    <input type="hidden" name="profesional_id" id="inp_profesional_id" value="">
                    <input type="hidden" name="fecha" id="inp_fecha" value="">
                    <input type="hidden" name="hora" id="inp_hora" value="">

                    <div class="form-card">


                        <div class="form-section visible" id="section-1">
                            <?php if ($receptionMode): ?>
                                <h2 class="section-title">Seleccionar paciente</h2>
                                <?php if (!$patientOptions): ?><div class="alert alert--error" role="status">No hay pacientes registrados. Registrá primero al paciente desde el panel de recepción.</div><?php else: ?>
                                    <div class="form-group"><label for="patient-filter">Buscar paciente por nombre o email</label><input class="form-control" type="search" id="patient-filter" placeholder="Escribí para filtrar pacientes" autocomplete="off"></div>
                                    <div class="form-group"><label for="patient_id">Paciente <span aria-hidden="true">*</span></label><select class="form-control" id="patient_id" name="patient_id" required><option value="">Seleccioná un paciente</option><?php foreach ($patientOptions as $option): $optionId=(int)$option['id']; ?><option value="<?= $optionId ?>" data-search="<?= htmlspecialchars(mb_strtolower((string)$option['name'].' '.(string)($option['email']??''), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>" <?= (int)($appointmentPatient['id']??0)===$optionId?'selected':'' ?>><?= htmlspecialchars((string)$option['name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)($option['email']??''), ENT_QUOTES, 'UTF-8') ?> (#<?= $optionId ?>)</option><?php endforeach; ?></select></div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <h2 class="section-title">Seleccionar la Sucursal</h2>
                            <p class="section-subtitle">Elegir la clÃ­nica mÃ¡s conveniente para la visita.</p>

                            <div class="card-grid card-grid--2" style="margin-bottom:2rem;">
                                <?php foreach ($sucursales as $suc): ?>
                                    <label class="selectable-card" data-type="sucursal" data-id="<?= $suc['id'] ?>">
                                        <input type="radio" name="sucursal_radio" value="<?= $suc['id'] ?>">
                                        <div class="card-check">
                                            <svg viewBox="0 0 12 10" fill="none">
                                                <path d="M1 5l3.5 3.5L11 1" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <div class="card-icon">ðŸ¥</div>
                                        <div class="card-title"><?= htmlspecialchars($suc['nombre']) ?></div>
                                        <div class="card-sub">ðŸ“ <?= htmlspecialchars($suc['direccion']) ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <h2 class="section-title">Seleccionar el Servicio</h2>
                            <p class="section-subtitle">Indicar el tipo de atenciÃ³n que necesita el paciente.</p>

                            <div class="card-grid card-grid--2">
                                <?php foreach ($servicios as $srv): ?>
                                    <label class="selectable-card" data-type="servicio" data-id="<?= $srv['id'] ?>"
                                        data-duracion="<?= $srv['duracion'] ?>"
                                        data-nombre="<?= htmlspecialchars($srv['nombre']) ?>">
                                        <input type="radio" name="servicio_radio" value="<?= $srv['id'] ?>">
                                        <div class="card-check">
                                            <svg viewBox="0 0 12 10" fill="none">
                                                <path d="M1 5l3.5 3.5L11 1" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <div class="card-icon"><?= $srv['icono'] ?></div>
                                        <div class="card-title"><?= htmlspecialchars($srv['nombre']) ?></div>
                                        <div class="card-sub">
                                            <span class="duracion-badge">â±ï¸ <?= $srv['duracion'] ?> min</span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>


                        <div class="form-section" id="section-2">
                            <h2 class="section-title">Elegir Profesional</h2>
                            <p class="section-subtitle">Seleccionar un profesional de preferencia</p>


                            <div class="selectable-card badge-none selected" data-type="profesional" data-id=""
                                data-nombre="Sin preferencia" style="margin-bottom:.9rem;" id="no-pref-card">
                                <input type="radio" name="prof_radio" value="" checked>
                                <span>ðŸ‘¤</span>
                                <span>Sin preferencia â€” asignar disponible</span>
                            </div>

                            <div class="card-grid" style="grid-template-columns:1fr;">
                                <?php foreach ($profesionales as $pro): ?>
                                    <label class="selectable-card prof-card" data-type="profesional" data-id="<?= $pro['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($pro['nombre']) ?>">
                                        <input type="radio" name="prof_radio" value="<?= $pro['id'] ?>">
                                        <div class="card-check">
                                            <svg viewBox="0 0 12 10" fill="none">
                                                <path d="M1 5l3.5 3.5L11 1" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <div class="prof-avatar">
                                            <?= strtoupper(substr($pro['nombre'], strpos($pro['nombre'], ' ') + 1, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="card-title"><?= htmlspecialchars($pro['nombre']) ?></div>
                                            <div class="card-sub">ðŸŽ“ <?= htmlspecialchars($pro['especialidad']) ?></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>


                        <div class="form-section" id="section-3">
                            <h2 class="section-title">Seleccionar la Fecha</h2>
                            <p class="section-subtitle">Los prÃ³ximos 14 dÃ­as hÃ¡biles con disponibilidad.</p>

                            <div class="date-grid">
                                <?php foreach ($fechas_disponibles as $fd): ?>
                                    <div class="date-card" data-fecha="<?= $fd['valor'] ?>"
                                        data-display="<?= $fd['display'] ?>">
                                        <div class="date-card__dow"><?= $fd['dia'] ?></div>
                                        <div class="date-card__day"><?= (int) explode('-', $fd['valor'])[2] ?></div>
                                        <div class="date-card__mon"><?= date('M', strtotime($fd['valor'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <h2 class="section-title">Seleccionar el Horario</h2>
                            <p class="section-subtitle">Horarios disponibles para la fecha elegida.</p>

                            <div class="time-group">
                                <div class="time-group__label">ðŸŒ… MaÃ±ana</div>
                                <div class="time-grid">
                                    <?php
                                    $manana = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];
                                    foreach ($manana as $h):
                                        $cls = '';
                                        ?>
                                        <div class="time-slot <?= $cls ?>" data-hora="<?= $h ?>"><?= $h ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="time-group">
                                <div class="time-group__label">ðŸŒ‡ Tarde</div>
                                <div class="time-grid">
                                    <?php
                                    $tarde = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
                                    foreach ($tarde as $h):
                                        $cls = '';
                                        ?>
                                        <div class="time-slot <?= $cls ?>" data-hora="<?= $h ?>"><?= $h ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" id="section-4">
                            <h2 class="section-title">Confirmar Cita</h2>
                            <p class="section-subtitle">Revisar los datos antes de enviar la solicitud.</p>

                            <!-- Resumen final (visible en mobile cuando sidebar estÃ¡ oculto) -->
                            <div
                                style="background:var(--blanco-roto); border:2px solid var(--gris-borde); border-radius:var(--radio); padding:1.25rem 1.5rem; margin-bottom:1.5rem;">
                                <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
                                    <tbody>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; width:130px; vertical-align:top;">
                                                ðŸ‘¤ Paciente</td>
                                            <td id="conf-paciente" style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);">
                                                <?= htmlspecialchars($appointmentPatient['nombre'] ?? 'Seleccioná un paciente') ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                Sucursal</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-sucursal">â€”</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                ðŸ¦· Servicio</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-servicio">â€”</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                ðŸ‘¨â€âš•ï¸ Profesional</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-profesional">â€”</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                ðŸ“… Fecha</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-fecha">â€”</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                â° Hora</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-hora">â€”</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-group">
                                <label for="notas">Notas adicionales <span
                                        style="color:var(--gris-muted); font-weight:400;">(opcional)</span></label>
                                <textarea class="form-control" id="notas" name="notas" rows="3"
                                    placeholder="Describir brevemente el motivo de la consulta o cualquier dato relevante..."></textarea>
                            </div>

                            <div class="alert" id="confirm-alert"
                                style="display:none; background:var(--turquesa-light); border:1px solid rgba(14,165,200,.3); color:#0369A1;">
                                <span>â„¹ï¸</span>
                                <span>Al enviar, la cita quedarÃ¡ en estado <strong>Pendiente de confirmaciÃ³n</strong>.
                                    Se enviarÃ¡ un correo a <strong><?= htmlspecialchars($appointmentPatient['email'] ?? '') ?></strong>
                                    cuando
                                    la cita sea confirmada.</span>
                            </div>
                        </div>

                    </div>


                    <div class="step-nav">
                        <button type="button" class="btn btn--outline" id="btn-back" onclick="prevStep()"
                            style="visibility:hidden;">
                            â† Anterior
                        </button>
                        <div style="font-size:.8rem; color:var(--gris-muted);" id="step-counter">Paso 1 de 4</div>
                        <button type="button" class="btn btn--primary" id="btn-next" onclick="nextStep()">
                            Siguiente â†’
                        </button>
                        <button type="submit" class="btn btn--turquesa" id="btn-submit" style="display:none;">
                            âœ… Confirmar Cita
                        </button>
                    </div>
                </form>
            </div>

            <aside class="sidebar">
                <div class="summary-card">
                    <div class="summary-card__head">
                        <span>ðŸ“‹</span>
                        <h3>Resumen de la Cita</h3>
                    </div>
                    <div class="summary-card__body">
                        <div class="summary-row">
                            <span class="summary-row__label">Paciente</span>
                            <span class="summary-row__value"><?= htmlspecialchars($appointmentPatient['nombre'] ?? 'Seleccioná un paciente') ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row__label">Sucursal</span>
                            <span class="summary-row__value empty" id="sb-sucursal">Sin seleccionar</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row__label">Servicio</span>
                            <span class="summary-row__value empty" id="sb-servicio">Sin seleccionar</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row__label">DuraciÃ³n aprox.</span>
                            <span class="summary-row__value empty" id="sb-duracion">â€”</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row__label">Profesional</span>
                            <span class="summary-row__value empty" id="sb-profesional">Sin preferencia</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row__label">Fecha</span>
                            <span class="summary-row__value empty" id="sb-fecha">Sin seleccionar</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row__label">Hora</span>
                            <span class="summary-row__value empty" id="sb-hora">Sin seleccionar</span>
                        </div>
                    </div>
                </div>

                <div class="help-card">
                    <div class="help-card__title">ðŸ’¬ Â¿NecesitÃ¡s ayuda?</div>
                    <p>
                        PodÃ©s llamarnos al <a href="tel:+59829001234">2900 1234</a> o escribirnos por <a
                            href="https://wa.me/59899123456" target="_blank">WhatsApp</a> para coordinar la cita con
                        atenciÃ³n personalizada.
                    </p>
                </div>
            </aside>
        </div>

    <?php endif; ?>
    <?php require_once __DIR__ . '/Components/footer.php'; ?>

    <?php if ($receptionMode): ?><script>
    (()=>{const search=document.getElementById('patient-filter'),select=document.getElementById('patient_id');if(!select)return;const summary=document.querySelector('.summary-card .summary-row__value');const update=()=>{const option=select.selectedOptions[0];if(summary)summary.textContent=option&&option.value?option.textContent.replace(/\s·\s.*$/,''):'Seleccioná un paciente'};search?.addEventListener('input',()=>{const term=search.value.trim().toLocaleLowerCase();Array.from(select.options).slice(1).forEach(option=>{option.hidden=!option.dataset.search.includes(term)});if(select.selectedOptions[0]?.hidden)select.value='';update()});select.addEventListener('change',update);document.getElementById('formAgenda')?.addEventListener('submit',event=>{if(!select.value){event.preventDefault();showToast('Seleccioná un paciente para continuar.','warn');return}const button=document.getElementById('btn-submit');if(button){button.disabled=true;button.textContent='Guardando…'}});update()})();
    </script><?php endif; ?>
    <script src="functions.js"></script>
    <script>
      window.occupiedAppointmentSlots = <?= json_encode($activeAppointments, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
      document.addEventListener('click', () => setTimeout(() => {
        if (typeof state === 'undefined') return;
        document.querySelectorAll('.time-slot').forEach(slot => {
          const slotStart = slot.dataset.hora.split(':').map(Number).reduce((hour, minute) => hour * 60 + minute, 0);
          const slotDuration = Number(state.servicio.duracion) || 30;
          const occupied = state.sucursal.id && state.fecha.valor && window.occupiedAppointmentSlots.some(item => {
            const sameResource = item.branch_id === Number(state.sucursal.id) || (state.profesional.id && item.professional_id === Number(state.profesional.id));
            const busyStart = item.time.split(':').map(Number).reduce((hour, minute) => hour * 60 + minute, 0);
            return sameResource && item.date === state.fecha.valor && busyStart < slotStart + slotDuration && slotStart < busyStart + item.duration;
          });
          slot.classList.toggle('unavailable', Boolean(occupied));
          if (occupied && slot.classList.contains('selected')) {
            slot.classList.remove('selected'); state.hora = '';
            const input = document.getElementById('inp_hora'); if (input) input.value = '';
            updateSidebar();
          }
        });
      }, 0));
      document.getElementById('patient_id')?.addEventListener('change', event => {
        const option = event.target.selectedOptions[0];
        const name = option && option.value ? option.textContent.replace(/\s·\s.*$/, '') : 'Seleccioná un paciente';
        const summary = document.getElementById('conf-paciente'); if (summary) summary.textContent = name;
      });
    </script>
    <?php if ($keepFormValues): ?><script>document.addEventListener('DOMContentLoaded',()=>{const saved=<?= json_encode(['branch'=>(string)($keepFormValues['sucursal_id']??''),'service'=>(string)($keepFormValues['servicio_id']??''),'professional'=>(string)($keepFormValues['profesional_id']??''),'date'=>(string)($keepFormValues['fecha']??''),'time'=>(string)($keepFormValues['hora']??''),'notes'=>(string)($keepFormValues['notas']??'')], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;for(const [type,id] of [['sucursal',saved.branch],['servicio',saved.service],['profesional',saved.professional]]){if(!id)continue;document.querySelector(`.selectable-card[data-type="${type}"][data-id="${id}"]`)?.click()}if(saved.date)document.querySelector(`.date-card[data-fecha="${saved.date}"]`)?.click();if(saved.time)document.querySelector(`.time-slot[data-hora="${saved.time}"]`)?.click();const notes=document.getElementById('notas');if(notes)notes.value=saved.notes});</script><?php endif; ?>

</body>

</html>


