<?php
namespace Servicios;

use Repositorios\DivisionRepositorio;
use Repositorios\SocioRepositorio;
use Repositorios\CategoriaRepositorio;
use Excepciones\ReglaNegocioException;
use Excepciones\ConflictoException;
use Excepciones\ProhibidoException;
use Excepciones\NoEncontradoException;
use Excepciones\ValidacionException;
use DateTime;

/**
 * Servicio de Gestion de Divisiones e Inscripciones Deportivas (Regla R8).
 * Capa: Servicios de dominio.
 *
 * Aplica validaciones de cupo maximo, rango de edad de la categoria,
 * estado contable del socio (no moroso/suspendido/baja) y unicidad de inscripcion.
 */
class DivisionServicio
{
    private DivisionRepositorio $divisionRepo;
    private SocioRepositorio $socioRepo;
    private CategoriaRepositorio $categoriaRepo;

    public function __construct(
        ?DivisionRepositorio $divisionRepo = null,
        ?SocioRepositorio $socioRepo = null,
        ?CategoriaRepositorio $categoriaRepo = null
    ) {
        $this->divisionRepo = $divisionRepo ?? new DivisionRepositorio();
        $this->socioRepo = $socioRepo ?? new SocioRepositorio();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepositorio();
    }

    /**
     * Lista todas las divisiones deportivas indicando cupos y si un socio especifico ya se encuentra inscripto.
     */
    public function listarDivisionesConDisponibilidad(?int $socioId = null): array
    {
        $divisiones = $this->divisionRepo->listarTodasConCupo();

        if ($socioId) {
            foreach ($divisiones as &$d) {
                $d['ya_inscripto'] = $this->divisionRepo->estaInscripto((int)$d['id'], $socioId);
            }
        }

        return $divisiones;
    }

    /**
     * Inscribe formalmente a un socio en una division deportiva (Regla R8).
     *
     * @param int $divisionId ID de la division deportiva
     * @param int $socioId ID del socio a inscribir
     * @param ?int $entrenadorUsuarioId ID del entrenador si la accion la ejecuta el cuerpo tecnico
     * @return array Detalle de la inscripcion realizada
     * @throws ReglaNegocioException Si el socio esta moroso/suspendido/baja o fuera del rango etario
     * @throws ConflictoException Si el cupo se encuentra lleno (HTTP 409)
     */
    public function inscribirSocio(int $divisionId, int $socioId, ?int $entrenadorUsuarioId = null): array
    {
        $division = $this->divisionRepo->buscarPorId($divisionId);
        if (!$division || !$division->activo) {
            throw new NoEncontradoException('La division deportiva solicitada no existe o no se encuentra activa.');
        }

        // Si la accion la ejecuta un entrenador, verificar que este asignado a dicha division
        if ($entrenadorUsuarioId !== null) {
            if (!$this->divisionRepo->esEntrenadorDeDivision($divisionId, $entrenadorUsuarioId)) {
                throw new ProhibidoException('No es el entrenador responsable de esta division.');
            }
        }

        $socio = $this->socioRepo->buscarPorId($socioId);
        if (!$socio) {
            throw new NoEncontradoException('El socio no existe.');
        }

        // Regla R8: Un socio MOROSO, SUSPENDIDO o BAJA no puede inscribirse a divisiones
        if ($socio->estado === 'MOROSO') {
            throw new ReglaNegocioException('Un socio en estado MOROSO pierde el acceso a inscribirse en divisiones deportivas. Regularice sus cuotas para continuar.');
        }
        if ($socio->estado === 'SUSPENDIDO') {
            throw new ReglaNegocioException('El socio se encuentra SUSPENDIDO administrativamente. No puede inscribirse a actividades.');
        }
        if ($socio->estado === 'BAJA') {
            throw new ReglaNegocioException('El socio figura en estado BAJA en la institucion.');
        }

        // Regla R8: Validar que no este ya inscripto en esta division
        if ($this->divisionRepo->estaInscripto($divisionId, $socioId)) {
            throw new ReglaNegocioException('El socio ya se encuentra formalmente inscripto en esta division.');
        }

        // Regla R8: Validar edad calculada desde la fecha de nacimiento dentro del rango de la categoria
        $categoria = $this->categoriaRepo->buscarPorId($division->categoriaId);
        if (!$categoria) {
            throw new ReglaNegocioException('La categoria asociada a la division no se encuentra disponible.');
        }

        $hoy = new DateTime();
        $nacimiento = new DateTime($socio->fechaNacimiento);
        $edad = $hoy->diff($nacimiento)->y;

        if ($edad < $categoria->edadMinima || $edad > $categoria->edadMaxima) {
            throw new ValidacionException(sprintf(
                'La edad del socio (%d anos) no corresponde al rango de la categoria %s (%d a %d anos).',
                $edad,
                $categoria->nombre,
                $categoria->edadMinima,
                $categoria->edadMaxima
            ), ['edad' => 'Fuera de rango etario'], 422);
        }

        // Regla R8: Validar cupo disponible. Cupo lleno devuelve 409
        $inscriptosActuales = $this->divisionRepo->contarInscriptosActivos($divisionId);
        if ($inscriptosActuales >= $division->cupoMaximo) {
            throw new ConflictoException(sprintf(
                'El cupo maximo de la division %s (%d socios) se encuentra agotado.',
                $division->nombre,
                $division->cupoMaximo
            ));
        }

        $fechaHoy = date('Y-m-d');
        $inscripcionId = $this->divisionRepo->inscribirSocio($divisionId, $socioId, $fechaHoy);

        return [
            'inscripcion_id' => $inscripcionId,
            'division_id' => $divisionId,
            'division_nombre' => $division->nombre,
            'socio_id' => $socioId,
            'fecha_inscripcion' => $fechaHoy,
            'mensaje' => "Inscripcion a {$division->nombre} confirmada exitosamente."
        ];
    }

    /**
     * Da de baja a un socio del plantel de una division.
     */
    public function darDeBajaPlantel(int $divisionId, int $socioId, ?int $entrenadorUsuarioId = null): bool
    {
        if ($entrenadorUsuarioId !== null) {
            if (!$this->divisionRepo->esEntrenadorDeDivision($divisionId, $entrenadorUsuarioId)) {
                throw new ProhibidoException('No es el entrenador responsable de esta division.');
            }
        }

        return $this->divisionRepo->desactivarInscripcion($divisionId, $socioId);
    }

    /**
     * Obtiene el plantel activo de una division deportiva.
     */
    public function obtenerPlantel(int $divisionId, ?int $entrenadorUsuarioId = null): array
    {
        if ($entrenadorUsuarioId !== null) {
            if (!$this->divisionRepo->esEntrenadorDeDivision($divisionId, $entrenadorUsuarioId)) {
                throw new ProhibidoException('No es el entrenador asignado a esta division.');
            }
        }

        return $this->divisionRepo->obtenerPlantel($divisionId);
    }

    /**
     * Da de alta una nueva division deportiva.
     */
    public function crear(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $categoriaId = (int)($datos['categoria_id'] ?? 0);
        $entrenadorId = (int)($datos['entrenador_id'] ?? 0);
        $cupoMaximo = (int)($datos['cupo_maximo'] ?? 30);
        $activo = isset($datos['activo']) ? (bool)$datos['activo'] : true;

        if (empty($nombre)) {
            throw new ValidacionException('El nombre de la division es obligatorio.');
        }

        if ($cupoMaximo <= 0) {
            throw new ValidacionException('El cupo maximo debe ser mayor a 0.');
        }

        $categoria = $this->categoriaRepo->buscarPorId($categoriaId);
        if (!$categoria || !$categoria->activo) {
            throw new ValidacionException('La categoria seleccionada no existe o no se encuentra activa.');
        }

        $usuarioRepo = new \Repositorios\UsuarioRepositorio();
        $entrenador = $usuarioRepo->buscarPorId($entrenadorId);
        if (!$entrenador || $entrenador->rolId !== 3) {
            throw new ValidacionException('El entrenador seleccionado no es valido o no tiene asignado el rol de ENTRENADOR.');
        }

        $division = new \Modelos\Division(
            null,
            $categoriaId,
            $entrenadorId,
            $nombre,
            $cupoMaximo,
            $activo
        );

        $id = $this->divisionRepo->crear($division);

        return [
            'id' => $id,
            'nombre' => $nombre,
            'mensaje' => "Division '{$nombre}' creada exitosamente."
        ];
    }

    /**
     * Actualiza una division existente.
     */
    public function actualizar(int $id, array $datos): array
    {
        $division = $this->divisionRepo->buscarPorId($id);
        if (!$division) {
            throw new NoEncontradoException('Division no encontrada.');
        }

        $nombre = trim($datos['nombre'] ?? $division->nombre);
        $categoriaId = isset($datos['categoria_id']) ? (int)$datos['categoria_id'] : $division->categoriaId;
        $entrenadorId = isset($datos['entrenador_id']) ? (int)$datos['entrenador_id'] : $division->entrenadorId;
        $cupoMaximo = isset($datos['cupo_maximo']) ? (int)$datos['cupo_maximo'] : $division->cupoMaximo;
        $activo = isset($datos['activo']) ? (bool)$datos['activo'] : $division->activo;

        if (empty($nombre)) {
            throw new ValidacionException('El nombre de la division es obligatorio.');
        }

        if ($cupoMaximo <= 0) {
            throw new ValidacionException('El cupo maximo debe ser mayor a 0.');
        }

        // Regla: El cupo no puede quedar por debajo de los inscriptos actuales
        $inscriptosActuales = $this->divisionRepo->contarInscriptosActivos($id);
        if ($cupoMaximo < $inscriptosActuales) {
            throw new ReglaNegocioException(
                "El cupo maximo ({$cupoMaximo} socios) no puede ser inferior a la cantidad de socios inscriptos actualmente ({$inscriptosActuales} socios)."
            );
        }

        $categoria = $this->categoriaRepo->buscarPorId($categoriaId);
        if (!$categoria) {
            throw new ValidacionException('La categoria seleccionada no existe.');
        }

        $usuarioRepo = new \Repositorios\UsuarioRepositorio();
        $entrenador = $usuarioRepo->buscarPorId($entrenadorId);
        if (!$entrenador || $entrenador->rolId !== 3) {
            throw new ValidacionException('El entrenador seleccionado no es valido o no tiene asignado el rol de ENTRENADOR.');
        }

        $actualizada = new \Modelos\Division(
            $id,
            $categoriaId,
            $entrenadorId,
            $nombre,
            $cupoMaximo,
            $activo
        );

        $this->divisionRepo->actualizar($actualizada);

        return [
            'id' => $id,
            'nombre' => $nombre,
            'mensaje' => "Division '{$nombre}' actualizada exitosamente."
        ];
    }

    /**
     * Elimina una division deportiva si no tiene registros dependientes.
     */
    public function eliminar(int $id): array
    {
        $division = $this->divisionRepo->buscarPorId($id);
        if (!$division) {
            throw new NoEncontradoException('Division no encontrada.');
        }

        // Regla: No borrar una division con inscriptos o entrenamientos
        $inscriptos = $this->divisionRepo->contarInscriptosTotales($id);
        if ($inscriptos > 0) {
            throw new ConflictoException(
                "No es posible eliminar la division '{$division->nombre}' porque registra {$inscriptos} socio(s) inscripto(s)."
            );
        }

        $entrenamientos = $this->divisionRepo->contarEntrenamientosAsociados($id);
        if ($entrenamientos > 0) {
            throw new ConflictoException(
                "No es posible eliminar la division '{$division->nombre}' porque registra {$entrenamientos} sesion(es) de entrenamiento registradas."
            );
        }

        $this->divisionRepo->eliminar($id);

        return [
            'id' => $id,
            'mensaje' => "Division '{$division->nombre}' eliminada exitosamente."
        ];
    }
}
