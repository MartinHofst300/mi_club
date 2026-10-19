/**
 * Vista de Carnet Digital del Socio (Caso de uso Consultar carnet, Regla R11).
 * Capa: Presentacion / Vistas.
 *
 * Muestra la credencial institucional del socio con fotografia simbolica,
 * datos de padron, categoria formativa, divisiones activas y estado de vigencia.
 */

const VistaCarnet = (function () {
    /**
     * Renderiza la pantalla del carnet digital.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     * @param {object} parametros Parametros de la ruta (opcional :id)
     */
    async function render(contenedor, parametros = {}) {
        UI.mostrarCargando(contenedor, 'Cargando carnet digital...');

        let socioId = null;
        if (parametros) {
            if (typeof parametros === 'object' && parametros.id) {
                socioId = parseInt(parametros.id, 10);
            } else if (typeof parametros === 'string' || typeof parametros === 'number') {
                socioId = parseInt(parametros, 10);
            }
        }

        if (!socioId) {
            socioId = Auth.obtenerSocioId();
        }

        if (!socioId) {
            contenedor.innerHTML = `
                <div class="aviso aviso-advertencia">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Perfil de socio no disponible:</strong>
                        <div style="margin-top: 0.25rem;">No se encontro un perfil de socio vinculado a su cuenta de usuario para consultar el carnet.</div>
                    </div>
                </div>
            `;
            return;
        }

        try {
            const carnet = await Api.get(`/socios/${socioId}/carnet`);
            const config = Tema.obtenerConfiguracion() || {};
            const nombreClub = config.nombre_club || 'Club Social y Deportivo MiClub';

            const esVigente = Boolean(carnet.vigente);
            const claseBadge = esVigente ? 'vigente' : 'no-vigente';
            const textoVigencia = esVigente ? 'VIGENTE - ACCESO HABILITADO' : 'NO VIGENTE';
            const iconoVigencia = esVigente ? UI.icono('check') : UI.icono('alerta');

            const divisionesTexto = (Array.isArray(carnet.divisiones) && carnet.divisiones.length > 0)
                ? carnet.divisiones.map(d => (typeof d === 'object' && d !== null ? (d.nombre || '') : String(d))).filter(Boolean).join(', ') || 'Sin divisiones asignadas'
                : 'Sin divisiones asignadas';

            let alertaMotivo = '';
            if (!esVigente && carnet.motivo_no_vigente) {
                alertaMotivo = `
                    <div class="alerta alerta-peligro" style="max-width: 480px; margin: 1.5rem auto 0 auto;">
                        <div class="alerta-icono">${UI.icono('alerta')}</div>
                        <div>
                            <strong>Acceso No Habilitado:</strong> ${UI.escaparHtml(carnet.motivo_no_vigente)}
                        </div>
                    </div>
                `;
            }

            contenedor.innerHTML = `
                <div style="max-width: 600px; margin: 0 auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;" class="no-imprimir">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Carnet Institucional</h2>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Credencial digital para ingreso a instalaciones y actividades.
                            </p>
                        </div>
                        <button type="button" class="btn btn-secundario btn-sm" id="btn-imprimir-carnet">
                            ${UI.icono('imprimir')} Imprimir Carnet
                        </button>
                    </div>

                    <div class="carnet-contenedor">
                        <div class="carnet-tarjeta">
                            <div class="carnet-cabecera">
                                <div class="carnet-institucion">
                                    <div class="carnet-escudo-icono" aria-hidden="true">MC</div>
                                    <div>
                                        <div class="carnet-nombre-club">${UI.escaparHtml(nombreClub)}</div>
                                        <div class="carnet-subtitulo">Credencial Oficial de Socio</div>
                                    </div>
                                </div>
                                <div style="font-family: var(--fuente-mono); font-size: 0.85rem; opacity: 0.9;">
                                    ${UI.escaparHtml(carnet.numero_socio)}
                                </div>
                            </div>

                            <div class="carnet-cuerpo">
                                <div class="carnet-foto-avatar" aria-label="Fotografia del socio">
                                    ${UI.icono('usuario')}
                                </div>
                                <div class="carnet-datos-socio">
                                    <div class="carnet-nombre-completo">
                                        ${UI.escaparHtml(carnet.nombre)} ${UI.escaparHtml(carnet.apellido)}
                                    </div>
                                    <div class="carnet-dato-fila">
                                        <span class="carnet-etiqueta">DNI:</span>
                                        <span class="carnet-valor">${UI.escaparHtml(carnet.dni)}</span>
                                    </div>
                                    <div class="carnet-dato-fila">
                                        <span class="carnet-etiqueta">Categoria:</span>
                                        <span class="carnet-valor">${UI.escaparHtml(carnet.categoria || 'Activo')}</span>
                                    </div>
                                    <div class="carnet-dato-fila">
                                        <span class="carnet-etiqueta">Divisiones:</span>
                                        <span class="carnet-valor">${UI.escaparHtml(divisionesTexto)}</span>
                                    </div>
                                    <div class="carnet-dato-fila">
                                        <span class="carnet-etiqueta">Alta:</span>
                                        <span class="carnet-valor">${UI.formatearFecha(carnet.fecha_alta)}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="carnet-pie">
                                <div class="carnet-badge-vigencia ${claseBadge}">
                                    ${iconoVigencia}
                                    <span>${textoVigencia}</span>
                                </div>
                                <div style="font-size: 0.75rem; opacity: 0.8;">
                                    Estado: ${UI.escaparHtml(carnet.estado)}
                                </div>
                            </div>
                        </div>
                    </div>

                    ${alertaMotivo}

                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;" class="no-imprimir">
                        <a href="#socio-estado-cuenta" class="btn btn-secundario">
                            ${UI.icono('cuota')} Ver Estado de Cuenta
                        </a>
                        ${!esVigente ? `
                            <a href="#socio-pagar" class="btn btn-acento">
                                ${UI.icono('pago')} Pagar Cuotas Pendientes
                            </a>
                        ` : ''}
                    </div>
                </div>
            `;

            const btnImprimir = contenedor.querySelector('#btn-imprimir-carnet');
            if (btnImprimir) {
                btnImprimir.addEventListener('click', () => window.print());
            }

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al consultar el carnet:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
