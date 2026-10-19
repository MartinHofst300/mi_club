<?php
namespace Servicios;

use Repositorios\ConfiguracionRepositorio;
use Repositorios\CategoriaRepositorio;
use Excepciones\ValidacionException;

/**
 * Servicio para la Gestion de Precios y Parametros Economicos (Caso de uso Administrador).
 * Capa: Servicios de dominio.
 */
class PrecioServicio
{
    private ConfiguracionRepositorio $configRepo;
    private CategoriaRepositorio $categoriaRepo;

    public function __construct(
        ?ConfiguracionRepositorio $configRepo = null,
        ?CategoriaRepositorio $categoriaRepo = null
    ) {
        $this->configRepo = $configRepo ?? new ConfiguracionRepositorio();
        $this->categoriaRepo = $categoriaRepo ?? new CategoriaRepositorio();
    }

    /**
     * Obtiene los precios actuales de las categorias y los parametros economicos globales.
     */
    public function obtenerPreciosYParametros(): array
    {
        $config = $this->configRepo->obtener();
        $categorias = $this->categoriaRepo->listarTodas(false);

        return [
            'parametros' => [
                'porcentaje_recargo' => (float)$config->porcentajeRecargo,
                'porcentaje_descuento_familiar' => (float)$config->porcentajeDescuentoFamiliar,
                'cuotas_para_moroso' => (int)$config->cuotasParaMoroso,
                'dias_vencimiento' => (int)$config->diasVencimiento,
                'actualizado_en' => $config->actualizadoEn
            ],
            'categorias' => $categorias
        ];
    }

    /**
     * Actualiza los precios de las categorias y los parametros economicos.
     * Cambiar estos valores no altera las cuotas ya emitidas.
     */
    public function actualizar(array $datos): array
    {
        $recargo = (float)($datos['porcentaje_recargo'] ?? 10.0);
        $descuentoFamiliar = (float)($datos['porcentaje_descuento_familiar'] ?? 15.0);
        $cuotasMoroso = (int)($datos['cuotas_para_moroso'] ?? 3);
        $diasVencimiento = (int)($datos['dias_vencimiento'] ?? 10);

        if ($recargo < 0.0 || $recargo > 100.0) {
            throw new ValidacionException('El porcentaje de recargo por mora debe estar comprendido entre 0% y 100%.');
        }

        if ($descuentoFamiliar < 0.0 || $descuentoFamiliar > 100.0) {
            throw new ValidacionException('El porcentaje de descuento familiar debe estar comprendido entre 0% y 100%.');
        }

        if ($cuotasMoroso < 1) {
            throw new ValidacionException('La cantidad de cuotas impagas para considerar moroso debe ser al menos 1.');
        }

        if ($diasVencimiento < 1 || $diasVencimiento > 28) {
            throw new ValidacionException('El dia de vencimiento mensual debe estar comprendido entre el 1 y el 28.');
        }

        // Actualizar parametros economicos en configuracion
        $this->configRepo->actualizarParametrosEconomicos(
            $cuotasMoroso,
            $recargo,
            $descuentoFamiliar,
            $diasVencimiento
        );

        // Actualizar precios individuales de categorias si se enviaron
        $preciosCategorias = $datos['precios_categorias'] ?? [];
        $actualizadas = 0;

        foreach ($preciosCategorias as $catItem) {
            if (!isset($catItem['id']) || !isset($catItem['precio_cuota'])) {
                continue;
            }
            $catId = (int)$catItem['id'];
            $precio = (float)$catItem['precio_cuota'];

            if ($precio < 0.0) {
                throw new ValidacionException("El precio para la categoria ID {$catId} no puede ser negativo.");
            }

            $this->categoriaRepo->actualizarPrecio($catId, $precio);
            $actualizadas++;
        }

        return [
            'cuotas_para_moroso' => $cuotasMoroso,
            'porcentaje_recargo' => $recargo,
            'porcentaje_descuento_familiar' => $descuentoFamiliar,
            'dias_vencimiento' => $diasVencimiento,
            'categorias_actualizadas' => $actualizadas,
            'mensaje' => 'Estructura tarifaria y parametros economicos actualizados exitosamente.'
        ];
    }
}
