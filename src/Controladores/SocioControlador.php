<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Sesion;
use Repositorios\SocioRepositorio;
use Repositorios\CuotaRepositorio;
use Repositorios\PagoRepositorio;
use Repositorios\ConfiguracionRepositorio;
use Servicios\CuotaServicio;
use Servicios\EstadoSocioServicio;
use Excepciones\ProhibidoException;
use Excepciones\NoEncontradoException;

/**
 * Controlador de gestion y consulta de socios.
 * Capa: Controladores.
 */
class SocioControlador
{
    private SocioRepositorio $socioRepo;
    private CuotaRepositorio $cuotaRepo;
    private PagoRepositorio $pagoRepo;
    private CuotaServicio $cuotaServicio;
    private EstadoSocioServicio $estadoSocioServicio;
    private ConfiguracionRepositorio $configRepo;

    public function __construct()
    {
        $this->socioRepo = new SocioRepositorio();
        $this->cuotaRepo = new CuotaRepositorio();
        $this->pagoRepo = new PagoRepositorio();
        $this->configRepo = new ConfiguracionRepositorio();
        $this->estadoSocioServicio = new EstadoSocioServicio($this->socioRepo, $this->cuotaRepo, $this->configRepo);
        $this->cuotaServicio = new CuotaServicio($this->cuotaRepo, $this->socioRepo, $this->configRepo, $this->estadoSocioServicio);
    }

    /**
     * Busca socios por nombre, apellido, DNI o numero de socio.
     * Ruta: GET /api/socios
     */
    public function buscar(Peticion $peticion): void
    {
        $termino = (string)$peticion->consulta('q', '');
        $estado = $peticion->consulta('estado', null);

        $socios = $this->socioRepo->buscar($termino, $estado);
        Respuesta::exito($socios, 200);
    }

    /**
     * Obtiene el detalle de un socio.
     * Ruta: GET /api/socios/{id}
     */
    public function obtener(Peticion $peticion, array $parametros): void
    {
        $socioId = (int)($parametros['id'] ?? 0);
        $this->validarPropiedadSocio($socioId);

        $socio = $this->socioRepo->obtenerDetalleCompleto($socioId);
        if (!$socio) {
            throw new NoEncontradoException('El socio no existe.');
        }

        Respuesta::exito($socio, 200);
    }

    /**
     * Obtiene el estado de cuenta y deuda del socio (Casos de uso Ver estado de cuenta y Registrar pago).
     * Ruta: GET /api/socios/{id}/estado-cuenta
     */
    public function estadoCuenta(Peticion $peticion, array $parametros): void
    {
        $socioId = (int)($parametros['id'] ?? 0);
        $this->validarPropiedadSocio($socioId);

        $socio = $this->socioRepo->buscarPorId($socioId);
        if (!$socio) {
            throw new NoEncontradoException('El socio no existe.');
        }

        // Actualizar cuotas vencidas y recalcular estado del socio
        $this->cuotaServicio->actualizarEstadosPorVencimiento();
        $estadoActual = $this->estadoSocioServicio->recalcularEstado($socioId);

        $cuotas = $this->cuotaRepo->listarPorSocio($socioId);
        $pagos = $this->pagoRepo->listarPorSocio($socioId);

        $deudaTotal = 0.0;
        $cuotasImpagas = 0;
        foreach ($cuotas as $c) {
            if ($c['estado'] !== 'PAGADA' && $c['estado'] !== 'ANULADA' && (float)$c['saldo'] > 0) {
                $deudaTotal += (float)$c['saldo'];
                $cuotasImpagas++;
            }
        }

        $datos = [
            'socio' => [
                'id' => $socio->id,
                'numero_socio' => $socio->numeroSocio,
                'nombre' => $socio->nombre,
                'apellido' => $socio->apellido,
                'dni' => $socio->dni,
                'estado' => $estadoActual
            ],
            'resumen' => [
                'total_adeudado' => round($deudaTotal, 2),
                'cuotas_impagas' => $cuotasImpagas
            ],
            'cuotas' => $cuotas,
            'pagos' => $pagos
        ];

        Respuesta::exito($datos, 200);
    }

    /**
     * Consulta el listado de socios morosos (Caso de uso Consultar morosos).
     * Ruta: GET /api/socios/morosos
     */
    public function morosos(Peticion $peticion): void
    {
        $this->cuotaServicio->actualizarEstadosPorVencimiento();
        $config = $this->configRepo->obtener();
        $hoy = date('Y-m-d');

        $morosos = $this->cuotaRepo->consultarMorosos($config->cuotasParaMoroso, $hoy);
        Respuesta::exito($morosos, 200);
    }

    /**
     * Devuelve los datos para el carnet del socio (Caso de uso Consultar carnet, Regla R11).
     * Ruta: GET /api/socios/{id}/carnet
     */
    public function carnet(Peticion $peticion, array $parametros): void
    {
        $socioId = (int)($parametros['id'] ?? 0);
        $this->validarPropiedadSocio($socioId);

        $socio = $this->socioRepo->obtenerDetalleCompleto($socioId);
        if (!$socio) {
            throw new NoEncontradoException('El socio no existe.');
        }

        // Regla R11: Vigente solo si el estado es ACTIVO
        $vigente = ($socio['estado'] === 'ACTIVO');
        $motivoNoVigente = null;

        if (!$vigente) {
            switch ($socio['estado']) {
                case 'MOROSO':
                    $motivoNoVigente = 'Registra cuotas sociales impagas vencidas.';
                    break;
                case 'SUSPENDIDO':
                    $motivoNoVigente = 'Socio suspendido administrativamente por la Comision Directiva.';
                    break;
                case 'BAJA':
                    $motivoNoVigente = 'Socio dado de baja en la institucion.';
                    break;
            }
        }

        $carnet = [
            'id' => $socio['id'],
            'numero_socio' => $socio['numero_socio'],
            'nombre' => $socio['nombre'],
            'apellido' => $socio['apellido'],
            'dni' => $socio['dni'],
            'categoria' => $socio['categoria_nombre'],
            'divisiones' => $socio['divisiones'],
            'estado' => $socio['estado'],
            'vigente' => $vigente,
            'motivo_no_vigente' => $motivoNoVigente,
            'fecha_alta' => $socio['fecha_alta']
        ];

        Respuesta::exito($carnet, 200);
    }

    /**
     * Actualiza los datos de un socio.
     * Si es SOCIO, edita unicamente sus datos de contacto y contrasena.
     * Si es ADMINISTRADOR, actualiza la ficha completa (incluyendo estado, categoria y grupo familiar).
     * Ruta: PUT /api/socios/{id}
     */
    public function editarMisDatos(Peticion $peticion, array $parametros): void
    {
        $socioId = (int)($parametros['id'] ?? 0);
        Sesion::iniciar();
        $rol = Sesion::obtener('rol_nombre');

        $socioServicio = new \Servicios\SocioServicio();

        if ($rol === 'ADMINISTRADOR') {
            $resultado = $socioServicio->actualizarAdministrativo($socioId, $peticion->getCuerpo());
        } else {
            $this->validarPropiedadSocio($socioId);
            $resultado = $socioServicio->editarMisDatos($socioId, $peticion->getCuerpo());
        }

        Respuesta::exito($resultado, 200);
    }

    /**
     * Da de alta un nuevo socio desde la administracion.
     * Ruta: POST /api/socios
     */
    public function crear(Peticion $peticion): void
    {
        $socioServicio = new \Servicios\SocioServicio();
        $resultado = $socioServicio->crearAdministrativo($peticion->getCuerpo());
        Respuesta::exito($resultado, 201);
    }

    /**
     * Lista los grupos familiares con sus integrantes.
     * Ruta: GET /api/grupos-familiares
     */
    public function listarGruposFamiliares(Peticion $peticion): void
    {
        $socioServicio = new \Servicios\SocioServicio();
        $grupos = $socioServicio->listarGruposFamiliares();
        Respuesta::exito($grupos, 200);
    }

    /**
     * Crea un nuevo grupo familiar.
     * Ruta: POST /api/grupos-familiares
     */
    public function crearGrupoFamiliar(Peticion $peticion): void
    {
        $cuerpo = $peticion->getCuerpo();
        $socioServicio = new \Servicios\SocioServicio();
        $resultado = $socioServicio->crearGrupoFamiliar($cuerpo['nombre'] ?? '');
        Respuesta::exito($resultado, 201);
    }

    /**
     * Lista los integrantes de un grupo familiar.
     * Ruta: GET /api/grupos-familiares/{id}/integrantes
     */
    public function integrantesGrupoFamiliar(Peticion $peticion, array $parametros): void
    {
        $grupoId = (int)($parametros['id'] ?? 0);
        $socioServicio = new \Servicios\SocioServicio();
        $integrantes = $socioServicio->obtenerIntegrantesGrupoFamiliar($grupoId);
        Respuesta::exito($integrantes, 200);
    }

    /**
     * Asigna o remueve a un socio de un grupo familiar.
     * Ruta: POST /api/socios/{id}/grupo-familiar
     */
    public function asignarGrupoFamiliar(Peticion $peticion, array $parametros): void
    {
        $socioId = (int)($parametros['id'] ?? 0);
        $cuerpo = $peticion->getCuerpo();
        $grupoId = array_key_exists('grupo_familiar_id', $cuerpo) ? ($cuerpo['grupo_familiar_id'] ? (int)$cuerpo['grupo_familiar_id'] : null) : null;

        $socioServicio = new \Servicios\SocioServicio();
        $resultado = $socioServicio->asignarGrupoFamiliar($socioId, $grupoId);
        Respuesta::exito($resultado, 200);
    }

    /**
     * Asigna un socio como integrante de un grupo familiar.
     * Ruta: POST /api/grupos-familiares/{id}/integrantes
     */
    public function asignarIntegranteAGrupo(Peticion $peticion, array $parametros): void
    {
        $grupoId = (int)($parametros['id'] ?? 0);
        $cuerpo = $peticion->getCuerpo();
        $socioId = (int)($cuerpo['socio_id'] ?? 0);

        $socioServicio = new \Servicios\SocioServicio();
        $resultado = $socioServicio->asignarGrupoFamiliar($socioId, $grupoId);
        Respuesta::exito($resultado, 200);
    }

    /**
     * Lista las sesiones de entrenamiento del socio (Caso de uso Ver entrenamientos).
     * Ruta: GET /api/socios/{id}/entrenamientos
     */
    public function entrenamientos(Peticion $peticion, array $parametros): void
    {
        $socioId = (int)($parametros['id'] ?? 0);
        $this->validarPropiedadSocio($socioId);

        $socioServicio = new \Servicios\SocioServicio();
        $resultado = $socioServicio->obtenerEntrenamientosSocio($socioId);

        Respuesta::exito($resultado, 200);
    }

    /**
     * Valida que un socio autenticado solo pueda consultar sus propios datos.
     */
    private function validarPropiedadSocio(int $socioId): void
    {
        Sesion::iniciar();
        $rol = Sesion::obtener('rol_nombre');

        if ($rol === 'SOCIO') {
            $miSocioId = (int)Sesion::obtener('socio_id');
            if ($miSocioId !== $socioId) {
                throw new ProhibidoException('Solo puede consultar su propia informacion de socio.');
            }
        }
    }
}

