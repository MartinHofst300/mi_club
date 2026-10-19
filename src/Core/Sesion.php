<?php
namespace Core;

/**
 * Gestor de sesiones de usuario del sistema MiClub.
 * Capa: Core del sistema.
 *
 * Configura cookies seguras (HttpOnly, SameSite=Lax) y regenera IDs para prevenir fijacion de sesion.
 */
class Sesion
{
    private static bool $iniciada = false;

    /**
     * Inicia la sesion de PHP aplicando parametros de seguridad.
     */
    public static function iniciar(): void
    {
        if (self::$iniciada || session_status() === PHP_SESSION_ACTIVE) {
            self::$iniciada = true;
            return;
        }

        $configuracion = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $cfgSesion = $configuracion['sesion'];

        session_name($cfgSesion['nombre']);

        session_set_cookie_params([
            'lifetime' => $cfgSesion['vida_util'],
            'path' => $cfgSesion['ruta'],
            'domain' => '',
            'secure' => $cfgSesion['secure'],
            'httponly' => $cfgSesion['http_only'],
            'samesite' => $cfgSesion['same_site']
        ]);

        session_start();
        self::$iniciada = true;
    }

    /**
     * Regenera el identificador de sesion ante inicios de sesion o cambios de privilegio.
     */
    public static function regenerar(): void
    {
        self::iniciar();
        session_regenerate_id(true);
    }

    /**
     * Guarda un valor en la sesion.
     */
    public static function establecer(string $clave, mixed $valor): void
    {
        self::iniciar();
        $_SESSION[$clave] = $valor;
    }

    /**
     * Obtiene un valor de la sesion.
     */
    public static function obtener(string $clave, mixed $predeterminado = null): mixed
    {
        self::iniciar();
        return $_SESSION[$clave] ?? $predeterminado;
    }

    /**
     * Verifica si existe una clave en la sesion.
     */
    public static function existe(string $clave): bool
    {
        self::iniciar();
        return isset($_SESSION[$clave]);
    }

    /**
     * Elimina una clave de la sesion.
     */
    public static function eliminar(string $clave): void
    {
        self::iniciar();
        unset($_SESSION[$clave]);
    }

    /**
     * Destruye la sesion actual y limpia la cookie del navegador.
     */
    public static function destruir(): void
    {
        self::iniciar();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$iniciada = false;
    }
}
