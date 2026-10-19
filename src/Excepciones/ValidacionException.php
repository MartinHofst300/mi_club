<?php
namespace Excepciones;

/**
 * Excepcion lanzada ante errores de formato o validacion de datos de entrada.
 * Capa: Excepciones.
 */
class ValidacionException extends AppException
{
    public function __construct(string $mensaje = 'Los datos proporcionados no son validos', array $erroresPorCampo = [], int $codigoHttp = 400)
    {
        parent::__construct($mensaje, $codigoHttp, 'ERROR_VALIDACION', $erroresPorCampo);
    }
}
