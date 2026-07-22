<?php
/**
 * home.php — Página de inicio · OdontoClinic
 */
session_start();

require_once "../Data/dataClinica.php";

// Índice profesional por especialidad para asociar fácil
$prof_idx = [];
foreach ($profesionales as $p) $prof_idx[$p['especialidad']] = $p;


$logged_in    = isset($_SESSION['patient_id']);
$patient_name = $logged_in ? htmlspecialchars($_SESSION['patient_name'] ?? '') : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OdontoClinic — Portal del Paciente</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="STYLES/navbar.css">
    <link rel="stylesheet" href="STYLES/home.css">
    <link rel="stylesheet" href="STYLES/footer.css">
</head>
<body>


<?php require_once __DIR__ . '/Components/navbar.php'; ?>

<!-- ══ HERO ════════════════════════════════════ -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-tag">4 sucursales en Montevideo</div>

        <h1>Salud bucal en<br><em>manos expertas.</em></h1>

        <p>Agendar citas online, acceder al historial clínico y gestionar los estudios desde cualquier dispositivo.</p>

        <div class="hero-btns">
            <?php if ($logged_in): ?>
                <a href="agenda.php"    class="btn btn--solid">📅 Agendar cita</a>
                <a href="dashboard.php" class="btn btn--ghost">Panel</a>
            <?php else: ?>
                <a href="registro.php" class="btn btn--solid">Crear cuenta</a>
                <a href="login.php"    class="btn btn--ghost">Ya tiene cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══ STATS ═══════════════════════════════════ -->
<div class="stats">
    <div class="stats-inner">
        <div class="stat">
            <div class="stat-n">12<span>K+</span></div>
            <div class="stat-l">Pacientes activos</div>
        </div>
        <div class="stat">
            <div class="stat-n"><?= count($sucursales) ?></div>
            <div class="stat-l">Sucursales</div>
        </div>
        <div class="stat">
            <div class="stat-n">98<span>%</span></div>
            <div class="stat-l">Satisfacción</div>
        </div>
        <div class="stat">
            <div class="stat-n">24<span>hs</span></div>
            <div class="stat-l">Urgencias</div>
        </div>
    </div>
</div>

<!-- ══ SERVICIOS (slider) ══════════════════════ -->
<section class="section section--alt">
    <div class="sec-wrap">
        <div class="sec-head">
            <div class="sec-eye">Nuestros servicios</div>
            <h2 class="sec-title">Servicios disponibles</h2>
            <p class="sec-sub">Seleccionar el servicio y agendar en segundos.</p>
        </div>

        <div class="slider-wrap">
            <div class="slider" id="slider">
                <?php foreach ($servicios as $srv):
                    $prof = $prof_idx[$srv['nombre']] ?? null;
                ?>
                <div class="slide">
                    <div class="slide-icon"><?= $srv['icono'] ?></div>
                    <div class="slide-name"><?= htmlspecialchars($srv['nombre']) ?></div>
                    <div class="slide-badge">⏱ <?= $srv['duracion'] ?> min</div>
                    <?php if ($prof): ?>
                    <div class="slide-prof">👨‍⚕️ <strong><?= htmlspecialchars($prof['nombre']) ?></strong></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="slider-btns">
                <button class="slider-btn" id="prev" aria-label="Anterior">‹</button>
                <button class="slider-btn" id="next" aria-label="Siguiente">›</button>
            </div>

            <div class="slider-dots" id="dots">
                <?php foreach ($servicios as $i => $srv): ?>
                <button class="dot <?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="text-align:center; margin-top:2.25rem;">
            <a href="agenda.php" class="btn btn--dark" style="font-size:.9rem; padding:.7rem 1.75rem;">
                📅 Agendar una consulta
            </a>
        </div>
    </div>
</section>

<!-- Testimonios (hardcodaeada)-->
<section class="section">
    <div class="sec-wrap">
        <div class="sec-head">
            <div class="sec-eye">Reseñas</div>
            <h2 class="sec-title">Lo que dicen los pacientes</h2>
        </div>

        <div class="reviews-grid">
            <?php foreach ($testimonios as $t): ?>
            <div class="review">
                <div class="stars"><?= str_repeat('★', $t['estrellas']) ?></div>
                <p class="r-text">"<?= htmlspecialchars($t['texto']) ?>"</p>
                <div class="r-autor">
                    <div class="r-av"><?= strtoupper(substr($t['nombre'], 0, 1)) ?></div>
                    <div>
                        <div class="r-name"><?= htmlspecialchars($t['nombre']) ?></div>
                        <div class="r-srv">✅ <?= htmlspecialchars($t['servicio']) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<?php include 'Components/footer.php'; ?>

<script>
const slider = document.getElementById('slider');
const dots   = document.querySelectorAll('.dot');
const slides = slider.querySelectorAll('.slide');
const slideW = () => slides[0].offsetWidth + 20; // gap 1.25rem = 20px

let current = 0;

function goTo(i) {
    current = Math.max(0, Math.min(i, slides.length - 1));
    slider.scrollTo({ left: current * slideW(), behavior: 'smooth' });
    dots.forEach((d, idx) => d.classList.toggle('active', idx === current));
}

document.getElementById('prev').addEventListener('click', () => goTo(current - 1));
document.getElementById('next').addEventListener('click', () => goTo(current + 1));
dots.forEach(d => d.addEventListener('click', () => goTo(+d.dataset.i)));

// Sync dots on manual swipe
slider.addEventListener('scroll', () => {
    const i = Math.round(slider.scrollLeft / slideW());
    if (i !== current) { current = i; dots.forEach((d, idx) => d.classList.toggle('active', idx === current)); }
}, { passive: true });
</script>

<script>
  const toggle = document.getElementById('navbarToggle');
  const menu = document.getElementById('navbarMenu');

  toggle.addEventListener('click', () => {
    toggle.classList.toggle('active');
    menu.classList.toggle('active');
  });
</script>
</body>
</html>