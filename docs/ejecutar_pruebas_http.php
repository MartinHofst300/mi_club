<?php
/**
 * Script de Pruebas HTTP Reales contra http://localhost/dashboard/miclub/public/api
 * Ejecuta los 9 puntos exigidos por el usuario en la seccion 6 de la Fase 2.
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autocargador.php';

$baseUrl = 'http://localhost/dashboard/miclub/public/api';
$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'miclub_test_cookies.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function httpPeticion(string $metodo, string $ruta, array $datos = [], ?string $csrfToken = null): array
{
    global $baseUrl, $cookieFile;
    $url = $baseUrl . $ruta;
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $headers = ['Accept: application/json'];

    if (!empty($datos) || in_array($metodo, ['POST', 'PUT', 'DELETE'], true)) {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    if ($csrfToken !== null) {
        $headers[] = 'X-CSRF-Token: ' . $csrfToken;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $respuestaRaw = curl_exec($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($errorCurl) {
        throw new RuntimeException("Error cURL en $metodo $ruta: $errorCurl");
    }

    $cuerpoJson = json_decode($respuestaRaw, true) ?: [];

    return [
        'codigo' => $codigoHttp,
        'cuerpo' => $cuerpoJson,
        'raw' => $respuestaRaw
    ];
}

function iniciarSesionHttp(string $email, string $clave): string
{
    global $cookieFile;
    if (file_exists($cookieFile)) {
        unlink($cookieFile);
    }

    $res = httpPeticion('POST', '/auth/iniciar-sesion', [
        'identificador' => $email,
        'password' => $clave
    ]);

    if ($res['codigo'] !== 200 || !isset($res['cuerpo']['datos']['token_csrf'])) {
        throw new RuntimeException("Fallo login para $email: " . ($res['cuerpo']['error']['mensaje'] ?? $res['raw']));
    }

    return $res['cuerpo']['datos']['token_csrf'];
}

echo "===============================================================================\n";
echo "EJECUCION DE PRUEBAS HTTP REALES CONTRA LA API (FASE 2)\n";
echo "URL Base: $baseUrl\n";
echo "===============================================================================\n\n";

$resultados = [];

// -----------------------------------------------------------------------------
// a) Pago del socio desde el portal: genera recibo y marca pago autogestionado.
// -----------------------------------------------------------------------------
echo "--- Prueba a) Pago del socio desde el portal ---\n";
// Iniciamos sesion como Socio Marcos Moroso (socio 1) que tiene deuda
$tokenSocio = iniciarSesionHttp('marcos.moroso@email.local', 'socio123');
$resPago = httpPeticion('POST', '/pagos', [
    'monto_total' => 2000.00,
    'medio_pago' => 'TARJETA_SIMULADA',
    'observaciones' => 'Pago desde portal de socios con tarjeta simulada'
], $tokenSocio);

$exitoA = ($resPago['codigo'] === 201) &&
          !empty($resPago['cuerpo']['datos']['numero_recibo']) &&
          !empty($resPago['cuerpo']['datos']['pago_id']);

echo "Codigo HTTP: " . $resPago['codigo'] . "\n";
echo "Numero de Recibo: " . ($resPago['cuerpo']['datos']['numero_recibo'] ?? 'N/A') . "\n";
echo "ID de Pago: " . ($resPago['cuerpo']['datos']['pago_id'] ?? 'N/A') . "\n";

// Verificar en base de datos que autogestionado sea 1
require_once dirname(__DIR__) . '/src/Core/Autocargador.php';
$pagoDb = (new \Repositorios\PagoRepositorio())->buscarPorId((int)$resPago['cuerpo']['datos']['pago_id']);
$autogestionadoOk = $pagoDb && ((int)$pagoDb['autogestionado'] === 1);
echo "Campo autogestionado en BD: " . ($autogestionadoOk ? "1 (SI)" : "0 (NO)") . "\n";

$resultados['a'] = $exitoA && $autogestionadoOk;
echo "Resultado Prueba a): " . ($resultados['a'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// b) Inscribir a un moroso: error. Inscribir en division con cupo lleno: 409. Inscribir fuera de edad: error de validacion.
// -----------------------------------------------------------------------------
echo "--- Prueba b) Validaciones de inscripcion a division (Regla R8) ---\n";
$tokenAdmin = iniciarSesionHttp('admin@miclub.local', 'admin123');

// 1. Inscribir a moroso (Marcos Moroso ID 1) en division 3
$resMoroso = httpPeticion('POST', '/divisiones/3/inscripciones', [
    'socio_id' => 1
], $tokenAdmin);
$morosoRechazado = ($resMoroso['codigo'] === 409 || $resMoroso['codigo'] === 422) &&
                   stripos($resMoroso['cuerpo']['error']['mensaje'] ?? '', 'MOROSO') !== false;
echo "1. Inscribir socio MOROSO -> HTTP {$resMoroso['codigo']}: " . ($resMoroso['cuerpo']['error']['mensaje'] ?? '') . "\n";

// 2. Inscribir en division con cupo lleno (Division 2: cupo_maximo 2, ya tiene 2 inscriptos)
// Camila Diaz (socio 7, 27 anos, categoria Mayores, ACTIVO)
$resCupoLleno = httpPeticion('POST', '/divisiones/2/inscripciones', [
    'socio_id' => 7
], $tokenAdmin);
$cupoLlenoOk = ($resCupoLleno['codigo'] === 409) &&
               stripos($resCupoLleno['cuerpo']['error']['mensaje'] ?? '', 'agotado') !== false;
echo "2. Inscribir en CUPO LLENO -> HTTP {$resCupoLleno['codigo']}: " . ($resCupoLleno['cuerpo']['error']['mensaje'] ?? '') . "\n";

// 3. Inscribir fuera de rango de edad
// Camila Diaz (27 anos) intenta inscribirse a Division 1 (Basquet Juvenil: 13 a 17 anos)
$resEdad = httpPeticion('POST', '/divisiones/1/inscripciones', [
    'socio_id' => 7
], $tokenAdmin);
$edadRechazada = ($resEdad['codigo'] === 409 || $resEdad['codigo'] === 400 || $resEdad['codigo'] === 422) &&
                 stripos($resEdad['cuerpo']['error']['mensaje'] ?? '', 'edad') !== false;
echo "3. Inscribir FUERA DE EDAD -> HTTP {$resEdad['codigo']}: " . ($resEdad['cuerpo']['error']['mensaje'] ?? '') . "\n";

$resultados['b'] = $morosoRechazado && $cupoLlenoOk && $edadRechazada;
echo "Resultado Prueba b): " . ($resultados['b'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// c) Carnet del moroso: no vigente con el motivo.
// -----------------------------------------------------------------------------
echo "--- Prueba c) Carnet del socio moroso (Regla R11) ---\n";
$resCarnet = httpPeticion('GET', '/socios/1/carnet', [], $tokenAdmin);
$carnetDatos = $resCarnet['cuerpo']['datos'] ?? [];
$exitoC = ($resCarnet['codigo'] === 200) &&
          ($carnetDatos['vigente'] === false) &&
          !empty($carnetDatos['motivo_no_vigente']);

echo "Codigo HTTP: " . $resCarnet['codigo'] . "\n";
echo "Vigente: " . ($carnetDatos['vigente'] ? 'TRUE' : 'FALSE') . "\n";
echo "Motivo No Vigente: " . ($carnetDatos['motivo_no_vigente'] ?? 'N/A') . "\n";

$resultados['c'] = $exitoC;
echo "Resultado Prueba c): " . ($resultados['c'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// d) Anular una cuota impaga y verificar que el estado del socio se recalcula. Intentar anular una PAGADA: error.
// -----------------------------------------------------------------------------
echo "--- Prueba d) Anular cuota con motivo (Regla R2) ---\n";
$tokenTesorero = iniciarSesionHttp('tesoreria@miclub.local', 'tesorero123');

// 1. Intentar anular una PAGADA (Cuota 4 esta PAGADA)
$resAnularPagada = httpPeticion('POST', '/cuotas/4/anular', [
    'motivo' => 'Intento invalido de anulacion'
], $tokenTesorero);
$bloqueoPagada = ($resAnularPagada['codigo'] === 409 || $resAnularPagada['codigo'] === 422) &&
                 stripos($resAnularPagada['cuerpo']['error']['mensaje'] ?? '', 'pagada') !== false;
echo "1. Anular cuota PAGADA -> HTTP {$resAnularPagada['codigo']}: " . ($resAnularPagada['cuerpo']['error']['mensaje'] ?? '') . "\n";

// 2. Anular una cuota impaga de Marcos Moroso (Cuota 3: 2026-10)
// Actualmente tiene 3 cuotas impagas vencidas (cuotas 1, 2 y 3). Al anular la 3, quedan 2 impagas.
// Con 2 impagas (< 3), su estado se debe recalcular a ACTIVO!
$resAnularImpaga = httpPeticion('POST', '/cuotas/3/anular', [
    'motivo' => 'Condonacion administrativa por resolucion directiva'
], $tokenTesorero);
echo "2. Anular cuota IMPAGA -> HTTP {$resAnularImpaga['codigo']}: " . ($resAnularImpaga['cuerpo']['datos']['mensaje'] ?? '') . "\n";

// Consultar estado de cuenta para comprobar recálculo
$resCuentaRecalculada = httpPeticion('GET', '/socios/1/estado-cuenta', [], $tokenTesorero);
$nuevoEstadoSocio = $resCuentaRecalculada['cuerpo']['datos']['socio']['estado'] ?? '';
echo "Estado recalculado del socio: " . $nuevoEstadoSocio . " (esperado: ACTIVO tras quedar con 2 cuotas impagas)\n";

$exitoD = $bloqueoPagada && ($resAnularImpaga['codigo'] === 200) && ($nuevoEstadoSocio === 'ACTIVO');
$resultados['d'] = $exitoD;
echo "Resultado Prueba d): " . ($resultados['d'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// e) Aplicar recargos dos veces: la segunda no duplica.
// -----------------------------------------------------------------------------
echo "--- Prueba e) Idempotencia en aplicacion de recargos (Regla R4) ---\n";
// Primera ejecucion
$resRecargo1 = httpPeticion('POST', '/cuotas/aplicar-recargos', [], $tokenTesorero);
$actualizadas1 = $resRecargo1['cuerpo']['datos']['cuotas_actualizadas'] ?? 0;
echo "1° Ejecucion -> HTTP {$resRecargo1['codigo']}, cuotas con recargo aplicado: $actualizadas1\n";

// Segunda ejecucion inmediata
$resRecargo2 = httpPeticion('POST', '/cuotas/aplicar-recargos', [], $tokenTesorero);
$actualizadas2 = $resRecargo2['cuerpo']['datos']['cuotas_actualizadas'] ?? -1;
echo "2° Ejecucion -> HTTP {$resRecargo2['codigo']}, cuotas con recargo aplicado: $actualizadas2\n";

$exitoE = ($resRecargo2['codigo'] === 200) && ($actualizadas2 === 0);
$resultados['e'] = $exitoE;
echo "Resultado Prueba e): " . ($resultados['e'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// f) Un entrenador intenta crear un entrenamiento o cargar un apercibimiento en la division de otro: 403.
// -----------------------------------------------------------------------------
echo "--- Prueba f) Autorizacion de entrenador por division (403) ---\n";
// Entrenador 3: Roberto Gomez (a cargo de division 1 y division 3)
// Division 2 (Voley Mayores) esta a cargo de la Entrenadora 4: Laura Fernandez
$tokenEntrenador = iniciarSesionHttp('roberto.gomez@miclub.local', 'entrenador123');

// 1. Roberto intenta crear un entrenamiento en Division 2
$resCrearAjeno = httpPeticion('POST', '/entrenamientos', [
    'division_id' => 2,
    'fecha' => '2026-10-25',
    'hora' => '18:30',
    'lugar' => 'Cancha 1 Cubierta',
    'observaciones' => 'Intento no autorizado'
], $tokenEntrenador);
echo "1. Crear entrenamiento en division ajena -> HTTP {$resCrearAjeno['codigo']}: " . ($resCrearAjeno['cuerpo']['error']['mensaje'] ?? '') . "\n";

// 2. Roberto intenta cargar un apercibimiento sobre Sofia Martinez (Socio 4), que pertenece a Division 2 (Voley Mayores, no a sus planteles)
$resApercibimientoAjeno = httpPeticion('POST', '/apercibimientos', [
    'socio_id' => 4,
    'fecha' => '2026-10-18',
    'gravedad' => 'LEVE',
    'motivo' => 'Falta no observada en su plantel'
], $tokenEntrenador);
echo "2. Cargar apercibimiento sobre socio de otro plantel -> HTTP {$resApercibimientoAjeno['codigo']}: " . ($resApercibimientoAjeno['cuerpo']['error']['mensaje'] ?? '') . "\n";

$exitoF = ($resCrearAjeno['codigo'] === 403) && ($resApercibimientoAjeno['codigo'] === 403);
$resultados['f'] = $exitoF;
echo "Resultado Prueba f): " . ($resultados['f'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// g) Un socio intenta ver el estado de cuenta de otro socio: 403.
// -----------------------------------------------------------------------------
echo "--- Prueba g) Validacion de propiedad de datos entre socios (403) ---\n";
// Socio Sofia Martinez (usuario_id 8, socio_id 4)
$tokenSofia = iniciarSesionHttp('sofia.martinez@email.local', 'socio123');

// Sofia intenta consultar el estado de cuenta de Marcos Moroso (Socio 1)
$resCuentaAjena = httpPeticion('GET', '/socios/1/estado-cuenta', [], $tokenSofia);
echo "Consultar estado de cuenta de otro socio -> HTTP {$resCuentaAjena['codigo']}: " . ($resCuentaAjena['cuerpo']['error']['mensaje'] ?? '') . "\n";

$exitoG = ($resCuentaAjena['codigo'] === 403);
$resultados['g'] = $exitoG;
echo "Resultado Prueba g): " . ($resultados['g'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// h) Cambiar la paleta desde la configuracion y comprobar que GET /api/configuracion devuelve los nuevos valores. Un color invalido devuelve 400.
// -----------------------------------------------------------------------------
echo "--- Prueba h) Configuracion de apariencia y validacion de colores ---\n";
$tokenAdmin = iniciarSesionHttp('admin@miclub.local', 'admin123');

// 1. Color invalido (texto no hexadecimal) -> debe retornar 400
$resColorInvalido = httpPeticion('PUT', '/configuracion', [
    'nombre_club' => 'MiClub',
    'color_primario' => 'COLOR_INVALIDO_AZUL',
    'color_primario_oscuro' => '#14402A',
    'color_secundario' => '#E4EFE7',
    'color_acento' => '#C98A1B',
    'color_fondo' => '#F6F4EC',
    'color_texto' => '#1E2A24'
], $tokenAdmin);
echo "1. Color invalido -> HTTP {$resColorInvalido['codigo']}: " . ($resColorInvalido['cuerpo']['error']['mensaje'] ?? '') . "\n";

// 2. Colores validos -> cambio de paleta
$resPaletaNueva = httpPeticion('PUT', '/configuracion', [
    'nombre_club' => 'Club Atletico MiClub',
    'color_primario' => '#0055AA',
    'color_primario_oscuro' => '#003366',
    'color_secundario' => '#DDEEFF',
    'color_acento' => '#FF8800',
    'color_fondo' => '#F0F4F8',
    'color_texto' => '#112233'
], $tokenAdmin);
echo "2. Guardar nueva paleta -> HTTP {$resPaletaNueva['codigo']}\n";

// Comprobar con GET /api/configuracion
$resGetConfig = httpPeticion('GET', '/configuracion');
$configObtenida = $resGetConfig['cuerpo']['datos'] ?? [];
$paletaGuardadaOk = ($configObtenida['color_primario'] === '#0055AA') &&
                     ($configObtenida['nombre_club'] === 'Club Atletico MiClub');
echo "3. GET /configuracion -> Primario: {$configObtenida['color_primario']}, Club: {$configObtenida['nombre_club']}\n";

$exitoH = ($resColorInvalido['codigo'] === 400) && ($resPaletaNueva['codigo'] === 200) && $paletaGuardadaOk;
$resultados['h'] = $exitoH;
echo "Resultado Prueba h): " . ($resultados['h'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// i) Pago parcial menor al importe de una cuota: monto_pagado acumulado y estado conservado.
// -----------------------------------------------------------------------------
echo "--- Prueba i) Pago parcial menor al importe de una cuota ---\n";
// Cuota 1 de Marcos Moroso: periodo 2026-08
$tokenTesorero = iniciarSesionHttp('tesoreria@miclub.local', 'tesorero123');

// Consultar estado previo
$db = \Core\BaseDatos::obtenerConexion();
$stmtAntes = $db->query("SELECT id, monto_final, monto_pagado, (monto_final - monto_pagado) AS saldo, estado FROM cuotas WHERE id = 1");
$cuotaAntes = $stmtAntes->fetch();
echo "Antes del pago -> Cuota ID 1: monto_final \${$cuotaAntes['monto_final']}, monto_pagado \${$cuotaAntes['monto_pagado']}, saldo \${$cuotaAntes['saldo']}, estado {$cuotaAntes['estado']}\n";

// Realizar pago parcial de $1500 (menor al saldo restante)
$resPagoParcial = httpPeticion('POST', '/pagos', [
    'socio_id' => 1,
    'monto_total' => 1500.00,
    'medio_pago' => 'EFECTIVO',
    'observaciones' => 'Abono parcial de prueba i)'
], $tokenTesorero);

$stmtDespues = $db->query("SELECT id, monto_final, monto_pagado, (monto_final - monto_pagado) AS saldo, estado FROM cuotas WHERE id = 1");
$cuotaDespues = $stmtDespues->fetch();
echo "Despues del pago -> Cuota ID 1: monto_final \${$cuotaDespues['monto_final']}, monto_pagado \${$cuotaDespues['monto_pagado']}, saldo \${$cuotaDespues['saldo']}, estado {$cuotaDespues['estado']}\n";

$pagadoEsperado = (float)$cuotaAntes['monto_pagado'] + 1500.00;
$montoAcumuladoOk = abs((float)$cuotaDespues['monto_pagado'] - $pagadoEsperado) < 0.01;
$estadoConservadoOk = ($cuotaDespues['estado'] === 'VENCIDA') && ((float)$cuotaDespues['saldo'] > 0);

echo "Acumulacion correcta del monto: " . ($montoAcumuladoOk ? "SI" : "NO") . "\n";
echo "Estado conservado con saldo: " . ($estadoConservadoOk ? "SI" : "NO") . "\n";

$exitoI = ($resPagoParcial['codigo'] === 201) && $montoAcumuladoOk && $estadoConservadoOk;
$resultados['i'] = $exitoI;
echo "Resultado Prueba i): " . ($resultados['i'] ? "[PASS]" : "[FAIL]") . "\n\n";

// -----------------------------------------------------------------------------
// Resumen de resultados
// -----------------------------------------------------------------------------
echo "===============================================================================\n";
echo "RESUMEN DE PRUEBAS HTTP:\n";
foreach ($resultados as $letra => $pasa) {
    echo "  Prueba $letra): " . ($pasa ? "PASO EXITOSAMENTE" : "FALLO") . "\n";
}
$todasPasan = !in_array(false, $resultados, true);
echo "ESTADO GLOBAL: " . ($todasPasan ? "100% EXITOSO" : "HAY FALLAS") . "\n";
echo "===============================================================================\n";
