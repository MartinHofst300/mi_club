/**
 * Modulo Principal de Inicializacion de la Aplicacion MiClub.
 * Capa: Presentacion / Bootstrap.
 *
 * Registra las rutas de todos los casos de uso implementados en las Fases 1 y 2,
 * construye la barra de navegacion reactiva al rol activo y enlaza eventos globales.
 */

const App = (function () {
    /**
     * Construye y actualiza el menu de navegacion lateral de acuerdo al rol del usuario activo.
     */
    function actualizarMenuLateral() {
        const usuario = Auth.obtenerUsuario();
        const listaMenu = document.getElementById('lista-menu-lateral');
        const pieUsuario = document.getElementById('info-usuario-pie');

        if (!listaMenu || !usuario) return;

        const rol = usuario.rol;
        let itemsHtml = `
            <li class="item-menu">
                <a href="#panel" class="enlace-menu">
                    <span class="icono-menu">${UI.icono('panel')}</span>
                    <span>Panel Principal</span>
                </a>
            </li>
        `;

        if (rol === 'SOCIO') {
            itemsHtml += `
                <li class="item-menu">
                    <a href="#socio-carnet" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('carnet')}</span>
                        <span>Mi Carnet Digital</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#socio-estado-cuenta" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('cuota')}</span>
                        <span>Estado de Cuenta</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#socio-pagar" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('pago')}</span>
                        <span>Pagar Cuota</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#socio-inscripcion" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('check')}</span>
                        <span>Inscripcion a Division</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#socio-entrenamientos" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('entrenamiento')}</span>
                        <span>Mis Entrenamientos</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#socio-mis-datos" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('editar')}</span>
                        <span>Editar Mis Datos</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#socio-historial" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('recibo')}</span>
                        <span>Historial de Pagos</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#apercibimientos" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('sancion')}</span>
                        <span>Mis Apercibimientos</span>
                    </a>
                </li>
            `;
        }

        if (rol === 'TESORERO') {
            itemsHtml += `
                <li class="item-menu">
                    <a href="#generar-cuotas" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('cuota')}</span>
                        <span>Generar Cuotas</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#registrar-pago" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('pago')}</span>
                        <span>Cobro e Imputacion</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#morosos" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('morosos')}</span>
                        <span>Padron de Morosos</span>
                    </a>
                </li>
            `;
        }

        if (rol === 'ENTRENADOR') {
            itemsHtml += `
                <li class="item-menu">
                    <a href="#plantel" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('plantel')}</span>
                        <span>Gestionar Plantel</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#crear-entrenamiento" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('entrenamiento')}</span>
                        <span>Programar Practica</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#asistencia" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('asistencia')}</span>
                        <span>Planilla de Asistencias</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#apercibimientos" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('sancion')}</span>
                        <span>Apercibimientos</span>
                    </a>
                </li>
            `;
        }

        if (rol === 'ADMINISTRADOR') {
            itemsHtml += `
                <li class="item-menu">
                    <a href="#usuarios" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('usuario')}</span>
                        <span>Gestionar Usuarios</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#socios" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('carnet')}</span>
                        <span>Gestionar Socios</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#divisiones" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('plantel')}</span>
                        <span>Gestionar Divisiones</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#categorias" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('cuota')}</span>
                        <span>Gestionar Categorias</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#precios" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('pago')}</span>
                        <span>Gestionar Precios</span>
                    </a>
                </li>
                <li class="item-menu">
                    <a href="#apariencia" class="enlace-menu">
                        <span class="icono-menu">${UI.icono('paleta')}</span>
                        <span>Apariencia del Club</span>
                    </a>
                </li>
            `;
        }

        listaMenu.innerHTML = itemsHtml;

        if (pieUsuario) {
            pieUsuario.innerHTML = `
                <div class="info-usuario-lateral">
                    <span class="nombre-usuario-lateral">${UI.escaparHtml(usuario.nombre)} ${UI.escaparHtml(usuario.apellido)}</span>
                    <span class="rol-usuario-lateral">${UI.escaparHtml(usuario.rol)}</span>
                </div>
                <button type="button" class="btn btn-sm btn-secundario btn-bloque" id="btn-cerrar-sesion-lateral" style="background-color: transparent; border-color: color-mix(in srgb, var(--color-texto-inverso) 25%, transparent); color: var(--color-texto-inverso);">
                    ${UI.icono('salir')} Cerrar Sesion
                </button>
            `;

            const btnSalir = pieUsuario.querySelector('#btn-cerrar-sesion-lateral');
            if (btnSalir) {
                btnSalir.addEventListener('click', async () => {
                    const confirmado = await UI.confirmar('Desea cerrar la sesion actual?', 'Cerrar Sesion');
                    if (confirmado) {
                        await Auth.cerrarSesion();
                    }
                });
            }
        }
    }

    /**
     * Registra las rutas de las Fases 1, 2 y 3 en el Router del cliente.
     */
    function registrarRutas() {
        // Autenticacion y Panel general
        Router.registrar('#inicio-sesion', VistaInicioSesion.render, [], 'Iniciar Sesion');
        Router.registrar('#panel', VistaPanel.render, ['ADMINISTRADOR', 'TESORERO', 'ENTRENADOR', 'SOCIO'], 'Panel Principal');

        // Modulo Socio
        Router.registrar('#socio-carnet', VistaCarnet.render, ['SOCIO', 'ADMINISTRADOR', 'TESORERO'], 'Mi Carnet Digital');
        Router.registrar('#carnet/:id', VistaCarnet.render, ['ADMINISTRADOR', 'TESORERO'], 'Carnet Oficial del Socio');
        Router.registrar('#socio-estado-cuenta', VistaEstadoCuenta.render, ['SOCIO', 'ADMINISTRADOR', 'TESORERO'], 'Estado de Cuenta Contable');
        Router.registrar('#socio-estado-cuenta/:id', VistaEstadoCuenta.render, ['ADMINISTRADOR', 'TESORERO'], 'Estado de Cuenta del Socio');
        Router.registrar('#socio-historial', VistaHistorialPagos.render, ['SOCIO', 'ADMINISTRADOR', 'TESORERO'], 'Historial de Pagos y Recibos');
        Router.registrar('#socio-historial/:id', VistaHistorialPagos.render, ['ADMINISTRADOR', 'TESORERO'], 'Historial de Pagos del Socio');
        Router.registrar('#socio-pagar', VistaPagarCuota.render, ['SOCIO', 'ADMINISTRADOR', 'TESORERO'], 'Pasarela de Pago de Cuotas');
        Router.registrar('#socio-pagar/:id', VistaPagarCuota.render, ['ADMINISTRADOR', 'TESORERO'], 'Cobro de Cuotas');
        Router.registrar('#socio-inscripcion', VistaInscripcionDivision.render, ['SOCIO'], 'Inscripcion a Divisiones Deportivas');
        Router.registrar('#socio-mis-datos', VistaEditarDatos.render, ['SOCIO'], 'Editar Mis Datos Personales');
        Router.registrar('#socio-entrenamientos', VistaMisEntrenamientos.render, ['SOCIO'], 'Mis Entrenamientos Programados');

        // Modulo Tesorero
        Router.registrar('#generar-cuotas', VistaGenerarCuotas.render, ['TESORERO', 'ADMINISTRADOR'], 'Generacion de Cuotas Sociales');
        Router.registrar('#registrar-pago', VistaRegistrarPago.render, ['TESORERO', 'ADMINISTRADOR'], 'Cobro e Imputacion de Cuotas');
        Router.registrar('#morosos', VistaMorosos.render, ['TESORERO', 'ADMINISTRADOR'], 'Padron de Socios Morosos');
        Router.registrar('#recibo/:id', VistaRecibo.render, ['TESORERO', 'SOCIO', 'ADMINISTRADOR'], 'Comprobante Oficial de Recibo');

        // Modulo Entrenador
        Router.registrar('#plantel', VistaPlantel.render, ['ENTRENADOR', 'ADMINISTRADOR'], 'Gestion de Planteles Deportivos');
        Router.registrar('#crear-entrenamiento', VistaCrearEntrenamiento.render, ['ENTRENADOR', 'ADMINISTRADOR'], 'Programacion de Entrenamiento');
        Router.registrar('#asistencia', VistaAsistencia.render, ['ENTRENADOR', 'ADMINISTRADOR'], 'Toma y Consulta de Asistencia');
        Router.registrar('#asistencia/:id', VistaAsistencia.render, ['ENTRENADOR', 'ADMINISTRADOR'], 'Planilla de Asistencia');
        Router.registrar('#apercibimientos', VistaApercibimientos.render, ['ENTRENADOR', 'ADMINISTRADOR', 'SOCIO'], 'Apercibimientos Disciplinarios');

        // Modulo Administrador (Fase 3 y Apariencia)
        Router.registrar('#usuarios', VistaUsuarios.render, ['ADMINISTRADOR'], 'Gestion de Usuarios y Roles');
        Router.registrar('#socios', VistaSocios.render, ['ADMINISTRADOR'], 'Gestion del Padron de Socios');
        Router.registrar('#categorias', VistaCategorias.render, ['ADMINISTRADOR'], 'Gestion de Categorias');
        Router.registrar('#precios', VistaPrecios.render, ['ADMINISTRADOR'], 'Gestion de Precios y Parametros');
        Router.registrar('#divisiones', VistaDivisiones.render, ['ADMINISTRADOR'], 'Gestion de Divisiones');
        Router.registrar('#apariencia', VistaApariencia.render, ['ADMINISTRADOR'], 'Apariencia Institucional del Club');
    }

    /**
     * Punto de entrada de la aplicacion al cargarse el DOM.
     */
    async function iniciar() {
        // 1. Cargar tema de colores y nombre del club desde la base de datos
        await Tema.cargar();

        // 2. Verificar sesion activa
        await Auth.verificarSesion();

        // 3. Registrar rutas y construir menu
        registrarRutas();
        actualizarMenuLateral();

        // 4. Iniciar despachador de hash
        Router.iniciar();
    }

    return {
        iniciar,
        actualizarMenuLateral
    };
})();

// Disparar arranque al cargar el documento
document.addEventListener('DOMContentLoaded', App.iniciar);
