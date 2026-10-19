/**
 * Vista de Recibo Oficial Imprimible de Cobro (Regla R7).
 * Capa: Presentacion / Vistas.
 */

const VistaRecibo = {
    render: async function (contenedor, parametro) {
        const reciboId = (parametro && typeof parametro === 'object') ? parametro.id : parametro;
        if (!reciboId) {
            contenedor.innerHTML = `
                <div class="aviso aviso-advertencia">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>Identificador de recibo no especificado.</div>
                </div>
            `;
            return;
        }

        UI.mostrarCargando(contenedor, 'Cargando comprobante oficial...');

        try {
            const recibo = await Api.get(`/recibos/${reciboId}`);

            let filasItems = '';
            (recibo.items || []).forEach(item => {
                filasItems += `
                    <tr>
                        <td><strong>Cuota Social Periodo ${UI.escaparHtml(item.periodo)}</strong></td>
                        <td>${UI.formatearMoneda(item.monto_base)}</td>
                        <td>${parseFloat(item.monto_descuento) > 0 ? `-${UI.formatearMoneda(item.monto_descuento)}` : '-'}</td>
                        <td>${parseFloat(item.monto_recargo) > 0 ? `+${UI.formatearMoneda(item.monto_recargo)}` : '-'}</td>
                        <td style="text-align: right; font-weight: 700;">${UI.formatearMoneda(item.monto_imputado)}</td>
                    </tr>
                `;
            });

            contenedor.innerHTML = `
                <div class="ancho-contenido">
                    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;" class="no-imprimir">
                        <button type="button" class="btn btn-secundario" onclick="window.history.back()">
                            Volver
                        </button>
                        <button type="button" class="btn btn-primario" onclick="window.print()">
                            ${UI.icono('imprimir')} Imprimir Recibo
                        </button>
                    </div>

                    <div class="recibo-hoja">
                        <div class="recibo-membrete">
                            <div class="recibo-club-info">
                                <h2 class="nombre-club-dinamico">${UI.escaparHtml(recibo.club.nombre)}</h2>
                                <p style="font-size: 0.85rem; color: var(--color-texto-suave); margin: 0;">Asociacion Civil Sin Fines de Lucro</p>
                                <p style="font-size: 0.85rem; color: var(--color-texto-suave); margin: 0;">Comprobante Oficial de Cobro de Cuotas</p>
                            </div>
                            <div class="recibo-datos-emision">
                                <div class="recibo-numero">${UI.escaparHtml(recibo.numero_recibo)}</div>
                                <div style="font-size: 0.85rem; color: var(--color-texto-suave); margin-top: 0.25rem;">
                                    Fecha de Emision: <strong>${UI.formatearFecha(recibo.fecha_emision)}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="recibo-seccion-socio">
                            <div>
                                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-texto-suave);">Socio Titular</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: var(--color-texto);">
                                    ${UI.escaparHtml(recibo.socio_apellido)}, ${UI.escaparHtml(recibo.socio_nombre)}
                                </div>
                                <div style="font-size: 0.85rem; color: var(--color-texto); margin-top: 0.2rem;">
                                    DNI: <strong>${UI.escaparHtml(recibo.socio_dni)}</strong> | Domicilio: ${UI.escaparHtml(recibo.socio_domicilio || '-')}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-texto-suave);">Numero de Socio</div>
                                <div style="font-size: 1.1rem; font-weight: 700; font-family: var(--fuente-mono); color: var(--color-primario);">
                                    ${UI.escaparHtml(recibo.numero_socio)}
                                </div>
                                <div style="font-size: 0.85rem; color: var(--color-texto); margin-top: 0.2rem;">
                                    Medio: <strong>${UI.escaparHtml(recibo.medio_pago)}</strong>
                                </div>
                            </div>
                        </div>

                        <h4 style="margin-bottom: 0.75rem; font-size: 1rem;">Detalle de Imputacion de Cuotas Sociales</h4>
                        <div class="tabla-responsiva" style="border: 1px solid var(--color-borde); margin-bottom: 1.5rem;">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Monto Base</th>
                                        <th>Desc. Familiar</th>
                                        <th>Recargo Mora</th>
                                        <th style="text-align: right;">Importe Imputado</th>
                                    </tr>
                                </thead>
                                <tbody>${filasItems}</tbody>
                            </table>
                        </div>

                        <div class="recibo-total-caja">
                            <span>TOTAL PERCIBIDO:</span>
                            <span style="font-size: 1.4rem; color: var(--color-primario); font-family: var(--fuente-mono);">
                                ${UI.formatearMoneda(recibo.monto_total)}
                            </span>
                        </div>

                        ${recibo.observaciones ? `
                            <div style="font-size: 0.85rem; color: var(--color-texto-suave); margin-top: 1rem;">
                                Observaciones: <em>${UI.escaparHtml(recibo.observaciones)}</em>
                            </div>
                        ` : ''}

                        <div class="recibo-firmas">
                            <div>
                                <div class="linea-firma">
                                    Firma y Aclaracion del Socio
                                </div>
                            </div>
                            <div>
                                <div class="linea-firma">
                                    Tesoreria / Administracion (${UI.escaparHtml(recibo.cajero_nombre ? `${recibo.cajero_nombre} ${recibo.cajero_apellido}` : 'Sistema')})
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            contenedor.innerHTML = `
                <div class="aviso aviso-error">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>${UI.escaparHtml(error.message)}</div>
                </div>
            `;
        }
    }
};
