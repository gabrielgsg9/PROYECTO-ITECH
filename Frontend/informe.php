<?php
require_once __DIR__ . '/Helpers/auth.php';
$patient = get_current_patient();
if (!$patient) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../Repository/AppointmentRepository.php';
require_once __DIR__ . '/../Repository/ReportRepository.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$appointment = null;
if ($id) foreach ((new AppointmentRepository())->all() as $row) if ((int)($row['id']??0)===$id && (int)($row['patient_id']??0)===(int)$patient['id']) { $appointment=$row; break; }
$report = $appointment ? (new ReportRepository())->findByAppointment($id) : null;
$path = $report ? (new ReportRepository())->filePath($report) : null;
if (!$appointment || !$report || !$path) { http_response_code(404); exit('Informe no disponible.'); }
header('Content-Type: application/pdf'); header('X-Content-Type-Options: nosniff'); header('Content-Length: '.filesize($path));
header('Content-Disposition: attachment; filename="informe-cita-'.$id.'.pdf"'); readfile($path);
