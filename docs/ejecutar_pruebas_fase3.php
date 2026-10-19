<?php
/**
 * Script de Pruebas HTTP Reales - Fase 3 (Administrador y Cierre)
 * MiClub - Sistema de Gestion de Club de Barrio
 *
 * Ejecuta via cURL peticiones HTTP reales contra http://localhost/dashboard/miclub/public/api/
 * cubriendo los puntos a) a g) especificados en el requerimiento.
 */

class SuitePruebasFase3
{
    private string $baseUrl = 'http://localhost/dashboard/miclub/public/api';
    private string $cookieAdmin;
    private string $cookieTesorero;
    private string $cookieEntrenador;
    private string $cookieSocio;
    private array $csrfTokens = [];
    private array $resultados = [];

    public function __construct()
    {
        $tempDir = sys_get_temp_dir();
        $this->cookieAdmin = $tempDir . DIRECTORY_SEPARATOR . 'cookie_admin_' . uniqid() . '.txt';
        $this->cookieTesorero = $tempDir . DIRECTORY_SEPARATOR . 'cookie_tesorero_' . uniqid() . '.txt';
        $this->cookieEntrenador = $tempDir . DIRECTORY_SEPARATOR . 'cookie_entrenador_' . uniqid() . '.txt';
        $this->cookieSocio = $tempDir . DIRECTORY_SEPARATOR . 'cookie_socio_' . uniqid() . '.txt';
    }

    public function __destruct()
    {
        @unlink($this->cookieAdmin);
        @unlink($this->cookieTesorero);
        @unlink($this->cookieEntrenador);
        @unlink($this->cookieSocio);
    }

    private function http(string $metodo, string $ruta, ?array $datos = null, ?string $cookieFile = null, ?string $csrfToken = null): array
    {
        $ch = curl_init();
        $url = $this->baseUrl . $ruta;

        $headers = ['Accept: application/json'];
        if ($csrfToken) {
            $headers[] = 'X-CSRF-Token: ' . $csrfToken;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($cookieFile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        }

        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($datos !== null) {
                $payload = json_encode($datos);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                $headers[] = 'Content-Type: application/json';
            }
        } elseif ($metodo === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($datos !== null) {
                $payload = json_encode($datos);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                $headers[] = 'Content-Type: application/json';
            }
        } elseif ($metodo === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $respuestaRaw = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        $json = json_decode($respuestaRaw, true);

        return [
            'codigo' => $codigoHttp,
            'json' => $json,
            'raw' => $respuestaRaw,
            'curl_error' => $errorCurl
        ];
    }

    private function login(string $usuario, string $clave, string $cookieFile): array
    {
        // 1. Obtener token CSRF inicial desde sesion
        $resSesion = $this->http('GET', '/auth/sesion', null, $cookieFile);
        $csrfInicial = $resSesion['json']['datos']['token_csrf'] ?? null;

        // 2. Iniciar sesion
        $resLogin = $this->http('POST', '/auth/iniciar-sesion', [
            'identificador' => $usuario,
            'password' => $clave
        ], $cookieFile, $csrfInicial);

        $csrfNuevo = $resLogin['json']['datos']['token_csrf'] ?? $csrfInicial;

        return [
            'res' => $resLogin,
            'csrf' => $csrfNuevo
        ];
    }

    private function registrarPaso(string $seccion, string $nombre, bool $exito, string $detalle): void
    {
        $this->resultados[] = [
            'seccion' => $seccion,
            'nombre' => $nombre,
            'exito' => $exito,
            'detalle' => $detalle
        ];
        echo sprintf("[%s] %s: %s (%s)\n", $exito ? 'OK' : 'FALLO', $seccion, $nombre, $detalle);
    }

    public function ejecutar(): void
    {
        echo "====================================================================\n";
        echo "INICIANDO SUITE DE PRUEBAS HTTP REALES - FASE 3 (ADMINISTRADOR)\n";
        echo "====================================================================\n\n";

        // Autenticar los 4 perfiles
        $loginAdmin = $this->login('admin@miclub.local', 'admin123', $this->cookieAdmin);
        $this->csrfTokens['ADMIN'] = $loginAdmin['csrf'];
        $this->registrarPaso('AUTH', 'Login Administrador', $loginAdmin['res']['codigo'] === 200, 'HTTP ' . $loginAdmin['res']['codigo']);

        $loginTesorero = $this->login('tesoreria@miclub.local', 'tesorero123', $this->cookieTesorero);
        $this->csrfTokens['TESORERO'] = $loginTesorero['csrf'];
        $this->registrarPaso('AUTH', 'Login Tesorero', $loginTesorero['res']['codigo'] === 200, 'HTTP ' . $loginTesorero['res']['codigo']);

        $loginEntrenador = $this->login('roberto.gomez@miclub.local', 'entrenador123', $this->cookieEntrenador);
        $this->csrfTokens['ENTRENADOR'] = $loginEntrenador['csrf'];
        $this->registrarPaso('AUTH', 'Login Entrenador', $loginEntrenador['res']['codigo'] === 200, 'HTTP ' . $loginEntrenador['res']['codigo']);

        $loginSocio = $this->login('marcos.moroso@email.local', 'socio123', $this->cookieSocio);
        $this->csrfTokens['SOCIO'] = $loginSocio['csrf'];
        $this->registrarPaso('AUTH', 'Login Socio', $loginSocio['res']['codigo'] === 200, 'HTTP ' . $loginSocio['res']['codigo']);

        echo "\n--------------------------------------------------------------------\n";
        echo "a) CRUD DE CADA RECURSO NUEVO CON ADMINISTRADOR\n";
        echo "--------------------------------------------------------------------\n";

        // a.1) CRUD Usuarios
        $randomDni = (string)rand(50000000, 59999999);
        $resUserCrear = $this->http('POST', '/usuarios', [
            'email' => 'nuevo.admin.test@miclub.local',
            'nombre' => 'Admin',
            'apellido' => 'Test',
            'dni' => $randomDni,
            'password' => 'temporal123',
            'rol_id' => 1
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $nuevoUserId = $resUserCrear['json']['datos']['id'] ?? null;
        $this->registrarPaso('PRUEBA_A', 'Crear Usuario (POST /api/usuarios)', $resUserCrear['codigo'] === 201 && $nuevoUserId > 0, "ID Creado: {$nuevoUserId}");

        $resUserGet = $this->http('GET', '/usuarios', null, $this->cookieAdmin);
        $totalUsuarios = count($resUserGet['json']['datos'] ?? []);
        $this->registrarPaso('PRUEBA_A', 'Listar Usuarios (GET /api/usuarios)', $resUserGet['codigo'] === 200 && $totalUsuarios > 0, "Total: {$totalUsuarios}");

        $resUserPut = $this->http('PUT', "/usuarios/{$nuevoUserId}", [
            'email' => 'nuevo.admin.modificado@miclub.local',
            'nombre' => 'Admin Modificado',
            'apellido' => 'Test',
            'dni' => $randomDni,
            'rol_id' => 2 // cambio a tesorero
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Modificar Usuario (PUT /api/usuarios/{id})', $resUserPut['codigo'] === 200, 'HTTP ' . $resUserPut['codigo']);

        $resUserDesactivar = $this->http('POST', "/usuarios/{$nuevoUserId}/desactivar", [], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Desactivar Usuario (POST /api/usuarios/{id}/desactivar)', $resUserDesactivar['codigo'] === 200, 'HTTP ' . $resUserDesactivar['codigo']);

        $resUserActivar = $this->http('POST', "/usuarios/{$nuevoUserId}/activar", [], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Activar Usuario (POST /api/usuarios/{id}/activar)', $resUserActivar['codigo'] === 200, 'HTTP ' . $resUserActivar['codigo']);

        $resUserReset = $this->http('POST', "/usuarios/{$nuevoUserId}/restablecer-password", ['password' => 'nuevaclave456'], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Restablecer Clave (POST /api/usuarios/{id}/restablecer-password)', $resUserReset['codigo'] === 200, 'HTTP ' . $resUserReset['codigo']);

        // a.2) CRUD Socios y Grupos Familiares
        $randomDniSocio = (string)rand(60000000, 69999999);
        $resSocioCrear = $this->http('POST', '/socios', [
            'nombre' => 'Esteban',
            'apellido' => 'Prueba',
            'dni' => $randomDniSocio,
            'fecha_nacimiento' => '1995-04-12', // 31 anos -> Categoria 3 (Mayores)
            'categoria_id' => 3,
            'email' => 'esteban.prueba@test.local',
            'telefono' => '11-5555-4444',
            'domicilio' => 'Calle Falsa 123'
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $nuevoSocioId = $resSocioCrear['json']['datos']['id'] ?? null;
        $nuevoNumSocio = $resSocioCrear['json']['datos']['numero_socio'] ?? null;
        $this->registrarPaso('PRUEBA_A', 'Crear Socio (POST /api/socios)', $resSocioCrear['codigo'] === 201 && $nuevoSocioId > 0, "ID: {$nuevoSocioId}, Num: {$nuevoNumSocio}");

        $resSocioPut = $this->http('PUT', "/socios/{$nuevoSocioId}", [
            'nombre' => 'Esteban Actualizado',
            'apellido' => 'Prueba',
            'dni' => $randomDniSocio,
            'fecha_nacimiento' => '1995-04-12',
            'categoria_id' => 3,
            'estado' => 'ACTIVO',
            'email' => 'esteban.prueba@test.local',
            'telefono' => '11-9999-8888',
            'domicilio' => 'Avenida Siempre Viva 742'
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Actualizar Socio (PUT /api/socios/{id})', $resSocioPut['codigo'] === 200, 'HTTP ' . $resSocioPut['codigo']);

        // Grupos familiares
        $resGrupoCrear = $this->http('POST', '/grupos-familiares', ['nombre' => 'Familia Esteban Test'], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $nuevoGrupoId = $resGrupoCrear['json']['datos']['id'] ?? null;
        $this->registrarPaso('PRUEBA_A', 'Crear Grupo Familiar (POST /api/grupos-familiares)', $resGrupoCrear['codigo'] === 201 && $nuevoGrupoId > 0, "Grupo ID: {$nuevoGrupoId}");

        $resGrupoAsignar = $this->http('POST', "/grupos-familiares/{$nuevoGrupoId}/integrantes", ['socio_id' => $nuevoSocioId], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Asignar Socio a Grupo Familiar', $resGrupoAsignar['codigo'] === 200, 'HTTP ' . $resGrupoAsignar['codigo']);

        // a.3) CRUD Categorias
        // Usar rango etario no solapado (1 a 5 anos - Pre-Infantiles)
        $resCatCrear = $this->http('POST', '/categorias', [
            'nombre' => 'Pre-Infantiles Test',
            'edad_minima' => 1,
            'edad_maxima' => 5,
            'precio_cuota' => 6000.00
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $nuevaCatId = $resCatCrear['json']['datos']['id'] ?? null;
        $this->registrarPaso('PRUEBA_A', 'Crear Categoria (POST /api/categorias)', $resCatCrear['codigo'] === 201 && $nuevaCatId > 0, "Cat ID: {$nuevaCatId}");

        $resCatPut = $this->http('PUT', "/categorias/{$nuevaCatId}", [
            'nombre' => 'Pre-Infantiles Test Modificada',
            'edad_minima' => 2,
            'edad_maxima' => 5,
            'precio_cuota' => 6500.00
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Actualizar Categoria (PUT /api/categorias/{id})', $resCatPut['codigo'] === 200, 'HTTP ' . $resCatPut['codigo']);

        $resCatDel = $this->http('DELETE', "/categorias/{$nuevaCatId}", null, $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Eliminar Categoria sin datos (DELETE /api/categorias/{id})', $resCatDel['codigo'] === 200, 'HTTP ' . $resCatDel['codigo']);

        // a.4) Precios y Parametros Economicos
        $resPreciosGet = $this->http('GET', '/precios', null, $this->cookieAdmin);
        $this->registrarPaso('PRUEBA_A', 'Consultar Precios (GET /api/precios)', $resPreciosGet['codigo'] === 200, 'HTTP ' . $resPreciosGet['codigo']);

        $resPreciosPut = $this->http('PUT', '/precios', [
            'porcentaje_recargo' => 10.0,
            'porcentaje_descuento_familiar' => 20.0,
            'cuotas_para_moroso' => 3,
            'dias_vencimiento' => 10,
            'precios_categorias' => [
                ['id' => 1, 'precio_cuota' => 8500.00]
            ]
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Actualizar Precios y Parametros (PUT /api/precios)', $resPreciosPut['codigo'] === 200, 'HTTP ' . $resPreciosPut['codigo']);

        // a.5) CRUD Divisiones
        $resDivCrear = $this->http('POST', '/divisiones', [
            'nombre' => 'Division Prueba Temporal',
            'categoria_id' => 1,
            'cupo_maximo' => 25,
            'entrenador_id' => 3
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $nuevaDivId = $resDivCrear['json']['datos']['id'] ?? null;
        $this->registrarPaso('PRUEBA_A', 'Crear Division (POST /api/divisiones)', $resDivCrear['codigo'] === 201 && $nuevaDivId > 0, "Div ID: {$nuevaDivId}");

        $resDivPut = $this->http('PUT', "/divisiones/{$nuevaDivId}", [
            'nombre' => 'Division Prueba Renombrada',
            'categoria_id' => 1,
            'cupo_maximo' => 30,
            'entrenador_id' => 3
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Actualizar Division (PUT /api/divisiones/{id})', $resDivPut['codigo'] === 200, 'HTTP ' . $resDivPut['codigo']);

        $resDivDel = $this->http('DELETE', "/divisiones/{$nuevaDivId}", null, $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_A', 'Eliminar Division sin datos (DELETE /api/divisiones/{id})', $resDivDel['codigo'] === 200, 'HTTP ' . $resDivDel['codigo']);

        echo "\n--------------------------------------------------------------------\n";
        echo "b) ROL SIN PERMISO: TESORERO, ENTRENADOR Y SOCIO ACCEDIENDO A RUTAS ADMIN (403)\n";
        echo "--------------------------------------------------------------------\n";

        $rutasAdmin = [
            ['metodo' => 'POST', 'ruta' => '/usuarios', 'datos' => ['email' => 'hacker@test.local']],
            ['metodo' => 'POST', 'ruta' => '/socios', 'datos' => ['nombre' => 'Invalido']],
            ['metodo' => 'POST', 'ruta' => '/categorias', 'datos' => ['nombre' => 'Invalida']],
            ['metodo' => 'PUT', 'ruta' => '/precios', 'datos' => ['porcentaje_recargo' => 10]],
            ['metodo' => 'POST', 'ruta' => '/divisiones', 'datos' => ['nombre' => 'Invalida']]
        ];

        foreach ($rutasAdmin as $r) {
            $metodo = $r['metodo'];
            $ruta = $r['ruta'];
            $datos = $r['datos'];

            $rTes = $this->http($metodo, $ruta, $datos, $this->cookieTesorero, $this->csrfTokens['TESORERO']);
            $this->registrarPaso('PRUEBA_B', "Tesorero {$metodo} {$ruta}", $rTes['codigo'] === 403, 'HTTP ' . $rTes['codigo']);

            $rEnt = $this->http($metodo, $ruta, $datos, $this->cookieEntrenador, $this->csrfTokens['ENTRENADOR']);
            $this->registrarPaso('PRUEBA_B', "Entrenador {$metodo} {$ruta}", $rEnt['codigo'] === 403, 'HTTP ' . $rEnt['codigo']);

            $rSoc = $this->http($metodo, $ruta, $datos, $this->cookieSocio, $this->csrfTokens['SOCIO']);
            $this->registrarPaso('PRUEBA_B', "Socio {$metodo} {$ruta}", $rSoc['codigo'] === 403, 'HTTP ' . $rSoc['codigo']);
        }

        echo "\n--------------------------------------------------------------------\n";
        echo "c) SESION VENCIDA POR INACTIVIDAD (401 SESION_EXPIRADA)\n";
        echo "--------------------------------------------------------------------\n";

        $cookieExpirada = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_expirada_' . uniqid() . '.txt';
        $loginExp = $this->login('admin@miclub.local', 'admin123', $cookieExpirada);
        $cookieContent = file_get_contents($cookieExpirada);
        preg_match('/MICLUB_SESION\s+([A-Za-z0-9,-]+)/', $cookieContent, $matches);
        $sessionId = $matches[1] ?? null;

        if ($sessionId) {
            $dirs = array_unique([ini_get('session.save_path'), 'C:\\xampp\\tmp', sys_get_temp_dir()]);
            $modificado = false;
            foreach ($dirs as $dir) {
                if (!$dir) continue;
                $sessFile = $dir . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
                if (file_exists($sessFile)) {
                    $sessData = file_get_contents($sessFile);
                    $sessDataMod = preg_replace('/ultimo_acceso\|i:\d+;/', 'ultimo_acceso|i:' . (time() - 7200) . ';', $sessData);
                    file_put_contents($sessFile, $sessDataMod);
                    $modificado = true;
                    break;
                }
            }

            if ($modificado) {
                $resSesExp = $this->http('GET', '/usuarios', null, $cookieExpirada);
                $codigoSesion = $resSesExp['json']['error']['codigo'] ?? '';
                $this->registrarPaso('PRUEBA_C', 'Peticion con sesion inactiva (GET /api/usuarios)', $resSesExp['codigo'] === 401 && $codigoSesion === 'SESION_EXPIRADA', "HTTP {$resSesExp['codigo']} Codigo: {$codigoSesion}");
            } else {
                $this->registrarPaso('PRUEBA_C', 'Modificacion de sesion en disco', false, 'Archivo de sesion no localizado');
            }
        } else {
            $this->registrarPaso('PRUEBA_C', 'Extraccion de Session ID', false, 'No se pudo leer la sesion');
        }
        @unlink($cookieExpirada);

        echo "\n--------------------------------------------------------------------\n";
        echo "d) VALIDACIONES DE NEGOCIO (PAGO EXCEDE DEUDA, CUOTA DUPLICADA, CUPO LLENO, FUERA DE RANGO 422)\n";
        echo "--------------------------------------------------------------------\n";

        // d.1) Pago que excede la deuda (409)
        // Marcos Moroso es Socio ID = 1
        $resPagoExcesivo = $this->http('POST', '/pagos', [
            'socio_id' => 1,
            'monto_total' => 9999999.00,
            'medio_pago' => 'EFECTIVO'
        ], $this->cookieTesorero, $this->csrfTokens['TESORERO']);
        $this->registrarPaso('PRUEBA_D', 'Pago que excede deuda total (409)', $resPagoExcesivo['codigo'] === 409, "HTTP {$resPagoExcesivo['codigo']}");

        // d.2) Cuota duplicada en el mismo periodo
        // Primera ejecucion absorbe a cualquier socio nuevo creado en la prueba
        $this->http('POST', '/cuotas/generar', ['periodo' => '2026-10'], $this->cookieTesorero, $this->csrfTokens['TESORERO']);
        // Segunda ejecucion: todos los socios ya tienen cuota emitida -> 0 generadas, todas omitidas
        $resCuotasDup = $this->http('POST', '/cuotas/generar', [
            'periodo' => '2026-10'
        ], $this->cookieTesorero, $this->csrfTokens['TESORERO']);
        $omitidas = $resCuotasDup['json']['datos']['omitidas'] ?? 0;
        $generadas = $resCuotasDup['json']['datos']['generadas'] ?? -1;
        $this->registrarPaso('PRUEBA_D', 'Cuotas duplicadas en mismo periodo omitidas', ($resCuotasDup['codigo'] === 200 || $resCuotasDup['codigo'] === 201) && $generadas === 0 && $omitidas > 0, "Generadas: {$generadas}, Omitidas: {$omitidas}");

        // d.3) Cupo lleno (409)
        // Creamos division con cupo 1
        $resDivCupo = $this->http('POST', '/divisiones', [
            'nombre' => 'Division Cupo 1 Test',
            'categoria_id' => 3, // Mayores
            'cupo_maximo' => 1,
            'entrenador_id' => 4
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $divCupoId = $resDivCupo['json']['datos']['id'];

        // Inscribimos socio 7 (Camila Diaz, Mayores, Activa, sin division actual)
        $resInsc1 = $this->http('POST', "/divisiones/{$divCupoId}/inscripciones", ['socio_id' => 7], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        // Intentamos inscribir socio 2 (Martin Gonzalez, Mayores, Activo)
        $resInsc2 = $this->http('POST', "/divisiones/{$divCupoId}/inscripciones", ['socio_id' => 2], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_D', 'Inscripcion con cupo lleno (409)', $resInsc2['codigo'] === 409, "HTTP {$resInsc2['codigo']}");

        // Limpiar inscripcion y division
        $this->http('DELETE', "/divisiones/{$divCupoId}/inscripciones/7", null, $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->http('DELETE', "/divisiones/{$divCupoId}", null, $this->cookieAdmin, $this->csrfTokens['ADMIN']);

        // d.4) Inscripcion fuera de rango etario (422)
        // Division 1 es Basquet Juvenil (Categoria 2: 13 a 17 anos). Socio 4 (Sofia Martinez) tiene 26 anos (Mayores).
        $resInscEdad = $this->http('POST', '/divisiones/1/inscripciones', ['socio_id' => 4], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_D', 'Inscripcion fuera de rango etario (422)', $resInscEdad['codigo'] === 422, "HTTP {$resInscEdad['codigo']}");

        echo "\n--------------------------------------------------------------------\n";
        echo "e) DESACTIVAR UN USUARIO Y VERIFICAR QUE NO PUEDE LOGUEARSE\n";
        echo "--------------------------------------------------------------------\n";

        // Desactivamos a Sofia Martinez (usuario id 8)
        $resDesactSofia = $this->http('POST', '/usuarios/8/desactivar', [], $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $cookieSofiaTest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_sofia_test_' . uniqid() . '.txt';
        $loginSofia = $this->login('sofia.martinez@email.local', 'socio123', $cookieSofiaTest);
        $this->registrarPaso('PRUEBA_E', 'Login de usuario inactivo rechazado (401)', $loginSofia['res']['codigo'] === 401, "HTTP {$loginSofia['res']['codigo']}");
        @unlink($cookieSofiaTest);

        // Reactivamos a Sofia
        $this->http('POST', '/usuarios/8/activar', [], $this->cookieAdmin, $this->csrfTokens['ADMIN']);

        echo "\n--------------------------------------------------------------------\n";
        echo "f) PONER UN SOCIO EN SUSPENDIDO Y VERIFICAR QUE EL RECALCULO AUTOMATICO NO LO CAMBIA\n";
        echo "--------------------------------------------------------------------\n";

        // Ponemos a Sofia (socio 4) en SUSPENDIDO con motivo
        $resSusp = $this->http('PUT', '/socios/4', [
            'nombre' => 'Sofia',
            'apellido' => 'Martinez',
            'dni' => '42111222',
            'fecha_nacimiento' => '2000-11-30',
            'categoria_id' => 3,
            'estado' => 'SUSPENDIDO',
            'motivo_estado' => 'Suspension disciplinaria preventiva por comision directiva'
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);

        // Forzamos recalculo consultando estado de cuenta
        $this->http('GET', '/socios/4/estado-cuenta', null, $this->cookieAdmin);

        // Verificamos estado del socio en la base
        $resSocioDetalle = $this->http('GET', '/socios/4', null, $this->cookieAdmin);
        $estadoPersistido = $resSocioDetalle['json']['datos']['estado'] ?? '';
        $this->registrarPaso('PRUEBA_F', 'Socio SUSPENDIDO conservado tras recalculo automatico', $estadoPersistido === 'SUSPENDIDO', "Estado: {$estadoPersistido}");

        // Restaurar estado a ACTIVO
        $this->http('PUT', '/socios/4', [
            'nombre' => 'Sofia',
            'apellido' => 'Martinez',
            'dni' => '42111222',
            'fecha_nacimiento' => '2000-11-30',
            'categoria_id' => 3,
            'estado' => 'ACTIVO'
        ], $this->cookieAdmin, $this->csrfTokens['ADMIN']);

        echo "\n--------------------------------------------------------------------\n";
        echo "g) BORRAR CATEGORIA O DIVISION CON DATOS ASOCIADOS (RECHAZADO 409)\n";
        echo "--------------------------------------------------------------------\n";

        // Intentar borrar Categoria 1 (Infantiles) -> tiene socios y divisiones
        $resDelCatOcupada = $this->http('DELETE', '/categorias/1', null, $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_G', 'Eliminar Categoria con datos asociados rechazada (409)', $resDelCatOcupada['codigo'] === 409, "HTTP {$resDelCatOcupada['codigo']}");

        // Intentar borrar Division 1 (Basquet Juvenil) -> tiene inscriptos y entrenamientos
        $resDelDivOcupada = $this->http('DELETE', '/divisiones/1', null, $this->cookieAdmin, $this->csrfTokens['ADMIN']);
        $this->registrarPaso('PRUEBA_G', 'Eliminar Division con datos asociados rechazada (409)', $resDelDivOcupada['codigo'] === 409, "HTTP {$resDelDivOcupada['codigo']}");

        echo "\n====================================================================\n";
        $total = count($this->resultados);
        $exitos = count(array_filter($this->resultados, fn($r) => $r['exito']));
        echo "RESUMEN FINAL: {$exitos} de {$total} pruebas superadas exitosamente.\n";
        echo "====================================================================\n";
    }
}

// Ejecucion directa si se llama por CLI
if (php_sapi_name() === 'cli') {
    $suite = new SuitePruebasFase3();
    $suite->ejecutar();
}
