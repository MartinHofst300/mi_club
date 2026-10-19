/**
 * Vista de Consulta de Socios Morosos (Caso de uso Consultar morosos, Reglas R1 y R4).
 * Capa: Presentacion / Vistas.
 *
 * Muestra el padron de socios en condicion de mora (3 o mas cuotas impagas vencidas),
 * calculo consolidado de deuda y acciones rapidas de cobro en caja o aplicacion de recargos.
 */

const VistaMorosos = (function () {
    /**
     * Renderiza la nomina de morosos.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Consultando padron de morosos...');

        try {
            const morosos = await Api.get('/socios/morosos');

            let totalDeudaMorosos = 0;
            morosos.forEach(m => {
                totalDeudaMorosos += parseFloat(m.deuda_total) || 0;
            });

            let filas = '';
            if (morosos.length === 0) {
                filas = `
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--color-texto-suave); padding: 2.5rem;">
                            No se registran socios en condicion de morosidad actualmente.
                        </td>
                    </tr>
                `;
            } else {
                filas = morosos.map(m => {
                    return `
                        <tr>
                            <td style="font-family: var(--fuente-mono); font-weight: 600;">${UI.escaparHtml(m.numero_socio)}</td>
                            <td>
                                <strong>${UI.escaparHtml(m.nombre)} ${UI.escaparHtml(m.apellido)}</strong>
                            </td>
                            <td>${UI.escaparHtml(m.dni)}</td>
                            <td><span class="etiqueta etiqueta-secundaria">${UI.escaparHtml(m.categoria_nombre)}</span></td>
                            <td style="text-align: center;">
                                <span class="etiqueta etiqueta-peligro">${m.cuotas_impagas} cuotas</span>
                            </td>
                            <td style="font-weight: 700; color: var(--color-error);">
                                ${UI.formatearMoneda(m.deuda_total)}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <a href="#socio-estado-cuenta/${m.id}" class="btn btn-sm btn-secundario" title="Ver cuenta y anular cuotas">
                                        ${UI.icono('cuota')} Cuenta
                                    </a>
                                    <a href="#registrar-pago" class="btn btn-sm btn-primario" title="Cobrar cuotas en caja">
                                        ${UI.icono('pago')} Cobrar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            contenedor.innerHTML = `
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Padron de Socios Morosos</h2>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Socios con 3 o mas cuotas sociales impagas vencidas (Regla R1).
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            <button type="button" class="btn btn-acento" id="btn-morosos-aplicar-recargos">
                                ${UI.icono('alerta')} Aplicar Recargos por Mora
                            </button>
                        </div>
                    </div>

                    <!-- Metricas de morosidad -->
                    <div class="rejilla-resumen">
                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('morosos')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Socios Morosos</div>
                                <div class="metrica-valor" style="color: var(--color-error);">
                                    ${morosos.length}
                                </div>
                            </div>
                        </div>

                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('pago')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Deuda Total en Mora</div>
                                <div class="metrica-valor" style="color: var(--color-error);">
                                    ${UI.formatearMoneda(totalDeudaMorosos)}
                                </div>
                            </div>
                        </div>

                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('carnet')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Criterio Institucional</div>
                                <div class="metrica-valor" style="font-size: 1.1rem;">
                                    >= 3 cuotas vencidas
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de morosos -->
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Nomina de Morosidad</h3>
                            <span style="font-size: 0.85rem; color: var(--color-texto-suave);">
                                Acceso a estado de cuenta para anulacion de cuotas o registro de pagos
                            </span>
                        </div>
                        <div class="tabla-contenedor">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Numero Socio</th>
                                        <th>Nombre y Apellido</th>
                                        <th>DNI</th>
                                        <th>Categoria</th>
                                        <th style="text-align: center;">Cuotas Impagas</th>
                                        <th>Deuda Total</th>
                                        <th style="text-align: right;">Acciones</th>
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

            // Accion: Aplicar recargos desde la interfaz
            const btnRecargos = contenedor.querySelector('#btn-morosos-aplicar-recargos');
            if (btnRecargos) {
                btnRecargos.addEventListener('click', async () => {
                    const confirmado = await UI.confirmar(
                        'Desea ejecutar la aplicacion automatica del 10% de recargo por mora sobre todas las cuotas vencidas que aun no lo tengan aplicado (Regla R4)?',
                        'Aplicar Recargos por Mora'
                    );

                    if (confirmado) {
                        btnRecargos.disabled = true;
                        btnRecargos.textContent = 'Aplicando recargos...';

                        try {
                            const res = await Api.post('/cuotas/aplicar-recargos', {});
                            UI.notificar(
                                `Se aplicaron recargos a ${res.cuotas_actualizadas} cuota(s) vencida(s).`,
                                'exito'
                            );
                            await render(contenedor);
                        } catch (error) {
                            btnRecargos.disabled = false;
                            btnRecargos.innerHTML = `${UI.icono('alerta')} Aplicar Recargos por Mora`;
                            UI.notificar(error.message, 'error');
                        }
                    }
                });
            }

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al consultar morosos:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
