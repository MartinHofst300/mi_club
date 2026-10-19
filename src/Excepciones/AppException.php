<?php
namespace Excepciones;

use Exception;

/**
 * Excepcion base para todas las excepciones del dominio y de la aplicacion MiClub.
 * Capa: Excepciones.
 */
class AppException extends Exception
{
    protected int $codigoHttp;
    protected string $codigoError;
    protected array $detalles;

    public function __construct(string $mensaje, int $codigoHttp = 500, string $codigoError = 'ERROR_INTERNO', array $detalles = [])
    {
        parent::__construct($mensaje, $codigoHttp);
        $this->codigoHttp = $codigoHttp;
        $this->codigoError = $codigoError;
        $this->detalles = $detalles;
    }

    public function getCodigoHttp(): int
    {
        return $this->codigoHttp;
    }

    public function getCodigoError(): string
    {
        return $this->codigoError;
    }

    public function getDetalles(): array
    {
        return $this->detalles;
    }
}
