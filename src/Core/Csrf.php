<?php
namespace Core;

/**
 * Gestor de proteccion CSRF (Cross-Site Request Forgery).
 * Capa: Core del sistema.
 */
class Csrf
{
    private const CLAVE_TOKEN = '_token_csrf';

    /**
     * Obtiene el token CSRF actual o genera uno nuevo si no existe.
     *
     * @return string Token hexadecimal de 64 caracteres
     */
    public static function obtenerToken(): string
    {
        Sesion::iniciar();
        $token = Sesion::obtener(self::CLAVE_TOKEN);

        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            Sesion::establecer(self::CLAVE_TOKEN, $token);
        }

        return $token;
    }

    /**
     * Valida un token CSRF provisto contra el almacenado en la sesion activa.
     *
     * @param string|null $tokenIngresado
     * @return bool
     */
    public static function validar(?string $tokenIngresado): bool
    {
        if (empty($tokenIngresado)) {
            return false;
        }

        $tokenGuardado = Sesion::obtener(self::CLAVE_TOKEN);
        if (empty($tokenGuardado)) {
            return false;
        }

        return hash_equals($tokenGuardado, $tokenIngresado);
    }

    /**
     * Regenera el token CSRF (por ejemplo luego de iniciar o cerrar sesion).
     */
    public static function regenerar(): string
    {
        Sesion::iniciar();
        $token = bin2hex(random_bytes(32));
        Sesion::establecer(self::CLAVE_TOKEN, $token);
        return $token;
    }
}
