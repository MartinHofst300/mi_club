/**
 * Vista de Historial de Pagos y Recibos del Socio (Caso de uso Historial de pagos y Emitir recibo).
 * Capa: Presentacion / Vistas.
 *
 * Muestra el registro cronologico de pagos percibidos, desglosando fecha,
 * medio de pago, canal (autogestion / caja) y enlace directo al comprobante oficial.
 */

const VistaHistorialPagos = (function () {
    /**
     * Renderiza el listado historico de pagos del socio.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     * @param {object} parametros Parametros de la ruta (opcional :id)
     */
    async function render(contenedor, parametros = {}) {
        UI.mostrarCargando(contenedor, 'Cargando historial de pagos...');

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
                        <div style="margin-top: 0.25rem;">No se encontro un perfil de socio vinculado a su cuenta de usuario para consultar el historial de pagos.</div>
                    </div>
                </div>
            `;
            return;
        }

        try {
            const pagos = await Api.get(`/socios/${socioId}/pagos`);

            let filas = '';
            if (pagos.length === 0) {
                filas = `
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--color-texto-suave); padding: 2.5rem;">
                            No se registran pagos percibidos para este socio.
                        </td>
                    </tr>
                `;
            } else {
                filas = pagos.map(p => {
                    const autogestionadoTexto = p.autogestionado ? 'Portal Web (Autogestion)' : 'Cobro en Caja';
                    const medioTexto = p.medio_pago ? p.medio_pago.replace('_', ' ') : 'EFECTIVO';

                    return `
                        <tr>
                            <td style="font-family: var(--fuente-mono); font-weight: 600;">#${p.id}</td>
                            <td>${UI.formatearFecha(p.fecha_pago)}</td>
                            <td style="font-weight: 700; color: var(--color-exito);">${UI.formatearMoneda(p.monto_total)}</td>
                            <td><span class="etiqueta etiqueta-secundaria">${UI.escaparHtml(medioTexto)}</span></td>
                            <td style="font-size: 0.85rem; color: var(--color-texto-suave);">${UI.escaparHtml(autogestionadoTexto)}</td>
                            <td style="font-family: var(--fuente-mono); font-weight: 600; color: var(--color-primario);">
                                ${UI.escaparHtml(p.numero_recibo || '-')}
                            </td>
                            <td style="text-align: right;">
                                ${p.recibo_id ? `
                                    <a href="#recibo/${p.recibo_id}" class="btn btn-sm btn-primario">
                                        ${UI.icono('recibo')} Ver Recibo
                                    </a>
                                ` : '-'}
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            contenedor.innerHTML = `
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Historial de Pagos y Recibos</h2>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Comprobantes emitidos y registro de cancelaciones contables.
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="#socio-estado-cuenta${parametros.id ? '/' + parametros.id : ''}" class="btn btn-secundario">
                                ${UI.icono('cuota')} Volver al Estado de Cuenta
                            </a>
                        </div>
                    </div>

                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Pagos Registrados</h3>
                        </div>
                        <div class="tabla-contenedor">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>ID Pago</th>
                                        <th>Fecha y Hora</th>
                                        <th>Monto Total</th>
                                        <th>Medio de Pago</th>
                                        <th>Canal</th>
                                        <th>Numero de Recibo</th>
                                        <th style="text-align: right;">Comprobante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filas}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al consultar historial:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
