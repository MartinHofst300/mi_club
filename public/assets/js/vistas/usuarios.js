/**
 * Vista de Gestion de Usuarios y Roles (Caso de uso Administrador).
 * Capa: Presentacion / Vistas.
 */

const VistaUsuarios = {
    render: async function (contenedor) {
        contenedor.innerHTML = `
            <div class="contenedor-vista">
                <div class="cabecera-vista" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <h1 class="titulo-vista" style="font-size: 1.5rem; color: var(--color-primario-oscuro); margin-bottom: 0.25rem;">
                            Gestion de Usuarios y Roles
                        </h1>
                        <p class="subtitulo-vista" style="font-size: 0.9rem; color: var(--color-texto-suave);">
                            Administracion de cuentas de acceso, roles institucionales y restablecimiento de credenciales.
                        </p>
                    </div>
                    <button class="btn btn-primario" id="btn-nuevo-usuario">
                        ${UI.icono('agregar', 16)} Nuevo Usuario
                    </button>
                </div>

                <div id="alerta-usuarios"></div>

                <div class="tarjeta" style="margin-bottom: 1.5rem;">
                    <div class="tarjeta-cuerpo" style="padding: 1.25rem;">
                        <div class="fila-controles" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                            <div style="flex: 1; min-width: 250px;">
                                <input type="text" id="filtro-termino" class="control-formulario" placeholder="Buscar por apellido, nombre, email o DNI...">
                            </div>
                            <div style="width: 200px;">
                                <select id="filtro-rol" class="control-formulario">
                                    <option value="">Todos los roles</option>
                                    <option value="1">Administrador</option>
                                    <option value="2">Tesorero</option>
                                    <option value="3">Entrenador</option>
                                    <option value="4">Socio</option>
                                </select>
                            </div>
                            <button class="btn btn-secundario" id="btn-buscar">
                                ${UI.icono('buscar', 16)} Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="tarjeta">
                    <div class="tarjeta-cuerpo" style="padding: 0;">
                        <div class="tabla-responsiva">
                            <table class="tabla" id="tabla-usuarios">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>DNI</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Socio Vinculado</th>
                                        <th style="text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-usuarios">
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 2rem;">
                                            <div class="indicador-carga"></div> Cargando usuarios...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Nuevo / Editar Usuario -->
            <div class="modal" id="modal-usuario" style="display: none;">
                <div class="modal-contenido" style="max-width: 500px;">
                    <div class="modal-cabecera">
                        <h3 id="modal-usuario-titulo" style="font-size: 1.15rem; color: var(--color-primario-oscuro);">Nuevo Usuario</h3>
                        <button class="btn-cerrar-modal" id="btn-cerrar-modal-usuario">&times;</button>
                    </div>
                    <form id="form-usuario">
                        <div class="modal-cuerpo" style="padding: 1.25rem;">
                            <input type="hidden" id="usuario-id" value="">
                            <div id="modal-usuario-alerta"></div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="grupo-formulario">
                                    <label for="usuario-nombre" class="etiqueta-formulario">Nombre *</label>
                                    <input type="text" id="usuario-nombre" class="control-formulario" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label for="usuario-apellido" class="etiqueta-formulario">Apellido *</label>
                                    <input type="text" id="usuario-apellido" class="control-formulario" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="grupo-formulario">
                                    <label for="usuario-dni" class="etiqueta-formulario">DNI *</label>
                                    <input type="text" id="usuario-dni" class="control-formulario" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label for="usuario-rol" class="etiqueta-formulario">Rol *</label>
                                    <select id="usuario-rol" class="control-formulario" required>
                                        <option value="1">Administrador</option>
                                        <option value="2">Tesorero</option>
                                        <option value="3">Entrenador</option>
                                        <option value="4" selected>Socio</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1rem;">
                                <label for="usuario-email" class="etiqueta-formulario">Correo Electronico *</label>
                                <input type="email" id="usuario-email" class="control-formulario" required>
                            </div>

                            <div class="grupo-formulario" id="grupo-password" style="margin-bottom: 1rem;">
                                <label for="usuario-password" class="etiqueta-formulario">Contrasena inicial *</label>
                                <input type="password" id="usuario-password" class="control-formulario" minlength="6" placeholder="Minimo 6 caracteres">
                            </div>

                            <div class="grupo-formulario" id="grupo-activo" style="display: none; margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" id="usuario-activo" checked>
                                    <span class="etiqueta-formulario" style="margin: 0;">Usuario Habilitado para Iniciar Sesion</span>
                                </label>
                            </div>

                            <div id="aviso-cambio-rol-socio" style="display: none;" class="alerta alerta-advertencia">
                                Este usuario esta vinculado al legajo de un socio.
                                <label style="display: block; margin-top: 0.5rem; font-weight: bold; cursor: pointer;">
                                    <input type="checkbox" id="chk-confirmar-cambio-rol">
                                    Confirmo que deseo cambiar su rol institucional.
                                </label>
                            </div>
                        </div>
                        <div class="modal-pie" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem;">
                            <button type="button" class="btn btn-secundario" id="btn-cancelar-modal-usuario">Cancelar</button>
                            <button type="submit" class="btn btn-primario" id="btn-guardar-usuario">Guardar Usuario</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Restablecer Contraseña -->
            <div class="modal" id="modal-restablecer" style="display: none;">
                <div class="modal-contenido" style="max-width: 420px;">
                    <div class="modal-cabecera">
                        <h3 style="font-size: 1.15rem; color: var(--color-primario-oscuro);">Restablecer Contrasena</h3>
                        <button class="btn-cerrar-modal" id="btn-cerrar-modal-restablecer">&times;</button>
                    </div>
                    <form id="form-restablecer">
                        <div class="modal-cuerpo" style="padding: 1.25rem;">
                            <input type="hidden" id="restablecer-usuario-id" value="">
                            <div id="modal-restablecer-alerta"></div>
                            <p id="restablecer-usuario-nombre" style="font-size: 0.9rem; color: var(--color-texto-suave); margin-bottom: 1rem;"></p>
                            
                            <div class="grupo-formulario">
                                <label for="nueva-clave" class="etiqueta-formulario">Nueva Contrasena *</label>
                                <input type="password" id="nueva-clave" class="control-formulario" minlength="6" placeholder="Minimo 6 caracteres" required autofocus>
                            </div>
                        </div>
                        <div class="modal-pie" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem;">
                            <button type="button" class="btn btn-secundario" id="btn-cancelar-restablecer">Cancelar</button>
                            <button type="submit" class="btn btn-peligro" id="btn-confirmar-restablecer">Actualizar Clave</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        const alertaDiv = contenedor.querySelector('#alerta-usuarios');
        const tbody = contenedor.querySelector('#tbody-usuarios');
        const filtroTermino = contenedor.querySelector('#filtro-termino');
        const filtroRol = contenedor.querySelector('#filtro-rol');
        const btnBuscar = contenedor.querySelector('#btn-buscar');
        const btnNuevoUsuario = contenedor.querySelector('#btn-nuevo-usuario');

        // Modal Usuario
        const modalUsuario = contenedor.querySelector('#modal-usuario');
        const formUsuario = contenedor.querySelector('#form-usuario');
        const modalUsuarioTitulo = contenedor.querySelector('#modal-usuario-titulo');
        const modalUsuarioAlerta = contenedor.querySelector('#modal-usuario-alerta');
        const btnCerrarModalUsuario = contenedor.querySelector('#btn-cerrar-modal-usuario');
        const btnCancelarModalUsuario = contenedor.querySelector('#btn-cancelar-modal-usuario');
        const inputUsuarioId = contenedor.querySelector('#usuario-id');
        const inputNombre = contenedor.querySelector('#usuario-nombre');
        const inputApellido = contenedor.querySelector('#usuario-apellido');
        const inputDni = contenedor.querySelector('#usuario-dni');
        const selectRol = contenedor.querySelector('#usuario-rol');
        const inputEmail = contenedor.querySelector('#usuario-email');
        const inputPassword = contenedor.querySelector('#usuario-password');
        const grupoPassword = contenedor.querySelector('#grupo-password');
        const grupoActivo = contenedor.querySelector('#grupo-activo');
        const chkActivo = contenedor.querySelector('#usuario-activo');
        const avisoCambioRolSocio = contenedor.querySelector('#aviso-cambio-rol-socio');
        const chkConfirmarCambioRol = contenedor.querySelector('#chk-confirmar-cambio-rol');

        // Modal Restablecer
        const modalRestablecer = contenedor.querySelector('#modal-restablecer');
        const formRestablecer = contenedor.querySelector('#form-restablecer');
        const inputRestablecerId = contenedor.querySelector('#restablecer-usuario-id');
        const textoRestablecerNombre = contenedor.querySelector('#restablecer-usuario-nombre');
        const inputNuevaClave = contenedor.querySelector('#nueva-clave');
        const modalRestablecerAlerta = contenedor.querySelector('#modal-restablecer-alerta');
        const btnCerrarRestablecer = contenedor.querySelector('#btn-cerrar-modal-restablecer');
        const btnCancelarRestablecer = contenedor.querySelector('#btn-cancelar-restablecer');

        let listaUsuarios = [];

        async function cargarUsuarios() {
            try {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 2rem;"><div class="indicador-carga"></div> Cargando usuarios...</td></tr>`;
                const query = new URLSearchParams();
                if (filtroTermino.value.trim()) query.append('termino', filtroTermino.value.trim());
                if (filtroRol.value) query.append('rol_id', filtroRol.value);

                const ruta = '/usuarios' + (query.toString() ? '?' + query.toString() : '');
                listaUsuarios = await Api.get(ruta);
                renderizarTabla();
            } catch (err) {
                alertaDiv.innerHTML = UI.alerta(err.message || 'Error al cargar usuarios', 'peligro');
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--color-peligro); padding: 1.5rem;">Fallo al cargar listado de usuarios.</td></tr>`;
            }
        }

        function renderizarTabla() {
            if (!listaUsuarios || listaUsuarios.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--color-texto-suave);">No se encontraron usuarios registrados con los criterios seleccionados.</td></tr>`;
                return;
            }

            const sesionActual = Auth.obtenerUsuario() || {};
            const miId = sesionActual.id || 0;

            tbody.innerHTML = listaUsuarios.map(u => {
                const badgeRol = `<span class="insignia insignia-info">${UI.escaparHtml(u.rol_nombre)}</span>`;
                const badgeEstado = (parseInt(u.activo) === 1)
                    ? `<span class="insignia insignia-exito">Activo</span>`
                    : `<span class="insignia insignia-peligro">Inactivo</span>`;
                const socioVinculado = u.numero_socio 
                    ? `<span class="insignia insignia-secundaria">${UI.escaparHtml(u.numero_socio)}</span>`
                    : `<span style="color: var(--color-texto-suave); font-size: 0.8rem;">Ninguno</span>`;

                const esMiUsuario = parseInt(u.id) === parseInt(miId);

                return `
                    <tr>
                        <td style="font-weight: 600; color: var(--color-primario-oscuro);">
                            ${UI.escaparHtml(u.apellido)}, ${UI.escaparHtml(u.nombre)}
                            ${esMiUsuario ? '<span style="font-size: 0.75rem; color: var(--color-primario); margin-left: 0.5rem;">(Tu cuenta)</span>' : ''}
                        </td>
                        <td>${UI.escaparHtml(u.dni)}</td>
                        <td>${UI.escaparHtml(u.email)}</td>
                        <td>${badgeRol}</td>
                        <td>${badgeEstado}</td>
                        <td>${socioVinculado}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button class="btn btn-sm btn-secundario btn-editar" data-id="${u.id}" title="Editar usuario">
                                ${UI.icono('editar', 14)} Editar
                            </button>
                            <button class="btn btn-sm btn-secundario btn-clave" data-id="${u.id}" data-nombre="${UI.escaparHtml(u.nombre + ' ' + u.apellido)}" title="Restablecer clave">
                                ${UI.icono('candado', 14)} Clave
                            </button>
                            ${!esMiUsuario ? `
                                <button class="btn btn-sm ${parseInt(u.activo) === 1 ? 'btn-advertencia' : 'btn-exito'} btn-toggle-activo" data-id="${u.id}" data-activo="${u.activo}">
                                    ${parseInt(u.activo) === 1 ? 'Desactivar' : 'Activar'}
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            }).join('');

            // Asignar eventos de acciones en la tabla
            tbody.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', () => abrirModalEditar(parseInt(btn.dataset.id)));
            });

            tbody.querySelectorAll('.btn-clave').forEach(btn => {
                btn.addEventListener('click', () => abrirModalRestablecer(parseInt(btn.dataset.id), btn.dataset.nombre));
            });

            tbody.querySelectorAll('.btn-toggle-activo').forEach(btn => {
                btn.addEventListener('click', () => toggleEstadoActivo(parseInt(btn.dataset.id), parseInt(btn.dataset.activo) === 1));
            });
        }

        async function toggleEstadoActivo(id, actualmenteActivo) {
            const accion = actualmenteActivo ? 'desactivar' : 'activar';
            const accionMsg = actualmenteActivo ? 'desactivar este usuario? No podra iniciar sesion en el sistema.' : 'activar este usuario?';
            
            UI.confirmar(`Esta seguro de que desea ${accionMsg}`, async () => {
                try {
                    await Api.post(`/usuarios/${id}/${accion}`, {});
                    alertaDiv.innerHTML = UI.alerta(`Usuario ${actualmenteActivo ? 'desactivado' : 'activado'} correctamente.`, 'exito');
                    cargarUsuarios();
                } catch (err) {
                    alertaDiv.innerHTML = UI.alerta(err.message || 'Error al cambiar estado del usuario', 'peligro');
                }
            });
        }

        function abrirModalNuevo() {
            inputUsuarioId.value = '';
            modalUsuarioTitulo.textContent = 'Nuevo Usuario';
            modalUsuarioAlerta.innerHTML = '';
            formUsuario.reset();
            grupoPassword.style.display = 'block';
            inputPassword.required = true;
            grupoActivo.style.display = 'none';
            avisoCambioRolSocio.style.display = 'none';
            modalUsuario.style.display = 'flex';
        }

        function abrirModalEditar(id) {
            const u = listaUsuarios.find(item => parseInt(item.id) === id);
            if (!u) return;

            inputUsuarioId.value = u.id;
            modalUsuarioTitulo.textContent = 'Editar Usuario: ' + u.nombre + ' ' + u.apellido;
            modalUsuarioAlerta.innerHTML = '';
            
            inputNombre.value = u.nombre;
            inputApellido.value = u.apellido;
            inputDni.value = u.dni;
            inputEmail.value = u.email;
            selectRol.value = u.rol_id;
            
            grupoPassword.style.display = 'none';
            inputPassword.required = false;
            grupoActivo.style.display = 'block';
            chkActivo.checked = (parseInt(u.activo) === 1);

            avisoCambioRolSocio.style.display = 'none';
            chkConfirmarCambioRol.checked = false;

            // Escuchar cambio de rol si tiene socio vinculado
            selectRol.onchange = () => {
                if (u.numero_socio && selectRol.value !== '4') {
                    avisoCambioRolSocio.style.display = 'block';
                } else {
                    avisoCambioRolSocio.style.display = 'none';
                }
            };

            modalUsuario.style.display = 'flex';
        }

        function cerrarModalUsuario() {
            modalUsuario.style.display = 'none';
        }

        formUsuario.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalUsuarioAlerta.innerHTML = '';

            const id = inputUsuarioId.value;
            const datos = {
                nombre: inputNombre.value.trim(),
                apellido: inputApellido.value.trim(),
                dni: inputDni.value.trim(),
                email: inputEmail.value.trim(),
                rol_id: parseInt(selectRol.value)
            };

            if (avisoCambioRolSocio.style.display === 'block') {
                datos.confirmar_cambio_rol_socio = chkConfirmarCambioRol.checked;
            }

            try {
                if (id) {
                    // Actualizacion
                    datos.activo = chkActivo.checked;
                    await Api.put(`/usuarios/${id}`, datos);
                    cerrarModalUsuario();
                    alertaDiv.innerHTML = UI.alerta('Usuario actualizado exitosamente.', 'exito');
                } else {
                    // Alta
                    datos.password = inputPassword.value;
                    await Api.post('/usuarios', datos);
                    cerrarModalUsuario();
                    alertaDiv.innerHTML = UI.alerta('Nuevo usuario creado exitosamente.', 'exito');
                }
                cargarUsuarios();
            } catch (err) {
                modalUsuarioAlerta.innerHTML = UI.alerta(err.message || 'Error al guardar usuario', 'peligro');
            }
        });

        // Restablecer contraseña
        function abrirModalRestablecer(id, nombre) {
            inputRestablecerId.value = id;
            textoRestablecerNombre.textContent = 'Ingrese la nueva clave para ' + nombre + '.';
            inputNuevaClave.value = '';
            modalRestablecerAlerta.innerHTML = '';
            modalRestablecer.style.display = 'flex';
        }

        function cerrarModalRestablecer() {
            modalRestablecer.style.display = 'none';
        }

        formRestablecer.addEventListener('submit', async (e) => {
            e.preventDefault();
            modalRestablecerAlerta.innerHTML = '';
            const id = inputRestablecerId.value;
            const password = inputNuevaClave.value;

            try {
                await Api.post(`/usuarios/${id}/restablecer-password`, { password: password });
                cerrarModalRestablecer();
                alertaDiv.innerHTML = UI.alerta('Contrasena actualizada exitosamente.', 'exito');
            } catch (err) {
                modalRestablecerAlerta.innerHTML = UI.alerta(err.message || 'Error al restablecer contrasena', 'peligro');
            }
        });

        // Eventos generales
        btnBuscar.addEventListener('click', cargarUsuarios);
        filtroTermino.addEventListener('keydown', (e) => { if (e.key === 'Enter') cargarUsuarios(); });
        filtroRol.addEventListener('change', cargarUsuarios);

        btnNuevoUsuario.addEventListener('click', abrirModalNuevo);
        btnCerrarModalUsuario.addEventListener('click', cerrarModalUsuario);
        btnCancelarModalUsuario.addEventListener('click', cerrarModalUsuario);

        btnCerrarRestablecer.addEventListener('click', cerrarModalRestablecer);
        btnCancelarRestablecer.addEventListener('click', cerrarModalRestablecer);

        await cargarUsuarios();
    }
};
