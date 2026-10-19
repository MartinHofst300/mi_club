<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Configuracion;

/**
 * Repositorio de acceso a datos para la configuracion institucional y tema visual de MiClub.
 * Capa: Acceso a datos.
 */
class ConfiguracionRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Obtiene la configuracion institucional activa (fila unica con id = 1).
     */
    public function obtener(): Configuracion
    {
        $sql = "SELECT id, nombre_club, color_primario, color_primario_oscuro, color_secundario,
                       color_acento, color_fondo, color_texto, cuotas_para_moroso,
                       porcentaje_recargo, porcentaje_descuento_familiar, dias_vencimiento, actualizado_en
                FROM configuracion 
                WHERE id = 1 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Configuracion::desdeArreglo($fila) : new Configuracion();
    }

    /**
     * Actualiza los colores y el nombre del club (Caso de uso Apariencia del club).
     */
    public function actualizarApariencia(
        string $nombreClub,
        string $colorPrimario,
        string $colorPrimarioOscuro,
        string $colorSecundario,
        string $colorAcento,
        string $colorFondo,
        string $colorTexto
    ): bool {
        $sql = "UPDATE configuracion SET 
                    nombre_club = :nombre,
                    color_primario = :c_primario,
                    color_primario_oscuro = :c_oscuro,
                    color_secundario = :c_secundario,
                    color_acento = :c_acento,
                    color_fondo = :c_fondo,
                    color_texto = :c_texto,
                    actualizado_en = NOW()
                WHERE id = 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':nombre', $nombreClub, PDO::PARAM_STR);
        $sentencia->bindValue(':c_primario', $colorPrimario, PDO::PARAM_STR);
        $sentencia->bindValue(':c_oscuro', $colorPrimarioOscuro, PDO::PARAM_STR);
        $sentencia->bindValue(':c_secundario', $colorSecundario, PDO::PARAM_STR);
        $sentencia->bindValue(':c_acento', $colorAcento, PDO::PARAM_STR);
        $sentencia->bindValue(':c_fondo', $colorFondo, PDO::PARAM_STR);
        $sentencia->bindValue(':c_texto', $colorTexto, PDO::PARAM_STR);
        return $sentencia->execute();
    }

    /**
     * Actualiza los parametros economicos y de vencimiento (Caso de uso Gestionar precios).
     */
    public function actualizarParametrosEconomicos(
        int $cuotasParaMoroso,
        float $porcentajeRecargo,
        float $porcentajeDescuentoFamiliar,
        int $diasVencimiento
    ): bool {
        $sql = "UPDATE configuracion SET 
                    cuotas_para_moroso = :moroso,
                    porcentaje_recargo = :recargo,
                    porcentaje_descuento_familiar = :desc_fam,
                    dias_vencimiento = :vencimiento,
                    actualizado_en = NOW()
                WHERE id = 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':moroso', $cuotasParaMoroso, PDO::PARAM_INT);
        $sentencia->bindValue(':recargo', $porcentajeRecargo);
        $sentencia->bindValue(':desc_fam', $porcentajeDescuentoFamiliar);
        $sentencia->bindValue(':vencimiento', $diasVencimiento, PDO::PARAM_INT);
        return $sentencia->execute();
    }
}
