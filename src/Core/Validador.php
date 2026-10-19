<?php
namespace Core;

use Excepciones\ValidacionException;

/**
 * Validador de datos de entrada del servidor.
 * Capa: Core del sistema.
 */
class Validador
{
    /**
     * Valida un conjunto de datos contra reglas definidas.
     *
     * Reglas admitidas:
     * - requerido
     * - email
     * - entero
     * - decimal
     * - positivo
     * - no_negativo
     * - fecha (YYYY-MM-DD)
     * - hora (HH:MM o HH:MM:SS)
     * - periodo (YYYY-MM)
     * - enum:VALOR1,VALOR2,...
     * - min:N
     * - max:N
     * - color_hex (#RRGGBB)
     *
     * @param array $datos Arreglo asociativo con los datos recibidos
     * @param array $reglasPorCampo Arreglo asociativo campo => 'regla1|regla2:param'
     * @return array Datos validados y filtrados
     * @throws ValidacionException Si alguna regla falla
     */
    public static function validar(array $datos, array $reglasPorCampo): array
    {
        $errores = [];

        foreach ($reglasPorCampo as $campo => $reglasTexto) {
            $reglas = explode('|', $reglasTexto);
            $valor = $datos[$campo] ?? null;

            foreach ($reglas as $reglaDef) {
                $partes = explode(':', $reglaDef, 2);
                $regla = $partes[0];
                $parametro = $partes[1] ?? null;

                // Si no es requerido y viene vacio, se omiten las demas reglas
                if ($regla !== 'requerido' && ($valor === null || $valor === '')) {
                    continue;
                }

                switch ($regla) {
                    case 'requerido':
                        if ($valor === null || $valor === '' || (is_array($valor) && empty($valor))) {
                            $errores[$campo][] = "El campo '{$campo}' es obligatorio.";
                        }
                        break;

                    case 'email':
                        if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                            $errores[$campo][] = "El campo '{$campo}' debe ser una direccion de correo electronico valida.";
                        }
                        break;

                    case 'entero':
                        if (!filter_var($valor, FILTER_VALIDATE_INT) && $valor !== 0 && $valor !== '0') {
                            $errores[$campo][] = "El campo '{$campo}' debe ser un numero entero.";
                        }
                        break;

                    case 'decimal':
                        if (!is_numeric($valor)) {
                            $errores[$campo][] = "El campo '{$campo}' debe ser un numero decimal valido.";
                        }
                        break;

                    case 'positivo':
                        if (!is_numeric($valor) || (float)$valor <= 0) {
                            $errores[$campo][] = "El campo '{$campo}' debe ser un numero mayor a cero.";
                        }
                        break;

                    case 'no_negativo':
                        if (!is_numeric($valor) || (float)$valor < 0) {
                            $errores[$campo][] = "El campo '{$campo}' no puede ser negativo.";
                        }
                        break;

                    case 'fecha':
                        if (!self::validarFormatoFecha($valor, 'Y-m-d')) {
                            $errores[$campo][] = "El campo '{$campo}' debe tener el formato de fecha AAAA-MM-DD.";
                        }
                        break;

                    case 'hora':
                        if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', (string)$valor)) {
                            $errores[$campo][] = "El campo '{$campo}' debe tener un formato de hora valido (HH:MM).";
                        }
                        break;

                    case 'periodo':
                        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$valor)) {
                            $errores[$campo][] = "El campo '{$campo}' debe tener el formato de periodo AAAA-MM.";
                        }
                        break;

                    case 'enum':
                        $opciones = explode(',', $parametro ?? '');
                        if (!in_array($valor, $opciones, true)) {
                            $errores[$campo][] = "El valor del campo '{$campo}' debe ser uno de los siguientes: " . implode(', ', $opciones) . ".";
                        }
                        break;

                    case 'min':
                        $limite = (int)$parametro;
                        if (is_numeric($valor) && (float)$valor < $limite) {
                            $errores[$campo][] = "El campo '{$campo}' no puede ser menor a {$limite}.";
                        } elseif (is_string($valor) && mb_strlen($valor) < $limite) {
                            $errores[$campo][] = "El campo '{$campo}' debe contener al menos {$limite} caracteres.";
                        }
                        break;

                    case 'max':
                        $limite = (int)$parametro;
                        if (is_numeric($valor) && (float)$valor > $limite) {
                            $errores[$campo][] = "El campo '{$campo}' no puede ser mayor a {$limite}.";
                        } elseif (is_string($valor) && mb_strlen($valor) > $limite) {
                            $errores[$campo][] = "El campo '{$campo}' no puede exceder {$limite} caracteres.";
                        }
                        break;

                    case 'color_hex':
                        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$valor)) {
                            $errores[$campo][] = "El campo '{$campo}' debe ser un color hexadecimal valido con formato #RRGGBB.";
                        }
                        break;
                }
            }
        }

        if (!empty($errores)) {
            throw new ValidacionException('Error en la validacion de los datos de entrada', $errores);
        }

        return $datos;
    }

    /**
     * Valida si una cadena cumple con un formato de fecha especifico.
     */
    private static function validarFormatoFecha(mixed $valor, string $formato): bool
    {
        if (!is_string($valor)) {
            return false;
        }
        $d = \DateTime::createFromFormat($formato, $valor);
        return $d && $d->format($formato) === $valor;
    }
}
