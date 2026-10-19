<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Sesion;
use Repositorios\ReciboRepositorio;
use Repositorios\ConfiguracionRepositorio;
use Excepciones\ProhibidoException;
use Excepciones\NoEncontradoException;

/**
 * Controlador para la consulta y emision visual de recibos (Caso de uso Emitir recibo).
 * Capa: Controladores.
 */
class ReciboControlador
{
    private ReciboRepositorio $reciboRepo;
    private ConfiguracionRepositorio $configRepo;

    public function __construct(
        ?ReciboRepositorio $reciboRepo = null,
        ?ConfiguracionRepositorio $configRepo = null
    ) {
        $this->reciboRepo = $reciboRepo ?? new ReciboRepositorio();
        $this->configRepo = $configRepo ?? new ConfiguracionRepositorio();
    }

    /**
     * Obtiene el recibo consolidado con membrete institucional para impresion (Caso de uso Emitir recibo).
     * Ruta: GET /api/recibos/{id}
     */
    public function obtener(Peticion $peticion, array $parametros): void
    {
        $reciboId = (int)($parametros['id'] ?? 0);
        $recibo = $this->reciboRepo->buscarPorId($reciboId);

        if (!$recibo) {
            throw new NoEncontradoException('El recibo solicitado no existe.');
        }

        // Si es socio, verificar pertenencia
        Sesion::iniciar();
        if (Sesion::obtener('rol_nombre') === 'SOCIO') {
            $miSocioId = (int)Sesion::obtener('socio_id');
            if ((int)$recibo['socio_id'] !== $miSocioId) {
                throw new ProhibidoException('No tiene autorizacion para acceder a este recibo.');
            }
        }

        $config = $this->configRepo->obtener();
        $recibo['club'] = [
            'nombre' => $config->nombreClub,
            'color_primario' => $config->colorPrimario
        ];

        Respuesta::exito($recibo, 200);
    }
}
