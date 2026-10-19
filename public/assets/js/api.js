/**
 * Cliente de comunicacion con la API REST de MiClub.
 * Capa: Presentacion / Comunicacion API.
 *
 * Calcula automaticamente la URL base de forma relativa a la ubicacion del archivo HTML,
 * adjunta tokens CSRF y normaliza respuestas y excepciones de red.
 */

const Api = (function () {
    // Calculo dinamico de la URL base de la API segun la ruta relativa del navegador
    function obtenerUrlBaseApi() {
        let rutaActual = window.location.pathname;
        // Limpiar sufijos como index.html o barras finales
        rutaActual = rutaActual.replace(/\/index\.html.*$/, '').replace(/\/+$/, '');
        return window.location.origin + rutaActual + '/api';
    }

    const URL_BASE = obtenerUrlBaseApi();

    /**
     * Realiza una peticion HTTP a la API REST.
     *
     * @param {string} ruta Ruta relativa (ej: '/auth/sesion' o '/cuotas/generar')
     * @param {string} metodo Metodo HTTP (GET, POST, PUT, DELETE)
     * @param {object|null} cuerpo Datos para enviar en el cuerpo
     * @returns {Promise<any>} Datos devueltos por el servidor
     */
    async function peticion(ruta, metodo = 'GET', cuerpo = null) {
        // Asegurar que la ruta comience sin barra duplicada
        const rutaLimpia = ruta.startsWith('/') ? ruta : '/' + ruta;
        const urlCompleta = URL_BASE + rutaLimpia;

        const encabezados = {
            'Accept': 'application/json'
        };

        // Adjuntar token CSRF si existe en la sesion del cliente
        const tokenCsrf = sessionStorage.getItem('token_csrf');
        if (tokenCsrf) {
            encabezados['X-CSRF-Token'] = tokenCsrf;
        }

        const opciones = {
            method: metodo.toUpperCase(),
            headers: encabezados,
            credentials: 'same-origin' // Incluir cookies de sesion de PHP
        };

        if (cuerpo !== null && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(opciones.method)) {
            encabezados['Content-Type'] = 'application/json';
            opciones.body = JSON.stringify(cuerpo);
        }

        try {
            const respuestaHttp = await fetch(urlCompleta, opciones);
            const respuestaJson = await respuestaHttp.json();

            // Si el servidor respondio con estructura uniforme
            if (respuestaJson && typeof respuestaJson === 'object') {
                if (respuestaJson.ok === true) {
                    return respuestaJson.datos;
                } else if (respuestaJson.ok === false && respuestaJson.error) {
                    const error = new Error(respuestaJson.error.mensaje || 'Error en la solicitud');
                    error.codigo = respuestaJson.error.codigo || 'ERROR';
                    error.detalles = respuestaJson.error.detalles || [];
                    error.codigoHttp = respuestaHttp.status;
                    throw error;
                }
            }

            // Si la respuesta no tuvo formato esperado
            if (!respuestaHttp.ok) {
                const error = new Error('Error de comunicacion con el servidor (HTTP ' + respuestaHttp.status + ')');
                error.codigoHttp = respuestaHttp.status;
                throw error;
            }

            return respuestaJson;
        } catch (error) {
            // Manejar sesion expirada redirigiendo si el codigo HTTP es 401
            if (error.codigoHttp === 401 && !ruta.includes('/auth/iniciar-sesion')) {
                sessionStorage.removeItem('usuario');
                sessionStorage.removeItem('token_csrf');
                sessionStorage.setItem('mensaje_sesion_expirada', error.message || 'Su sesion ha expirado. Por favor inicie sesion nuevamente.');
                window.location.hash = '#inicio-sesion';
            }
            throw error;
        }
    }

    return {
        obtenerUrlBase: () => URL_BASE,
        get: (ruta) => peticion(ruta, 'GET'),
        post: (ruta, cuerpo) => peticion(ruta, 'POST', cuerpo),
        put: (ruta, cuerpo) => peticion(ruta, 'PUT', cuerpo),
        delete: (ruta, cuerpo) => peticion(ruta, 'DELETE', cuerpo)
    };
})();
