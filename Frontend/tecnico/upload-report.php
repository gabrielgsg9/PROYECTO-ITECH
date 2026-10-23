<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['tecnico'], '../login.php');
require_once __DIR__ . '/../../Repository/AppointmentRepository.php';
require_once __DIR__ . '/../../Repository/ReportRepository.php';
require_once __DIR__ . '/../../Service/StudyWorkflowService.php';

function study_workflow_redirect(string $result, int $patientId = 0): never
{
    $query = $result . '=1';
    if ($patientId > 0) {
        $query .= '&patient=' . $patientId;
    }
    header('Location: dashboard.php?' . $query);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf($_POST['csrf_token'] ?? null)) {
    study_workflow_redirect('error');
}

$appointmentId = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT);
$patientId = filter_var($_POST['return_patient_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
if ($appointmentId === false || $appointmentId === null || $appointmentId < 1) {
    study_workflow_redirect('error');
}

$appointments = new AppointmentRepository();
$reports = new ReportRepository();
$workflow = new StudyWorkflowService($appointments, $reports);
$action = (string) ($_POST['action'] ?? 'upload');

try {
    if ($action === 'complete') {
        if (!$workflow->confirmCompleted($appointmentId)) {
            study_workflow_redirect('error', $patientId);
        }
        study_workflow_redirect('completed', $patientId);
    }

    if ($action !== 'upload' || !isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK
        || !is_numeric($_FILES['pdf']['size'] ?? null) || (int) $_FILES['pdf']['size'] > 10 * 1024 * 1024) {
        study_workflow_redirect('error', $patientId);
    }

    $temporaryPath = (string) ($_FILES['pdf']['tmp_name'] ?? '');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (!is_uploaded_file($temporaryPath) || $finfo->file($temporaryPath) !== 'application/pdf'
        || file_get_contents($temporaryPath, false, null, 0, 5) !== '%PDF-') {
        study_workflow_redirect('error', $patientId);
    }

    $workflow->uploadReport(
        $appointmentId,
        (int) $_SESSION['user_id'],
        (string) ($_FILES['pdf']['name'] ?? 'informe.pdf'),
        $temporaryPath
    );
    study_workflow_redirect('uploaded', $patientId);
} catch (DomainException $exception) {
    error_log('No se pudo completar el flujo del informe: ' . $exception->getMessage());
    study_workflow_redirect('error', $patientId);
} catch (Throwable $exception) {
    error_log('Error al procesar el flujo del informe: ' . $exception->getMessage());
    study_workflow_redirect('error', $patientId);
}
