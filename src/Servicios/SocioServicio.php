<?php
namespace Servicios;

use Repositorios\SocioRepositorio;
use Repositorios\UsuarioRepositorio;
use Repositorios\EntrenamientoRepositorio;
use Excepciones\ReglaNegocioException;
use Excepciones\ValidacionException;
use Excepciones\NoEncontradoException;
use Core\Validador;

/**
 * Servicio de Gestion y Operaciones del Socio.
 * Capa: Servicios de dominio.
 */
class SocioServicio
{
    private SocioRepositorio $socioRepo;
    private UsuarioRepositorio $usuarioRepo;
    private EntrenamientoRepositorio $entrenamientoRepo;

    public function __construct(
        ?SocioRepositorio $socioRepo = null,
        ?UsuarioRepositorio $usuarioRepo = null,
        ?EntrenamientoRepositorio $entrenamientoRepo = null
    ) {
        $this->socioRepo = $socioRepo ?? new SocioRepositorio();
        $this->usuarioRepo = $usuarioRepo ?? new UsuarioRepositorio();
        $this->entrenamientoRepo = $entrenamientoRepo ?? new EntrenamientoRepositorio();
    }

    /**
     * Permite a un socio editar sus datos de contacto y contrasena (Caso de uso Editar mis datos).
     *
     * @param int $socioId ID del socio
     * @param array $datos ['email', 'telefono', 'domicilio', 'password_actual', 'password_nueva']
     * @return array
     */
    public function editarMisDatos(int $socioId, array $datos): array
    {
        $socio = $this->socioRepo->buscarPorId($socioId);
        if (!$socio) {
            throw new NoEncontradoException('El socio no existe.');
        }

        Validador::validar($datos, [
            'email' => 'requerido|email',
            'telefono' => 'max:50',
            'domicilio' => 'max:200'
        ]);

        $nuevoEmail = trim($datos['email']);
        $nuevoTelefono = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        $nuevoDomicilio = !empty($datos['domicilio']) ? trim($datos['domicilio']) : null;

        // Actualizar datos de contacto en la tabla socios
        $this->socioRepo->actualizarDatosContacto($socioId, $nuevoEmail, $nuevoTelefono, $nuevoDomicilio);

        // Si desea cambiar su contrasena
        if (!empty($datos['password_nueva'])) {
            if (empty($datos['password_actual'])) {
                throw new ValidacionException('Para cambiar la contrasena debe ingresar su clave actual.');
            }

            if (mb_strlen($datos['password_nueva']) < 6) {
                throw new ValidacionException('La nueva contrasena debe contener al menos 6 caracteres.');
            }

            if (!$socio->usuarioId) {
                throw new ReglaNegocioException('El socio no posee cuenta de usuario web asociada.');
            }

            $usuario = $this->usuarioRepo->buscarPorId($socio->usuarioId);
            if (!$usuario || !password_verify($datos['password_actual'], $usuario->passwordHash)) {
                throw new ValidacionException('La contrasena actual ingresada es incorrecta.');
            }

            $nuevoHash = password_hash($datos['password_nueva'], PASSWORD_BCRYPT);
            $this->usuarioRepo->actualizarPassword($socio->usuarioId, $nuevoHash);
        }

        return [
            'socio_id' => $socioId,
            'email' => $nuevoEmail,
            'telefono' => $nuevoTelefono,
            'domicilio' => $nuevoDomicilio,
            'mensaje' => 'Datos actualizados correctamente.'
        ];
    }

    /**
     * Lista los entrenamientos de las divisiones en las que el socio esta inscripto activo (Caso de uso Ver entrenamientos).
     */
    public function obtenerEntrenamientosSocio(int $socioId): array
    {
        return $this->entrenamientoRepo->listarPorSocio($socioId);
    }

    /**
     * Da de alta un nuevo socio desde la administracion.
     */
    public function crearAdministrativo(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $apellido = trim($datos['apellido'] ?? '');
        $dni = trim($datos['dni'] ?? '');
        $email = trim(strtolower($datos['email'] ?? ''));
        $telefono = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        $domicilio = !empty($datos['domicilio']) ? trim($datos['domicilio']) : null;
        $fechaNacimiento = trim($datos['fecha_nacimiento'] ?? '');
        $categoriaId = (int)($datos['categoria_id'] ?? 0);
        $usuarioId = !empty($datos['usuario_id']) ? (int)$datos['usuario_id'] : null;
        $grupoFamiliarId = !empty($datos['grupo_familiar_id']) ? (int)$datos['grupo_familiar_id'] : null;

        if (empty($nombre) || empty($apellido) || empty($dni) || empty($email) || empty($fechaNacimiento) || $categoriaId <= 0) {
            throw new ValidacionException('Todos los campos obligatorios deben completarse.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidacionException('El formato de correo electronico es invalido.');
        }

        // Comprobar DNI unico
        if ($this->socioRepo->buscarPorDni($dni)) {
            throw new \Excepciones\ConflictoException('Ya existe un socio registrado con el DNI ingresado.');
        }

        // Comprobar Email unico
        if ($this->socioRepo->buscarPorEmail($email)) {
            throw new \Excepciones\ConflictoException('Ya existe un socio registrado con el correo electronico ingresado.');
        }

        // Validar categoria y rango etario
        $catRepo = new \Repositorios\CategoriaRepositorio();
        $categoria = $catRepo->buscarPorId($categoriaId);
        if (!$categoria || !$categoria->activo) {
            throw new ValidacionException('La categoria seleccionada no existe o no se encuentra activa.');
        }

        $hoy = new \DateTime();
        $nacimiento = new \DateTime($fechaNacimiento);
        $edad = $hoy->diff($nacimiento)->y;
        if ($edad < $categoria->edadMinima || $edad > $categoria->edadMaxima) {
            throw new ValidacionException(sprintf(
                'La edad del socio (%d anos) no corresponde al rango de la categoria %s (%d a %d anos).',
                $edad,
                $categoria->nombre,
                $categoria->edadMinima,
                $categoria->edadMaxima
            ), ['edad' => 'Fuera de rango'], 422);
        }

        // Validar usuario web si se especifico
        if ($usuarioId !== null) {
            $usuario = $this->usuarioRepo->buscarPorId($usuarioId);
            if (!$usuario) {
                throw new ValidacionException('El usuario web especificado no existe.');
            }
            $socioExistente = $this->socioRepo->buscarPorUsuarioId($usuarioId);
            if ($socioExistente) {
                throw new \Excepciones\ConflictoException('El usuario web ya se encuentra vinculado a otro socio.');
            }
        }

        $numeroSocio = $this->socioRepo->obtenerSiguienteNumeroSocio();
        $socio = new \Modelos\Socio(
            null,
            $usuarioId,
            $grupoFamiliarId,
            $categoriaId,
            $numeroSocio,
            $nombre,
            $apellido,
            $dni,
            $email,
            $telefono,
            $domicilio,
            $fechaNacimiento,
            date('Y-m-d'),
            'ACTIVO'
        );

        $id = $this->socioRepo->crear($socio);

        return [
            'id' => $id,
            'numero_socio' => $numeroSocio,
            'mensaje' => "Socio {$numeroSocio} registrado exitosamente en el padron del club."
        ];
    }

    /**
     * Actualiza la ficha completa de un socio desde la administracion.
     */
    public function actualizarAdministrativo(int $id, array $datos): array
    {
        $socio = $this->socioRepo->buscarPorId($id);
        if (!$socio) {
            throw new NoEncontradoException('Socio no encontrado.');
        }

        $nombre = trim($datos['nombre'] ?? $socio->nombre);
        $apellido = trim($datos['apellido'] ?? $socio->apellido);
        $dni = trim($datos['dni'] ?? $socio->dni);
        $email = trim(strtolower($datos['email'] ?? $socio->email));
        $telefono = isset($datos['telefono']) ? (trim($datos['telefono']) ?: null) : $socio->telefono;
        $domicilio = isset($datos['domicilio']) ? (trim($datos['domicilio']) ?: null) : $socio->domicilio;
        $fechaNacimiento = trim($datos['fecha_nacimiento'] ?? $socio->fechaNacimiento);
        $categoriaId = isset($datos['categoria_id']) ? (int)$datos['categoria_id'] : $socio->categoriaId;
        $usuarioId = array_key_exists('usuario_id', $datos) ? ($datos['usuario_id'] ? (int)$datos['usuario_id'] : null) : $socio->usuarioId;
        $grupoFamiliarId = array_key_exists('grupo_familiar_id', $datos) ? ($datos['grupo_familiar_id'] ? (int)$datos['grupo_familiar_id'] : null) : $socio->grupoFamiliarId;
        $nuevoEstado = isset($datos['estado']) ? strtoupper(trim($datos['estado'])) : $socio->estado;

        // Comprobar DNI unico
        $existenteDni = $this->socioRepo->buscarPorDni($dni, $id);
        if ($existenteDni) {
            throw new \Excepciones\ConflictoException('El DNI ingresado ya se encuentra registrado por otro socio.');
        }

        // Comprobar Email unico
        $existenteEmail = $this->socioRepo->buscarPorEmail($email, $id);
        if ($existenteEmail) {
            throw new \Excepciones\ConflictoException('El correo electronico ya se encuentra registrado por otro socio.');
        }

        // Validacion de estado: SUSPENDIDO y BAJA solo los fija Administracion con motivo
        if (in_array($nuevoEstado, ['SUSPENDIDO', 'BAJA'], true) && $socio->estado !== $nuevoEstado) {
            $motivo = trim($datos['motivo_estado'] ?? '');
            if (empty($motivo)) {
                throw new ValidacionException("Para fijar el estado {$nuevoEstado} debe indicar obligatoriamente el motivo institucional.");
            }
        }

        // Validar usuario web unico
        if ($usuarioId !== null && $usuarioId !== $socio->usuarioId) {
            $socioExistente = $this->socioRepo->buscarPorUsuarioId($usuarioId);
            if ($socioExistente && $socioExistente->id !== $id) {
                throw new \Excepciones\ConflictoException('El usuario web ya se encuentra vinculado a otro socio.');
            }
        }

        $socioActualizado = new \Modelos\Socio(
            $id,
            $usuarioId,
            $grupoFamiliarId,
            $categoriaId,
            $socio->numeroSocio,
            $nombre,
            $apellido,
            $dni,
            $email,
            $telefono,
            $domicilio,
            $fechaNacimiento,
            $socio->fechaAlta,
            $nuevoEstado
        );

        $this->socioRepo->actualizarAdministrativo($socioActualizado);

        return [
            'id' => $id,
            'numero_socio' => $socio->numeroSocio,
            'estado' => $nuevoEstado,
            'mensaje' => 'Ficha del socio actualizada exitosamente.'
        ];
    }

    /**
     * Lista todos los grupos familiares registrados.
     */
    public function listarGruposFamiliares(): array
    {
        return $this->socioRepo->listarGruposFamiliares();
    }

    /**
     * Crea un nuevo grupo familiar.
     */
    public function crearGrupoFamiliar(string $nombre): array
    {
        $nombre = trim($nombre);
        if (empty($nombre)) {
            throw new ValidacionException('El nombre del grupo familiar es obligatorio.');
        }

        $id = $this->socioRepo->crearGrupoFamiliar($nombre);
        return [
            'id' => $id,
            'nombre' => $nombre,
            'mensaje' => "Grupo familiar '{$nombre}' creado exitosamente."
        ];
    }

    /**
     * Obtiene los integrantes de un grupo familiar.
     */
    public function obtenerIntegrantesGrupoFamiliar(int $grupoId): array
    {
        return $this->socioRepo->obtenerIntegrantesGrupoFamiliar($grupoId);
    }

    /**
     * Asigna o remueve un socio de un grupo familiar.
     */
    public function asignarGrupoFamiliar(int $socioId, ?int $grupoId): array
    {
        $socio = $this->socioRepo->buscarPorId($socioId);
        if (!$socio) {
            throw new NoEncontradoException('Socio no encontrado.');
        }

        $this->socioRepo->asignarGrupoFamiliar($socioId, $grupoId);
        return [
            'socio_id' => $socioId,
            'grupo_familiar_id' => $grupoId,
            'mensaje' => $grupoId ? 'Socio asignado al grupo familiar correctamente.' : 'Socio desvinculado del grupo familiar.'
        ];
    }
}
