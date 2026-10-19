<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Validador;
use Servicios\PrecioServicio;

/**
 * Controlador de Gestion de Precios y Parametros Economicos.
 * Capa: Controladores.
 */
class PrecioControlador
{
    private PrecioServicio $precioServicio;

    public function __construct(?PrecioServicio $precioServicio = null)
    {
        $this->precioServicio = $precioServicio ?? new PrecioServicio();
    }

    /**
     * Obtiene la estructura tarifaria y parametros de cobranza.
     * Ruta: GET /api/precios
     */
    public function obtener(Peticion $peticion): void
    {
        $resultado = $this->precioServicio->obtenerPreciosYParametros();
        Respuesta::exito($resultado, 200);
    }

    /**
     * Actualiza la estructura tarifaria y parametros economicos.
     * Ruta: PUT /api/precios
     */
    public function actualizar(Peticion $peticion): void
    {
        $datos = Validador::validar($peticion->getCuerpo(), [
            'porcentaje_recargo' => 'requerido|decimal|no_negativo',
            'porcentaje_descuento_familiar' => 'requerido|decimal|no_negativo',
            'cuotas_para_moroso' => 'requerido|entero|positivo',
            'dias_vencimiento' => 'requerido|entero|positivo'
        ]);

        $cuerpo = $peticion->getCuerpo();
        if (isset($cuerpo['precios_categorias']) && is_array($cuerpo['precios_categorias'])) {
            $datos['precios_categorias'] = $cuerpo['precios_categorias'];
        }

        $resultado = $this->precioServicio->actualizar($datos);
        Respuesta::exito($resultado, 200);
    }
}
