/**
 * Vista de Gestion de Precios y Parametros Economicos (Caso de uso Administrador).
 * Capa: Presentacion / Vistas.
 */

const VistaPrecios = {
    render: async function (contenedor) {
        contenedor.innerHTML = `
            <div class="contenedor-vista">
                <div class="cabecera-vista" style="margin-bottom: 1.5rem;">
                    <h1 class="titulo-vista" style="font-size: 1.5rem; color: var(--color-primario-oscuro); margin-bottom: 0.25rem;">
                        Gestion de Precios y Politica Economica
                    </h1>
                    <p class="subtitulo-vista" style="font-size: 0.9rem; color: var(--color-texto-suave);">
                        Configuracion de aranceles de cuota por categoria, recargos por mora y descuentos de grupo familiar.
                    </p>
                </div>

                <div id="alerta-precios"></div>

                <div class="alerta alerta-info" style="margin-bottom: 1.5rem; font-size: 0.88rem;">
                    <strong>Principio de inalterabilidad historica:</strong> Cambiar un precio o parametro rige unicamente para las cuotas que se emitan en periodos futuros. Las cuotas generadas previamente conservan estrictamente sus importes y condiciones fijadas.
                </div>

                <form id="form-precios">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        
                        <!-- Tarjeta Parametros Economicos -->
                        <div class="tarjeta">
                            <div class="tarjeta-cabecera">
                                <h3 style="font-size: 1.1rem; color: var(--color-primario-oscuro);">
                                    Parametros de Cobranza y Mora
                                </h3>
                            </div>
                            <div class="tarjeta-cuerpo" style="padding: 1.25rem;">
                                <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                    <label for="precio-recargo" class="etiqueta-formulario">
                                        Recargo por Mora (%) *
                                    </label>
                                    <input type="number" id="precio-recargo" class="control-formulario" min="0" max="100" step="0.01" required>
                                    <small style="color: var(--color-texto-suave); font-size: 0.78rem;">
                                        Porcentaje adicional aplicado a cuotas vencidas impagas (Regla R4).
                                    </small>
                                </div>

                                <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                    <label for="precio-descuento" class="etiqueta-formulario">
                                        Descuento Grupo Familiar (%) *
                                    </label>
                                    <input type="number" id="precio-descuento" class="control-formulario" min="0" max="100" step="0.01" required>
                                    <small style="color: var(--color-texto-suave); font-size: 0.78rem;">
                                        Bonificacion aplicada cuando hay 2 o mas socios activos en el grupo (Regla R5).
                                    </small>
                                </div>

                                <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                    <label for="precio-cuotas-moroso" class="etiqueta-formulario">
                                        Cuotas Impagas para Estado Moroso *
                                    </label>
                                    <input type="number" id="precio-cuotas-moroso" class="control-formulario" min="1" max="12" step="1" required>
                                    <small style="color: var(--color-texto-suave); font-size: 0.78rem;">
                                        Cantidad minima de cuotas vencidas impagas para cambiar a estado MOROSO (Regla R1).
                                    </small>
                                </div>

                                <div class="grupo-formulario">
                                    <label for="precio-dias-vencimiento" class="etiqueta-formulario">
                                        Dia del Mes de Vencimiento *
                                    </label>
                                    <input type="number" id="precio-dias-vencimiento" class="control-formulario" min="1" max="28" step="1" required>
                                    <small style="color: var(--color-texto-suave); font-size: 0.78rem;">
                                        Dia limite de pago mensual antes del devengamiento de recargo (entre 1 y 28).
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta Cuotas por Categoria -->
                        <div class="tarjeta">
                            <div class="tarjeta-cabecera">
                                <h3 style="font-size: 1.1rem; color: var(--color-primario-oscuro);">
                                    Arancel Mensual por Categoria (Regla R3)
                                </h3>
                            </div>
                            <div class="tarjeta-cuerpo" style="padding: 1.25rem;">
                                <div id="contenedor-precios-categorias">
                                    <div class="indicador-carga"></div> Cargando categorias...
                                </div>
                            </div>
                        </div>

                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="submit" class="btn btn-primario" id="btn-guardar-precios" style="padding: 0.75rem 1.75rem;">
                            ${UI.icono('guardar', 16)} Guardar Estructura Tarifaria
                        </button>
                    </div>
                </form>
            </div>
        `;

        const alertaDiv = contenedor.querySelector('#alerta-precios');
        const form = contenedor.querySelector('#form-precios');
        const inputRecargo = contenedor.querySelector('#precio-recargo');
        const inputDescuento = contenedor.querySelector('#precio-descuento');
        const inputCuotasMoroso = contenedor.querySelector('#precio-cuotas-moroso');
        const inputDiasVencimiento = contenedor.querySelector('#precio-dias-vencimiento');
        const contenedorCategorias = contenedor.querySelector('#contenedor-precios-categorias');
        const btnGuardar = contenedor.querySelector('#btn-guardar-precios');

        let categorias = [];

        async function cargarDatos() {
            try {
                const res = await Api.get('/precios');
                const params = res.parametros;
                categorias = res.categorias || [];

                inputRecargo.value = params.porcentaje_recargo;
                inputDescuento.value = params.porcentaje_descuento_familiar;
                inputCuotasMoroso.value = params.cuotas_para_moroso;
                inputDiasVencimiento.value = params.dias_vencimiento;

                renderizarCategorias();
            } catch (err) {
                alertaDiv.innerHTML = UI.alerta(err.message || 'Error al cargar precios y parametros', 'peligro');
            }
        }

        function renderizarCategorias() {
            if (!categorias || categorias.length === 0) {
                contenedorCategorias.innerHTML = '<p style="color: var(--color-texto-suave);">No hay categorias registradas.</p>';
                return;
            }

            contenedorCategorias.innerHTML = categorias.map(c => `
                <div class="grupo-formulario" style="margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--color-borde);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label for="precio-cat-${c.id}" class="etiqueta-formulario" style="font-weight: 600; margin: 0;">
                            ${UI.escaparHtml(c.nombre)}
                        </label>
                        <span class="insignia insignia-info" style="font-size: 0.75rem;">
                            ${c.edad_minima} a ${c.edad_maxima} anos
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-weight: bold; color: var(--color-texto-suave);">$</span>
                        <input type="number" id="precio-cat-${c.id}" class="control-formulario input-precio-cat" data-id="${c.id}" value="${c.precio_cuota}" min="0" step="0.01" required>
                    </div>
                </div>
            `).join('');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertaDiv.innerHTML = '';
            btnGuardar.disabled = true;

            const preciosCats = [];
            contenedor.querySelectorAll('.input-precio-cat').forEach(inp => {
                preciosCats.push({
                    id: parseInt(inp.dataset.id),
                    precio_cuota: parseFloat(inp.value)
                });
            });

            const datos = {
                porcentaje_recargo: parseFloat(inputRecargo.value),
                porcentaje_descuento_familiar: parseFloat(inputDescuento.value),
                cuotas_para_moroso: parseInt(inputCuotasMoroso.value),
                dias_vencimiento: parseInt(inputDiasVencimiento.value),
                precios_categorias: preciosCats
            };

            try {
                const res = await Api.put('/precios', datos);
                alertaDiv.innerHTML = UI.alerta(res.mensaje || 'Precios actualizados exitosamente.', 'exito');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                await cargarDatos();
            } catch (err) {
                alertaDiv.innerHTML = UI.alerta(err.message || 'Error al actualizar precios', 'peligro');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } finally {
                btnGuardar.disabled = false;
            }
        });

        await cargarDatos();
    }
};
