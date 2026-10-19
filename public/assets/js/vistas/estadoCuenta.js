/**
 * Vista de Estado de Cuenta y Deuda del Socio (Casos de uso Ver estado de cuenta y Anular cuota).
 * Capa: Presentacion / Vistas.
 *
 * Muestra el resumen contable, total adeudado, cuotas vencidas/pendientes con sus recargos,
 * y permite a Tesoreros o Administradores anular cuotas con motivo segun Regla R2.
 */

const VistaEstadoCuenta = (function () {
    /**
     * Renderiza el estado de cuenta del socio autenticado o consultado.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     * @param {object} parametros Parametros de la ruta (opcional :id)
     */
    async function render(contenedor, parametros = {}) {
        UI.mostrarCargando(contenedor, 'Cargando estado de cuenta contable...');

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
                        <div style="margin-top: 0.25rem;">No se encontro un perfil de socio vinculado a su cuenta de usuario para consultar el estado de cuenta.</div>
                    </div>
                </div>
            `;
            return;
        }

        try {
            const data = await Api.get(`/socios/${socioId}/estado-cuenta`);
            const socio = data.socio;
            const resumen = data.resumen;
            const cuotas = data.cuotas || [];
            const rol = Auth.obtenerRol() || '';
            const puedeAnular = (rol === 'TESORERO' || rol === 'ADMINISTRADOR');

            let badgeEstadoSocio = 'etiqueta-exito';
            if (socio.estado === 'MOROSO') badgeEstadoSocio = 'etiqueta-peligro';
            if (socio.estado === 'SUSPENDIDO') badgeEstadoSocio = 'etiqueta-advertencia';
            if (socio.estado === 'BAJA') badgeEstadoSocio = 'etiqueta-secundaria';

            // Filas de cuotas
            let filasCuotas = '';
            if (cuotas.length === 0) {
                filasCuotas = `
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--color-texto-suave); padding: 2rem;">
                            No se registran cuotas emitidas para este socio.
                        </td>
                    </tr>
                `;
            } else {
                filasCuotas = cuotas.map(c => {
                    let badgeClase = 'etiqueta-secundaria';
                    if (c.estado === 'PAGADA') badgeClase = 'etiqueta-exito';
                    if (c.estado === 'PENDIENTE') badgeClase = 'etiqueta-advertencia';
                    if (c.estado === 'VENCIDA') badgeClase = 'etiqueta-peligro';
                    if (c.estado === 'ANULADA') badgeClase = 'etiqueta-secundaria';

                    const esImpaga = (c.estado !== 'PAGADA' && c.estado !== 'ANULADA');

                    let botonAnular = '';
                    if (puedeAnular && esImpaga) {
                        botonAnular = `
                            <button type="button" class="btn btn-sm btn-peligro btn-anular-cuota" data-id="${c.id}" data-periodo="${c.periodo}">
                                ${UI.icono('anular')} Anular
                            </button>
                        `;
                    } else if (c.estado === 'ANULADA') {
                        botonAnular = `<span style="font-size: 0.75rem; color: var(--color-texto-suave);">${UI.escaparHtml(c.motivo_anulacion || 'Anulada')}</span>`;
                    }

                    return `
                        <tr>
                            <td style="font-family: var(--fuente-mono); font-weight: 600;">${UI.escaparHtml(c.periodo)}</td>
                            <td>${UI.formatearMoneda(c.monto_base)}</td>
                            <td>${parseFloat(c.monto_descuento) > 0 ? '-' + UI.formatearMoneda(c.monto_descuento) : '$ 0,00'}</td>
                            <td>${parseFloat(c.monto_recargo) > 0 ? '+' + UI.formatearMoneda(c.monto_recargo) : '$ 0,00'}</td>
                            <td style="font-weight: 600;">${UI.formatearMoneda(c.monto_final)}</td>
                            <td style="color: var(--color-exito);">${UI.formatearMoneda(c.monto_pagado)}</td>
                            <td style="font-weight: 700; color: ${parseFloat(c.saldo) > 0 ? 'var(--color-error)' : 'inherit'};">
                                ${UI.formatearMoneda(c.saldo)}
                            </td>
                            <td>${UI.formatearFecha(c.fecha_vencimiento)}</td>
                            <td><span class="etiqueta ${badgeClase}">${UI.escaparHtml(c.estado)}</span></td>
                            ${puedeAnular ? `<td style="text-align: right;">${botonAnular}</td>` : ''}
                        </tr>
                    `;
                }).join('');
            }

            contenedor.innerHTML = `
                <div>
                    <!-- Encabezado de perfil y acciones -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">
                                    ${UI.escaparHtml(socio.nombre)} ${UI.escaparHtml(socio.apellido)}
                                </h2>
                                <span class="etiqueta ${badgeEstadoSocio}">${UI.escaparHtml(socio.estado)}</span>
                            </div>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Socio N° <strong>${UI.escaparHtml(socio.numero_socio)}</strong> | DNI: <strong>${UI.escaparHtml(socio.dni)}</strong>
                            </p>
                        </div>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="#socio-historial${parametros.id ? '/' + parametros.id : ''}" class="btn btn-secundario">
                                ${UI.icono('recibo')} Ver Historial de Pagos
                            </a>
                            ${resumen.total_adeudado > 0 ? `
                                <a href="#socio-pagar${parametros.id ? '/' + parametros.id : ''}" class="btn btn-acento">
                                    ${UI.icono('pago')} Pagar Cuotas Pendientes
                                </a>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Tarjetas de resumen economico -->
                    <div class="rejilla-resumen">
                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('pago')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Deuda Total Adeudada</div>
                                <div class="metrica-valor" style="color: ${resumen.total_adeudado > 0 ? 'var(--color-error)' : 'var(--color-exito)'};">
                                    ${UI.formatearMoneda(resumen.total_adeudado)}
                                </div>
                            </div>
                        </div>

                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('cuota')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Cuotas Impagas</div>
                                <div class="metrica-valor">
                                    ${resumen.cuotas_impagas}
                                </div>
                            </div>
                        </div>

                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('carnet')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Estado del Socio (R1)</div>
                                <div class="metrica-valor" style="font-size: 1.25rem;">
                                    ${UI.escaparHtml(socio.estado)}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de cuotas sociales -->
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Detalle de Cuotas Sociales</h3>
                            <span style="font-size: 0.85rem; color: var(--color-texto-suave);">
                                Imputacion automatica en orden cronologico (Regla R7)
                            </span>
                        </div>
                        <div class="tabla-contenedor">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Periodo</th>
                                        <th>Base</th>
                                        <th>Desc. Fam.</th>
                                        <th>Recargo</th>
                                        <th>Final</th>
                                        <th>Pagado</th>
                                        <th>Saldo</th>
                                        <th>Vencimiento</th>
                                        <th>Estado</th>
                                        ${puedeAnular ? '<th style="text-align: right;">Acciones</th>' : ''}
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filasCuotas}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            // Vincular botones de anulacion para Tesorero / Administrador
            if (puedeAnular) {
                contenedor.querySelectorAll('.btn-anular-cuota').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const cuotaId = parseInt(btn.dataset.id, 10);
                        const periodo = btn.dataset.periodo;

                        await mostrarModalAnulacion(cuotaId, periodo, contenedor, parametros);
                    });
                });
            }

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al consultar estado de cuenta:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    /**
     * Modal interactivo para anular una cuota solicitando el motivo obligatorio (Regla R2).
     */
    function mostrarModalAnulacion(cuotaId, periodo, contenedor, parametros) {
        return new Promise((resolve) => {
            const modalFondo = document.createElement('div');
            modalFondo.className = 'modal-fondo';
            modalFondo.innerHTML = `
                <div class="modal-caja" style="max-width: 480px;">
                    <div class="modal-cabecera">
                        <h3 class="modal-titulo">Anular Cuota Social - Periodo ${UI.escaparHtml(periodo)}</h3>
                    </div>
                    <div class="modal-cuerpo">
                        <p style="margin-bottom: 1rem; font-size: 0.9rem; color: var(--color-texto-suave);">
                            Al anular la cuota, dejara de computarse como saldo impago y se recalculara el estado del socio (Regla R2).
                            Esta accion requiere ingresar una justificacion obligatoria.
                        </p>
                        <div class="campo-grupo">
                            <label class="etiqueta-campo" for="motivo-anulacion-txt">Motivo de anulacion *</label>
                            <textarea id="motivo-anulacion-txt" class="input-control" rows="3" placeholder="Ej: Error en emision administrativa o condonacion autorizada por Comision Directiva..." required></textarea>
                            <div id="error-motivo-anulacion" style="color: var(--color-error); font-size: 0.8rem; margin-top: 0.25rem; display: none;"></div>
                        </div>
                    </div>
                    <div class="modal-pie">
                        <button type="button" class="btn btn-secundario" id="btn-cancelar-anulacion">Cancelar</button>
                        <button type="button" class="btn btn-peligro" id="btn-confirmar-anulacion">
                            ${UI.icono('anular')} Confirmar Anulacion
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modalFondo);

            const btnCancelar = modalFondo.querySelector('#btn-cancelar-anulacion');
            const btnConfirmar = modalFondo.querySelector('#btn-confirmar-anulacion');
            const txtMotivo = modalFondo.querySelector('#motivo-anulacion-txt');
            const errorDiv = modalFondo.querySelector('#error-motivo-anulacion');

            btnCancelar.addEventListener('click', () => {
                modalFondo.remove();
                resolve(false);
            });

            btnConfirmar.addEventListener('click', async () => {
                const motivo = txtMotivo.value.trim();
                if (motivo.length < 5) {
                    errorDiv.textContent = 'Debe indicar un motivo detallado (minimo 5 caracteres).';
                    errorDiv.style.display = 'block';
                    txtMotivo.focus();
                    return;
                }

                btnConfirmar.disabled = true;
                btnConfirmar.textContent = 'Procesando...';

                try {
                    const res = await Api.post(`/cuotas/${cuotaId}/anular`, { motivo });
                    UI.notificar(res.mensaje || 'Cuota anulada exitosamente.', 'exito');
                    modalFondo.remove();
                    resolve(true);
                    // Recargar vista para reflejar el nuevo estado contable del socio
                    await render(contenedor, parametros);
                } catch (error) {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = `${UI.icono('anular')} Confirmar Anulacion`;
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                }
            });
        });
    }

    return {
        render
    };
})();
