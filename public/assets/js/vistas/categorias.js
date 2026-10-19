/**
 * Vista de Gestion de Categorias Sociales (Caso de uso Administrador).
 * Capa: Presentacion / Vistas.
 */

const VistaCategorias = {
    render: async function (contenedor) {
        contenedor.innerHTML = `
            <div class="contenedor-vista">
                <div class="cabecera-vista" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <h1 class="titulo-vista" style="font-size: 1.5rem; color: var(--color-primario-oscuro); margin-bottom: 0.25rem;">
                            Gestion de Categorias
                        </h1>
                        <p class="subtitulo-vista" style="font-size: 0.9rem; color: var(--color-texto-suave);">
                            Configuracion de segmentos etarios formativos y asignacion de cuota base mensual (Reglas R3 y R8).
                        </p>
                    </div>
                    <button class="btn btn-primario" id="btn-nueva-categoria">
                        ${UI.icono('agregar', 16)} Nueva Categoria
                    </button>
                </div>

                <div id="alerta-categorias"></div>

                <div class="tarjeta">
                    <div class="tarjeta-cuerpo" style="padding: 0;">
                        <div class="tabla-responsiva">
                            <table class="tabla" id="tabla-categorias">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripcion</th>
                                        <th>Rango Etario</th>
                                        <th>Cuota Mensual</th>
                                        <th>Estado</th>
                                        <th style="text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-categorias">
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 2rem;">
                                            <div class="indicador-carga"></div> Cargando categorias...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Nueva / Editar Categoria -->
            <div class="modal" id="modal-categoria" style="display: none;">
                <div class="modal-contenido" style="max-width: 500px;">
                    <div class="modal-cabecera">
                        <h3 id="modal-categoria-titulo" style="font-size: 1.15rem; color: var(--color-primario-oscuro);">Categoria Social</h3>
                        <button class="btn-cerrar-modal" id="btn-cerrar-modal-categoria">&times;</button>
                    </div>
                    <form id="form-categoria">
                        <div class="modal-cuerpo" style="padding: 1.25rem;">
                            <input type="hidden" id="categoria-id" value="">
                            <div id="modal-categoria-alerta"></div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="categoria-nombre" class="etiqueta-formulario">Nombre de la Categoria *</label>
                                <input type="text" id="categoria-nombre" class="control-formulario" placeholder="Ej: Infantiles, Juveniles, Mayores" required>
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="categoria-descripcion" class="etiqueta-formulario">Descripcion</label>
                                <input type="text" id="categoria-descripcion" class="control-formulario" placeholder="Detalle institucional">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="grupo-formulario">
                                    <label for="categoria-edad-min" class="etiqueta-formulario">Edad Minima (anos) *</label>
                                    <input type="number" id="categoria-edad-min" class="control-formulario" min="0" max="120" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label for="categoria-edad-max" class="etiqueta-formulario">Edad Maxima (anos) *</label>
                                    <input type="number" id="categoria-edad-max" class="control-formulario" min="0" max="120" required>
                                </div>
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="categoria-precio" class="etiqueta-formulario">Precio Mensual Base ($) *</label>
                                <input type="number" id="categoria-precio" class="control-formulario" min="0" step="0.01" required>
                            </div>

                            <div class="grupo-formulario" id="grupo-categoria-activo">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" id="categoria-activo" checked>
                                    <span class="etiqueta-formulario" style="margin: 0;">Categoria Habilitada</span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-pie" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem;">
                            <button type="button" class="btn btn-secundario" id="btn-cancelar-modal-categoria">Cancelar</button>
                            <button type="submit" class="btn btn-primario" id="btn-guardar-categoria">Guardar Categoria</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        const alertaDiv = contenedor.querySelector('#alerta-categorias');
        const tbody = contenedor.querySelector('#tbody-categorias');
        const btnNueva = contenedor.querySelector('#btn-nueva-categoria');

        const modal = contenedor.querySelector('#modal-categoria');
        const form = contenedor.querySelector('#form-categoria');
        const modalTitulo = contenedor.querySelector('#modal-categoria-titulo');
        const modalAlerta = contenedor.querySelector('#modal-categoria-alerta');
        const btnCerrar = contenedor.querySelector('#btn-cerrar-modal-categoria');
        const btnCancelar = contenedor.querySelector('#btn-cancelar-modal-categoria');

        const inputId = contenedor.querySelector('#categoria-id');
        const inputNombre = contenedor.querySelector('#categoria-nombre');
        const inputDesc = contenedor.querySelector('#categoria-descripcion');
        const inputEdadMin = contenedor.querySelector('#categoria-edad-min');
        const inputEdadMax = contenedor.querySelector('#categoria-edad-max');
        const inputPrecio = contenedor.querySelector('#categoria-precio');
        const chkActivo = contenedor.querySelector('#categoria-activo');

        let listaCategorias = [];

        async function cargarCategorias() {
            try {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 2rem;"><div class="indicador-carga"></div> Cargando categorias...</td></tr>`;
                listaCategorias = await Api.get('/categorias');
                renderizarTabla();
            } catch (err) {
                alertaDiv.innerHTML = UI.alerta(err.message || 'Error al cargar categorias', 'peligro');
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--color-peligro); padding: 1.5rem;">Fallo al cargar las categorias.</td></tr>`;
            }
        }

        function renderizarTabla() {
            if (!listaCategorias || listaCategorias.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--color-texto-suave);">No hay categorias registradas.</td></tr>`;
                return;
            }

            tbody.innerHTML = listaCategorias.map(c => `
                <tr>
                    <td style="font-weight: 600; color: var(--color-primario-oscuro);">${UI.escaparHtml(c.nombre)}</td>
                    <td style="color: var(--color-texto-suave); font-size: 0.85rem;">${UI.escaparHtml(c.descripcion || '-')}</td>
                    <td><span class="insignia insignia-info">${c.edad_minima} a ${c.edad_maxima} anos</span></td>
                    <td style="font-weight: 600;">$${UI.formatearMoneda(c.precio_cuota)}</td>
                    <td>
                        ${parseInt(c.activo) === 1 ? '<span class="insignia insignia-exito">Activa</span>' : '<span class="insignia insignia-peligro">Inactiva</span>'}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <button class="btn btn-sm btn-secundario btn-editar" data-id="${c.id}" title="Editar Categoria">
                            ${UI.icono('editar', 14)} Editar
                        </button>
                        <button class="btn btn-sm btn-peligro btn-eliminar" data-id="${c.id}" data-nombre="${UI.escaparHtml(c.nombre)}" title="Eliminar Categoria">
                            Eliminar
                        </button>
                    </td>
                </tr>
            `).join('');

            tbody.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', () => abrirModalEditar(parseInt(btn.dataset.id)));
            });

            tbody.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', () => eliminarCategoria(parseInt(btn.dataset.id), btn.dataset.nombre));
            });
        }

        function abrirModalNueva() {
            inputId.value = '';
            modalTitulo.textContent = 'Nueva Categoria';
            modalAlerta.innerHTML = '';
            form.reset();
            chkActivo.checked = true;
            modal.style.display = 'flex';
        }

        function abrirModalEditar(id) {
            const cat = listaCategorias.find(item => parseInt(item.id) === id);
            if (!cat) return;

            inputId.value = cat.id;
            modalTitulo.textContent = 'Editar Categoria: ' + cat.nombre;
            modalAlerta.innerHTML = '';

            inputNombre.value = cat.nombre;
            inputDesc.value = cat.descripcion || '';
            inputEdadMin.value = cat.edad_minima;
            inputEdadMax.value = cat.edad_maxima;
            inputPrecio.value = cat.precio_cuota;
            chkActivo.checked = (parseInt(cat.activo) === 1);

            modal.style.display = 'flex';
        }

        function cerrarModal() {
            modal.style.display = 'none';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalAlerta.innerHTML = '';

            const id = inputId.value;
            const datos = {
                nombre: inputNombre.value.trim(),
                descripcion: inputDesc.value.trim(),
                edad_minima: parseInt(inputEdadMin.value),
                edad_maxima: parseInt(inputEdadMax.value),
                precio_cuota: parseFloat(inputPrecio.value),
                activo: chkActivo.checked
            };

            try {
                if (id) {
                    await Api.put(`/categorias/${id}`, datos);
                    cerrarModal();
                    alertaDiv.innerHTML = UI.alerta('Categoria actualizada exitosamente.', 'exito');
                } else {
                    await Api.post('/categorias', datos);
                    cerrarModal();
                    alertaDiv.innerHTML = UI.alerta('Categoria creada exitosamente.', 'exito');
                }
                cargarCategorias();
            } catch (err) {
                modalAlerta.innerHTML = UI.alerta(err.message || 'Error al guardar categoria', 'peligro');
            }
        });

        async function eliminarCategoria(id, nombre) {
            UI.confirmar(`Esta seguro de que desea eliminar permanentemente la categoria '${nombre}'?`, async () => {
                try {
                    await Api.delete(`/categorias/${id}`);
                    alertaDiv.innerHTML = UI.alerta(`Categoria '${nombre}' eliminada exitosamente.`, 'exito');
                    cargarCategorias();
                } catch (err) {
                    alertaDiv.innerHTML = UI.alerta(err.message || 'No fue posible eliminar la categoria', 'peligro');
                }
            });
        }

        btnNueva.addEventListener('click', abrirModalNueva);
        btnCerrar.addEventListener('click', cerrarModal);
        btnCancelar.addEventListener('click', cerrarModal);

        await cargarCategorias();
    }
};
