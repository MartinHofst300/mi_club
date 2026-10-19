/**
 * Vista de Gestion de Socios y Grupos Familiares (Caso de uso Administrador).
 * Capa: Presentacion / Vistas.
 */

const VistaSocios = {
    render: async function (contenedor) {
        contenedor.innerHTML = `
            <div class="contenedor-vista">
                <div class="cabecera-vista" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <h1 class="titulo-vista" style="font-size: 1.5rem; color: var(--color-primario-oscuro); margin-bottom: 0.25rem;">
                            Gestion del Padron de Socios
                        </h1>
                        <p class="subtitulo-vista" style="font-size: 0.9rem; color: var(--color-texto-suave);">
                            Administracion integral de socios, asignacion de categorias, legajos civiles y grupos familiares.
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.75rem;">
                        <button class="btn btn-secundario" id="btn-gestionar-familias">
                            ${UI.icono('escudo', 16)} Grupos Familiares
                        </button>
                        <button class="btn btn-primario" id="btn-nuevo-socio">
                            ${UI.icono('agregar', 16)} Nuevo Socio
                        </button>
                    </div>
                </div>

                <div id="alerta-socios"></div>

                <div class="tarjeta" style="margin-bottom: 1.5rem;">
                    <div class="tarjeta-cuerpo" style="padding: 1.25rem;">
                        <div class="fila-controles" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                            <div style="flex: 1; min-width: 250px;">
                                <input type="text" id="filtro-termino" class="control-formulario" placeholder="Buscar por numero de socio, nombre, apellido o DNI...">
                            </div>
                            <div style="width: 180px;">
                                <select id="filtro-estado" class="control-formulario">
                                    <option value="">Todos los estados</option>
                                    <option value="ACTIVO">Activo</option>
                                    <option value="MOROSO">Moroso</option>
                                    <option value="SUSPENDIDO">Suspendido</option>
                                    <option value="BAJA">Baja</option>
                                </select>
                            </div>
                            <button class="btn btn-secundario" id="btn-buscar">
                                ${UI.icono('buscar', 16)} Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="tarjeta">
                    <div class="tarjeta-cuerpo" style="padding: 0;">
                        <div class="tabla-responsiva">
                            <table class="tabla" id="tabla-socios">
                                <thead>
                                    <tr>
                                        <th>Socio</th>
                                        <th>DNI</th>
                                        <th>Categoria</th>
                                        <th>Grupo Familiar</th>
                                        <th>Fecha Alta</th>
                                        <th>Estado</th>
                                        <th style="text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-socios">
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 2rem;">
                                            <div class="indicador-carga"></div> Cargando padron de socios...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Nuevo / Editar Socio -->
            <div class="modal" id="modal-socio" style="display: none;">
                <div class="modal-contenido" style="max-width: 650px;">
                    <div class="modal-cabecera">
                        <h3 id="modal-socio-titulo" style="font-size: 1.15rem; color: var(--color-primario-oscuro);">Ficha del Socio</h3>
                        <button class="btn-cerrar-modal" id="btn-cerrar-modal-socio">&times;</button>
                    </div>
                    <form id="form-socio">
                        <div class="modal-cuerpo" style="padding: 1.25rem;">
                            <input type="hidden" id="socio-id" value="">
                            <div id="modal-socio-alerta"></div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="grupo-formulario">
                                    <label for="socio-nombre" class="etiqueta-formulario">Nombre *</label>
                                    <input type="text" id="socio-nombre" class="control-formulario" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label for="socio-apellido" class="etiqueta-formulario">Apellido *</label>
                                    <input type="text" id="socio-apellido" class="control-formulario" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="grupo-formulario">
                                    <label for="socio-dni" class="etiqueta-formulario">DNI *</label>
                                    <input type="text" id="socio-dni" class="control-formulario" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label for="socio-fecha-nac" class="etiqueta-formulario">Fecha de Nacimiento *</label>
                                    <input type="date" id="socio-fecha-nac" class="control-formulario" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="grupo-formulario">
                                    <label for="socio-email" class="etiqueta-formulario">Correo Electronico *</label>
                                    <input type="email" id="socio-email" class="control-formulario" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label for="socio-telefono" class="etiqueta-formulario">Telefono</label>
                                    <input type="text" id="socio-telefono" class="control-formulario">
                                </div>
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="socio-domicilio" class="etiqueta-formulario">Domicilio</label>
                                <input type="text" id="socio-domicilio" class="control-formulario">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="grupo-formulario">
                                    <label for="socio-categoria" class="etiqueta-formulario">Categoria Social *</label>
                                    <select id="socio-categoria" class="control-formulario" required></select>
                                </div>
                                <div class="grupo-formulario">
                                    <label for="socio-grupo" class="etiqueta-formulario">Grupo Familiar (Opcional)</label>
                                    <select id="socio-grupo" class="control-formulario">
                                        <option value="">Sin grupo familiar</option>
                                    </select>
                                </div>
                            </div>

                            <div id="seccion-estado-socio" style="display: none; padding: 1rem; background: var(--color-secundario); border-radius: var(--radio-borde); margin-bottom: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="grupo-formulario">
                                        <label for="socio-estado" class="etiqueta-formulario">Estado Administrativo *</label>
                                        <select id="socio-estado" class="control-formulario">
                                            <option value="ACTIVO">ACTIVO</option>
                                            <option value="MOROSO">MOROSO (Recalculado por cuotas)</option>
                                            <option value="SUSPENDIDO">SUSPENDIDO (Administrativo)</option>
                                            <option value="BAJA">BAJA (Definitiva)</option>
                                        </select>
                                    </div>
                                    <div class="grupo-formulario" id="grupo-motivo-estado" style="display: none;">
                                        <label for="socio-motivo-estado" class="etiqueta-formulario">Motivo de Suspension / Baja *</label>
                                        <input type="text" id="socio-motivo-estado" class="control-formulario" placeholder="Resolucion directiva, falta de pago, etc.">
                                    </div>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--color-texto-suave); margin-top: 0.5rem;">
                                    Nota: Los estados SUSPENDIDO y BAJA son fijados unicamente por la Administracion y el sistema automatico nunca los modifica.
                                </div>
                            </div>
                        </div>
                        <div class="modal-pie" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem;">
                            <button type="button" class="btn btn-secundario" id="btn-cancelar-modal-socio">Cancelar</button>
                            <button type="submit" class="btn btn-primario" id="btn-guardar-socio">Guardar Ficha</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Grupos Familiares -->
            <div class="modal" id="modal-familias" style="display: none;">
                <div class="modal-contenido" style="max-width: 600px;">
                    <div class="modal-cabecera">
                        <h3 style="font-size: 1.15rem; color: var(--color-primario-oscuro);">Grupos Familiares (Regla R5)</h3>
                        <button class="btn-cerrar-modal" id="btn-cerrar-modal-familias">&times;</button>
                    </div>
                    <div class="modal-cuerpo" style="padding: 1.25rem;">
                        <div id="modal-familias-alerta"></div>
                        
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem;">
                            <input type="text" id="nuevo-grupo-nombre" class="control-formulario" placeholder="Nombre de la nueva familia (ej: Familia Perez)">
                            <button class="btn btn-primario" id="btn-crear-grupo" style="white-space: nowrap;">
                                ${UI.icono('agregar', 14)} Crear Grupo
                            </button>
                        </div>

                        <div class="tabla-responsiva">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Nombre del Grupo</th>
                                        <th>Integrantes</th>
                                        <th>Fecha Creacion</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-familias">
                                    <tr><td colspan="3" style="text-align: center;">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-pie" style="display: flex; justify-content: flex-end; padding: 1rem 1.25rem;">
                        <button type="button" class="btn btn-secundario" id="btn-cerrar-familias-pie">Cerrar</button>
                    </div>
                </div>
            </div>
        `;

        const alertaDiv = contenedor.querySelector('#alerta-socios');
        const tbody = contenedor.querySelector('#tbody-socios');
        const filtroTermino = contenedor.querySelector('#filtro-termino');
        const filtroEstado = contenedor.querySelector('#filtro-estado');
        const btnBuscar = contenedor.querySelector('#btn-buscar');
        const btnNuevoSocio = contenedor.querySelector('#btn-nuevo-socio');
        const btnGestionarFamilias = contenedor.querySelector('#btn-gestionar-familias');

        // Modal Socio
        const modalSocio = contenedor.querySelector('#modal-socio');
        const formSocio = contenedor.querySelector('#form-socio');
        const modalSocioTitulo = contenedor.querySelector('#modal-socio-titulo');
        const modalSocioAlerta = contenedor.querySelector('#modal-socio-alerta');
        const btnCerrarModalSocio = contenedor.querySelector('#btn-cerrar-modal-socio');
        const btnCancelarModalSocio = contenedor.querySelector('#btn-cancelar-modal-socio');

        const inputSocioId = contenedor.querySelector('#socio-id');
        const inputNombre = contenedor.querySelector('#socio-nombre');
        const inputApellido = contenedor.querySelector('#socio-apellido');
        const inputDni = contenedor.querySelector('#socio-dni');
        const inputFechaNac = contenedor.querySelector('#socio-fecha-nac');
        const inputEmail = contenedor.querySelector('#socio-email');
        const inputTelefono = contenedor.querySelector('#socio-telefono');
        const inputDomicilio = contenedor.querySelector('#socio-domicilio');
        const selectCategoria = contenedor.querySelector('#socio-categoria');
        const selectGrupo = contenedor.querySelector('#socio-grupo');
        const seccionEstado = contenedor.querySelector('#seccion-estado-socio');
        const selectEstado = contenedor.querySelector('#socio-estado');
        const grupoMotivo = contenedor.querySelector('#grupo-motivo-estado');
        const inputMotivo = contenedor.querySelector('#socio-motivo-estado');

        // Modal Familias
        const modalFamilias = contenedor.querySelector('#modal-familias');
        const tbodyFamilias = contenedor.querySelector('#tbody-familias');
        const inputNuevoGrupo = contenedor.querySelector('#nuevo-grupo-nombre');
        const btnCrearGrupo = contenedor.querySelector('#btn-crear-grupo');
        const modalFamiliasAlerta = contenedor.querySelector('#modal-familias-alerta');
        const btnCerrarFamilias = contenedor.querySelector('#btn-cerrar-modal-familias');
        const btnCerrarFamiliasPie = contenedor.querySelector('#btn-cerrar-familias-pie');

        let categoriasDisponibles = [];
        let gruposDisponibles = [];
        let listaSocios = [];

        async function inicializarDatosAuxiliares() {
            try {
                categoriasDisponibles = await Api.get('/categorias?activas=1');
                gruposDisponibles = await Api.get('/grupos-familiares');

                selectCategoria.innerHTML = categoriasDisponibles.map(c => 
                    `<option value="${c.id}">${UI.escaparHtml(c.nombre)} (${c.edad_minima} a ${c.edad_maxima} anos - $${UI.formatearMoneda(c.precio_cuota)})</option>`
                ).join('');

                actualizarSelectGrupos();
            } catch (err) {
                console.error('Error al cargar datos auxiliares:', err);
            }
        }

        function actualizarSelectGrupos() {
            selectGrupo.innerHTML = '<option value="">Sin grupo familiar</option>' + gruposDisponibles.map(g =>
                `<option value="${g.id}">${UI.escaparHtml(g.nombre)}</option>`
            ).join('');
        }

        async function cargarSocios() {
            try {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 2rem;"><div class="indicador-carga"></div> Cargando padron de socios...</td></tr>`;
                const query = new URLSearchParams();
                if (filtroTermino.value.trim()) query.append('termino', filtroTermino.value.trim());
                if (filtroEstado.value) query.append('estado', filtroEstado.value);

                const ruta = '/socios' + (query.toString() ? '?' + query.toString() : '');
                listaSocios = await Api.get(ruta);
                renderizarTabla();
            } catch (err) {
                alertaDiv.innerHTML = UI.alerta(err.message || 'Error al cargar socios', 'peligro');
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--color-peligro); padding: 1.5rem;">Fallo al cargar el padron de socios.</td></tr>`;
            }
        }

        function renderizarTabla() {
            if (!listaSocios || listaSocios.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--color-texto-suave);">No se encontraron socios con los criterios de busqueda especificados.</td></tr>`;
                return;
            }

            tbody.innerHTML = listaSocios.map(s => {
                let badgeEstado = '';
                if (s.estado === 'ACTIVO') badgeEstado = '<span class="insignia insignia-exito">ACTIVO</span>';
                else if (s.estado === 'MOROSO') badgeEstado = '<span class="insignia insignia-peligro">MOROSO</span>';
                else if (s.estado === 'SUSPENDIDO') badgeEstado = '<span class="insignia insignia-advertencia">SUSPENDIDO</span>';
                else badgeEstado = '<span class="insignia insignia-secundaria">BAJA</span>';

                const grupoFam = s.grupo_familiar_nombre
                    ? `<span class="insignia insignia-info">${UI.escaparHtml(s.grupo_familiar_nombre)}</span>`
                    : '<span style="color: var(--color-texto-suave); font-size: 0.8rem;">Individual</span>';

                return `
                    <tr>
                        <td style="font-weight: 600; color: var(--color-primario-oscuro);">
                            ${UI.escaparHtml(s.apellido)}, ${UI.escaparHtml(s.nombre)}
                            <div style="font-size: 0.8rem; color: var(--color-texto-suave); font-weight: normal;">
                                Legajo: <strong>${UI.escaparHtml(s.numero_socio)}</strong>
                            </div>
                        </td>
                        <td>${UI.escaparHtml(s.dni)}</td>
                        <td>${UI.escaparHtml(s.categoria_nombre)}</td>
                        <td>${grupoFam}</td>
                        <td>${UI.escaparHtml(s.fecha_alta || '-')}</td>
                        <td>${badgeEstado}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button class="btn btn-sm btn-secundario btn-editar" data-id="${s.id}" title="Editar Ficha">
                                ${UI.icono('editar', 14)} Ficha
                            </button>
                            <a href="#socio-carnet?id=${s.id}" class="btn btn-sm btn-secundario" title="Ver Carnet">
                                Carnet
                            </a>
                            <a href="#socio-estado-cuenta?id=${s.id}" class="btn btn-sm btn-secundario" title="Estado de Cuenta">
                                Cuenta
                            </a>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', () => abrirModalEditar(parseInt(btn.dataset.id)));
            });
        }

        function abrirModalNuevo() {
            inputSocioId.value = '';
            modalSocioTitulo.textContent = 'Nuevo Socio en el Padron';
            modalSocioAlerta.innerHTML = '';
            formSocio.reset();
            seccionEstado.style.display = 'none';
            modalSocio.style.display = 'flex';
        }

        async function abrirModalEditar(id) {
            try {
                const s = await Api.get(`/socios/${id}`);
                inputSocioId.value = s.id;
                modalSocioTitulo.textContent = `Ficha de ${s.nombre} ${s.apellido} (${s.numero_socio})`;
                modalSocioAlerta.innerHTML = '';

                inputNombre.value = s.nombre;
                inputApellido.value = s.apellido;
                inputDni.value = s.dni;
                inputFechaNac.value = s.fecha_nacimiento;
                inputEmail.value = s.email;
                inputTelefono.value = s.telefono || '';
                inputDomicilio.value = s.domicilio || '';
                selectCategoria.value = s.categoria_id;
                selectGrupo.value = s.grupo_familiar_id || '';

                seccionEstado.style.display = 'block';
                selectEstado.value = s.estado;

                actualizarVisibilidadMotivo();
                modalSocio.style.display = 'flex';
            } catch (err) {
                alertaDiv.innerHTML = UI.alerta(err.message || 'Error al obtener socio', 'peligro');
            }
        }

        function actualizarVisibilidadMotivo() {
            const estado = selectEstado.value;
            if (estado === 'SUSPENDIDO' || estado === 'BAJA') {
                grupoMotivo.style.display = 'block';
                inputMotivo.required = true;
            } else {
                grupoMotivo.style.display = 'none';
                inputMotivo.required = false;
            }
        }

        selectEstado.addEventListener('change', actualizarVisibilidadMotivo);

        function cerrarModalSocio() {
            modalSocio.style.display = 'none';
        }

        formSocio.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalSocioAlerta.innerHTML = '';

            const id = inputSocioId.value;
            const datos = {
                nombre: inputNombre.value.trim(),
                apellido: inputApellido.value.trim(),
                dni: inputDni.value.trim(),
                fecha_nacimiento: inputFechaNac.value,
                email: inputEmail.value.trim(),
                telefono: inputTelefono.value.trim(),
                domicilio: inputDomicilio.value.trim(),
                categoria_id: parseInt(selectCategoria.value),
                grupo_familiar_id: selectGrupo.value ? parseInt(selectGrupo.value) : null
            };

            if (id) {
                datos.estado = selectEstado.value;
                if (datos.estado === 'SUSPENDIDO' || datos.estado === 'BAJA') {
                    datos.motivo_estado = inputMotivo.value.trim();
                }
            }

            try {
                if (id) {
                    await Api.put(`/socios/${id}`, datos);
                    cerrarModalSocio();
                    alertaDiv.innerHTML = UI.alerta('Ficha del socio actualizada exitosamente.', 'exito');
                } else {
                    await Api.post('/socios', datos);
                    cerrarModalSocio();
                    alertaDiv.innerHTML = UI.alerta('Nuevo socio incorporado exitosamente al padron.', 'exito');
                }
                cargarSocios();
            } catch (err) {
                modalSocioAlerta.innerHTML = UI.alerta(err.message || 'Error al guardar socio', 'peligro');
            }
        });

        // Grupos Familiares
        async function abrirModalFamilias() {
            modalFamiliasAlerta.innerHTML = '';
            inputNuevoGrupo.value = '';
            modalFamilias.style.display = 'flex';
            await cargarGruposFam();
        }

        async function cargarGruposFam() {
            try {
                tbodyFamilias.innerHTML = `<tr><td colspan="3" style="text-align: center;"><div class="indicador-carga"></div> Cargando familias...</td></tr>`;
                gruposDisponibles = await Api.get('/grupos-familiares');
                actualizarSelectGrupos();

                if (!gruposDisponibles || gruposDisponibles.length === 0) {
                    tbodyFamilias.innerHTML = `<tr><td colspan="3" style="text-align: center; color: var(--color-texto-suave);">No hay grupos familiares registrados actualmente.</td></tr>`;
                    return;
                }

                tbodyFamilias.innerHTML = gruposDisponibles.map(g => `
                    <tr>
                        <td style="font-weight: 600;">${UI.escaparHtml(g.nombre)}</td>
                        <td><span class="insignia insignia-info">${g.total_integrantes} integrante(s)</span></td>
                        <td style="font-size: 0.85rem; color: var(--color-texto-suave);">${UI.escaparHtml(g.creado_en || '-')}</td>
                    </tr>
                `).join('');
            } catch (err) {
                modalFamiliasAlerta.innerHTML = UI.alerta(err.message || 'Error al cargar grupos familiares', 'peligro');
            }
        }

        btnCrearGrupo.addEventListener('click', async () => {
            const nombre = inputNuevoGrupo.value.trim();
            if (!nombre) {
                modalFamiliasAlerta.innerHTML = UI.alerta('Ingrese el nombre del grupo familiar.', 'peligro');
                return;
            }

            try {
                await Api.post('/grupos-familiares', { nombre: nombre });
                inputNuevoGrupo.value = '';
                modalFamiliasAlerta.innerHTML = UI.alerta('Grupo familiar creado exitosamente.', 'exito');
                await cargarGruposFam();
            } catch (err) {
                modalFamiliasAlerta.innerHTML = UI.alerta(err.message || 'Error al crear grupo', 'peligro');
            }
        });

        function cerrarModalFamilias() {
            modalFamilias.style.display = 'none';
        }

        // Eventos
        btnBuscar.addEventListener('click', cargarSocios);
        filtroTermino.addEventListener('keydown', (e) => { if (e.key === 'Enter') cargarSocios(); });
        filtroEstado.addEventListener('change', cargarSocios);

        btnNuevoSocio.addEventListener('click', abrirModalNuevo);
        btnCerrarModalSocio.addEventListener('click', cerrarModalSocio);
        btnCancelarModalSocio.addEventListener('click', cerrarModalSocio);

        btnGestionarFamilias.addEventListener('click', abrirModalFamilias);
        btnCerrarFamilias.addEventListener('click', cerrarModalFamilias);
        btnCerrarFamiliasPie.addEventListener('click', cerrarModalFamilias);

        await inicializarDatosAuxiliares();
        await cargarSocios();
    }
};
