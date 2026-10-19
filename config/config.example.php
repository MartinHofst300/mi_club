<?php
/**
 * Plantilla de configuracion del sistema MiClub (ejemplo).
 * Copie este archivo como config/config.php y configure las credenciales de su entorno.
 */

return [
    'base_datos' => [
        'host' => '127.0.0.1',
        'puerto' => 3306,
        'nombre' => 'mi_club',
        'usuario' => 'root',
        'clave' => '',
        'charset' => 'utf8mb4'
    ],
    'aplicacion' => [
        'nombre' => 'MiClub',
        'zona_horaria' => 'America/Argentina/Buenos_Aires',
        'modo_depuracion' => false,
        'ruta_raiz' => dirname(__DIR__),
        'ruta_logs' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'errores.log'
    ],
    'sesion' => [
        'nombre' => 'MICLUB_SESION',
        'vida_util' => 7200,
        'tiempo_inactividad' => 1800, // Segundos de inactividad maxima permitida
        'ruta' => '/',
        'http_only' => true,
        'same_site' => 'Lax',
        'secure' => false
    ]
];
