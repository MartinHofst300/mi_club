<?php
namespace Excepciones;

/**
 * Excepcion lanzada cuando el recurso solicitado no existe en la base de datos.
 * Capa: Excepciones.
 */
class NoEncontradoException extends AppException
{
    public function __construct(string $mensaje = 'El recurso solicitado no fue encontrado', array $detalles = [])
    {
        parent::__construct($mensaje, 404, 'NO_ENCONTRADO', $detalles);
    }
}
