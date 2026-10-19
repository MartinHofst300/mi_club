<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Sesion;
use Core\Validador;
use Servicios\PagoServicio;
use Repositorios\PagoRepositorio;
use Excepciones\ProhibidoException;
use Excepciones\NoEncontradoException;

/**
 * Controlador para el registro de cobros y recepcion de pagos.
 * Capa: Controladores.
 */
class PagoControlador
{
    private PagoServicio $pagoServicio;
    private PagoRepositorio $pagoRepo;

    public function __construct(
        ?PagoServicio $pagoServicio = null,
        ?PagoRepositorio $pagoRepo = null
    ) {
        $this->pagoServicio = $pagoServicio ?? new PagoServicio();
        $this->pagoRepo = $pagoRepo ?? new PagoRepositorio();
    }

    /**
     * Registra un pago y ejecuta la imputacion automatica sobre las cuotas mas antiguas (Casos de uso Registrar pago y Pagar cuota).
     * Ruta: POST /api/pagos
     */
    public function registrar(Peticion $peticion): void
    {
        Sesion::iniciar();
        $rol = Sesion::obtener('rol_nombre');
        $usuarioId = (int)Sesion::obtener('usuario_id');

        $reglas = [
            'monto_total' => 'requerido|positivo',
            'medio_pago' => 'requerido|enum:EFECTIVO,TRANSFERENCIA,TARJETA_SIMULADA'
        ];

        // Si es TESORERO, debe indicar el socio_id
        if ($rol !== 'SOCIO') {
            $reglas['socio_id'] = 'requerido|entero|positivo';
        }

        $datos = Validador::validar($peticion->getCuerpo(), $reglas);

        // Si es SOCIO, opera exclusivamente sobre si mismo (Autogestion)
        if ($rol === 'SOCIO') {
            $socioId = (int)Sesion::obtener('socio_id');
            $autogestionado = true;
        } else {
            $socioId = (int)$datos['socio_id'];
            $autogestionado = false;
        }

        $observaciones = $peticion->obtener('observaciones');

        $resultado = $this->pagoServicio->registrarPagoEImputar(
            $socioId,
            $usuarioId,
            (float)$datos['monto_total'],
            $datos['medio_pago'],
            $autogestionado,
            $observaciones
        );

        Respuesta::exito($resultado, 201);
    }

    /**
     * Obtiene el detalle de un pago con sus imputaciones.
     * Ruta: GET /api/pagos/{id}
     */
    public function obtener(Peticion $peticion, array $parametros): void
    {
        $pagoId = (int)($parametros['id'] ?? 0);
        $pago = $this->pagoRepo->buscarPorId($pagoId);

        if (!$pago) {
            throw new NoEncontradoException('El pago solicitado no existe.');
        }

        // Si es socio, verificar pertenencia
        Sesion::iniciar();
        if (Sesion::obtener('rol_nombre') === 'SOCIO') {
            $miSocioId = (int)Sesion::obtener('socio_id');
            if ((int)$pago['socio_id'] !== $miSocioId) {
                throw new ProhibidoException('No tiene autorizacion para ver pagos de otro socio.');
            }
        }

        Respuesta::exito($pago, 200);
    }

    /**
     * Lista los pagos de un socio (Caso de uso Historial de pagos).
     * Ruta: GET /api/socios/{id}/pagos
     */
    public function listarPorSocio(Peticion $peticion, array $parametros): void
    {
        $socioId = (int)($parametros['id'] ?? 0);

        Sesion::iniciar();
        if (Sesion::obtener('rol_nombre') === 'SOCIO') {
            $miSocioId = (int)Sesion::obtener('socio_id');
            if ($socioId !== $miSocioId) {
                throw new ProhibidoException('No tiene autorizacion para ver el historial de otro socio.');
            }
        }

        $pagos = $this->pagoRepo->listarPorSocio($socioId);
        Respuesta::exito($pagos, 200);
    }
}
