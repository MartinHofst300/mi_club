<?php
namespace Servicios;

use Repositorios\ApercibimientoRepositorio;
use Repositorios\SocioRepositorio;
use Repositorios\DivisionRepositorio;
use Modelos\Apercibimiento;
use Excepciones\ReglaNegocioException;
use Excepciones\ProhibidoException;
use Excepciones\NoEncontradoException;
use Excepciones\ValidacionException;

/**
 * Servicio de Gestion de Apercibimientos Disciplinarios (Regla R10).
 * Capa: Servicios de dominio.
 */
class ApercibimientoServicio
{
    private ApercibimientoRepositorio $apercibimientoRepo;
    private SocioRepositorio $socioRepo;
    private DivisionRepositorio $divisionRepo;

    public function __construct(
        ?ApercibimientoRepositorio $apercibimientoRepo = null,
        ?SocioRepositorio $socioRepo = null,
        ?DivisionRepositorio $divisionRepo = null
    ) {
        $this->apercibimientoRepo = $apercibimientoRepo ?? new ApercibimientoRepositorio();
        $this->socioRepo = $socioRepo ?? new SocioRepositorio();
        $this->divisionRepo = $divisionRepo ?? new DivisionRepositorio();
    }

    /**
     * Carga un apercibimiento sobre un socio del plantel del entrenador (Regla R10).
     *
     * @param int $socioId ID del socio sancionado
     * @param int $entrenadorUsuarioId ID del usuario entrenador
     * @param string $fecha Fecha del incidente (AAAA-MM-DD)
     * @param string $gravedad LEVE o GRAVE
     * @param string $motivo Descripcion clara del motivo
     * @return array
     */
    public function registrarApercibimiento(
        int $socioId,
        int $entrenadorUsuarioId,
        string $fecha,
        string $gravedad,
        string $motivo
    ): array {
        $gravedad = strtoupper(trim($gravedad));
        if (!in_array($gravedad, ['LEVE', 'GRAVE'], true)) {
            throw new ValidacionException('La gravedad del apercibimiento debe ser LEVE o GRAVE.');
        }

        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 5) {
            throw new ValidacionException('Debe detallar el motivo del apercibimiento (minimo 5 caracteres).');
        }

        $socio = $this->socioRepo->buscarPorId($socioId);
        if (!$socio) {
            throw new NoEncontradoException('El socio no existe.');
        }

        // Regla R10: Validar que el socio pertenezca al plantel de alguna division a cargo del entrenador
        $divisionesEntrenador = $this->divisionRepo->listarPorEntrenador($entrenadorUsuarioId);
        $pertenece = false;

        foreach ($divisionesEntrenador as $div) {
            if ($this->divisionRepo->estaInscripto((int)$div['id'], $socioId)) {
                $pertenece = true;
                break;
            }
        }

        if (!$pertenece) {
            $usuarioRepo = new \Repositorios\UsuarioRepositorio();
            $usuario = $usuarioRepo->buscarPorId($entrenadorUsuarioId);
            if ($usuario && $usuario->rolId === 1) {
                $pertenece = true;
            }
        }

        if (!$pertenece) {
            throw new ProhibidoException('Solo puede cargar apercibimientos sobre socios que formen parte de sus planteles.');
        }

        $apercibimiento = new Apercibimiento(
            null,
            $socioId,
            $entrenadorUsuarioId,
            $fecha,
            $gravedad,
            $motivo
        );

        $id = $this->apercibimientoRepo->crear($apercibimiento);

        return [
            'id' => $id,
            'socio_id' => $socioId,
            'gravedad' => $gravedad,
            'fecha' => $fecha,
            'mensaje' => 'Apercibimiento registrado exitosamente.'
        ];
    }

    /**
     * Lista los apercibimientos de un socio (Caso de uso Consultar carnet / perfil).
     */
    public function listarPorSocio(int $socioId): array
    {
        return $this->apercibimientoRepo->listarPorSocio($socioId);
    }

    /**
     * Lista los apercibimientos cargados por un entrenador.
     */
    public function listarPorEntrenador(int $entrenadorUsuarioId): array
    {
        return $this->apercibimientoRepo->listarPorEntrenador($entrenadorUsuarioId);
    }

    /**
     * Lista todos los apercibimientos para administradores.
     */
    public function listarTodos(): array
    {
        return $this->apercibimientoRepo->listarTodos();
    }
}
