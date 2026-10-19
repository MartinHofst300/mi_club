/**
 * Vista de Cobro de Cuotas e Imputacion Automatica (Regla R7).
 * Capa: Presentacion / Vistas.
 */

const VistaRegistrarPago = {
    render: async function (contenedor) {
        contenedor.innerHTML = `
            <div class="ancho-contenido">
                <div class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <h3 class="tarjeta-titulo">Registro de Cobro e Imputacion (Regla R7)</h3>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <p>Busque al socio por DNI, apellido, nombre o numero de socio para consultar su estado contable:</p>
                        
                        <div class="barra-busqueda" style="max-width: 600px; margin-top: 1rem;">
                            <input type="text" id="busqueda-socio" class="control-formulario" placeholder="Ingrese DNI, apellido o numero (ej: 35123456 o Moroso)">
                            <button type="button" class="btn btn-primario" id="btn-buscar-socio">
                                ${UI.icono('buscar')} Buscar
                            </button>
                        </div>

                        <div id="contenedor-resultados-busqueda" style="margin-top: 1rem; display: none;"></div>
                    </div>
                </div>

                <div id="contenedor-detalle-deuda" style="display: none;"></div>
            </div>
        `;

        const inputBusqueda = contenedor.querySelector('#busqueda-socio');
        const btnBuscar = contenedor.querySelector('#btn-buscar-socio');
        const contResultados = contenedor.querySelector('#contenedor-resultados-busqueda');
        const contDetalle = contenedor.querySelector('#contenedor-detalle-deuda');

        async function buscarSocios() {
            const termino = inputBusqueda.value.trim();
            if (!termino) {
                UI.notificar('Ingrese un termino de busqueda.', 'advertencia');
                return;
            }

            btnBuscar.disabled = true;
            btnBuscar.textContent = 'Buscando...';

            try {
                const socios = await Api.get(`/socios?q=${encodeURIComponent(termino)}`);
                if (socios.length === 0) {
                    contResultados.innerHTML = `<div class="aviso aviso-info">No se encontraron socios que coincidan con la busqueda.</div>`;
                    contResultados.style.display = 'block';
                    contDetalle.style.display = 'none';
                    return;
                }

                let filas = '';
                socios.forEach(s => {
                    const claseBadge = `etiqueta-${s.estado.toLowerCase()}`;
                    filas += `
                        <tr>
                            <td><strong>${UI.escaparHtml(s.numero_socio)}</strong></td>
                            <td>${UI.escaparHtml(s.apellido)}, ${UI.escaparHtml(s.nombre)}</td>
                            <td>${UI.escaparHtml(s.dni)}</td>
                            <td>${UI.escaparHtml(s.categoria_nombre)}</td>
                            <td><span class="etiqueta ${claseBadge}">${UI.escaparHtml(s.estado)}</span></td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-primario btn-seleccionar-socio" data-id="${s.id}">
                                    Seleccionar
                                </button>
                            </td>
                        </tr>
                    `;
                });

                contResultados.innerHTML = `
                    <div class="tabla-responsiva">
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th>N° Socio</th>
                                    <th>Apellido y Nombre</th>
                                    <th>DNI</th>
                                    <th>Categoria</th>
                                    <th>Estado</th>
                                    <th style="text-align: right;">Accion</th>
                                </tr>
                            </thead>
                            <tbody>${filas}</tbody>
                        </table>
                    </div>
                `;
                contResultados.style.display = 'block';

                contResultados.querySelectorAll('.btn-seleccionar-socio').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const socioId = parseInt(btn.getAttribute('data-id'), 10);
                        cargarEstadoDeuda(socioId);
                    });
                });
            } catch (error) {
                UI.notificar(error.message, 'error');
            } finally {
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = `${UI.icono('buscar')} Buscar`;
            }
        }

        btnBuscar.addEventListener('click', buscarSocios);
        inputBusqueda.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') buscarSocios();
        });

        async function cargarEstadoDeuda(socioId) {
            UI.mostrarCargando(contDetalle, 'Obteniendo estado de cuenta actualizado...');
            contDetalle.style.display = 'block';

            try {
                const datos = await Api.get(`/socios/${socioId}/estado-cuenta`);
                const socio = datos.socio;
                const resumen = datos.resumen;
                const cuotas = datos.cuotas || [];

                let cuotasImpagas = cuotas.filter(c => c.estado !== 'PAGADA' && c.estado !== 'ANULADA' && parseFloat(c.saldo) > 0);

                let filasCuotas = '';
                if (cuotasImpagas.length === 0) {
                    filasCuotas = `<tr><td colspan="7" style="text-align: center; color: var(--color-exito); font-weight: 600; padding: 2rem;">El socio se encuentra al dia (no registra cuotas adeudadas).</td></tr>`;
                } else {
                    cuotasImpagas.forEach(c => {
                        const claseBadge = `etiqueta-${c.estado.toLowerCase()}`;
                        filasCuotas += `
                            <tr>
                                <td><strong>${UI.escaparHtml(c.periodo)}</strong></td>
                                <td>${UI.formatearFecha(c.fecha_vencimiento)}</td>
                                <td>${UI.formatearMoneda(c.monto_final)}</td>
                                <td>${UI.formatearMoneda(c.monto_pagado)}</td>
                                <td style="color: var(--color-error); font-weight: 700;">${UI.formatearMoneda(c.saldo)}</td>
                                <td><span class="etiqueta ${claseBadge}">${UI.escaparHtml(c.estado)}</span></td>
                                <td style="font-size: 0.8rem; color: var(--color-texto-suave);">
                                    ${parseFloat(c.monto_recargo) > 0 ? `Recargo mora: ${UI.formatearMoneda(c.monto_recargo)}` : ''}
                                    ${parseFloat(c.monto_descuento) > 0 ? `Desc. fam: ${UI.formatearMoneda(c.monto_descuento)}` : ''}
                                </td>
                            </tr>
                        `;
                    });
                }

                const claseBadgeSocio = `etiqueta-${socio.estado.toLowerCase()}`;

                contDetalle.innerHTML = `
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">
                                Estado de Cuenta: ${UI.escaparHtml(socio.apellido)}, ${UI.escaparHtml(socio.nombre)} (${UI.escaparHtml(socio.numero_socio)})
                            </h3>
                            <span class="etiqueta ${claseBadgeSocio}">${UI.escaparHtml(socio.estado)}</span>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <div class="rejilla-resumen" style="margin-bottom: 1.5rem;">
                                <div class="caja-metrica">
                                    <span class="etiqueta-metrica">Deuda Total Acumulada</span>
                                    <span class="valor-metrica" style="color: ${resumen.total_adeudado > 0 ? 'var(--color-error)' : 'var(--color-exito)'};">
                                        ${UI.formatearMoneda(resumen.total_adeudado)}
                                    </span>
                                    <span class="detalle-metrica">${resumen.cuotas_impagas} cuota(s) pendiente(s)</span>
                                </div>
                            </div>

                            <h4 style="margin-bottom: 0.75rem;">Cuotas con Saldo Pendiente (Prioridad de Imputacion segun R7)</h4>
                            <div class="tabla-responsiva" style="margin-bottom: 2rem;">
                                <table class="tabla">
                                    <thead>
                                        <tr>
                                            <th>Periodo</th>
                                            <th>Vencimiento</th>
                                            <th>Monto Final</th>
                                            <th>Pagado</th>
                                            <th>Saldo Adeudado</th>
                                            <th>Estado</th>
                                            <th>Detalle Tarifa</th>
                                        </tr>
                                    </thead>
                                    <tbody>${filasCuotas}</tbody>
                                </table>
                            </div>

                            ${resumen.total_adeudado > 0 ? `
                                <div class="tarjeta" style="background-color: var(--color-secundario); border: 1px solid var(--color-borde-fuerte); margin-bottom: 0;">
                                    <div class="tarjeta-cabecera" style="background: transparent;">
                                        <h4 class="tarjeta-titulo">Formulario de Cobro en Mostrador</h4>
                                    </div>
                                    <div class="tarjeta-cuerpo">
                                        <form id="form-registro-cobro">
                                            <div class="rejilla-formulario">
                                                <div class="grupo-formulario">
                                                    <label for="pago-monto" class="etiqueta-formulario">Importe a Cobrar ($)</label>
                                                    <div style="display: flex; gap: 0.5rem;">
                                                        <input type="number" step="0.01" min="0.01" max="${resumen.total_adeudado}" id="pago-monto" class="control-formulario" value="${resumen.total_adeudado}" required>
                                                        <button type="button" class="btn btn-secundario btn-sm" id="btn-monto-total">Total</button>
                                                    </div>
                                                    <div class="texto-ayuda">Maximo permitido: ${UI.formatearMoneda(resumen.total_adeudado)} (no se admite saldo a favor).</div>
                                                </div>

                                                <div class="grupo-formulario">
                                                    <label for="pago-medio" class="etiqueta-formulario">Medio de Pago</label>
                                                    <select id="pago-medio" class="control-formulario" required>
                                                        <option value="EFECTIVO">Efectivo (Caja)</option>
                                                        <option value="TRANSFERENCIA">Transferencia Bancaria</option>
                                                        <option value="TARJETA_SIMULADA">Tarjeta de Debito/Credito</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="grupo-formulario">
                                                <label for="pago-obs" class="etiqueta-formulario">Observaciones (Opcional)</label>
                                                <input type="text" id="pago-obs" class="control-formulario" placeholder="Ej: Comprobante nro, aclaraciones de caja">
                                            </div>

                                            <button type="submit" class="btn btn-acento" id="btn-confirmar-pago" style="margin-top: 1rem;">
                                                ${UI.icono('pago')} Confirmar Cobro e Imputar (R7)
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;

                // Boton para fijar el total
                const btnMontoTotal = contDetalle.querySelector('#btn-monto-total');
                const inputMonto = contDetalle.querySelector('#pago-monto');
                if (btnMontoTotal && inputMonto) {
                    btnMontoTotal.addEventListener('click', () => {
                        inputMonto.value = resumen.total_adeudado;
                    });
                }

                // Envio del formulario de cobro
                const formPago = contDetalle.querySelector('#form-registro-cobro');
                if (formPago) {
                    formPago.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const monto = parseFloat(inputMonto.value);
                        const medio = contDetalle.querySelector('#pago-medio').value;
                        const obs = contDetalle.querySelector('#pago-obs').value.trim();

                        const confirmado = await UI.confirmar(
                            `Confirma el cobro de ${UI.formatearMoneda(monto)} a ${socio.nombre} ${socio.apellido}? El importe sera imputado en estricto orden a las cuotas mas antiguas.`,
                            'Confirmar Percepcion de Pago'
                        );

                        if (!confirmado) return;

                        const btnConfirmar = contDetalle.querySelector('#btn-confirmar-pago');
                        btnConfirmar.disabled = true;
                        btnConfirmar.textContent = 'Procesando pago...';

                        try {
                            const resPago = await Api.post('/pagos', {
                                socio_id: socio.id,
                                monto_total: monto,
                                medio_pago: medio,
                                observaciones: obs || null
                            });

                            UI.notificar(`Pago registrado correctamente. Recibo ${resPago.numero_recibo}`, 'exito');

                            // Mostrar modal con resumen de imputacion y recibo
                            mostrarModalResultadoPago(resPago);

                            // Recargar estado de cuenta
                            cargarEstadoDeuda(socio.id);
                        } catch (error) {
                            UI.notificar(error.message, 'error');
                            btnConfirmar.disabled = false;
                            btnConfirmar.innerHTML = `${UI.icono('pago')} Confirmar Cobro e Imputar (R7)`;
                        }
                    });
                }
            } catch (error) {
                UI.notificar(error.message, 'error');
            }
        }

        function mostrarModalResultadoPago(resPago) {
            let filasImp = '';
            (resPago.imputaciones || []).forEach(i => {
                filasImp += `
                    <tr>
                        <td><strong>${UI.escaparHtml(i.periodo)}</strong></td>
                        <td style="color: var(--color-exito); font-weight: 700;">${UI.formatearMoneda(i.monto_imputado)}</td>
                        <td>${UI.formatearMoneda(i.saldo_restante)}</td>
                        <td><span class="etiqueta etiqueta-${i.estado_final.toLowerCase()}">${UI.escaparHtml(i.estado_final)}</span></td>
                    </tr>
                `;
            });

            const modalFondo = document.createElement('div');
            modalFondo.className = 'modal-fondo';
            modalFondo.innerHTML = `
                <div class="modal-caja modal-caja-grande">
                    <div class="modal-cabecera">
                        <h3 class="modal-titulo">Pago e Imputacion Exitosa</h3>
                    </div>
                    <div class="modal-cuerpo">
                        <div class="aviso aviso-exito" style="margin-bottom: 1.5rem;">
                            <div class="aviso-icono">${UI.icono('check')}</div>
                            <div>
                                <strong>Recibo Oficial N° ${UI.escaparHtml(resPago.numero_recibo)}</strong> emitido por un total de <strong>${UI.formatearMoneda(resPago.monto_total)}</strong> (${UI.escaparHtml(resPago.medio_pago)}).
                                <div style="margin-top: 0.25rem;">
                                    Estado actual del socio: <strong><span class="etiqueta etiqueta-${resPago.socio.estado_actualizado.toLowerCase()}">${UI.escaparHtml(resPago.socio.estado_actualizado)}</span></strong>
                                </div>
                            </div>
                        </div>

                        <h4 style="margin-bottom: 0.75rem;">Detalle de Imputacion Aplicada a Cuotas</h4>
                        <div class="tabla-responsiva" style="margin-bottom: 1.5rem;">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Periodo</th>
                                        <th>Monto Imputado</th>
                                        <th>Saldo Restante</th>
                                        <th>Estado Final</th>
                                    </tr>
                                </thead>
                                <tbody>${filasImp}</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-pie">
                        <button type="button" class="btn btn-secundario" id="btn-cerrar-modal-pago">Cerrar</button>
                        <a href="#recibo/${resPago.recibo_id}" class="btn btn-primario" id="btn-ver-recibo-oficial">
                            ${UI.icono('imprimir')} Ver / Imprimir Recibo Oficial
                        </a>
                    </div>
                </div>
            `;

            document.body.appendChild(modalFondo);

            modalFondo.querySelector('#btn-cerrar-modal-pago').addEventListener('click', () => {
                modalFondo.remove();
            });

            modalFondo.querySelector('#btn-ver-recibo-oficial').addEventListener('click', () => {
                modalFondo.remove();
            });
        }
    }
};
