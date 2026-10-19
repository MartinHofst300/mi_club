<?php
namespace Modelos;

/**
 * Entidad Configuracion. Agregada por diseno de persistencia para parametros institucionales y tema visual.
 * Capa: Modelos del dominio.
 */
class Configuracion
{
    public int $id;
    public string $nombreClub;
    public string $colorPrimario;
    public string $colorPrimarioOscuro;
    public string $colorSecundario;
    public string $colorAcento;
    public string $colorFondo;
    public string $colorTexto;
    public int $cuotasParaMoroso;
    public float $porcentajeRecargo;
    public float $porcentajeDescuentoFamiliar;
    public int $diasVencimiento;
    public ?string $actualizadoEn;

    public function __construct(
        int $id = 1,
        string $nombreClub = 'Club Social y Deportivo MiClub',
        string $colorPrimario = '#1F5C3B',
        string $colorPrimarioOscuro = '#14402A',
        string $colorSecundario = '#E4EFE7',
        string $colorAcento = '#C98A1B',
        string $colorFondo = '#F6F4EC',
        string $colorTexto = '#1E2A24',
        int $cuotasParaMoroso = 3,
        float $porcentajeRecargo = 10.0,
        float $porcentajeDescuentoFamiliar = 15.0,
        int $diasVencimiento = 10,
        ?string $actualizadoEn = null
    ) {
        $this->id = $id;
        $this->nombreClub = $nombreClub;
        $this->colorPrimario = $colorPrimario;
        $this->colorPrimarioOscuro = $colorPrimarioOscuro;
        $this->colorSecundario = $colorSecundario;
        $this->colorAcento = $colorAcento;
        $this->colorFondo = $colorFondo;
        $this->colorTexto = $colorTexto;
        $this->cuotasParaMoroso = $cuotasParaMoroso;
        $this->porcentajeRecargo = $porcentajeRecargo;
        $this->porcentajeDescuentoFamiliar = $porcentajeDescuentoFamiliar;
        $this->diasVencimiento = $diasVencimiento;
        $this->actualizadoEn = $actualizadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            (int)($datos['id'] ?? 1),
            $datos['nombre_club'] ?? 'Club Social y Deportivo MiClub',
            $datos['color_primario'] ?? '#1F5C3B',
            $datos['color_primario_oscuro'] ?? '#14402A',
            $datos['color_secundario'] ?? '#E4EFE7',
            $datos['color_acento'] ?? '#C98A1B',
            $datos['color_fondo'] ?? '#F6F4EC',
            $datos['color_texto'] ?? '#1E2A24',
            (int)($datos['cuotas_para_moroso'] ?? 3),
            (float)($datos['porcentaje_recargo'] ?? 10.0),
            (float)($datos['porcentaje_descuento_familiar'] ?? 15.0),
            (int)($datos['dias_vencimiento'] ?? 10),
            $datos['actualizado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'nombre_club' => $this->nombreClub,
            'color_primario' => $this->colorPrimario,
            'color_primario_oscuro' => $this->colorPrimarioOscuro,
            'color_secundario' => $this->colorSecundario,
            'color_acento' => $this->colorAcento,
            'color_fondo' => $this->colorFondo,
            'color_texto' => $this->colorTexto,
            'cuotas_para_moroso' => $this->cuotasParaMoroso,
            'porcentaje_recargo' => $this->porcentajeRecargo,
            'porcentaje_descuento_familiar' => $this->porcentajeDescuentoFamiliar,
            'dias_vencimiento' => $this->diasVencimiento,
            'actualizado_en' => $this->actualizadoEn
        ];
    }
}
