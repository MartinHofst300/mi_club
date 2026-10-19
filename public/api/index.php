<?php
/**
 * Controlador frontal de la API REST de MiClub.
 * Capa: Presentacion / Despachador API.
 *
 * Todas las peticiones HTTP destinadas a /api/* ingresan por este punto unico,
 * inicializan el entorno de ejecucion, registran el manejador global de errores y despachan la ruta.
 */

// Establecer zona horaria oficial del sistema
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Cargar autocargador modular PSR-4
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autocargador.php';

use Core\ManejadorErrores;
use Core\Router;
use Core\Peticion;

// Registrar manejador de excepciones y errores uniforme
ManejadorErrores::registrar();

// Instanciar el router y registrar las rutas declarando roles permitidos
$router = new Router();

// -----------------------------------------------------------------------------
// Rutas de Autenticacion y Sesion
// -----------------------------------------------------------------------------
$router->registrar('POST', '/api/auth/iniciar-sesion', 'Controladores\AuthControlador', 'iniciarSesion', [], false);
$router->registrar('POST', '/api/auth/cerrar-sesion', 'Controladores\AuthControlador', 'cerrarSesion', ['ADMINISTRADOR', 'TESORERO', 'ENTRENADOR', 'SOCIO'], false);
$router->registrar('GET',  '/api/auth/sesion',         'Controladores\AuthControlador', 'sesion', [], false);

// -----------------------------------------------------------------------------
// Rutas de Configuracion y Tema Institucional
// -----------------------------------------------------------------------------
$router->registrar('GET', '/api/configuracion', 'Controladores\ConfiguracionControlador', 'obtener', [], false);
$router->registrar('PUT', '/api/configuracion', 'Controladores\ConfiguracionControlador', 'actualizar', ['ADMINISTRADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Socios
// -----------------------------------------------------------------------------
$router->registrar('GET', '/api/socios',                 'Controladores\SocioControlador', 'buscar', ['TESORERO', 'ADMINISTRADOR'], false);
$router->registrar('GET', '/api/socios/morosos',         'Controladores\SocioControlador', 'morosos', ['TESORERO', 'ADMINISTRADOR'], false);
$router->registrar('GET', '/api/socios/{id}',            'Controladores\SocioControlador', 'obtener', ['TESORERO', 'ADMINISTRADOR', 'SOCIO'], false);
$router->registrar('GET', '/api/socios/{id}/estado-cuenta', 'Controladores\SocioControlador', 'estadoCuenta', ['TESORERO', 'ADMINISTRADOR', 'SOCIO'], false);
$router->registrar('GET', '/api/socios/{id}/carnet',     'Controladores\SocioControlador', 'carnet', ['TESORERO', 'ADMINISTRADOR', 'SOCIO'], false);
$router->registrar('GET', '/api/socios/{id}/pagos',      'Controladores\PagoControlador', 'listarPorSocio', ['TESORERO', 'ADMINISTRADOR', 'SOCIO'], false);
$router->registrar('PUT', '/api/socios/{id}',            'Controladores\SocioControlador', 'editarMisDatos', ['SOCIO', 'ADMINISTRADOR'], true);
$router->registrar('GET', '/api/socios/{id}/entrenamientos', 'Controladores\SocioControlador', 'entrenamientos', ['SOCIO', 'ADMINISTRADOR'], false);

// -----------------------------------------------------------------------------
// Rutas de Cuotas
// -----------------------------------------------------------------------------
$router->registrar('POST', '/api/cuotas/generar',          'Controladores\CuotaControlador', 'generar', ['TESORERO', 'ADMINISTRADOR'], true);
$router->registrar('POST', '/api/cuotas/aplicar-recargos', 'Controladores\CuotaControlador', 'aplicarRecargos', ['TESORERO', 'ADMINISTRADOR'], true);
$router->registrar('POST', '/api/cuotas/{id}/anular',      'Controladores\CuotaControlador', 'anular', ['TESORERO', 'ADMINISTRADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Pagos y Recibos
// -----------------------------------------------------------------------------
$router->registrar('POST', '/api/pagos',       'Controladores\PagoControlador', 'registrar', ['TESORERO', 'SOCIO', 'ADMINISTRADOR'], true);
$router->registrar('GET',  '/api/pagos/{id}',  'Controladores\PagoControlador', 'obtener', ['TESORERO', 'SOCIO', 'ADMINISTRADOR'], false);
$router->registrar('GET',  '/api/recibos/{id}', 'Controladores\ReciboControlador', 'obtener', ['TESORERO', 'SOCIO', 'ADMINISTRADOR'], false);

// -----------------------------------------------------------------------------
// Rutas de Usuarios y Roles (Fase 3: Gestionar usuarios y roles)
// -----------------------------------------------------------------------------
$router->registrar('GET',  '/api/usuarios',                           'Controladores\UsuarioControlador', 'listar',              ['ADMINISTRADOR'], false);
$router->registrar('GET',  '/api/roles',                              'Controladores\UsuarioControlador', 'listarRoles',         ['ADMINISTRADOR'], false);
$router->registrar('GET',  '/api/usuarios/{id}',                      'Controladores\UsuarioControlador', 'obtener',             ['ADMINISTRADOR'], false);
$router->registrar('POST', '/api/usuarios',                           'Controladores\UsuarioControlador', 'crear',               ['ADMINISTRADOR'], true);
$router->registrar('PUT',  '/api/usuarios/{id}',                      'Controladores\UsuarioControlador', 'actualizar',          ['ADMINISTRADOR'], true);
$router->registrar('POST', '/api/usuarios/{id}/activar',              'Controladores\UsuarioControlador', 'activar',             ['ADMINISTRADOR'], true);
$router->registrar('POST', '/api/usuarios/{id}/desactivar',            'Controladores\UsuarioControlador', 'desactivar',           ['ADMINISTRADOR'], true);
$router->registrar('POST', '/api/usuarios/{id}/restablecer-password', 'Controladores\UsuarioControlador', 'restablecerPassword', ['ADMINISTRADOR'], true);
$router->registrar('POST', '/api/usuarios/{id}/restablecer-clave',    'Controladores\UsuarioControlador', 'restablecerPassword', ['ADMINISTRADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Socios y Grupos Familiares (Fase 3: Gestionar socios)
// -----------------------------------------------------------------------------
$router->registrar('POST', '/api/socios',                             'Controladores\SocioControlador', 'crear',                     ['ADMINISTRADOR'], true);
$router->registrar('GET',  '/api/grupos-familiares',                  'Controladores\SocioControlador', 'listarGruposFamiliares',     ['ADMINISTRADOR'], false);
$router->registrar('POST', '/api/grupos-familiares',                  'Controladores\SocioControlador', 'crearGrupoFamiliar',         ['ADMINISTRADOR'], true);
$router->registrar('GET',  '/api/grupos-familiares/{id}/integrantes', 'Controladores\SocioControlador', 'integrantesGrupoFamiliar', ['ADMINISTRADOR'], false);
$router->registrar('POST', '/api/grupos-familiares/{id}/integrantes', 'Controladores\SocioControlador', 'asignarIntegranteAGrupo',    ['ADMINISTRADOR'], true);
$router->registrar('POST', '/api/socios/{id}/grupo-familiar',         'Controladores\SocioControlador', 'asignarGrupoFamiliar',       ['ADMINISTRADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Categorias (Fase 3: Gestionar categorias)
// -----------------------------------------------------------------------------
$router->registrar('GET',    '/api/categorias',      'Controladores\CategoriaControlador', 'listar',     ['ADMINISTRADOR', 'TESORERO', 'ENTRENADOR', 'SOCIO'], false);
$router->registrar('GET',    '/api/categorias/{id}', 'Controladores\CategoriaControlador', 'obtener',    ['ADMINISTRADOR'], false);
$router->registrar('POST',   '/api/categorias',      'Controladores\CategoriaControlador', 'crear',      ['ADMINISTRADOR'], true);
$router->registrar('PUT',    '/api/categorias/{id}', 'Controladores\CategoriaControlador', 'actualizar', ['ADMINISTRADOR'], true);
$router->registrar('DELETE', '/api/categorias/{id}', 'Controladores\CategoriaControlador', 'eliminar',   ['ADMINISTRADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Precios y Estructura Tarifaria (Fase 3: Gestionar precios)
// -----------------------------------------------------------------------------
$router->registrar('GET', '/api/precios', 'Controladores\PrecioControlador', 'obtener',    ['ADMINISTRADOR'], false);
$router->registrar('PUT', '/api/precios', 'Controladores\PrecioControlador', 'actualizar', ['ADMINISTRADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Divisiones e Inscripciones Deportivas (Regla R8 y Gestionar divisiones)
// -----------------------------------------------------------------------------
$router->registrar('GET',    '/api/divisiones',                              'Controladores\DivisionControlador', 'listar',     ['SOCIO', 'ENTRENADOR', 'ADMINISTRADOR', 'TESORERO'], false);
$router->registrar('GET',    '/api/divisiones/{id}/plantel',                 'Controladores\DivisionControlador', 'plantel',    ['ENTRENADOR', 'ADMINISTRADOR'], false);
$router->registrar('POST',   '/api/divisiones',                              'Controladores\DivisionControlador', 'crear',      ['ADMINISTRADOR'], true);
$router->registrar('PUT',    '/api/divisiones/{id}',                         'Controladores\DivisionControlador', 'actualizar', ['ADMINISTRADOR'], true);
$router->registrar('DELETE', '/api/divisiones/{id}',                         'Controladores\DivisionControlador', 'eliminar',   ['ADMINISTRADOR'], true);
$router->registrar('POST',   '/api/divisiones/{id}/inscripciones',           'Controladores\DivisionControlador', 'inscribir',  ['SOCIO', 'ENTRENADOR', 'ADMINISTRADOR'], true);
$router->registrar('DELETE', '/api/divisiones/{id}/inscripciones/{socioId}', 'Controladores\DivisionControlador', 'desactivar', ['ENTRENADOR', 'ADMINISTRADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Entrenamientos y Asistencias (Regla R9)
// -----------------------------------------------------------------------------
$router->registrar('GET',  '/api/entrenamientos',                'Controladores\EntrenamientoControlador', 'listar',           ['ENTRENADOR', 'ADMINISTRADOR'], false);
$router->registrar('POST', '/api/entrenamientos',                'Controladores\EntrenamientoControlador', 'crear',            ['ENTRENADOR', 'ADMINISTRADOR'], true);
$router->registrar('GET',  '/api/entrenamientos/{id}/asistencia', 'Controladores\EntrenamientoControlador', 'obtenerPlanilla', ['ENTRENADOR', 'ADMINISTRADOR'], false);
$router->registrar('PUT',  '/api/entrenamientos/{id}/asistencia', 'Controladores\EntrenamientoControlador', 'guardarAsistencia', ['ENTRENADOR'], true);

// -----------------------------------------------------------------------------
// Rutas de Apercibimientos Disciplinarios (Regla R10)
// -----------------------------------------------------------------------------
$router->registrar('POST', '/api/apercibimientos', 'Controladores\ApercibimientoControlador', 'crear',  ['ENTRENADOR', 'ADMINISTRADOR'], true);
$router->registrar('GET',  '/api/apercibimientos', 'Controladores\ApercibimientoControlador', 'listar', ['SOCIO', 'ENTRENADOR', 'ADMINISTRADOR'], false);

// Parsear peticion entrante y despachar al recurso correspondiente
$peticion = new Peticion();
$router->despachar($peticion);

