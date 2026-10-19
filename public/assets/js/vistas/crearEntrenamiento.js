/**
 * Vista de Programacion de Nuevos Entrenamientos (Caso de uso Crear entrenamiento).
 * Capa: Presentacion / Vistas.
 *
 * Permite al entrenador programar sesiones de practica deportiva para las divisiones
 * a su cargo, definiendo fecha, horario, lugar y plan de entrenamiento.
 */

const VistaCrearEntrenamiento = (function () {
    /**
     * Renderiza el formulario de creacion de entrenamientos.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando formulario de entrenamiento...');

        const usuario = Auth.obtenerUsuario();
        const usuarioId = Auth.obtenerUsuarioId();
        const esAdmin = usuario && usuario.rol === 'ADMINISTRADOR';

        try {
            const todasDivisiones = await Api.get('/divisiones');

            const divisiones = esAdmin
                ? todasDivisiones
                : todasDivisiones.filter(d => usuarioId !== null && parseInt(d.entrenador_id, 10) === usuarioId);

            if (divisiones.length === 0) {
                contenedor.innerHTML = `
                    <div class="tarjeta" style="padding: 3rem 2rem; text-align: center; color: var(--color-texto-suave);">
                        <h3 style="color: var(--color-primario-oscuro); margin-bottom: 0.5rem;">Sin divisiones asignadas</h3>
                        <p>No tienes divisiones deportivas asignadas para programar entrenamientos.</p>
                    </div>
                `;
                return;
            }

            const opcionesDivisiones = divisiones.map(d => `
                <option value="${d.id}">
                    ${UI.escaparHtml(d.nombre)} (${UI.escaparHtml(d.categoria_nombre)})
                </option>
            `).join('');

            const hoyStr = new Date().toISOString().split('T')[0];

            contenedor.innerHTML = `
                <div style="max-width: 640px; margin: 0 auto;">
                    <div style="margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Programar Nueva Sesion de Entrenamiento</h2>
                        <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                            Planifica una practica para el plantel de tu division deportiva.
                        </p>
                    </div>

                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Datos de la Sesion</h3>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <form id="form-crear-entrenamiento">
                                <div class="campo-grupo">
                                    <label class="etiqueta-campo" for="entrenamiento-division">Division Deportiva *</label>
                                    <select id="entrenamiento-division" class="input-control" required>
                                        ${opcionesDivisiones}
                                    </select>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo" for="entrenamiento-fecha">Fecha *</label>
                                        <input type="date" id="entrenamiento-fecha" class="input-control" value="${hoyStr}" required>
                                    </div>
                                    <div class="campo-grupo">
                                        <label class="etiqueta-campo" for="entrenamiento-hora">Hora de Inicio *</label>
                                        <input type="time" id="entrenamiento-hora" class="input-control" value="18:00" required>
                                    </div>
                                </div>

                                <div class="campo-grupo">
                                    <label class="etiqueta-campo" for="entrenamiento-lugar">Lugar / Cancha *</label>
                                    <input type="text" id="entrenamiento-lugar" class="input-control" placeholder="Ej: Gimnasio Principal, Cancha 1 Cubierta, Playon..." required minlength="3" maxlength="150">
                                </div>

                                <div class="campo-grupo">
                                    <label class="etiqueta-campo" for="entrenamiento-observaciones">Plan de Trabajo / Observaciones</label>
                                    <textarea id="entrenamiento-observaciones" class="input-control" rows="3" placeholder="Ej: Ejercicios de posesion, lanzamientos y elongacion final..." maxlength="500"></textarea>
                                </div>

                                <div id="error-crear-entrenamiento" class="alerta alerta-error" style="display: none; margin-bottom: 1.25rem;"></div>

                                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                                    <a href="#asistencia" class="btn btn-secundario">Cancelar</a>
                                    <button type="submit" class="btn btn-primario" id="btn-submit-entrenamiento">
                                        ${UI.icono('entrenamiento')} Programar Entrenamiento
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            const form = contenedor.querySelector('#form-crear-entrenamiento');
            const errorDiv = contenedor.querySelector('#error-crear-entrenamiento');
            const btnSubmit = contenedor.querySelector('#btn-submit-entrenamiento');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                errorDiv.style.display = 'none';

                const divisionId = parseInt(contenedor.querySelector('#entrenamiento-division').value, 10);
                const fecha = contenedor.querySelector('#entrenamiento-fecha').value;
                const hora = contenedor.querySelector('#entrenamiento-hora').value;
                const lugar = contenedor.querySelector('#entrenamiento-lugar').value.trim();
                const observaciones = contenedor.querySelector('#entrenamiento-observaciones').value.trim();

                if (!divisionId || !fecha || !hora || !lugar) {
                    errorDiv.textContent = 'Por favor complete todos los campos obligatorios.';
                    errorDiv.style.display = 'block';
                    return;
                }

                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Guardando sesion...';

                try {
                    const res = await Api.post('/entrenamientos', {
                        division_id: divisionId,
                        fecha: fecha,
                        hora: hora,
                        lugar: lugar,
                        observaciones: observaciones || null
                    });

                    UI.notificar(res.mensaje || 'Entrenamiento programado exitosamente.', 'exito');
                    window.location.hash = '#asistencia';

                } catch (error) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = `${UI.icono('entrenamiento')} Programar Entrenamiento`;
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                }
            });

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al preparar formulario:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
