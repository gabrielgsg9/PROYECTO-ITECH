<?php

$patient = [
    'id' => 42,
    'nombre' => 'María González',
    'email' => 'maria.gonzalez@email.com',
    'cedula' => '4.521.830-7',
];

$sucursales = [
    ['id' => 1, 'nombre' => 'Sucursal Centro', 'direccion' => 'Av. 18 de Julio 1234, Montevideo'],
    ['id' => 2, 'nombre' => 'Sucursal Pocitos', 'direccion' => 'Br. España 2780, Montevideo'],
    ['id' => 3, 'nombre' => 'Sucursal Carrasco', 'direccion' => 'Av. Rivera 6200, Montevideo'],
    ['id' => 4, 'nombre' => 'Sucursal Punta Carretas', 'direccion' => 'Solano García 2785, Montevideo'],
];

$servicios = [
    ['id' => 1, 'nombre' => 'Radiografía Periapical', 'duracion' => 15, 'icono' => '🦷'],
    ['id' => 2, 'nombre' => 'Radiografía Bite-Wing', 'duracion' => 15, 'icono' => '🩻'],
    ['id' => 3, 'nombre' => 'Radiografía Panorámica', 'duracion' => 20, 'icono' => '📸'],
    ['id' => 4, 'nombre' => 'Telerradiografía', 'duracion' => 20, 'icono' => '📐'],
    ['id' => 5, 'nombre' => 'Tomografía Cone Beam (CBCT)', 'duracion' => 40, 'icono' => '🖥️'],
    ['id' => 6, 'nombre' => 'Escaneo Intraoral 3D', 'duracion' => 30, 'icono' => '📷'],
];

$profesionales = [
    [
        'id' => 1,
        'nombre' => 'Dr. Juan Pérez',
        'especialidad' => 'Consulta General'
    ],
    [
        'id' => 2,
        'nombre' => 'Dra. Ana López',
        'especialidad' => 'Limpieza Dental'
    ],
    [
        'id' => 3,
        'nombre' => 'Dr. Carlos Rodríguez',
        'especialidad' => 'Ortodoncia'
    ]
];

$testimonios = [
    [
        'texto'    => 'El equipo me hizo sentir completamente tranquila desde el primer momento. El tratamiento de ortodoncia cambió mi vida.',
        'nombre'   => 'Valentina R.',
        'servicio' => 'Ortodoncia',
        'estrellas'=> 5,
    ],
    [
        'texto'    => 'Excelente atención e instalaciones muy modernas. El blanqueamiento superó todas mis expectativas.',
        'nombre'   => 'Martín G.',
        'servicio' => 'Consulta General',
        'estrellas'=> 5,
    ],
    [
        'texto'    => 'Llevé a mis hijos por primera vez y fue una experiencia fantástica. Quedaron encantados y sin miedo.',
        'nombre'   => 'Carolina S.',
        'servicio' => 'Limpieza Dental',
        'estrellas'=> 5,
    ],
    [
        'texto'    => 'El seguimiento fue constante y el resultado final es increíble. Lo recomiendo sin dudarlo.',
        'nombre'   => 'Roberto D.',
        'servicio' => 'Consulta General',
        'estrellas'=> 5,
    ],
];