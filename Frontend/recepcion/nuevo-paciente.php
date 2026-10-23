<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['recepcionista'], '../login.php');
require_once __DIR__ . '/../../Service/UserService.php';

$errors = [];
$name = '';
$email = '';
$created = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = (string) ($_POST['name'] ?? '');
    $email = (string) ($_POST['email'] ?? '');
    if (!valid_csrf($_POST['csrf_token'] ?? null))
        $errors[] = 'La solicitud venció o no es válida. Recargá la página e intentá nuevamente.';
    else
        try {
            $result = (new UserService())->createPatient($name, $email, (string) ($_POST['password'] ?? ''), (string) ($_POST['confirmPassword'] ?? ''));
            $errors = $result['errors'];
            $created = $result['user'] !== null;
        } catch (Throwable $exception) {
            error_log('No se pudo crear paciente desde recepción: ' . $exception->getMessage());
            $errors[] = 'No fue posible guardar el paciente. Intentá más tarde.';
        }
}
function patient_form_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
$token = csrf_token();
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo paciente — Recepción</title>
    <link rel="stylesheet" href="../STYLES/dashboard.css">
    <link rel="stylesheet" href="../STYLES/pages/citas.css">
</head>

<body>
    <nav class="breadcrumb" aria-label="Ruta de navegación"><a href="dashboard.php">Panel</a><span
            class="sep">/</span><span>Nuevo paciente</span></nav>
    <header class="page-hero">
        <div class="page-hero__content"><span class="page-hero__eyebrow">Recepción</span>
            <h1>Nuevo paciente</h1>
            <p>Creá una cuenta de paciente para que pueda seleccionarse en la agenda.</p>
        </div>
    </header>
    <main class="main">
        <section class="form-card">
            <div class="card-head">
                <div>
                    <h2 class="section-title">Datos de la cuenta</h2>
                    <p class="section-subtitle">Se guardará en el registro compartido de usuarios y pacientes.</p>
                </div>
            </div>
            <?php if ($created): ?>
                <p class="alert alert--ok" role="status">Paciente registrado correctamente. Ya está disponible en la
                    búsqueda de pacientes para agendar citas.</p><?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert--error" role="alert">
                    <ul><?php foreach ($errors as $error): ?>
                            <li><?= patient_form_e($error) ?></li><?php endforeach; ?>
                    </ul>
                </div><?php endif; ?>
            <form method="post" class="patient-create-form"><input type="hidden" name="csrf_token"
                    value="<?= patient_form_e($token) ?>">
                <label for="name">Nombre completo</label><input id="name" name="name" type="text" maxlength="120"
                    required autocomplete="name" value="<?= patient_form_e($name) ?>">
                <label for="email">Email</label><input id="email" name="email" type="email" maxlength="254" required
                    autocomplete="email" value="<?= patient_form_e($email) ?>">
                <label for="password">Contraseña inicial</label><input id="password" name="password" type="password"
                    minlength="4" required autocomplete="new-password"><small>La cuenta utiliza el acceso habitual de
                    pacientes. Compartí la contraseña inicial con el paciente de forma segura.</small>
                <label for="confirmPassword">Repetir contraseña</label><input id="confirmPassword"
                    name="confirmPassword" type="password" minlength="4" required autocomplete="new-password">
                <div class="patient-create-actions"><button class="btn btn--turquesa" type="submit">Guardar
                        paciente</button><a class="btn btn--outline" href="dashboard.php">Volver al panel</a></div>
            </form>
        </section>
    </main>
    <style>
        .patient-create-form {
            display: grid;
            gap: .65rem;
            max-width: 36rem
        }

        .patient-create-form label {
            font-weight: 700;
            margin-top: .55rem
        }

        .patient-create-form input {
            width: 100%;
            padding: .75rem;
            border: 1px solid #d3dce2;
            border-radius: .55rem;
            font: inherit
        }

        .patient-create-form small {
            color: #586a75
        }

        .patient-create-actions {
            display: flex;
            gap: .75rem;
            align-items: center;
            margin-top: 1rem;
            flex-wrap: wrap
        }

        .patient-create-actions a {
            text-decoration: none
        }
    </style>
    <script>document.querySelector('.patient-create-form').addEventListener('submit', function () { const button = this.querySelector('button[type="submit"]'); button.disabled = true; button.textContent = 'Guardando…'; });</script>
</body>

</html>