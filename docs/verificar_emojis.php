<?php
$directorioBase = dirname(__DIR__);
$iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorioBase));
$encontrados = 0;

$patronEmoji = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

foreach ($iterador as $archivo) {
    if (!$archivo->isFile()) {
        continue;
    }

    $ruta = $archivo->getPathname();
    if (str_contains($ruta, '.git')) {
        continue;
    }

    $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
    if (!in_array($extension, ['php', 'js', 'html', 'css', 'sql', 'md', 'json', 'htaccess'], true)) {
        continue;
    }

    $contenido = file_get_contents($ruta);
    if (preg_match_all($patronEmoji, $contenido, $coincidencias, PREG_OFFSET_CAPTURE)) {
        foreach ($coincidencias[0] as $coincidencia) {
            echo "Emoji detectado en {$ruta}: '{$coincidencia[0]}'\n";
            $encontrados++;
        }
    }
}

if ($encontrados === 0) {
    echo "VERIFICACION EXITOSA: Cero emojis encontrados en todos los archivos de codigo, configuracion, vistas y documentacion.\n";
} else {
    echo "FALLO: Se encontraron {$encontrados} emojis en el proyecto.\n";
}
