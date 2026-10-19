<?php
/**
 * Autocargador PSR-4 simplificado para el proyecto MiClub sin Composer.
 * Capa: Core del sistema.
 *
 * Carga clases bajo la estructura src/<EspacioDeNombres>/<Clase>.php
 */

spl_autoload_register(function ($clase) {
    $prefijoBase = '';
    $directorioBase = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    // Normalizar separadores de namespace a separadores de directorio
    $archivoRelativo = str_replace('\\', DIRECTORY_SEPARATOR, $clase) . '.php';
    $rutaCompleta = $directorioBase . $archivoRelativo;

    if (file_exists($rutaCompleta)) {
        require_once $rutaCompleta;
        return true;
    }

    return false;
});
