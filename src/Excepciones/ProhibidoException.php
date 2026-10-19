<?php
namespace Excepciones;

/**
 * Excepcion lanzada cuando el usuario autenticado no posee los permisos o rol requeridos.
 * Capa: Excepciones.
 */
class ProhibidoException extends AppException
{
    public function __construct(string $mensaje = 'No posee permisos suficientes para realizar esta accion', array $detalles = [])
    {
        parent::__construct($mensaje, 403, 'ACCESO_PROHIBIDO', $detalles);
    }
}
