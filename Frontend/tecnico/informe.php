<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['tecnico'], '../login.php');
require_once __DIR__ . '/../../Repository/AppointmentRepository.php';
require_once __DIR__ . '/../../Repository/ReportRepository.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Informe no disponible.'); }
$appointment = null;
foreach ((new AppointmentRepository())->all() as $row) if ((int)($row['id']??0)===$id) { $appointment=$row; break; }
$report = (new ReportRepository())->findByAppointment($id);
$path = $report ? (new ReportRepository())->filePath($report) : null;
if (!$appointment || !$report || !$path) { http_response_code(404); exit('Informe no disponible.'); }
header('Content-Type: application/pdf'); header('X-Content-Type-Options: nosniff'); header('Content-Length: '.filesize($path));
header('Content-Disposition: attachment; filename="informe-cita-'.$id.'.pdf"'); readfile($path);
