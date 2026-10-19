<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Cuota;

/**
 * Repositorio de acceso a datos para la entidad Cuota.
 * Capa: Acceso a datos.
 *
 * Ejecuta consultas preparadas vinculadas a la persistencia de cuotas, saldos y estados.
 */
class CuotaRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Busca una cuota por su ID primario.
     */
    public function buscarPorId(int $id): ?Cuota
    {
        $sql = "SELECT id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
                       monto_final, monto_pagado, fecha_vencimiento, estado,
                       fecha_anulacion, motivo_anulacion, creado_en
                FROM cuotas 
                WHERE id = :id 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Cuota::desdeArreglo($fila) : null;
    }

    /**
     * Verifica si ya existe una cuota generada para un socio y periodo determinado (Regla R6).
     */
    public function existeCuotaSocioPeriodo(int $socioId, string $periodo): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM cuotas WHERE socio_id = :socio_id AND periodo = :periodo";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':periodo', $periodo, PDO::PARAM_STR);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return ((int)($fila['total'] ?? 0)) > 0;
    }

    /**
     * Inserta una nueva cuota en la base de datos (Regla R6).
     */
    public function crear(Cuota $cuota): int
    {
        $sql = "INSERT INTO cuotas (
                    socio_id, periodo, monto_base, monto_descuento, monto_recargo,
                    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
                ) VALUES (
                    :socio_id, :periodo, :monto_base, :monto_descuento, :monto_recargo,
                    :monto_final, :monto_pagado, :fecha_vencimiento, :estado, NOW()
                )";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $cuota->socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':periodo', $cuota->periodo, PDO::PARAM_STR);
        $sentencia->bindValue(':monto_base', $cuota->montoBase);
        $sentencia->bindValue(':monto_descuento', $cuota->montoDescuento);
        $sentencia->bindValue(':monto_recargo', $cuota->montoRecargo);
        $sentencia->bindValue(':monto_final', $cuota->montoFinal);
        $sentencia->bindValue(':monto_pagado', $cuota->montoPagado);
        $sentencia->bindValue(':fecha_vencimiento', $cuota->fechaVencimiento, PDO::PARAM_STR);
        $sentencia->bindValue(':estado', $cuota->estado, PDO::PARAM_STR);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Obtiene las cuotas con saldo pendiente de pago para un socio, ordenadas por periodo cronologico ascendente (Regla R7).
     *
     * @return Cuota[]
     */
    public function buscarCuotasImpagasPorSocio(int $socioId): array
    {
        $sql = "SELECT id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
                       monto_final, monto_pagado, fecha_vencimiento, estado,
                       fecha_anulacion, motivo_anulacion, creado_en
                FROM cuotas
                WHERE socio_id = :socio_id 
                  AND estado NOT IN ('PAGADA', 'ANULADA')
                  AND (monto_final - monto_pagado) > 0.00
                ORDER BY periodo ASC, id ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->execute();

        $filas = $sentencia->fetchAll();
        return array_map(fn($f) => Cuota::desdeArreglo($f), $filas);
    }

    /**
     * Actualiza el monto pagado acumulado y el estado resultante de una cuota (Regla R2 y R7).
     */
    public function actualizarPagoCuota(int $cuotaId, float $montoPagadoAcumulado, string $nuevoEstado): bool
    {
        $sql = "UPDATE cuotas 
                SET monto_pagado = :monto_pagado, estado = :estado 
                WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':monto_pagado', $montoPagadoAcumulado);
        $sentencia->bindValue(':estado', $nuevoEstado, PDO::PARAM_STR);
        $sentencia->bindValue(':id', $cuotaId, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Cuenta cuantas cuotas impagas vencidas posee un socio para evaluar mora (Regla R1).
     * Criterio: estado PENDIENTE o VENCIDA con fecha de vencimiento menor a hoy y saldo adeudado > 0.
     */
    public function contarCuotasImpagasVencidas(int $socioId, string $fechaCorte): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM cuotas
                WHERE socio_id = :socio_id
                  AND estado IN ('PENDIENTE', 'VENCIDA')
                  AND fecha_vencimiento < :fecha_corte
                  AND (monto_final - monto_pagado) > 0.00";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':fecha_corte', $fechaCorte, PDO::PARAM_STR);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return (int)($fila['total'] ?? 0);
    }

    /**
     * Actualiza masivamente a estado VENCIDA las cuotas cuya fecha ya paso y aun tienen saldo (Regla R2).
     */
    public function actualizarCuotasVencidas(string $fechaCorte): int
    {
        $sql = "UPDATE cuotas 
                SET estado = 'VENCIDA' 
                WHERE estado = 'PENDIENTE' 
                  AND fecha_vencimiento < :fecha_corte 
                  AND (monto_final - monto_pagado) > 0.00";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':fecha_corte', $fechaCorte, PDO::PARAM_STR);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    /**
     * Busca cuotas vencidas que aun no tengan recargo aplicado (monto_recargo = 0) para aplicar recargo (Regla R4).
     */
    public function buscarVencidasSinRecargo(string $fechaCorte): array
    {
        $sql = "SELECT id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
                       monto_final, monto_pagado, fecha_vencimiento, estado,
                       fecha_anulacion, motivo_anulacion, creado_en
                FROM cuotas
                WHERE fecha_vencimiento < :fecha_corte
                  AND estado IN ('PENDIENTE', 'VENCIDA')
                  AND monto_recargo = 0.00
                  AND (monto_final - monto_pagado) > 0.00";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':fecha_corte', $fechaCorte, PDO::PARAM_STR);
        $sentencia->execute();

        $filas = $sentencia->fetchAll();
        return array_map(fn($f) => Cuota::desdeArreglo($f), $filas);
    }

    /**
     * Aplica el recargo por mora actualizando el monto final (Regla R4).
     */
    public function aplicarRecargo(int $cuotaId, float $montoRecargo, float $nuevoMontoFinal): bool
    {
        $sql = "UPDATE cuotas 
                SET monto_recargo = :recargo, monto_final = :monto_final 
                WHERE id = :id AND monto_recargo = 0.00";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':recargo', $montoRecargo);
        $sentencia->bindValue(':monto_final', $nuevoMontoFinal);
        $sentencia->bindValue(':id', $cuotaId, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Anula una cuota registrando fecha y motivo (Regla R2).
     */
    public function anular(int $cuotaId, string $motivo): bool
    {
        $sql = "UPDATE cuotas 
                SET estado = 'ANULADA', fecha_anulacion = NOW(), motivo_anulacion = :motivo 
                WHERE id = :id AND estado != 'PAGADA'";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':motivo', $motivo, PDO::PARAM_STR);
        $sentencia->bindValue(':id', $cuotaId, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Obtiene el listado de cuotas de un socio para su estado de cuenta.
     */
    public function listarPorSocio(int $socioId): array
    {
        $sql = "SELECT id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
                       monto_final, monto_pagado, (monto_final - monto_pagado) AS saldo,
                       fecha_vencimiento, estado, fecha_anulacion, motivo_anulacion, creado_en
                FROM cuotas
                WHERE socio_id = :socio_id
                ORDER BY periodo DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Consulta el listado de socios morosos con detalle de deuda acumulada.
     */
    public function consultarMorosos(int $cuotasParaMoroso, string $fechaCorte): array
    {
        $sql = "SELECT s.id, s.id AS socio_id, s.numero_socio, s.nombre, s.apellido, s.dni, s.email, s.telefono, s.estado,
                       c_cat.nombre AS categoria_nombre,
                       COUNT(c.id) AS cuotas_impagas,
                       SUM(c.monto_final - c.monto_pagado) AS total_adeudado,
                       SUM(c.monto_final - c.monto_pagado) AS deuda_total
                FROM socios s
                LEFT JOIN categorias c_cat ON s.categoria_id = c_cat.id
                JOIN cuotas c ON s.id = c.socio_id
                WHERE c.estado IN ('PENDIENTE', 'VENCIDA')
                  AND c.fecha_vencimiento < :fecha_corte
                  AND (c.monto_final - c.monto_pagado) > 0.00
                GROUP BY s.id, c_cat.nombre
                HAVING cuotas_impagas >= :limite
                ORDER BY total_adeudado DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':fecha_corte', $fechaCorte, PDO::PARAM_STR);
        $sentencia->bindValue(':limite', $cuotasParaMoroso, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }
}
