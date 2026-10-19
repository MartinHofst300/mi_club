<?php
namespace Servicios;

use Repositorios\CategoriaRepositorio;
use Modelos\Categoria;
use Excepciones\ReglaNegocioException;
use Excepciones\NoEncontradoException;
use Excepciones\ValidacionException;
use Excepciones\ConflictoException;

/**
 * Servicio de Gestion de Categorias y Tarifas Sociales (Caso de uso Administrador).
 * Capa: Servicios de dominio.
 */
class CategoriaServicio
{
    private CategoriaRepositorio $categoriaRepo;

    public function __construct(?CategoriaRepositorio $categoriaRepo = null)
    {
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepositorio();
    }

    /**
     * Lista todas las categorias (activas o inactivas).
     */
    public function listar(bool $soloActivas = false): array
    {
        return $this->categoriaRepo->listarTodas($soloActivas);
    }

    /**
     * Obtiene una categoria por ID.
     */
    public function obtenerPorId(int $id): Categoria
    {
        $cat = $this->categoriaRepo->buscarPorId($id);
        if (!$cat) {
            throw new NoEncontradoException('Categoria no encontrada.');
        }
        return $cat;
    }

    /**
     * Da de alta una nueva categoria validando reglas etarias.
     */
    public function crear(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $descripcion = !empty($datos['descripcion']) ? trim($datos['descripcion']) : null;
        $edadMin = (int)($datos['edad_minima'] ?? 0);
        $edadMax = (int)($datos['edad_maxima'] ?? 0);
        $precio = (float)($datos['precio_cuota'] ?? 0.0);
        $activo = isset($datos['activo']) ? (bool)$datos['activo'] : true;

        if (empty($nombre)) {
            throw new ValidacionException('El nombre de la categoria es obligatorio.');
        }

        if ($edadMin < 0 || $edadMax < 0 || $edadMin > $edadMax) {
            throw new ValidacionException('La edad minima debe ser mayor o igual a 0 y menor o igual a la edad maxima.');
        }

        if ($precio < 0.0) {
            throw new ValidacionException('El precio de cuota mensual no puede ser negativo.');
        }

        // Validar que los rangos etarios no se solapen con otras categorias activas
        $solapadas = $this->categoriaRepo->buscarSuperposicionEdades($edadMin, $edadMax);
        if (!empty($solapadas)) {
            $nombres = implode(', ', array_column($solapadas, 'nombre'));
            throw new ReglaNegocioException("El rango de edad ({$edadMin} a {$edadMax} anos) se solapa con la(s) categoria(s) activa(s): {$nombres}.");
        }

        $categoria = new Categoria(
            null,
            $nombre,
            $descripcion,
            $edadMin,
            $edadMax,
            $precio,
            $activo
        );

        $id = $this->categoriaRepo->crear($categoria);

        return [
            'id' => $id,
            'nombre' => $nombre,
            'mensaje' => "Categoria '{$nombre}' creada exitosamente."
        ];
    }

    /**
     * Actualiza una categoria existente.
     */
    public function actualizar(int $id, array $datos): array
    {
        $cat = $this->categoriaRepo->buscarPorId($id);
        if (!$cat) {
            throw new NoEncontradoException('Categoria no encontrada.');
        }

        $nombre = trim($datos['nombre'] ?? $cat->nombre);
        $descripcion = array_key_exists('descripcion', $datos) ? ($datos['descripcion'] ? trim($datos['descripcion']) : null) : $cat->descripcion;
        $edadMin = isset($datos['edad_minima']) ? (int)$datos['edad_minima'] : $cat->edadMinima;
        $edadMax = isset($datos['edad_maxima']) ? (int)$datos['edad_maxima'] : $cat->edadMaxima;
        $precio = isset($datos['precio_cuota']) ? (float)$datos['precio_cuota'] : $cat->precioCuota;
        $activo = isset($datos['activo']) ? (bool)$datos['activo'] : $cat->activo;

        if (empty($nombre)) {
            throw new ValidacionException('El nombre de la categoria es obligatorio.');
        }

        if ($edadMin < 0 || $edadMax < 0 || $edadMin > $edadMax) {
            throw new ValidacionException('La edad minima debe ser mayor o igual a 0 y menor o igual a la edad maxima.');
        }

        if ($precio < 0.0) {
            throw new ValidacionException('El precio de cuota mensual no puede ser negativo.');
        }

        // Validar solapamiento con otras categorias activas
        $solapadas = $this->categoriaRepo->buscarSuperposicionEdades($edadMin, $edadMax, $id);
        if (!empty($solapadas)) {
            $nombres = implode(', ', array_column($solapadas, 'nombre'));
            throw new ReglaNegocioException("El rango de edad ({$edadMin} a {$edadMax} anos) se solapa con la(s) categoria(s) activa(s): {$nombres}.");
        }

        $actualizada = new Categoria(
            $id,
            $nombre,
            $descripcion,
            $edadMin,
            $edadMax,
            $precio,
            $activo
        );

        $this->categoriaRepo->actualizar($actualizada);

        return [
            'id' => $id,
            'nombre' => $nombre,
            'mensaje' => "Categoria '{$nombre}' actualizada exitosamente."
        ];
    }

    /**
     * Elimina una categoria validando que no tenga socios o divisiones asociadas.
     */
    public function eliminar(int $id): array
    {
        $cat = $this->categoriaRepo->buscarPorId($id);
        if (!$cat) {
            throw new NoEncontradoException('Categoria no encontrada.');
        }

        $socios = $this->categoriaRepo->contarSociosAsociados($id);
        if ($socios > 0) {
            throw new ConflictoException("No es posible eliminar la categoria '{$cat->nombre}' porque tiene {$socios} socio(s) inscripto(s).");
        }

        $divisiones = $this->categoriaRepo->contarDivisionesAsociadas($id);
        if ($divisiones > 0) {
            throw new ConflictoException("No es posible eliminar la categoria '{$cat->nombre}' porque tiene {$divisiones} division(es) vinculada(s).");
        }

        $this->categoriaRepo->eliminar($id);

        return [
            'id' => $id,
            'mensaje' => "Categoria '{$cat->nombre}' eliminada exitosamente."
        ];
    }
}
