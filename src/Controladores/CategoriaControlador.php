<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Validador;
use Servicios\CategoriaServicio;

/**
 * Controlador para la Gestion de Categorias.
 * Capa: Controladores.
 */
class CategoriaControlador
{
    private CategoriaServicio $categoriaServicio;

    public function __construct(?CategoriaServicio $categoriaServicio = null)
    {
        $this->categoriaServicio = $categoriaServicio ?? new CategoriaServicio();
    }

    /**
     * Lista todas las categorias.
     * Ruta: GET /api/categorias
     */
    public function listar(Peticion $peticion): void
    {
        $soloActivas = $peticion->obtener('activas') === '1';
        $categorias = $this->categoriaServicio->listar($soloActivas);
        Respuesta::exito($categorias, 200);
    }

    /**
     * Obtiene una categoria por ID.
     * Ruta: GET /api/categorias/{id}
     */
    public function obtener(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $categoria = $this->categoriaServicio->obtenerPorId($id);
        Respuesta::exito($categoria->aArreglo(), 200);
    }

    /**
     * Da de alta una nueva categoria.
     * Ruta: POST /api/categorias
     */
    public function crear(Peticion $peticion): void
    {
        $datos = Validador::validar($peticion->getCuerpo(), [
            'nombre' => 'requerido',
            'edad_minima' => 'requerido|entero|no_negativo',
            'edad_maxima' => 'requerido|entero|no_negativo',
            'precio_cuota' => 'requerido|decimal|no_negativo'
        ]);

        $cuerpo = $peticion->getCuerpo();
        if (isset($cuerpo['descripcion'])) {
            $datos['descripcion'] = $cuerpo['descripcion'];
        }
        if (isset($cuerpo['activo'])) {
            $datos['activo'] = (bool)$cuerpo['activo'];
        }

        $resultado = $this->categoriaServicio->crear($datos);
        Respuesta::exito($resultado, 201);
    }

    /**
     * Actualiza una categoria existente.
     * Ruta: PUT /api/categorias/{id}
     */
    public function actualizar(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);

        $datos = Validador::validar($peticion->getCuerpo(), [
            'nombre' => 'requerido',
            'edad_minima' => 'requerido|entero|no_negativo',
            'edad_maxima' => 'requerido|entero|no_negativo',
            'precio_cuota' => 'requerido|decimal|no_negativo'
        ]);

        $cuerpo = $peticion->getCuerpo();
        if (isset($cuerpo['descripcion'])) {
            $datos['descripcion'] = $cuerpo['descripcion'];
        }
        if (isset($cuerpo['activo'])) {
            $datos['activo'] = (bool)$cuerpo['activo'];
        }

        $resultado = $this->categoriaServicio->actualizar($id, $datos);
        Respuesta::exito($resultado, 200);
    }

    /**
     * Elimina una categoria si no tiene datos asociados.
     * Ruta: DELETE /api/categorias/{id}
     */
    public function eliminar(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $resultado = $this->categoriaServicio->eliminar($id);
        Respuesta::exito($resultado, 200);
    }
}
