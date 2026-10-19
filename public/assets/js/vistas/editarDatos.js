/**
 * Vista de Edicion de Datos Personales del Socio (Caso de uso Editar mis datos, Decision 14).
 * Capa: Presentacion / Vistas.
 *
 * Permite al socio actualizar sus datos de contacto (email, telefono, domicilio)
 * y cambiar su clave web mediante verificacion obligatoria de su contrasena actual.
 */

const VistaEditarDatos = (function () {
    /**
     * Renderiza el formulario de edicion de datos del socio.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando datos personales...');

        const socioId = Auth.obtenerSocioId();

        if (!socioId) {
            contenedor.innerHTML = `
                <div class="aviso aviso-advertencia">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Perfil de socio no disponible:</strong>
                        <div style="margin-top: 0.25rem;">Solo los socios con perfil vinculado pueden editar sus datos personales.</div>
                    </div>
                </div>
            `;
            return;
        }

        try {
            const socio = await Api.get(`/socios/${socioId}`);

            contenedor.innerHTML = `
                <div style="max-width: 680px; margin: 0 auto;">
                    <div style="margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Editar Mis Datos</h2>
                        <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                            Actualiza tus datos de contacto y contrasena de acceso al portal.
                        </p>
                    </div>

                    <form id="form-editar-datos">
                        <!-- Tarjeta 1: Datos de identificacion (Solo lectura) -->
                        <div class="tarjeta">
                            <div class="tarjeta-cabecera">
                                <h3 class="tarjeta-titulo">Identificacion Institucional</h3>
                                <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Datos protegidos</span>
                            </div>
                            <div class="tarjeta-cuerpo">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo">Nombre y Apellido</label>
                                        <input type="text" class="input-control" value="${UI.escaparHtml(socio.nombre + ' ' + socio.apellido)}" disabled style="background-color: var(--color-fondo);">
                                    </div>
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo">DNI</label>
                                        <input type="text" class="input-control" value="${UI.escaparHtml(socio.dni)}" disabled style="background-color: var(--color-fondo);">
                                    </div>
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo">Numero de Socio</label>
                                        <input type="text" class="input-control" value="${UI.escaparHtml(socio.numero_socio)}" disabled style="background-color: var(--color-fondo);">
                                    </div>
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo">Categoria</label>
                                        <input type="text" class="input-control" value="${UI.escaparHtml(socio.categoria_nombre)}" disabled style="background-color: var(--color-fondo);">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 2: Datos de Contacto (Editables) -->
                        <div class="tarjeta">
                            <div class="tarjeta-cabecera">
                                <h3 class="tarjeta-titulo">Datos de Contacto</h3>
                            </div>
                            <div class="tarjeta-cuerpo">
                                <div class="campo-grupo">
                                    <label class="etiqueta-campo" for="socio-email">Correo Electronico *</label>
                                    <input type="email" id="socio-email" class="input-control" value="${UI.escaparHtml(socio.email || '')}" required>
                                </div>

                                <div class="campo-grupo">
                                    <label class="etiqueta-campo" for="socio-telefono">Telefono de Contacto</label>
                                    <input type="tel" id="socio-telefono" class="input-control" value="${UI.escaparHtml(socio.telefono || '')}" placeholder="Ej: 11 4455-6677">
                                </div>

                                <div class="campo-grupo">
                                    <label class="etiqueta-campo" for="socio-domicilio">Domicilio / Direccion</label>
                                    <input type="text" id="socio-domicilio" class="input-control" value="${UI.escaparHtml(socio.domicilio || '')}" placeholder="Calle, numero y localidad">
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 3: Cambio de Contrasena (Opcional) -->
                        <div class="tarjeta">
                            <div class="tarjeta-cabecera">
                                <h3 class="tarjeta-titulo">Seguridad y Acceso</h3>
                                <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Opcional</span>
                            </div>
                            <div class="tarjeta-cuerpo">
                                <p style="font-size: 0.85rem; color: var(--color-texto-suave); margin-bottom: 1rem;">
                                    Complete estos campos unicamente si desea cambiar su clave de ingreso.
                                </p>

                                <div class="campo-grupo">
                                    <label class="etiqueta-campo" for="socio-password-actual">Contrasena Actual</label>
                                    <input type="password" id="socio-password-actual" class="input-control" placeholder="Ingrese su clave actual para validar su identidad">
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo" for="socio-password-nueva">Nueva Contrasena</label>
                                        <input type="password" id="socio-password-nueva" class="input-control" placeholder="Minimo 6 caracteres">
                                    </div>
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo" for="socio-password-confirmar">Repetir Nueva Contrasena</label>
                                        <input type="password" id="socio-password-confirmar" class="input-control" placeholder="Repita la nueva clave">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="error-editar-datos" class="alerta alerta-error" style="display: none; margin-bottom: 1.5rem;"></div>

                        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <a href="#socio-carnet" class="btn btn-secundario">Cancelar</a>
                            <button type="submit" class="btn btn-primario" id="btn-guardar-datos">
                                ${UI.icono('check')} Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            `;

            const form = contenedor.querySelector('#form-editar-datos');
            const errorDiv = contenedor.querySelector('#error-editar-datos');
            const btnGuardar = contenedor.querySelector('#btn-guardar-datos');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                errorDiv.style.display = 'none';

                const email = contenedor.querySelector('#socio-email').value.trim();
                const telefono = contenedor.querySelector('#socio-telefono').value.trim();
                const domicilio = contenedor.querySelector('#socio-domicilio').value.trim();
                const passActual = contenedor.querySelector('#socio-password-actual').value;
                const passNueva = contenedor.querySelector('#socio-password-nueva').value;
                const passConfirmar = contenedor.querySelector('#socio-password-confirmar').value;

                if (!email) {
                    errorDiv.textContent = 'El correo electronico es obligatorio.';
                    errorDiv.style.display = 'block';
                    return;
                }

                if (passNueva) {
                    if (passNueva.length < 6) {
                        errorDiv.textContent = 'La nueva contrasena debe contener al menos 6 caracteres.';
                        errorDiv.style.display = 'block';
                        return;
                    }
                    if (passNueva !== passConfirmar) {
                        errorDiv.textContent = 'Las nuevas contrasenas ingresadas no coinciden.';
                        errorDiv.style.display = 'block';
                        return;
                    }
                    if (!passActual) {
                        errorDiv.textContent = 'Debe ingresar su contrasena actual para autorizar el cambio de clave.';
                        errorDiv.style.display = 'block';
                        return;
                    }
                }

                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando cambios...';

                try {
                    const payload = {
                        email: email,
                        telefono: telefono,
                        domicilio: domicilio
                    };

                    if (passNueva) {
                        payload.password_actual = passActual;
                        payload.password_nueva = passNueva;
                    }

                    const res = await Api.put(`/socios/${socioId}`, payload);
                    UI.notificar(res.mensaje || 'Datos actualizados correctamente.', 'exito');

                    // Limpiar campos de clave
                    contenedor.querySelector('#socio-password-actual').value = '';
                    contenedor.querySelector('#socio-password-nueva').value = '';
                    contenedor.querySelector('#socio-password-confirmar').value = '';

                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = `${UI.icono('check')} Guardar Cambios`;

                } catch (error) {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = `${UI.icono('check')} Guardar Cambios`;
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                }
            });

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al cargar datos del socio:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
