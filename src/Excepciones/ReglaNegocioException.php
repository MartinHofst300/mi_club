<?php
namespace Excepciones;

/**
 * Excepcion lanzada cuando se transgrede una regla de negocio del sistema MiClub (R1 a R11).
 * Capa: Excepciones.
 */
class ReglaNegocioException extends AppException
{
    public function __construct(string $mensaje, array $detalles = [], int $codigoHttp = 409)
    {
        parent::__construct($mensaje, $codigoHttp, 'REGLA_NEGOCIO', $detalles);
    }
}
