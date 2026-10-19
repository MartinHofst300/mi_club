<?php
namespace Excepciones;

/**
 * Excepcion lanzada cuando se intenta acceder a un recurso protegido sin sesion iniciada.
 * Capa: Excepciones.
 */
class NoAutorizadoException extends AppException
{
    public function __construct(
        string $mensaje = 'Debe iniciar sesion para acceder a este recurso',
        string $codigoError = 'NO_AUTORIZADO',
        array $detalles = []
    ) {
        parent::__construct($mensaje, 401, $codigoError, $detalles);
    }
}
