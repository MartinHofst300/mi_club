/**
 * Enrutador por Hash para la Aplicacion de Una Sola Pagina (SPA).
 * Capa: Presentacion / Router.
 *
 * Mapea fragmentos hash de la URL con funciones de renderizado de vistas,
 * protegiendo las pantallas segun los roles del usuario autenticado.
 */

const Router = (function () {
    const rutas = [];

    /**
     * Registra una vista en el enrutador del frontend.
     *
     * @param {string} patron Patron de hash (ej: '#panel', '#recibo/:id', '#asistencia/:id')
     * @param {Function} manejador Funcion que renderiza la vista
     * @param {Array} rolesPermitidos Lista de roles con permiso (vacio para acceso publico)
     * @param {string} titulo Titulo descriptivo de la pantalla
     */
    function registrar(patron, manejador, rolesPermitidos = [], titulo = 'MiClub') {
        rutas.push({
            patron,
            manejador,
            roles: rolesPermitidos,
            titulo
        });
    }

    /**
     * Resuelve el hash actual de la URL y ejecuta la vista correspondiente.
     */
    async function navegar() {
        let hash = window.location.hash || '#panel';
        if (hash === '#') hash = '#panel';

        // Separar query string si existe (#ruta?param=val)
        let queryString = '';
        if (hash.includes('?')) {
            const partesQuery = hash.split('?');
            hash = partesQuery[0];
            queryString = partesQuery[1] || '';
        }

        // Descomponer ruta y posibles parametros (ej: #recibo/5)
        const partesRuta = hash.split('/');
        const rutaBase = partesRuta[0];
        const parametroPosicional = partesRuta[1] || null;

        // Construir objeto estandar de parametros para evitar nulos
        const parametros = {};
        if (parametroPosicional) {
            parametros.id = parametroPosicional;
        }
        if (queryString) {
            const searchParams = new URLSearchParams(queryString);
            for (const [clave, valor] of searchParams.entries()) {
                parametros[clave] = valor;
            }
        }

        let coincidencia = null;

        for (const r of rutas) {
            // Coincidencia exacta o con patron parametrizado :id
            if (r.patron === hash || (r.patron.includes('/:id') && r.patron.startsWith(rutaBase + '/:id')) || (parametroPosicional && r.patron === rutaBase)) {
                coincidencia = r;
                break;
            }
        }

        const contenedor = document.getElementById('contenido-vista');
        const tituloElem = document.getElementById('titulo-vista');
        const barraLateral = document.getElementById('barra-lateral');
        const encabezado = document.getElementById('encabezado-superior');

        // Si la ruta solicitada no existe, redirigir a #panel o #inicio-sesion
        if (!coincidencia) {
            if (Auth.estaAutenticado()) {
                window.location.hash = '#panel';
            } else {
                window.location.hash = '#inicio-sesion';
            }
            return;
        }

        // Control de acceso para rutas que requieren inicio de sesion
        if (coincidencia.roles.length > 0) {
            if (!Auth.estaAutenticado()) {
                window.location.hash = '#inicio-sesion';
                return;
            }

            if (!Auth.tieneRol(coincidencia.roles)) {
                if (tituloElem) tituloElem.textContent = 'Acceso Denegado';
                contenedor.innerHTML = `
                    <div class="tarjeta" style="max-width: 500px; margin: 3rem auto; text-align: center;">
                        <div class="tarjeta-cuerpo">
                            <h2 style="color: var(--color-error); margin-bottom: 0.75rem;">Acceso no autorizado</h2>
                            <p>Su rol actual (${UI.escaparHtml(Auth.obtenerRol())}) no tiene permisos para ingresar a esta pantalla.</p>
                            <a href="#panel" class="btn btn-primario" style="margin-top: 1rem;">Volver al Panel</a>
                        </div>
                    </div>
                `;
                return;
            }
        }

        // Si el usuario ya esta logueado e intenta ir a #inicio-sesion
        if (hash === '#inicio-sesion' && Auth.estaAutenticado()) {
            window.location.hash = '#panel';
            return;
        }

        // Ocultar barra lateral en pantalla de inicio de sesion
        if (hash === '#inicio-sesion') {
            if (barraLateral) barraLateral.style.display = 'none';
            if (encabezado) encabezado.style.display = 'none';
        } else {
            if (barraLateral) barraLateral.style.display = 'flex';
            if (encabezado) encabezado.style.display = 'flex';
        }

        // Actualizar titulo de la barra superior
        if (tituloElem) {
            tituloElem.textContent = coincidencia.titulo;
        }

        // Actualizar elemento activo en el menu lateral
        document.querySelectorAll('.enlace-menu').forEach(enlace => {
            const href = enlace.getAttribute('href');
            if (href === hash || (parametroPosicional && href === rutaBase)) {
                enlace.classList.add('activo');
            } else {
                enlace.classList.remove('activo');
            }
        });

        // Ejecutar el manejador de la vista pasando el contenedor y parametros
        try {
            await coincidencia.manejador(contenedor, parametros);
        } catch (error) {
            console.error('Error al renderizar vista:', error);
            contenedor.innerHTML = `
                <div class="aviso aviso-error">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al cargar la pantalla:</strong>
                        <div>${UI.escaparHtml(error.message)}</div>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Inicia la escucha de eventos de cambio de hash en la ventana.
     */
    function iniciar() {
        window.addEventListener('hashchange', navegar);
        navegar();
    }

    return {
        registrar,
        iniciar,
        navegar
    };
})();
