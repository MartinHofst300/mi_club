<?php
/**
 * Utilidad de consola para generar el hash de una contraseña con algoritmo bcrypt.
 * Capa: Utilidades de base de datos.
 *
 * Uso desde la linea de comandos:
 *   php database/generar_hash.php MiClave123
 */

if ($argc < 2) {
    echo "Uso: php database/generar_hash.php <contrasenia>\n";
    exit(1);
}

$clave = $argv[1];
$hash = password_hash($clave, PASSWORD_BCRYPT);

echo "Clave ingresada: " . $clave . PHP_EOL;
echo "Hash generado:   " . $hash . PHP_EOL;
echo "Verificacion:    " . (password_verify($clave, $hash) ? "VALIDO" : "ERROR") . PHP_EOL;
