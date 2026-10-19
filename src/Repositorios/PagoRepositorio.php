<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Pago;
use Modelos\Imputacion;

/**
 * Repositorio de acceso a datos para pagos e imputaciones asociadas (Regla R7).
 * Capa: Acceso a datos.
 */
class PagoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Registra una cabecera de pago en la base de datos.
     */
    public function crear(Pago $pago): int
    {
        $sql = "INSERT INTO pagos (socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones)
                VALUES (:socio_id, :usuario_id, :monto_total, :medio_pago, :autogestionado, :fecha_pago, :observaciones)";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $pago->socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':usuario_id', $pago->usuarioId, $pago->usuarioId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sentencia->bindValue(':monto_total', $pago->montoTotal);
        $sentencia->bindValue(':medio_pago', $pago->medioPago, PDO::PARAM_STR);
        $sentencia->bindValue(':autogestionado', $pago->autogestionado ? 1 : 0, PDO::PARAM_INT);
        $sentencia->bindValue(':fecha_pago', $pago->fechaPago, PDO::PARAM_STR);
        $sentencia->bindValue(':observaciones', $pago->observaciones, PDO::PARAM_STR);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Registra una imputacion de pago a una cuota especifica (Regla R7).
     */
    public function crearImputacion(Imputacion $imputacion): int
    {
        $sql = "INSERT INTO imputaciones (pago_id, cuota_id, monto_imputado, creado_en)
                VALUES (:pago_id, :cuota_id, :monto_imputado, NOW())";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':pago_id', $imputacion->pagoId, PDO::PARAM_INT);
        $sentencia->bindValue(':cuota_id', $imputacion->cuotaId, PDO::PARAM_INT);
        $sentencia->bindValue(':monto_imputado', $imputacion->montoImputado);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Obtiene el detalle completo de un pago junto con sus imputaciones y recibo emitido.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT p.id, p.socio_id, p.usuario_id, p.monto_total, p.medio_pago,
                       p.autogestionado, p.fecha_pago, p.observaciones,
                       s.numero_socio, s.nombre AS socio_nombre, s.apellido AS socio_apellido, s.dni AS socio_dni,
                       r.numero_recibo, r.fecha_emision AS recibo_fecha
                FROM pagos p
                JOIN socios s ON p.socio_id = s.id
                LEFT JOIN recibos r ON r.pago_id = p.id
                WHERE p.id = :id
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $pago = $sentencia->fetch();
        if (!$pago) {
            return null;
        }

        $sqlImp = "SELECT i.id, i.cuota_id, i.monto_imputado, c.periodo, c.monto_final, c.monto_pagado, c.estado AS cuota_estado
                   FROM imputaciones i
                   JOIN cuotas c ON i.cuota_id = c.id
                   WHERE i.pago_id = :pago_id
                   ORDER BY c.periodo ASC";
        $sentenciaImp = $this->conexion->prepare($sqlImp);
        $sentenciaImp->bindValue(':pago_id', $id, PDO::PARAM_INT);
        $sentenciaImp->execute();

        $pago['imputaciones'] = $sentenciaImp->fetchAll();
        return $pago;
    }

    /**
     * Lista los pagos efectuados por un socio con el recibo asociado.
     */
    public function listarPorSocio(int $socioId): array
    {
        $sql = "SELECT p.id, p.monto_total, p.medio_pago, p.autogestionado, p.fecha_pago, p.observaciones,
                       r.id AS recibo_id, r.numero_recibo
                FROM pagos p
                LEFT JOIN recibos r ON r.pago_id = p.id
                WHERE p.socio_id = :socio_id
                ORDER BY p.fecha_pago DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }
}
