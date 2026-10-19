/**
 * Vista de Gestion de Divisiones Deportivas (Caso de uso Administrador).
 * Capa: Presentacion / Vistas.
 */

const VistaDivisiones = {
    render: async function (contenedor) {
        contenedor.innerHTML = `
            <div class="contenedor-vista">
                <div class="cabecera-vista" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <h1 class="titulo-vista" style="font-size: 1.5rem; color: var(--color-primario-oscuro); margin-bottom: 0.25rem;">
                            Gestion de Divisiones Deportivas
                        </h1>
                        <p class="subtitulo-vista" style="font-size: 0.9rem; color: var(--color-texto-suave);">
                            Administracion de actividades, cupos de atletas y asignacion del cuerpo tecnico (Regla R8).
                        </p>
                    </div>
                    <button class="btn btn-primario" id="btn-nueva-division">
                        ${UI.icono('agregar', 16)} Nueva Division
                    </button>
                </div>

                <div id="alerta-divisiones"></div>

                <div class="tarjeta">
                    <div class="tarjeta-cuerpo" style="padding: 0;">
                        <div class="tabla-responsiva">
                            <table class="tabla" id="tabla-divisiones">
                                <thead>
                                    <tr>
                                        <th>Division</th>
                                        <th>Categoria</th>
                                        <th>Entrenador a Cargo</th>
                                        <th>Ocupacion / Cupo</th>
                                        <th>Estado</th>
                                        <th style="text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-divisiones">
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 2rem;">
                                            <div class="indicador-carga"></div> Cargando divisiones...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Nueva / Editar Division -->
            <div class="modal" id="modal-division" style="display: none;">
                <div class="modal-contenido" style="max-width: 520px;">
                    <div class="modal-cabecera">
                        <h3 id="modal-division-titulo" style="font-size: 1.15rem; color: var(--color-primario-oscuro);">Division Deportiva</h3>
                        <button class="btn-cerrar-modal" id="btn-cerrar-modal-division">&times;</button>
                    </div>
                    <form id="form-division">
                        <div class="modal-cuerpo" style="padding: 1.25rem;">
                            <input type="hidden" id="division-id" value="">
                            <div id="modal-division-alerta"></div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="division-nombre" class="etiqueta-formulario">Nombre de la Division *</label>
                                <input type="text" id="division-nombre" class="control-formulario" placeholder="Ej: Basquet Juvenil, Voley Mayores" required>
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="division-categoria" class="etiqueta-formulario">Categoria Social *</label>
                                <select id="division-categoria" class="control-formulario" required></select>
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="division-entrenador" class="etiqueta-formulario">Entrenador Responsable *</label>
                                <select id="division-entrenador" class="control-formulario" required></select>
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="division-cupo" class="etiqueta-formulario">Cupo Maximo de Socios *</label>
                                <input type="number" id="division-cupo" class="control-formulario" min="1" max="500" required>
                                <small id="ayuda-cupo" style="color: var(--color-texto-suave); font-size: 0.78rem;">
                                    El cupo no puede quedar por debajo de los inscriptos actuales.
                                </small>
                            </div>

                            <div class="grupo-formulario" id="grupo-division-activo">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" id="division-activo" checked>
                                    <span class="etiqueta-formulario" style="margin: 0;">Division Activa</span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-pie" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem;">
                            <button type="button" class="btn btn-secundario" id="btn-cancelar-modal-division">Cancelar</button>
                            <button type="submit" class="btn btn-primario" id="btn-guardar-division">Guardar Division</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        const alertaDiv = contenedor.querySelector('#alerta-divisiones');
        const tbody = contenedor.querySelector('#tbody-divisiones');
        const btnNueva = contenedor.querySelector('#btn-nueva-division');

        const modal = contenedor.querySelector('#modal-division');
        const form = contenedor.querySelector('#form-division');
        const modalTitulo = contenedor.querySelector('#modal-division-titulo');
        const modalAlerta = contenedor.querySelector('#modal-division-alerta');
        const btnCerrar = contenedor.querySelector('#btn-cerrar-modal-division');
        const btnCancelar = contenedor.querySelector('#btn-cancelar-modal-division');

        const inputId = contenedor.querySelector('#division-id');
        const inputNombre = contenedor.querySelector('#division-nombre');
        const selectCategoria = contenedor.querySelector('#division-categoria');
        const selectEntrenador = contenedor.querySelector('#division-entrenador');
        const inputCupo = contenedor.querySelector('#division-cupo');
        const chkActivo = contenedor.querySelector('#division-activo');
        const ayudaCupo = contenedor.querySelector('#ayuda-cupo');

        let listaDivisiones = [];
        let categorias = [];
        let entrenadores = [];

        async function inicializarCatalogos() {
            try {
                categorias = await Api.get('/categorias?activas=1');
                const usuarios = await Api.get('/usuarios?rol_id=3'); // Solo entrenadores
                entrenadores = usuarios || [];

                selectCategoria.innerHTML = categorias.map(c => 
                    `<option value="${c.id}">${UI.escaparHtml(c.nombre)} (${c.edad_minima} a ${c.edad_maxima} anos)</option>`
                ).join('');

                selectEntrenador.innerHTML = entrenadores.map(e =>
                    `<option value="${e.id}">${UI.escaparHtml(e.apellido)}, ${UI.escaparHtml(e.nombre)}</option>`
                ).join('');
            } catch (err) {
                console.error('Error al cargar catalogos:', err);
            }
        }

        async function cargarDivisiones() {
            try {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 2rem;"><div class="indicador-carga"></div> Cargando divisiones...</td></tr>`;
                listaDivisiones = await Api.get('/divisiones');
                renderizarTabla();
            } catch (err) {
                alertaDiv.innerHTML = UI.alerta(err.message || 'Error al cargar divisiones', 'peligro');
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--color-peligro); padding: 1.5rem;">Fallo al cargar divisiones.</td></tr>`;
            }
        }

        function renderizarTabla() {
            if (!listaDivisiones || listaDivisiones.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--color-texto-suave);">No hay divisiones registradas.</td></tr>`;
                return;
            }

            tbody.innerHTML = listaDivisiones.map(d => {
                const inscriptos = parseInt(d.inscriptos_actuales || 0);
                const cupoMax = parseInt(d.cupo_maximo);
                const porcentaje = Math.min(100, Math.round((inscriptos / cupoMax) * 100));

                let badgeCupo = '';
                if (inscriptos >= cupoMax) {
                    badgeCupo = `<span class="insignia insignia-peligro">Lleno (${inscriptos}/${cupoMax})</span>`;
                } else {
                    badgeCupo = `<span class="insignia insignia-exito">${inscriptos}/${cupoMax} (${cupoMax - inscriptos} libres)</span>`;
                }

                return `
                    <tr>
                        <td style="font-weight: 600; color: var(--color-primario-oscuro);">
                            ${UI.escaparHtml(d.nombre)}
                        </td>
                        <td>
                            <span class="insignia insignia-info">${UI.escaparHtml(d.categoria_nombre)}</span>
                        </td>
                        <td>${UI.escaparHtml(d.entrenador_apellido)}, ${UI.escaparHtml(d.entrenador_nombre)}</td>
                        <td>${badgeCupo}</td>
                        <td>
                            ${parseInt(d.activo) === 1 ? '<span class="insignia insignia-exito">Activa</span>' : '<span class="insignia insignia-peligro">Inactiva</span>'}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="#plantel?id=${d.id}" class="btn btn-sm btn-secundario" title="Gestionar Plantel">
                                ${UI.icono('plantel', 14)} Plantel
                            </a>
                            <button class="btn btn-sm btn-secundario btn-editar" data-id="${d.id}" title="Editar Division">
                                ${UI.icono('editar', 14)} Editar
                            </button>
                            <button class="btn btn-sm btn-peligro btn-eliminar" data-id="${d.id}" data-nombre="${UI.escaparHtml(d.nombre)}" title="Eliminar Division">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', () => abrirModalEditar(parseInt(btn.dataset.id)));
            });

            tbody.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', () => eliminarDivision(parseInt(btn.dataset.id), btn.dataset.nombre));
            });
        }

        function abrirModalNueva() {
            inputId.value = '';
            modalTitulo.textContent = 'Nueva Division Deportiva';
            modalAlerta.innerHTML = '';
            form.reset();
            chkActivo.checked = true;
            ayudaCupo.textContent = 'Indique la cantidad maxima de socios permitidos.';
            modal.style.display = 'flex';
        }

        function abrirModalEditar(id) {
            const d = listaDivisiones.find(item => parseInt(item.id) === id);
            if (!d) return;

            inputId.value = d.id;
            modalTitulo.textContent = 'Editar Division: ' + d.nombre;
            modalAlerta.innerHTML = '';

            inputNombre.value = d.nombre;
            selectCategoria.value = d.categoria_id;
            selectEntrenador.value = d.entrenador_id;
            inputCupo.value = d.cupo_maximo;
            chkActivo.checked = (parseInt(d.activo) === 1);

            ayudaCupo.textContent = `Inscriptos actuales: ${d.inscriptos_actuales}. El cupo maximo no puede ser menor a este valor.`;
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
                categoria_id: parseInt(selectCategoria.value),
                entrenador_id: parseInt(selectEntrenador.value),
                cupo_maximo: parseInt(inputCupo.value),
                activo: chkActivo.checked
            };

            try {
                if (id) {
                    await Api.put(`/divisiones/${id}`, datos);
                    cerrarModal();
                    alertaDiv.innerHTML = UI.alerta('Division actualizada exitosamente.', 'exito');
                } else {
                    await Api.post('/divisiones', datos);
                    cerrarModal();
                    alertaDiv.innerHTML = UI.alerta('Division creada exitosamente.', 'exito');
                }
                cargarDivisiones();
            } catch (err) {
                modalAlerta.innerHTML = UI.alerta(err.message || 'Error al guardar division', 'peligro');
            }
        });

        async function eliminarDivision(id, nombre) {
            UI.confirmar(`Esta seguro de que desea eliminar permanentemente la division '${nombre}'?`, async () => {
                try {
                    await Api.delete(`/divisiones/${id}`);
                    alertaDiv.innerHTML = UI.alerta(`Division '${nombre}' eliminada exitosamente.`, 'exito');
                    cargarDivisiones();
                } catch (err) {
                    alertaDiv.innerHTML = UI.alerta(err.message || 'No fue posible eliminar la division', 'peligro');
                }
            });
        }

        btnNueva.addEventListener('click', abrirModalNueva);
        btnCerrar.addEventListener('click', cerrarModal);
        btnCancelar.addEventListener('click', cerrarModal);

        await inicializarCatalogos();
        await cargarDivisiones();
    }
};
