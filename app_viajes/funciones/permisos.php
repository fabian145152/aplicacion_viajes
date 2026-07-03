<?php

function obtenerModulosPorPermiso($permiso) {

    $modulos = [

        0 => [ // SUPERUSUARIO
            'Dashboard' => 'dashboard.php',
            'Usuarios' => 'operadores.php',
            'Vehículos' => 'vehiculos.php',
            'Viajes' => 'viajes.php',
            'Reportes' => 'reportes.php',
            'Configuración' => 'config.php'
        ],

        1 => [ // EXPERTO
            'Dashboard' => 'dashboard.php',
            'Vehículos' => 'vehiculos.php',
            'Viajes' => 'viajes.php',
            'Reportes' => 'reportes.php'
        ],

        2 => [ // STANDARD
            'Dashboard' => 'dashboard.php',
            'Viajes' => 'viajes.php'
        ],

        3 => [ // ADMIN
            'Dashboard' => 'dashboard.php',
            'Usuarios' => 'operadores.php',
            'Viajes' => 'viajes.php'
        ]
    ];

    return $modulos[$permiso] ?? [];
}