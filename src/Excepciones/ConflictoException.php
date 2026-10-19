<?php
namespace Excepciones;

/**
 * Excepcion lanzada cuando una solicitud entra en conflicto con el estado actual del recurso (HTTP 409).
 * Capa: Excepciones.
 */
class ConflictoException extends AppException
{
    public function __construct(string $mensaje, array $detalles = [])
    {
        parent::__construct($mensaje, 409, 'CONFLICTO', $detalles);
    }
}
