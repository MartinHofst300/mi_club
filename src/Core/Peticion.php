<?php
namespace Core;

/**
 * Representacion y parseo de la peticion HTTP entrante.
 * Capa: Core del sistema.
 */
class Peticion
{
    private string $metodo;
    private string $ruta;
    private array $parametrosConsulta;
    private array $cuerpo;
    private array $encabezados;

    public function __construct()
    {
        $this->metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->parametrosConsulta = $_GET;
        $this->encabezados = $this->obtenerEncabezadosEntrantes();
        $this->cuerpo = $this->parsearCuerpo();
        $this->ruta = $this->determinarRuta();
    }

    /**
     * Obtiene el metodo HTTP de la peticion (GET, POST, PUT, DELETE).
     */
    public function getMetodo(): string
    {
        return $this->metodo;
    }

    /**
     * Obtiene la ruta limpia solicitada, normalizada sin el prefijo del directorio base.
     */
    public function getRuta(): string
    {
        return $this->ruta;
    }

    /**
     * Obtiene todos los parametros del cuerpo de la peticion (JSON o formulario).
     */
    public function getCuerpo(): array
    {
        return $this->cuerpo;
    }

    /**
     * Obtiene un parametro especifico del cuerpo.
     */
    public function obtener(string $clave, mixed $predeterminado = null): mixed
    {
        return $this->cuerpo[$clave] ?? $predeterminado;
    }

    /**
     * Obtiene todos los parametros de consulta (?campo=valor).
     */
    public function getParametrosConsulta(): array
    {
        return $this->parametrosConsulta;
    }

    /**
     * Obtiene un parametro de consulta especifico.
     */
    public function consulta(string $clave, mixed $predeterminado = null): mixed
    {
        return $this->parametrosConsulta[$clave] ?? $predeterminado;
    }

    /**
     * Obtiene un encabezado HTTP recibido.
     */
    public function getEncabezado(string $nombre): ?string
    {
        $nombreNormalizado = strtolower($nombre);
        foreach ($this->encabezados as $clave => $valor) {
            if (strtolower($clave) === $nombreNormalizado) {
                return $valor;
            }
        }
        return null;
    }

    /**
     * Parsea el cuerpo entrante segun el Content-Type (preferentemente JSON).
     */
    private function parsearCuerpo(): array
    {
        $tipoContenido = $this->getEncabezado('Content-Type') ?? '';

        if (str_contains($tipoContenido, 'application/json')) {
            $entradaBruta = file_get_contents('php://input');
            if (empty($entradaBruta)) {
                return [];
            }
            $decodificado = json_decode($entradaBruta, true);
            return is_array($decodificado) ? $decodificado : [];
        }

        if ($this->metodo === 'POST') {
            return $_POST;
        }

        // Para PUT u otros metodos con form-urlencoded
        $entradaBruta = file_get_contents('php://input');
        if (!empty($entradaBruta)) {
            parse_str($entradaBruta, $datos);
            return is_array($datos) ? $datos : [];
        }

        return [];
    }

    /**
     * Determina la ruta de la peticion normalizada (ej: /api/cuotas/generar).
     */
    private function determinarRuta(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $posInterrogacion = strpos($uri, '?');
        if ($posInterrogacion !== false) {
            $uri = substr($uri, 0, $posInterrogacion);
        }

        // Localizar el segmento /api/ dentro de la URI
        $posApi = strpos($uri, '/api');
        if ($posApi !== false) {
            $ruta = substr($uri, $posApi);
        } else {
            $ruta = '/' . ltrim($uri, '/');
        }

        // Remover barra final si no es la raiz
        if (strlen($ruta) > 1 && str_ends_with($ruta, '/')) {
            $ruta = rtrim($ruta, '/');
        }

        return $ruta;
    }

    /**
     * Obtiene los encabezados entrantes de forma compatible con cualquier servidor web.
     */
    private function obtenerEncabezadosEntrantes(): array
    {
        if (function_exists('getallheaders')) {
            $encabezados = getallheaders();
            if (is_array($encabezados)) {
                return $encabezados;
            }
        }

        $encabezados = [];
        foreach ($_SERVER as $clave => $valor) {
            if (str_starts_with($clave, 'HTTP_')) {
                $nombre = str_replace('_', '-', substr($clave, 5));
                $encabezados[$nombre] = $valor;
            } elseif (in_array($clave, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $nombre = str_replace('_', '-', $clave);
                $encabezados[$nombre] = $valor;
            }
        }

        return $encabezados;
    }
}
