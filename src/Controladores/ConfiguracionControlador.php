<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Servicios\ConfiguracionServicio;

/**
 * Controlador de configuracion institucional y apariencia.
 * Capa: Controladores.
 */
class ConfiguracionControlador
{
    private ConfiguracionServicio $configServicio;

    public function __construct(?ConfiguracionServicio $configServicio = null)
    {
        $this->configServicio = $configServicio ?? new ConfiguracionServicio();
    }

    /**
     * Devuelve la configuracion institucional y tema de colores (Lectura publica).
     * Ruta: GET /api/configuracion
     */
    public function obtener(Peticion $peticion): void
    {
        $configuracion = $this->configServicio->obtenerConfiguracion();
        Respuesta::exito($configuracion, 200);
    }

    /**
     * Actualiza la apariencia visual del club (Solo ADMINISTRADOR).
     * Ruta: PUT /api/configuracion
     */
    public function actualizar(Peticion $peticion): void
    {
        $resultado = $this->configServicio->actualizarApariencia($peticion->getCuerpo());
        Respuesta::exito($resultado, 200);
    }
}
