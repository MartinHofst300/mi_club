<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Recibo;

/**
 * Repositorio de acceso a datos para recibos emitidos (Regla R7).
 * Capa: Acceso a datos.
 */
class ReciboRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Inserta un nuevo recibo oficial.
     */
    public function crear(Recibo $recibo): int
    {
        $sql = "INSERT INTO recibos (pago_id, numero_recibo, fecha_emision, monto_total, creado_en)
                VALUES (:pago_id, :numero_recibo, :fecha_emision, :monto_total, NOW())";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':pago_id', $recibo->pagoId, PDO::PARAM_INT);
        $sentencia->bindValue(':numero_recibo', $recibo->numeroRecibo, PDO::PARAM_STR);
        $sentencia->bindValue(':fecha_emision', $recibo->fechaEmision, PDO::PARAM_STR);
        $sentencia->bindValue(':monto_total', $recibo->montoTotal);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Genera el siguiente numero correlativo de recibo en formato R-00000001.
     */
    public function obtenerSiguienteNumero(): string
    {
        $sql = "SELECT COALESCE(MAX(id), 0) + 1 AS siguiente FROM recibos";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $fila = $sentencia->fetch();

        $numero = (int)($fila['siguiente'] ?? 1);
        return sprintf('R-%08d', $numero);
    }

    /**
     * Obtiene los datos completos de un recibo para su visualizacion e impresion.
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT r.id, r.pago_id, r.numero_recibo, r.fecha_emision, r.monto_total,
                       p.medio_pago, p.autogestionado, p.observaciones, p.fecha_pago,
                       s.id AS socio_id, s.numero_socio, s.nombre AS socio_nombre, s.apellido AS socio_apellido,
                       s.dni AS socio_dni, s.domicilio AS socio_domicilio,
                       u.nombre AS cajero_nombre, u.apellido AS cajero_apellido
                FROM recibos r
                JOIN pagos p ON r.pago_id = p.id
                JOIN socios s ON p.socio_id = s.id
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE r.id = :id
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $recibo = $sentencia->fetch();
        if (!$recibo) {
            return null;
        }

        // Obtener detalle de las cuotas imputadas en dicho recibo
        $sqlDetalle = "SELECT i.monto_imputado, c.periodo, c.monto_final, c.monto_pagado,
                              c.monto_base, c.monto_descuento, c.monto_recargo
                       FROM imputaciones i
                       JOIN cuotas c ON i.cuota_id = c.id
                       WHERE i.pago_id = :pago_id
                       ORDER BY c.periodo ASC";
        $sentenciaDetalle = $this->conexion->prepare($sqlDetalle);
        $sentenciaDetalle->bindValue(':pago_id', $recibo['pago_id'], PDO::PARAM_INT);
        $sentenciaDetalle->execute();

        $recibo['items'] = $sentenciaDetalle->fetchAll();
        return $recibo;
    }
}
