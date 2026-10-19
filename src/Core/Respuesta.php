<?php
namespace Core;

/**
 * Gestor de respuestas HTTP uniformes en formato JSON.
 * Capa: Core del sistema.
 */
class Respuesta
{
    /**
     * Envia una respuesta exitosa en formato JSON con codigo HTTP.
     *
     * @param mixed $datos Datos del recurso o resultado
     * @param int $codigoHttp Codigo de estado HTTP (200, 201, etc.)
     */
    public static function exito(mixed $datos = null, int $codigoHttp = 200): void
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');

        $cuerpo = [
            'ok' => true,
            'datos' => $datos
        ];

        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envia una respuesta de error uniforme en formato JSON.
     *
     * @param string $mensaje Descripcion clara del error en espanol
     * @param int $codigoHttp Codigo de estado HTTP (400, 401, 403, 404, 409, 500)
     * @param string $codigoError Identificador textual del error
     * @param array $detalles Arreglo opcional con informacion adicional o errores por campo
     */
    public static function error(string $mensaje, int $codigoHttp = 400, string $codigoError = 'ERROR', array $detalles = []): void
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');

        $cuerpo = [
            'ok' => false,
            'error' => [
                'codigo' => $codigoError,
                'mensaje' => $mensaje,
                'detalles' => $detalles
            ]
        ];

        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
