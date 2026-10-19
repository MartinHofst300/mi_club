/**
 * Gestor de Autenticacion y Estado de Sesion del Cliente.
 * Capa: Presentacion / Autenticacion.
 *
 * Mantiene en memoria y sessionStorage los datos del usuario logueado y el token CSRF.
 */

const Auth = (function () {
    let usuarioActual = null;

    function guardarSesion(datos) {
        if (datos && datos.id) {
            usuarioActual = datos;
            sessionStorage.setItem('usuario', JSON.stringify(datos));
            if (datos.token_csrf) {
                sessionStorage.setItem('token_csrf', datos.token_csrf);
            }
        } else {
            usuarioActual = null;
            sessionStorage.removeItem('usuario');
            sessionStorage.removeItem('token_csrf');
        }
    }

    // Inicializar desde almacenamiento local si existe
    const guardado = sessionStorage.getItem('usuario');
    if (guardado) {
        try {
            usuarioActual = JSON.parse(guardado);
        } catch (e) {
            usuarioActual = null;
        }
    }

    /**
     * Inicia sesion en el backend mediante peticion POST.
     */
    async function iniciarSesion(identificador, password) {
        const resultado = await Api.post('/auth/iniciar-sesion', {
            identificador,
            password
        });

        guardarSesion(resultado);
        return resultado;
    }

    /**
     * Cierra la sesion en el servidor y limpia el almacenamiento del cliente.
     */
    async function cerrarSesion() {
        try {
            await Api.post('/auth/cerrar-sesion', {});
        } catch (error) {
            console.warn('Error al notificar cierre de sesion al servidor:', error);
        } finally {
            guardarSesion(null);
            window.location.hash = '#inicio-sesion';
        }
    }

    /**
     * Consulta al backend la sesion activa actual.
     */
    async function verificarSesion() {
        try {
            const datos = await Api.get('/auth/sesion');
            if (datos && datos.id) {
                guardarSesion(datos);
                return datos;
            } else {
                guardarSesion(null);
                return null;
            }
        } catch (error) {
            guardarSesion(null);
            return null;
        }
    }

    /**
     * Verifica si el usuario logueado posee alguno de los roles solicitados.
     */
    function tieneRol(roles) {
        if (!usuarioActual) return false;
        if (!roles || roles.length === 0) return true;

        const listaRoles = Array.isArray(roles) ? roles : [roles];
        return listaRoles.includes(usuarioActual.rol);
    }

    /**
     * Obtiene el identificador de socio vinculado al usuario autenticado, o null si no posee.
     */
    function obtenerSocioId() {
        if (!usuarioActual) return null;
        if (usuarioActual.socio_id !== undefined && usuarioActual.socio_id !== null) {
            const parsed = parseInt(usuarioActual.socio_id, 10);
            return isNaN(parsed) ? null : parsed;
        }
        return null;
    }

    /**
     * Obtiene el identificador primario del usuario web en sesion.
     */
    function obtenerUsuarioId() {
        if (!usuarioActual) return null;
        if (usuarioActual.id !== undefined && usuarioActual.id !== null) {
            const parsed = parseInt(usuarioActual.id, 10);
            return isNaN(parsed) ? null : parsed;
        }
        return null;
    }

    return {
        iniciarSesion,
        cerrarSesion,
        verificarSesion,
        obtenerUsuario: () => usuarioActual,
        obtenerRol: () => usuarioActual ? usuarioActual.rol : null,
        obtenerSocioId,
        obtenerUsuarioId,
        estaAutenticado: () => usuarioActual !== null,
        tieneRol
    };
})();
