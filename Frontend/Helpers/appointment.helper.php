<?php
//ACLARACION: Este helper esta hecho unicamente para poder probar la funcionnalida completa del form
function procesar_formulario_cita(array $sucursales, array $servicios, array $profesionales): array
{
    $confirmacion = null;
    $errores = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_cita'])) {
        require_once __DIR__ . '/auth.php';
        $sucursal_id = filter_var($_POST['sucursal_id'] ?? null, FILTER_VALIDATE_INT);
        $servicio_id = filter_var($_POST['servicio_id'] ?? null, FILTER_VALIDATE_INT);
        $profesional_id = ($_POST['profesional_id'] ?? '') === '' ? null : filter_var($_POST['profesional_id'], FILTER_VALIDATE_INT);
        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $hora = trim((string)($_POST['hora'] ?? ''));
        $notas = trim((string)($_POST['notas'] ?? ''));

        if (!valid_csrf($_POST['csrf_token'] ?? null)) $errores[] = 'La solicitud venció o no es válida.';

        if (!$sucursal_id) {
            $errores[] = 'Debess seleccionar una sucursal.';
        }
        if (!$servicio_id) {
            $errores[] = 'Debe seleccionar un servicio.';
        }
        if (!$fecha) {
            $errores[] = 'Debe seleccionar una fecha.';
        }
        if (!$hora) {
            $errores[] = 'Debe selecccionar un horario.';
        }
        if (($_POST['profesional_id'] ?? '') !== '' && $profesional_id === false) $errores[] = 'El profesional seleccionado no es válido.';
        $disponibles = array_column(generar_fechas_disponibles(14), 'valor');
        if ($fecha !== '' && !in_array($fecha, $disponibles, true)) $errores[] = 'La fecha seleccionada no está disponible.';
        if ($hora !== '' && !in_array($hora, ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30'], true)) $errores[] = 'El horario seleccionado no está disponible.';

        if (empty($errores)) {
            // Buscar datos de las selecciones
            $suc = array_filter($sucursales, fn($s) => $s['id'] == $sucursal_id);
            $srv = array_filter($servicios, fn($s) => $s['id'] == $servicio_id);
            $pro = $profesional_id ? array_filter($profesionales, fn($p) => $p['id'] == $profesional_id) : [];

            if (!$suc || !$srv || ($profesional_id && !$pro)) {
                $errores[] = 'La sucursal, servicio o profesional seleccionado no es válido.';
                return ['confirmacion' => null, 'errores' => $errores];
            }

            $confirmacion = [
                'numero' => 'C-' . date('Y') . '-' . bin2hex(random_bytes(4)),
                'sucursal' => reset($suc)['nombre'] ?? '',
                'servicio' => reset($srv)['nombre'] ?? '',
                'profesional' => $pro ? reset($pro)['nombre'] : 'Sin preferencia',
                'fecha' => $fecha,
                'hora' => $hora,
                'notas' => $notas,
                'sucursal_id' => $sucursal_id,
                'servicio_id' => $servicio_id,
                'profesional_id' => $profesional_id,
            ];
        }
    }

    return [
        'confirmacion' => $confirmacion,
        'errores' => $errores,
    ];
}


function generar_fechas_disponibles(int $cantidad_dias = 14): array
{
    $fechas_disponibles = [];
    $hoy = new DateTime();
    $count = 0;
    $i = 1;

    while ($count < $cantidad_dias) {
        $dia = clone $hoy;
        $dia->modify("+{$i} days");
        $dow = (int) $dia->format('N'); 

        if ($dow <= 6) { // Lunes a Sabado
            $fechas_disponibles[] = [
                'valor' => $dia->format('Y-m-d'),
                'display' => $dia->format('d/m/Y'),
                'dia' => ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][$dow],
            ];
            $count++;
        }
        $i++;
    }

    return $fechas_disponibles;
}
