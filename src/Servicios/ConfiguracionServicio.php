<?php
namespace Servicios;

use Repositorios\ConfiguracionRepositorio;
use Core\Validador;
use Excepciones\ReglaNegocioException;

/**
 * Servicio de Gestion de Configuracion Institucional y Apariencia.
 * Capa: Servicios de dominio.
 */
class ConfiguracionServicio
{
    private ConfiguracionRepositorio $configRepo;

    public function __construct(?ConfiguracionRepositorio $configRepo = null)
    {
        $this->configRepo = $configRepo ?? new ConfiguracionRepositorio();
    }

    /**
     * Obtiene la configuracion institucional activa para el tema visual y parametros.
     */
    public function obtenerConfiguracion(): array
    {
        $config = $this->configRepo->obtener();
        return $config->aArreglo();
    }

    /**
     * Actualiza la paleta de colores y el nombre del club (Caso de uso Apariencia del club).
     * Valida que cada color sea un hexadecimal valido (#RRGGBB).
     */
    public function actualizarApariencia(array $datos): array
    {
        Validador::validar($datos, [
            'nombre_club' => 'requerido|min:3|max:150',
            'color_primario' => 'requerido|color_hex',
            'color_primario_oscuro' => 'requerido|color_hex',
            'color_secundario' => 'requerido|color_hex',
            'color_acento' => 'requerido|color_hex',
            'color_fondo' => 'requerido|color_hex',
            'color_texto' => 'requerido|color_hex'
        ]);

        $exito = $this->configRepo->actualizarApariencia(
            trim($datos['nombre_club']),
            strtoupper(trim($datos['color_primario'])),
            strtoupper(trim($datos['color_primario_oscuro'])),
            strtoupper(trim($datos['color_secundario'])),
            strtoupper(trim($datos['color_acento'])),
            strtoupper(trim($datos['color_fondo'])),
            strtoupper(trim($datos['color_texto']))
        );

        if (!$exito) {
            throw new ReglaNegocioException('No fue posible actualizar la apariencia del club.');
        }

        return $this->obtenerConfiguracion();
    }

    /**
     * Actualiza los parametros economicos y de vencimiento (Caso de uso Gestionar precios).
     */
    public function actualizarParametrosEconomicos(array $datos): array
    {
        Validador::validar($datos, [
            'cuotas_para_moroso' => 'requerido|entero|min:1|max:12',
            'porcentaje_recargo' => 'requerido|no_negativo|max:100',
            'porcentaje_descuento_familiar' => 'requerido|no_negativo|max:100',
            'dias_vencimiento' => 'requerido|entero|min:1|max:28'
        ]);

        $exito = $this->configRepo->actualizarParametrosEconomicos(
            (int)$datos['cuotas_para_moroso'],
            (float)$datos['porcentaje_recargo'],
            (float)$datos['porcentaje_descuento_familiar'],
            (int)$datos['dias_vencimiento']
        );

        if (!$exito) {
            throw new ReglaNegocioException('No fue posible actualizar los parametros economicos.');
        }

        return $this->obtenerConfiguracion();
    }
}
