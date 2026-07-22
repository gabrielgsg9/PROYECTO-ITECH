<?php
//ACLARACION: Este helper esta hecho unicamente para poder probar la funcionnalida completa del form
function procesar_formulario_cita(array $sucursales, array $servicios, array $profesionales): array
{
    $confirmacion = null;
    $errores = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_cita'])) {

        $sucursal_id = filter_input(INPUT_POST, 'sucursal_id', FILTER_VALIDATE_INT);
        $servicio_id = filter_input(INPUT_POST, 'servicio_id', FILTER_VALIDATE_INT);
        $profesional_id = filter_input(INPUT_POST, 'profesional_id', FILTER_VALIDATE_INT);
        $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS);
        $hora = filter_input(INPUT_POST, 'hora', FILTER_SANITIZE_SPECIAL_CHARS);
        $notas = htmlspecialchars(trim($_POST['notas'] ?? ''), ENT_QUOTES, 'UTF-8');

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

        if (empty($errores)) {
            // Buscar datos de las selecciones
            $suc = array_filter($sucursales, fn($s) => $s['id'] == $sucursal_id);
            $srv = array_filter($servicios, fn($s) => $s['id'] == $servicio_id);
            $pro = $profesional_id ? array_filter($profesionales, fn($p) => $p['id'] == $profesional_id) : [];

            $confirmacion = [
                'numero' => 'C-2025-' . rand(10000, 99999),
                'sucursal' => reset($suc)['nombre'] ?? '',
                'servicio' => reset($srv)['nombre'] ?? '',
                'profesional' => $pro ? reset($pro)['nombre'] : 'Sin preferencia',
                'fecha' => $fecha,
                'hora' => $hora,
                'notas' => $notas,
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