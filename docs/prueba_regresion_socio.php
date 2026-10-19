<?php
/**
 * Prueba de Regresion Automatizada: Flujo del Navegador para el Rol Socio
 *
 * Simula el camino exacto que realiza la aplicacion SPA en el navegador:
 * 1. POST /api/auth/iniciar-sesion
 * 2. GET /api/auth/sesion para leer el socio_id dinamicamente (sin hardcodeo)
 * 3. Consumo de los 4 endpoints del portal del socio:
 *    - GET /api/socios/{socio_id}/carnet
 *    - GET /api/socios/{socio_id}/estado-cuenta
 *    - GET /api/socios/{socio_id}/pagos
 *    - GET /api/socios/{socio_id}/entrenamientos
 *
 * Ejecutado para Marcos Moroso (socio_id 1, Moroso) y Camila Diaz (socio_id 7, Activo sin division).
 */

$baseUrl = 'http://localhost/dashboard/miclub/public/api';
$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'miclub_regresion_cookies.txt';

function peticion(string $metodo, string $ruta, array $datos = [], ?string $tokenCsrf = null): array
{
    global $baseUrl, $cookieFile;
    $ch = curl_init($baseUrl . $ruta);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $headers = ['Accept: application/json'];
    if (!empty($datos)) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
    if ($tokenCsrf) {
        $headers[] = 'X-CSRF-Token: ' . $tokenCsrf;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $raw = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'codigo' => $codigo,
        'cuerpo' => json_decode($raw, true) ?: [],
        'raw' => $raw
    ];
}

echo "===============================================================================\n";
echo "PRUEBA DE REGRESION: FLUJO DE SESION Y ENDPOINTS DEL SOCIO\n";
echo "===============================================================================\n\n";

$sociosPrueba = [
    [
        'nombre' => 'Marcos Moroso (Moroso)',
        'email' => 'marcos.moroso@email.local',
        'clave' => 'socio123',
        'esperaVigente' => false,
        'esperaDeudaMayorCero' => true
    ],
    [
        'nombre' => 'Camila Diaz (Activa)',
        'email' => 'camila.diaz@email.local',
        'clave' => 'socio123',
        'esperaVigente' => true,
        'esperaDeudaMayorCero' => true
    ]
];

$todoOk = true;

foreach ($sociosPrueba as $s) {
    echo "--- Evaluando: {$s['nombre']} ({$s['email']}) ---\n";
    if (file_exists($cookieFile)) {
        unlink($cookieFile);
    }

    // 1. Iniciar sesion
    $login = peticion('POST', '/auth/iniciar-sesion', [
        'identificador' => $s['email'],
        'password' => $s['clave']
    ]);

    if ($login['codigo'] !== 200 || !isset($login['cuerpo']['datos'])) {
        echo "  [FALLO] Login fallido (HTTP {$login['codigo']})\n";
        $todoOk = false;
        continue;
    }
    echo "  [OK] Login exitoso (HTTP 200)\n";

    // 2. Consultar sesion activa para obtener socio_id sin escribirlo a mano
    $sesion = peticion('GET', '/auth/sesion');
    if ($sesion['codigo'] !== 200 || !isset($sesion['cuerpo']['datos']['socio_id'])) {
        echo "  [FALLO] No se pudo obtener sesion activa con socio_id\n";
        $todoOk = false;
        continue;
    }

    $socioId = (int)$sesion['cuerpo']['datos']['socio_id'];
    $rol = $sesion['cuerpo']['datos']['rol'] ?? '';
    echo "  [OK] Sesion activa verificada: socio_id = $socioId, rol = $rol\n";

    if ($socioId <= 0 || $rol !== 'SOCIO') {
        echo "  [FALLO] socio_id invalido o rol incorrecto\n";
        $todoOk = false;
        continue;
    }

    // 3. Consumir Carnet
    $carnet = peticion('GET', "/socios/{$socioId}/carnet");
    if ($carnet['codigo'] === 200 && isset($carnet['cuerpo']['datos']['vigente'])) {
        $vigente = (bool)$carnet['cuerpo']['datos']['vigente'];
        if ($vigente === $s['esperaVigente']) {
            echo "  [OK] Carnet institucional (HTTP 200, vigente = " . ($vigente ? 'TRUE' : 'FALSE') . ")\n";
        } else {
            echo "  [FALLO] Vigencia de carnet inesperada: obtenida " . ($vigente ? 'TRUE' : 'FALSE') . "\n";
            $todoOk = false;
        }
    } else {
        echo "  [FALLO] Error al obtener carnet (HTTP {$carnet['codigo']})\n";
        $todoOk = false;
    }

    // 4. Consumir Estado de Cuenta
    $estadoCuenta = peticion('GET', "/socios/{$socioId}/estado-cuenta");
    if ($estadoCuenta['codigo'] === 200 && isset($estadoCuenta['cuerpo']['datos']['resumen'])) {
        $deuda = (float)($estadoCuenta['cuerpo']['datos']['resumen']['total_adeudado'] ?? 0);
        $deudaEsperadaOk = $s['esperaDeudaMayorCero'] ? ($deuda > 0) : ($deuda == 0);
        if ($deudaEsperadaOk) {
            echo "  [OK] Estado de cuenta contable (HTTP 200, total_adeudado = \${$deuda})\n";
        } else {
            echo "  [FALLO] Deuda no coincide con perfil: total_adeudado = \${$deuda}\n";
            $todoOk = false;
        }
    } else {
        echo "  [FALLO] Error al obtener estado de cuenta (HTTP {$estadoCuenta['codigo']})\n";
        $todoOk = false;
    }

    // 5. Consumir Pagos
    $pagos = peticion('GET', "/socios/{$socioId}/pagos");
    if ($pagos['codigo'] === 200 && isset($pagos['cuerpo']['datos'])) {
        $cantPagos = count($pagos['cuerpo']['datos']);
        echo "  [OK] Historial de pagos (HTTP 200, registros = {$cantPagos})\n";
    } else {
        echo "  [FALLO] Error al obtener pagos (HTTP {$pagos['codigo']})\n";
        $todoOk = false;
    }

    // 6. Consumir Entrenamientos
    $entrenamientos = peticion('GET', "/socios/{$socioId}/entrenamientos");
    if ($entrenamientos['codigo'] === 200 && isset($entrenamientos['cuerpo']['datos'])) {
        $cantEntrenamientos = count($entrenamientos['cuerpo']['datos']);
        echo "  [OK] Sesiones de entrenamiento (HTTP 200, asignados = {$cantEntrenamientos})\n";
    } else {
        echo "  [FALLO] Error al obtener entrenamientos (HTTP {$entrenamientos['codigo']})\n";
        $todoOk = false;
    }

    echo "\n";
}

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "===============================================================================\n";
if ($todoOk) {
    echo "RESULTADO GENERAL DE REGRESION: 100% EXITOSO - SIN ERRORES\n";
    exit(0);
} else {
    echo "RESULTADO GENERAL DE REGRESION: FALLARON PRUEBAS\n";
    exit(1);
}
echo "===============================================================================\n";
