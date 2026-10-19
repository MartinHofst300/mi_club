/**
 * Vista de Pasarela de Pago Simulada del Socio (Caso de uso Pagar cuota, Regla R7).
 * Capa: Presentacion / Vistas.
 *
 * Permite al socio autogestionar el pago total o parcial de sus cuotas adeudadas,
 * simulando transacciones mediante tarjeta de debito/credito o transferencia bancaria,
 * con imputacion automatica en orden cronologico y emision instantanea de recibo.
 */

const VistaPagarCuota = (function () {
    /**
     * Renderiza el portal de pago simulado.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     * @param {object} parametros Parametros de la ruta (opcional :id)
     */
    async function render(contenedor, parametros = {}) {
        UI.mostrarCargando(contenedor, 'Cargando portal de pagos...');

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
                        <div style="margin-top: 0.25rem;">No se encontro un perfil de socio vinculado a su cuenta de usuario para realizar el pago.</div>
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

            // Filtrar cuotas impagas en orden cronologico
            const cuotasImpagas = cuotas.filter(c => c.estado !== 'PAGADA' && c.estado !== 'ANULADA' && parseFloat(c.saldo) > 0);

            if (cuotasImpagas.length === 0 || resumen.total_adeudado <= 0) {
                contenedor.innerHTML = `
                    <div style="max-width: 600px; margin: 2rem auto; text-align: center;">
                        <div class="tarjeta" style="padding: 3rem 2rem;">
                            <div style="width: 64px; height: 64px; background-color: var(--color-secundario); color: var(--color-exito); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
                                ${UI.icono('check')}
                            </div>
                            <h2 style="color: var(--color-primario-oscuro); margin-bottom: 0.5rem;">Cuotas al Dia</h2>
                            <p style="color: var(--color-texto-suave); margin-bottom: 2rem;">
                                No registras cuotas pendientes de pago en este momento. Tu estado contable se encuentra al dia.
                            </p>
                            <div style="display: flex; gap: 1rem; justify-content: center;">
                                <a href="#socio-carnet" class="btn btn-primario">
                                    ${UI.icono('carnet')} Ver Mi Carnet
                                </a>
                                <a href="#socio-estado-cuenta" class="btn btn-secundario">
                                    ${UI.icono('cuota')} Ver Estado de Cuenta
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            const totalDeuda = resumen.total_adeudado;

            contenedor.innerHTML = `
                <div style="max-width: 960px; margin: 0 auto;">
                    <div style="margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Pagar Cuotas Sociales</h2>
                        <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                            Pasarela de pago en linea con imputacion automatica en orden cronologico (Regla R7).
                        </p>
                    </div>

                    <div class="pasarela-contenedor">
                        <!-- Columna izquierda: Formulario de pago -->
                        <div>
                            <div class="tarjeta">
                                <div class="tarjeta-cabecera">
                                    <h3 class="tarjeta-titulo">Datos de la Transaccion</h3>
                                </div>
                                <div class="tarjeta-cuerpo">
                                    <form id="form-pasarela-pago">
                                        <div class="campo-grupo">
                                            <label class="etiqueta-campo" for="monto-pago-input">Monto a abonar ($) *</label>
                                            <input type="number" step="0.01" min="1" max="${totalDeuda}" value="${totalDeuda.toFixed(2)}" id="monto-pago-input" class="input-control" required style="font-size: 1.25rem; font-weight: 700; color: var(--color-primario);">
                                            <span style="font-size: 0.8rem; color: var(--color-texto-suave);">
                                                Deuda total: <strong>${UI.formatearMoneda(totalDeuda)}</strong> (Maximo admitido, sin saldo a favor).
                                            </span>
                                        </div>

                                        <div class="campo-grupo">
                                            <label class="etiqueta-campo">Medio de Pago *</label>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border: 1px solid var(--color-borde); border-radius: 6px;">
                                                    <input type="radio" name="medio_pago" value="TARJETA_SIMULADA" checked>
                                                    <span style="font-weight: 600;">Tarjeta de Debito / Credito (Simulada)</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border: 1px solid var(--color-borde); border-radius: 6px;">
                                                    <input type="radio" name="medio_pago" value="TRANSFERENCIA">
                                                    <span style="font-weight: 600;">Transferencia Bancaria Inmediata</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Campos de Tarjeta -->
                                        <div id="seccion-tarjeta" style="display: block;">
                                            <div class="campo-grupo">
                                                <label class="etiqueta-campo" for="tarjeta-titular">Nombre y Apellido del Titular</label>
                                                <input type="text" id="tarjeta-titular" class="input-control" value="${UI.escaparHtml(socio.nombre + ' ' + socio.apellido)}" placeholder="Como figura en la tarjeta" required>
                                            </div>

                                            <div class="campo-grupo">
                                                <label class="etiqueta-campo" for="tarjeta-numero">Numero de Tarjeta</label>
                                                <input type="text" id="tarjeta-numero" class="input-control" value="4532 8912 3456 7890" maxlength="19" placeholder="4532 0000 0000 0000" required>
                                            </div>

                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                                <div class="campo-grupo">
                                                    <label class="etiqueta-campo" for="tarjeta-vencimiento">Vencimiento</label>
                                                    <input type="text" id="tarjeta-vencimiento" class="input-control" value="12/28" placeholder="MM/AA" maxlength="5" required>
                                                </div>
                                                <div class="campo-grupo">
                                                    <label class="etiqueta-campo" for="tarjeta-cvv">Codigo Seg. (CVV)</label>
                                                    <input type="password" id="tarjeta-cvv" class="input-control" value="123" placeholder="123" maxlength="4" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Campos de Transferencia -->
                                        <div id="seccion-transferencia" style="display: none; background-color: var(--color-secundario); padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                            <div style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                                <strong>Datos de la Cuenta del Club:</strong><br>
                                                Banco: Banco Ciudad<br>
                                                Alias: <strong>MICLUB.SOCIAL.OFICIAL</strong><br>
                                                CBU: <strong>0290000100000012345678</strong>
                                            </div>
                                            <div class="campo-grupo" style="margin-bottom: 0;">
                                                <label class="etiqueta-campo" for="transferencia-comprobante">Numero de Operacion / Referencia *</label>
                                                <input type="text" id="transferencia-comprobante" class="input-control" placeholder="Ej: OP-89412356">
                                            </div>
                                        </div>

                                        <div id="error-pago-div" class="alerta alerta-error" style="display: none; margin-bottom: 1rem;"></div>

                                        <button type="submit" class="btn btn-acento btn-bloque" id="btn-procesar-pago" style="padding: 0.75rem; font-size: 1rem;">
                                            ${UI.icono('pago')} Confirmar y Pagar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Columna derecha: Vista previa y orden de imputacion -->
                        <div>
                            <!-- Tarjeta simulada de decoracion -->
                            <div class="tarjeta-credito-simulada" id="preview-tarjeta-visual" style="margin-bottom: 1.5rem;">
                                <div class="tarjeta-simulada-chip"></div>
                                <div class="tarjeta-simulada-numero" id="preview-tarjeta-num">4532 8912 3456 7890</div>
                                <div class="tarjeta-simulada-pie">
                                    <div>
                                        <div style="font-size: 0.7rem; opacity: 0.75;">TITULAR</div>
                                        <div class="tarjeta-simulada-titular" id="preview-tarjeta-nom">${UI.escaparHtml(socio.nombre + ' ' + socio.apellido)}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.7rem; opacity: 0.75;">VENCE</div>
                                        <div id="preview-tarjeta-exp">12/28</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desglose de imputacion en vivo -->
                            <div class="tarjeta">
                                <div class="tarjeta-cabecera">
                                    <h3 class="tarjeta-titulo">Simulacion de Imputacion (R7)</h3>
                                </div>
                                <div class="tarjeta-cuerpo" style="padding: 1rem;">
                                    <p style="font-size: 0.8rem; color: var(--color-texto-suave); margin-bottom: 0.75rem;">
                                        El importe ingresado se cancelara en cascada cronologica sobre las cuotas mas antiguas:
                                    </p>
                                    <div id="lista-imputacion-vivo"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Elementos del DOM
            const form = contenedor.querySelector('#form-pasarela-pago');
            const inputMonto = contenedor.querySelector('#monto-pago-input');
            const radiosMedio = contenedor.querySelectorAll('input[name="medio_pago"]');
            const seccionTarjeta = contenedor.querySelector('#seccion-tarjeta');
            const seccionTransf = contenedor.querySelector('#seccion-transferencia');
            const tarjetaVisual = contenedor.querySelector('#preview-tarjeta-visual');
            const listaImputacion = contenedor.querySelector('#lista-imputacion-vivo');
            const errorDiv = contenedor.querySelector('#error-pago-div');
            const btnProcesar = contenedor.querySelector('#btn-procesar-pago');

            // Actualizacion de vista previa de tarjeta
            const inputNum = contenedor.querySelector('#tarjeta-numero');
            const inputTit = contenedor.querySelector('#tarjeta-titular');
            const inputExp = contenedor.querySelector('#tarjeta-vencimiento');

            if (inputNum) inputNum.addEventListener('input', () => {
                contenedor.querySelector('#preview-tarjeta-num').textContent = inputNum.value || '4532 0000 0000 0000';
            });
            if (inputTit) inputTit.addEventListener('input', () => {
                contenedor.querySelector('#preview-tarjeta-nom').textContent = (inputTit.value || 'TITULAR').toUpperCase();
            });
            if (inputExp) inputExp.addEventListener('input', () => {
                contenedor.querySelector('#preview-tarjeta-exp').textContent = inputExp.value || 'MM/AA';
            });

            // Cambio de medio de pago
            radiosMedio.forEach(radio => {
                radio.addEventListener('change', () => {
                    if (radio.value === 'TARJETA_SIMULADA') {
                        seccionTarjeta.style.display = 'block';
                        seccionTransf.style.display = 'none';
                        tarjetaVisual.style.display = 'block';
                    } else {
                        seccionTarjeta.style.display = 'none';
                        seccionTransf.style.display = 'block';
                        tarjetaVisual.style.display = 'none';
                    }
                });
            });

            /**
             * Calcula y renderiza en vivo el desglose de cuotas imputadas segun el monto
             */
            function actualizarDesgloseEnVivo() {
                const monto = parseFloat(inputMonto.value) || 0;
                let remanente = monto;
                let html = '';

                for (const c of cuotasImpagas) {
                    const saldo = parseFloat(c.saldo);
                    const imputado = Math.min(saldo, remanente);
                    const nuevoSaldo = Math.max(0, saldo - imputado);
                    const quedaraPagada = nuevoSaldo <= 0;

                    let estadoPrevisto = quedaraPagada
                        ? '<span class="etiqueta etiqueta-exito">QUEDARA PAGADA</span>'
                        : (imputado > 0 ? '<span class="etiqueta etiqueta-advertencia">PAGO PARCIAL</span>' : '<span class="etiqueta etiqueta-peligro">SIN IMPUTAR</span>');

                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--color-borde); font-size: 0.85rem;">
                            <div>
                                <strong>Periodo ${UI.escaparHtml(c.periodo)}</strong> (Saldo: ${UI.formatearMoneda(saldo)})
                                <div style="font-size: 0.75rem; color: var(--color-texto-suave);">Imputacion: ${UI.formatearMoneda(imputado)}</div>
                            </div>
                            <div>${estadoPrevisto}</div>
                        </div>
                    `;

                    remanente = Math.max(0, remanente - imputado);
                }

                listaImputacion.innerHTML = html;
            }

            inputMonto.addEventListener('input', actualizarDesgloseEnVivo);
            actualizarDesgloseEnVivo();

            // Envio del pago
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                errorDiv.style.display = 'none';

                const monto = parseFloat(inputMonto.value);
                if (!monto || monto <= 0) {
                    errorDiv.textContent = 'El monto a pagar debe ser mayor a cero.';
                    errorDiv.style.display = 'block';
                    return;
                }

                if (monto > totalDeuda) {
                    errorDiv.textContent = `El monto ($${monto.toFixed(2)}) supera la deuda total ($${totalDeuda.toFixed(2)}). El club no admite saldo a favor (Regla R7).`;
                    errorDiv.style.display = 'block';
                    return;
                }

                const medio = form.querySelector('input[name="medio_pago"]:checked').value;
                let observaciones = 'Pago autogestionado por portal de socios';

                if (medio === 'TRANSFERENCIA') {
                    const ref = contenedor.querySelector('#transferencia-comprobante').value.trim();
                    if (!ref) {
                        errorDiv.textContent = 'Debe ingresar el numero de referencia de la transferencia.';
                        errorDiv.style.display = 'block';
                        return;
                    }
                    observaciones = `Transferencia ref: ${ref}`;
                }

                btnProcesar.disabled = true;
                btnProcesar.textContent = 'Procesando transaccion con pasarela...';

                try {
                    const resultado = await Api.post('/pagos', {
                        monto_total: monto,
                        medio_pago: medio,
                        observaciones: observaciones
                    });

                    UI.notificar(`Pago registrado exitosamente. Recibo ${resultado.numero_recibo}`, 'exito');

                    // Redireccionar al comprobante oficial emitido
                    window.location.hash = `#recibo/${resultado.recibo_id}`;

                } catch (error) {
                    btnProcesar.disabled = false;
                    btnProcesar.innerHTML = `${UI.icono('pago')} Confirmar y Pagar`;
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                }
            });

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al cargar pasarela:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
