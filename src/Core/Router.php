<?php
namespace Core;

use Excepciones\NoAutorizadoException;
use Excepciones\ProhibidoException;
use Excepciones\NoEncontradoException;
use Excepciones\AppException;

/**
 * Enrutador centralizado de la API REST de MiClub.
 * Capa: Core del sistema.
 *
 * Responsabilidad unica: despachar la peticion HTTP a su controlador correspondiente,
 * aplicando en un unico punto la autorizacion por rol, verificacion de sesion y proteccion CSRF.
 */
class Router
{
    private array $rutas = [];

    /**
     * Registra una ruta en el despachador.
     *
     * @param string $metodo Metodo HTTP (GET, POST, PUT, DELETE)
     * @param string $patron Patron de la ruta (ej: /api/socios/{id}/estado-cuenta)
     * @param string $controlador Nombre de la clase del controlador
     * @param string $accion Nombre del metodo de accion
     * @param array $rolesPermitidos Lista de roles permitidos ('ADMINISTRADOR', 'TESORERO', 'ENTRENADOR', 'SOCIO'). Vacio para acceso publico.
     * @param bool $requiereCsrf Indica si exige token CSRF en operaciones que modifican estado
     */
    public function registrar(
        string $metodo,
        string $patron,
        string $controlador,
        string $accion,
        array $rolesPermitidos = [],
        bool $requiereCsrf = true
    ): void {
        $this->rutas[] = [
            'metodo' => strtoupper($metodo),
            'patron' => $patron,
            'controlador' => $controlador,
            'accion' => $accion,
            'roles' => $rolesPermitidos,
            'requiere_csrf' => $requiereCsrf
        ];
    }

    /**
     * Resuelve la peticion entrante, valida autorizaciones y ejecuta la accion del controlador.
     *
     * @param Peticion $peticion Peticion HTTP parseada
     * @throws NoAutorizadoException Si la ruta requiere sesion y no la hay
     * @throws ProhibidoException Si el rol del usuario no tiene permiso
     * @throws NoEncontradoException Si no existe coincidencia de ruta y metodo
     */
    public function despachar(Peticion $peticion): void
    {
        $metodoPeticion = $peticion->getMetodo();
        $rutaPeticion = $peticion->getRuta();

        // Responder pre-flight OPTIONS para navegadores si fuera necesario
        if ($metodoPeticion === 'OPTIONS') {
            http_response_code(204);
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
            exit;
        }

        foreach ($this->rutas as $definicion) {
            if ($definicion['metodo'] !== $metodoPeticion) {
                continue;
            }

            $parametros = [];
            if ($this->coincideRuta($definicion['patron'], $rutaPeticion, $parametros)) {
                // 1. Control de sesion y autorizacion por rol en punto unico
                $this->verificarAutorizacion($definicion['roles']);

                // 2. Proteccion CSRF para metodos que modifican estado (POST, PUT, DELETE)
                if ($definicion['requiere_csrf'] && in_array($metodoPeticion, ['POST', 'PUT', 'DELETE'], true)) {
                    $this->verificarCsrf($peticion);
                }

                // 3. Instanciar y ejecutar el controlador
                $claseControlador = $definicion['controlador'];
                if (!class_exists($claseControlador)) {
                    throw new AppException("Controlador '{$claseControlador}' no encontrado", 500, 'ERROR_CONTROLADOR');
                }

                $controlador = new $claseControlador();
                $metodoAccion = $definicion['accion'];

                if (!method_exists($controlador, $metodoAccion)) {
                    throw new AppException("Accion '{$metodoAccion}' no encontrada en {$claseControlador}", 500, 'ERROR_ACCION');
                }

                // Invocar accion pasando la peticion y los parametros extraidos de la ruta
                $controlador->$metodoAccion($peticion, $parametros);
                return;
            }
        }

        throw new NoEncontradoException("Ruta no encontrada para {$metodoPeticion} {$rutaPeticion}");
    }

    /**
     * Comprueba si la ruta solicitada coincide con el patron definido y extrae parametros {param}.
     */
    private function coincideRuta(string $patron, string $rutaSolicitada, array &$parametros): bool
    {
        // Convertir {parametro} en grupo de captura regex ([a-zA-Z0-9_\-]+)
        $patronRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $patron);
        $patronRegex = '#^' . $patronRegex . '$#';

        if (preg_match($patronRegex, $rutaSolicitada, $coincidencias)) {
            foreach ($coincidencias as $clave => $valor) {
                if (is_string($clave)) {
                    $parametros[$clave] = $valor;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Aplica la tabla de permisos en un unico punto.
     *
     * @param array $rolesPermitidos Roles autorizados para la ruta
     * @throws NoAutorizadoException Si se requiere sesion y no esta activa
     * @throws ProhibidoException Si el rol no tiene acceso
     */
    private function verificarAutorizacion(array $rolesPermitidos): void
    {
        // Si no define roles, es una ruta publica
        if (empty($rolesPermitidos)) {
            return;
        }

        Sesion::iniciar();
        $usuarioId = Sesion::obtener('usuario_id');
        $rolNombre = Sesion::obtener('rol_nombre');
        $activo = Sesion::obtener('usuario_activo', 1);

        if (!$usuarioId || !$rolNombre) {
            throw new NoAutorizadoException('Debe iniciar sesion para acceder a este recurso.');
        }

        // Control de inactividad de sesion
        $rutaConfig = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        if (!file_exists($rutaConfig)) {
            $rutaConfig = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.example.php';
        }
        $configuracion = require $rutaConfig;
        $tiempoInactividad = $configuracion['sesion']['tiempo_inactividad'] ?? 1800;

        $ultimoAcceso = Sesion::obtener('ultimo_acceso');
        $ahora = time();

        if ($ultimoAcceso !== null && ($ahora - $ultimoAcceso > $tiempoInactividad)) {
            Sesion::destruir();
            throw new NoAutorizadoException('La sesion ha expirado por inactividad. Inicie sesion nuevamente.', 'SESION_EXPIRADA');
        }

        Sesion::establecer('ultimo_acceso', $ahora);

        if (!$activo) {
            Sesion::destruir();
            throw new NoAutorizadoException('Su cuenta de usuario se encuentra inactiva. Comuniquese con el club.');
        }

        if (!in_array($rolNombre, $rolesPermitidos, true)) {
            throw new ProhibidoException("Su rol '{$rolNombre}' no tiene autorizacion para acceder a esta funcion.");
        }
    }

    /**
     * Verifica la presencia y validez del token CSRF.
     */
    private function verificarCsrf(Peticion $peticion): void
    {
        // Si no hay sesion iniciada en el cliente, el token no aplica o se requiere inicio
        Sesion::iniciar();
        if (!Sesion::existe('usuario_id')) {
            return;
        }

        $token = $peticion->getEncabezado('X-CSRF-Token') ?? $peticion->obtener('_token');

        if (!Csrf::validar($token)) {
            throw new AppException('Token CSRF invalido o sesion expirada. Por favor actualice la pagina.', 403, 'TOKEN_CSRF_INVALIDO');
        }
    }
}
