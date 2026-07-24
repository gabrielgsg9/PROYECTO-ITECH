<?php

session_start();

require_once __DIR__ . '/../Data/dataClinica.php';

require_once 'Helpers/agenda.helper.php';

$resultado = procesar_formulario_cita($sucursales, $servicios, $profesionales);
$confirmacion = $resultado['confirmacion'];
$errores = $resultado['errores'];

$fechas_disponibles = generar_fechas_disponibles(14);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita — Portal del Paciente</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="STYLES/agenda.css">
    <link rel="stylesheet" href="STYLES/navbar.css">
    <link rel="stylesheet" href="STYLES/footer.css">
</head>

<body>

    <?php require_once __DIR__ . '/Components/navbar.php'; ?>

    <?php if ($confirmacion): ?>
        <!--PANTALLA DE CONFIRMACIÓN-->
        <div class="page-hero">
            <div class="page-hero__content">
                <div class="page-hero__eyebrow">✅ Solicitud enviada</div>
                <h1>¡Cita Agendada!</h1>
                <p>Solicitud recibida. Se enviará la confirmación al correo del paciente.</p>
            </div>
        </div>

        <div class="confirmation-wrap">
            <div class="confirmation-icon">✅</div>
            <div class="confirmation-number"><?= htmlspecialchars($confirmacion['numero']) ?></div>
            <p style="color:var(--gris-texto); margin-bottom: 1rem;">Número de confirmación de la cita</p>

            <div class="email-notice">
                📧 Se enviará un correo de confirmación a <strong><?= htmlspecialchars($patient['email']) ?></strong>
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
                <a href="mis-citas.php" class="btn btn--primary">📅 Ver citas</a>
                <a href="agenda.php" class="btn btn--outline">➕ Agendar otra cita</a>
                <a href="dashboard.php" class="btn btn--outline">🏠 Ir al inicio</a>
            </div>
        </div>

    <?php else: ?>

        <!--HERO-->
        <div class="page-hero">
            <div class="page-hero__content">
                <div class="page-hero__eyebrow">📅 Nueva cita</div>
                <h1>Agendar una Cita</h1>
                <p>Seleccionar sucursal, servicio, profesional y el horario que mejor se adapte al paciente.</p>
            </div>
        </div>

        <!--STEPPER-->
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

        <!--FORM PRINCIPAL-->
        <div class="main">
            <div>
                <!-- Errores PHP -->
                <?php if (!empty($errores)): ?>
                    <div class="alert alert--error">
                        <span>⚠️</span>
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
                    <!-- CSRF token -->
                    <input type="hidden" name="csrf_token" value="<?= bin2hex(random_bytes(32)) ?>">
                    <input type="hidden" name="confirmar_cita" value="1">

                    <!-- Campos de selección (actualizados por JS) -->
                    <input type="hidden" name="sucursal_id" id="inp_sucursal_id" value="">
                    <input type="hidden" name="servicio_id" id="inp_servicio_id" value="">
                    <input type="hidden" name="profesional_id" id="inp_profesional_id" value="">
                    <input type="hidden" name="fecha" id="inp_fecha" value="">
                    <input type="hidden" name="hora" id="inp_hora" value="">

                    <div class="form-card">

                        <!-- ─── PASO 1: Sucursal & Servicio ─────────────── -->
                        <div class="form-section visible" id="section-1">
                            <h2 class="section-title">Seleccionar la Sucursal</h2>
                            <p class="section-subtitle">Elegir la clínica más conveniente para la visita.</p>

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
                                        <div class="card-icon">🏥</div>
                                        <div class="card-title"><?= htmlspecialchars($suc['nombre']) ?></div>
                                        <div class="card-sub">📍 <?= htmlspecialchars($suc['direccion']) ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <h2 class="section-title">Seleccionar el Servicio</h2>
                            <p class="section-subtitle">Indicar el tipo de atención que necesita el paciente.</p>

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
                                            <span class="duracion-badge">⏱️ <?= $srv['duracion'] ?> min</span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ─── PASO 2: Profesional ──────────────────────── -->
                        <div class="form-section" id="section-2">
                            <h2 class="section-title">Elegir Profesional</h2>
                            <p class="section-subtitle">Seleccionar un profesional de preferencia</p>

                            <!-- Sin preferencia -->
                            <div class="selectable-card badge-none selected" data-type="profesional" data-id=""
                                data-nombre="Sin preferencia" style="margin-bottom:.9rem;" id="no-pref-card">
                                <input type="radio" name="prof_radio" value="" checked>
                                <span>👤</span>
                                <span>Sin preferencia — asignar disponible</span>
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
                                            <div class="card-sub">🎓 <?= htmlspecialchars($pro['especialidad']) ?></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ─── PASO 3: Fecha & Hora ─────────────────────── -->
                        <div class="form-section" id="section-3">
                            <h2 class="section-title">Seleccionar la Fecha</h2>
                            <p class="section-subtitle">Los próximos 14 días hábiles con disponibilidad.</p>

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
                                <div class="time-group__label">🌅 Mañana</div>
                                <div class="time-grid">
                                    <?php
                                    $manana = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];
                                    $no_disponibles = ['09:00', '10:00', '11:00']; // Simulados
                                    foreach ($manana as $h):
                                        $cls = in_array($h, $no_disponibles) ? 'unavailable' : '';
                                        ?>
                                        <div class="time-slot <?= $cls ?>" data-hora="<?= $h ?>"><?= $h ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="time-group">
                                <div class="time-group__label">🌇 Tarde</div>
                                <div class="time-grid">
                                    <?php
                                    $tarde = ['14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
                                    $no_disp_tarde = ['14:00', '15:00'];
                                    foreach ($tarde as $h):
                                        $cls = in_array($h, $no_disp_tarde) ? 'unavailable' : '';
                                        ?>
                                        <div class="time-slot <?= $cls ?>" data-hora="<?= $h ?>"><?= $h ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ─── PASO 4: Confirmar ──────────────────-->
                        <div class="form-section" id="section-4">
                            <h2 class="section-title">Confirmar Cita</h2>
                            <p class="section-subtitle">Revisar los datos antes de enviar la solicitud.</p>

                            <!-- Resumen final (visible en mobile cuando sidebar está oculto) -->
                            <div
                                style="background:var(--blanco-roto); border:2px solid var(--gris-borde); border-radius:var(--radio); padding:1.25rem 1.5rem; margin-bottom:1.5rem;">
                                <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
                                    <tbody>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; width:130px; vertical-align:top;">
                                                👤 Paciente</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);">
                                                <?= htmlspecialchars($patient['nombre']) ?>
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
                                                id="conf-sucursal">—</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                🦷 Servicio</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-servicio">—</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                👨‍⚕️ Profesional</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-profesional">—</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                📅 Fecha</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-fecha">—</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="height:1px; background:var(--gris-borde);"></td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding:.5rem 0; color:var(--gris-texto); font-weight:600; vertical-align:top;">
                                                ⏰ Hora</td>
                                            <td style="padding:.5rem 0; font-weight:700; color:var(--azul-profundo);"
                                                id="conf-hora">—</td>
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
                                <span>ℹ️</span>
                                <span>Al enviar, la cita quedará en estado <strong>Pendiente de confirmación</strong>.
                                    Se enviará un correo a <strong><?= htmlspecialchars($patient['email']) ?></strong>
                                    cuando
                                    la cita sea confirmada.</span>
                            </div>
                        </div>

                    </div><!-- /form-card -->

                    <!-- ── Navegación entre pasos ── -->
                    <div class="step-nav">
                        <button type="button" class="btn btn--outline" id="btn-back" onclick="prevStep()"
                            style="visibility:hidden;">
                            ← Anterior
                        </button>
                        <div style="font-size:.8rem; color:var(--gris-muted);" id="step-counter">Paso 1 de 4</div>
                        <button type="button" class="btn btn--primary" id="btn-next" onclick="nextStep()">
                            Siguiente →
                        </button>
                        <button type="submit" class="btn btn--turquesa" id="btn-submit" style="display:none;">
                            ✅ Confirmar Cita
                        </button>
                    </div>
                </form>
            </div><!-- /col principal -->

            <!-- ── SIDEBAR ── -->
            <aside class="sidebar">
                <div class="summary-card">
                    <div class="summary-card__head">
                        <span>📋</span>
                        <h3>Resumen de la Cita</h3>
                    </div>
                    <div class="summary-card__body">
                        <div class="summary-row">
                            <span class="summary-row__label">Paciente</span>
                            <span class="summary-row__value"><?= htmlspecialchars($patient['nombre']) ?></span>
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
                            <span class="summary-row__label">Duración aprox.</span>
                            <span class="summary-row__value empty" id="sb-duracion">—</span>
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
                    <div class="help-card__title">💬 ¿Necesitás ayuda?</div>
                    <p>
                        Podés llamarnos al <a href="tel:+59829001234">2900 1234</a> o escribirnos por <a
                            href="https://wa.me/59899123456" target="_blank">WhatsApp</a> para coordinar la cita con
                        atención personalizada.
                    </p>
                </div>
            </aside>
        </div>

    <?php endif; ?>
    <?php require_once __DIR__ . '/Components/footer.php'; ?>

    <script src="functions.js"></script>

</body>

</html>