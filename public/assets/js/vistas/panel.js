/**
 * Vista de Panel Principal (Dashboard) segun el rol del usuario activo.
 * Capa: Presentacion / Vistas.
 *
 * Muestra el resumen ejecutivo, estado de cuenta y accesos directos
 * a todos los casos de uso implementados en las Fases 1 y 2.
 */

const VistaPanel = {
    render: async function (contenedor) {
        const usuario = Auth.obtenerUsuario();
        if (!usuario) {
            window.location.hash = '#inicio-sesion';
            return;
        }

        const rol = usuario.rol;
        let htmlContenido = '';

        if (rol === 'TESORERO') {
            htmlContenido = `
                <div class="rejilla-resumen">
                    <div class="caja-metrica">
                        <div class="metrica-icono">${UI.icono('pago')}</div>
                        <div class="metrica-datos">
                            <div class="metrica-etiqueta">Modulo Economico</div>
                            <div class="metrica-valor" style="font-size: 1.35rem;">Tesoreria</div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <h3 class="tarjeta-titulo">Operaciones de Cobranzas y Cuotas</h3>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <p style="color: var(--color-texto-suave); margin-bottom: 1.25rem;">
                            Acceso directo a las herramientas financieras del club:
                        </p>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="#generar-cuotas" class="btn btn-primario">
                                ${UI.icono('cuota')} Generar Lote de Cuotas
                            </a>
                            <a href="#registrar-pago" class="btn btn-acento">
                                ${UI.icono('pago')} Cobro e Imputacion de Cuotas
                            </a>
                            <a href="#morosos" class="btn btn-secundario">
                                ${UI.icono('morosos')} Padron de Socios Morosos
                            </a>
                        </div>
                    </div>
                </div>
            `;
        } else if (rol === 'ENTRENADOR') {
            htmlContenido = `
                <div class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <h3 class="tarjeta-titulo">Modulo Deportivo - Cuerpo Tecnico</h3>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <p style="margin-bottom: 1rem;">
                            Bienvenido/a profesor/a <strong>${UI.escaparHtml(usuario.nombre)} ${UI.escaparHtml(usuario.apellido)}</strong>.
                        </p>
                        <p style="color: var(--color-texto-suave); margin-bottom: 1.5rem;">
                            Administre la nomina de sus planteles, planifique entrenamientos y registre la asistencia de los atletas:
                        </p>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="#plantel" class="btn btn-primario">
                                ${UI.icono('plantel')} Gestionar Plantel
                            </a>
                            <a href="#crear-entrenamiento" class="btn btn-secundario">
                                ${UI.icono('entrenamiento')} Programar Entrenamiento
                            </a>
                            <a href="#asistencia" class="btn btn-secundario">
                                ${UI.icono('asistencia')} Planilla de Asistencias
                            </a>
                            <a href="#apercibimientos" class="btn btn-secundario">
                                ${UI.icono('sancion')} Apercibimientos Disciplinarios
                            </a>
                        </div>
                    </div>
                </div>
            `;
        } else if (rol === 'SOCIO') {
            let datosSocio = null;
            const socioId = Auth.obtenerSocioId();
            if (socioId) {
                try {
                    datosSocio = await Api.get(`/socios/${socioId}/estado-cuenta`);
                } catch (e) {
                    console.error('Error al cargar datos del socio:', e);
                }
            }

            const estado = datosSocio ? datosSocio.socio.estado : 'ACTIVO';
            let claseEtiqueta = 'etiqueta-exito';
            if (estado === 'MOROSO') claseEtiqueta = 'etiqueta-peligro';
            if (estado === 'SUSPENDIDO') claseEtiqueta = 'etiqueta-advertencia';
            if (estado === 'BAJA') claseEtiqueta = 'etiqueta-secundaria';

            const deuda = datosSocio ? datosSocio.resumen.total_adeudado : 0;
            const impagas = datosSocio ? datosSocio.resumen.cuotas_impagas : 0;

            htmlContenido = `
                <div class="rejilla-resumen">
                    <div class="caja-metrica">
                        <div class="metrica-icono">${UI.icono('carnet')}</div>
                        <div class="metrica-datos">
                            <div class="metrica-etiqueta">Condicion Social</div>
                            <div style="margin-top: 0.25rem;">
                                <span class="etiqueta ${claseEtiqueta}">${UI.escaparHtml(estado)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="caja-metrica">
                        <div class="metrica-icono">${UI.icono('pago')}</div>
                        <div class="metrica-datos">
                            <div class="metrica-etiqueta">Saldo Adeudado</div>
                            <div class="metrica-valor" style="color: ${deuda > 0 ? 'var(--color-error)' : 'var(--color-exito)'};">
                                ${UI.formatearMoneda(deuda)}
                            </div>
                        </div>
                    </div>

                    <div class="caja-metrica">
                        <div class="metrica-icono">${UI.icono('cuota')}</div>
                        <div class="metrica-datos">
                            <div class="metrica-etiqueta">Cuotas Impagas</div>
                            <div class="metrica-valor">
                                ${impagas}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <h3 class="tarjeta-titulo">Portal de Autogestion del Socio</h3>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <p style="color: var(--color-texto-suave); margin-bottom: 1.25rem;">
                            Acceso rapido a tus servicios institucionales:
                        </p>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="#socio-carnet" class="btn btn-primario">
                                ${UI.icono('carnet')} Mi Carnet Digital
                            </a>
                            <a href="#socio-estado-cuenta" class="btn btn-secundario">
                                ${UI.icono('cuota')} Estado de Cuenta
                            </a>
                            ${deuda > 0 ? `
                                <a href="#socio-pagar" class="btn btn-acento">
                                    ${UI.icono('pago')} Pagar Cuotas Pendientes
                                </a>
                            ` : ''}
                            <a href="#socio-inscripcion" class="btn btn-secundario">
                                ${UI.icono('check')} Inscripcion a Division
                            </a>
                            <a href="#socio-entrenamientos" class="btn btn-secundario">
                                ${UI.icono('entrenamiento')} Mis Entrenamientos
                            </a>
                            <a href="#socio-mis-datos" class="btn btn-secundario">
                                ${UI.icono('editar')} Editar Mis Datos
                            </a>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // ADMINISTRADOR
            htmlContenido = `
                <div class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <h3 class="tarjeta-titulo">Panel de Administracion Integral</h3>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <p style="margin-bottom: 1rem;">
                            Bienvenido/a <strong>${UI.escaparHtml(usuario.nombre)} ${UI.escaparHtml(usuario.apellido)}</strong>.
                        </p>
                        <p style="color: var(--color-texto-suave); margin-bottom: 1.5rem;">
                            Seleccione el modulo institucional sobre el que desea operar:
                        </p>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                            <!-- Modulo Administracion y Usuarios -->
                            <div style="border: 1px solid var(--color-borde); border-radius: 8px; padding: 1rem; background-color: var(--color-fondo);">
                                <h4 style="margin: 0 0 0.75rem 0; color: var(--color-primario); font-size: 1rem;">
                                    Usuarios y Padron
                                </h4>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <a href="#usuarios" class="btn btn-sm btn-primario btn-bloque">
                                        ${UI.icono('perfil')} Gestionar Usuarios y Roles
                                    </a>
                                    <a href="#socios" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('carnet')} Gestionar Socios
                                    </a>
                                </div>
                            </div>

                            <!-- Modulo Deportivo y Categorias -->
                            <div style="border: 1px solid var(--color-borde); border-radius: 8px; padding: 1rem; background-color: var(--color-fondo);">
                                <h4 style="margin: 0 0 0.75rem 0; color: var(--color-primario); font-size: 1rem;">
                                    Deporte y Divisiones
                                </h4>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <a href="#categorias" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('plantel')} Gestionar Categorias
                                    </a>
                                    <a href="#divisiones" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('plantel')} Gestionar Divisiones
                                    </a>
                                </div>
                            </div>

                            <!-- Modulo Economico y Tarifas -->
                            <div style="border: 1px solid var(--color-borde); border-radius: 8px; padding: 1rem; background-color: var(--color-fondo);">
                                <h4 style="margin: 0 0 0.75rem 0; color: var(--color-primario); font-size: 1rem;">
                                    Precios y Tesoreria
                                </h4>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <a href="#precios" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('pago')} Gestionar Precios y Recargos
                                    </a>
                                    <a href="#generar-cuotas" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('cuota')} Generar Cuotas
                                    </a>
                                    <a href="#registrar-pago" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('pago')} Cobro e Imputacion
                                    </a>
                                    <a href="#morosos" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('morosos')} Padron de Morosos
                                    </a>
                                </div>
                            </div>

                            <!-- Configuracion Institucional -->
                            <div style="border: 1px solid var(--color-borde); border-radius: 8px; padding: 1rem; background-color: var(--color-fondo);">
                                <h4 style="margin: 0 0 0.75rem 0; color: var(--color-primario); font-size: 1rem;">
                                    Identidad del Club
                                </h4>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <a href="#apariencia" class="btn btn-sm btn-secundario btn-bloque">
                                        ${UI.icono('paleta')} Apariencia del Club
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        contenedor.innerHTML = htmlContenido;
    }
};
