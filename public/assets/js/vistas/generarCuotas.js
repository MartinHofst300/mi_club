/**
 * Vista de Generacion de Cuotas Sociales y Aplicacion de Recargos (Reglas R2, R3, R4, R5, R6).
 * Capa: Presentacion / Vistas.
 */

const VistaGenerarCuotas = {
    render: async function (contenedor) {
        // Sugerir el proximo mes o mes actual (ej: 2026-11)
        const fechaActual = new Date();
        const anio = fechaActual.getFullYear();
        const mesSiguiente = String(fechaActual.getMonth() + 2).padStart(2, '0');
        const periodoSugerido = `${anio}-${mesSiguiente}`;

        contenedor.innerHTML = `
            <div class="ancho-contenido">
                <div class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <h3 class="tarjeta-titulo">Generacion Mensual de Cuotas (Regla R6)</h3>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <p>
                            Este proceso emite la obligacion de pago para todos los socios en estado <strong>ACTIVO</strong> o <strong>MOROSO</strong>.
                            Aplica automaticamente el descuento familiar del 15% (Regla R5) cuando correspondan 2 o mas integrantes activos.
                        </p>

                        <form id="form-generar-cuotas" style="max-width: 500px; margin-top: 1.5rem;">
                            <div class="grupo-formulario">
                                <label for="input-periodo" class="etiqueta-formulario">Periodo a Emitir (Formato AAAA-MM)</label>
                                <input type="month" id="input-periodo" class="control-formulario" value="${periodoSugerido}" required>
                                <div class="texto-ayuda">El vencimiento se computara segun el dia configurado del club.</div>
                            </div>

                            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primario" id="btn-ejecutar-generacion">
                                    ${UI.icono('cuota')} Generar Lote de Cuotas
                                </button>
                                <button type="button" class="btn btn-secundario" id="btn-aplicar-recargos">
                                    ${UI.icono('alerta')} Aplicar Recargos por Mora (R4)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="resultado-generacion" style="display: none;"></div>
            </div>
        `;

        const form = contenedor.querySelector('#form-generar-cuotas');
        const btnGenerar = contenedor.querySelector('#btn-ejecutar-generacion');
        const btnRecargos = contenedor.querySelector('#btn-aplicar-recargos');
        const divResultado = contenedor.querySelector('#resultado-generacion');

        // Accion 1: Generar cuotas
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const periodo = contenedor.querySelector('#input-periodo').value;

            const confirmado = await UI.confirmar(
                `Desea emitir el lote de cuotas sociales correspondiente al periodo ${periodo}? Las cuotas ya existentes seran omitidas.`,
                'Confirmar Generacion de Cuotas'
            );

            if (!confirmado) return;

            btnGenerar.disabled = true;
            btnGenerar.textContent = 'Procesando generacion...';

            try {
                const respuesta = await Api.post('/cuotas/generar', { periodo });
                UI.notificar(`Generacion completada: ${respuesta.generadas} generadas, ${respuesta.omitidas} omitidas.`, 'exito');

                let filasDetalle = '';
                (respuesta.detalles || []).forEach(d => {
                    const claseBadge = d.estado === 'GENERADA' ? 'etiqueta-activo' : (d.estado === 'OMITIDA' ? 'etiqueta-suspendido' : 'etiqueta-moroso');
                    filasDetalle += `
                        <tr>
                            <td>${UI.escaparHtml(d.socio)}</td>
                            <td><span class="etiqueta ${claseBadge}">${UI.escaparHtml(d.estado)}</span></td>
                            <td>${d.monto_base ? UI.formatearMoneda(d.monto_base) : '-'}</td>
                            <td>${d.descuento_familiar ? UI.formatearMoneda(d.descuento_familiar) : '$ 0,00'}</td>
                            <td><strong>${d.monto_final ? UI.formatearMoneda(d.monto_final) : '-'}</strong></td>
                            <td style="font-size: 0.8rem; color: var(--color-texto-suave);">${UI.escaparHtml(d.motivo || 'Emitida correctamente')}</td>
                        </tr>
                    `;
                });

                divResultado.innerHTML = `
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Resultado del Proceso - Periodo ${UI.escaparHtml(respuesta.periodo)}</h3>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <div class="rejilla-resumen" style="margin-bottom: 1.5rem;">
                                <div class="caja-metrica">
                                    <span class="etiqueta-metrica">Cuotas Generadas</span>
                                    <span class="valor-metrica" style="color: var(--color-exito);">${respuesta.generadas}</span>
                                </div>
                                <div class="caja-metrica">
                                    <span class="etiqueta-metrica">Omitidas (Existentes)</span>
                                    <span class="valor-metrica" style="color: var(--color-acento);">${respuesta.omitidas}</span>
                                </div>
                                <div class="caja-metrica">
                                    <span class="etiqueta-metrica">Con Error</span>
                                    <span class="valor-metrica" style="color: var(--color-error);">${respuesta.errores}</span>
                                </div>
                            </div>

                            <h4 style="margin-bottom: 0.75rem;">Detalle por Socio</h4>
                            <div class="tabla-responsiva">
                                <table class="tabla">
                                    <thead>
                                        <tr>
                                            <th>Socio</th>
                                            <th>Resultado</th>
                                            <th>Base</th>
                                            <th>Desc. Familiar</th>
                                            <th>Final</th>
                                            <th>Observacion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${filasDetalle}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                divResultado.style.display = 'block';
            } catch (error) {
                UI.notificar(error.message, 'error');
            } finally {
                btnGenerar.disabled = false;
                btnGenerar.innerHTML = `${UI.icono('cuota')} Generar Lote de Cuotas`;
            }
        });

        // Accion 2: Aplicar recargos por mora (Regla R4)
        btnRecargos.addEventListener('click', async () => {
            const confirmado = await UI.confirmar(
                'Desea calcular y aplicar el recargo por mora (10% sobre la base) a todas las cuotas vencidas que aun no lo hayan recibido?',
                'Aplicar Recargos por Mora'
            );

            if (!confirmado) return;

            btnRecargos.disabled = true;
            btnRecargos.textContent = 'Aplicando recargos...';

            try {
                const res = await Api.post('/cuotas/aplicar-recargos', {});
                UI.notificar(`Se aplicaron recargos a ${res.cuotas_actualizadas} cuota(s) vencida(s).`, 'exito');
            } catch (error) {
                UI.notificar(error.message, 'error');
            } finally {
                btnRecargos.disabled = false;
                btnRecargos.innerHTML = `${UI.icono('alerta')} Aplicar Recargos por Mora (R4)`;
            }
        });
    }
};
