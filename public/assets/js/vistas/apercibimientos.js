/**
 * Vista de Apercibimientos Disciplinarios (Caso de uso Cargar apercibimiento, Regla R10).
 * Capa: Presentacion / Vistas.
 *
 * Permite al entrenador registrar sanciones disciplinarias (LEVE o GRAVE)
 * sobre socios que formen parte de sus planteles, con motivo obligatorio y fecha.
 */

const VistaApercibimientos = (function () {
    /**
     * Renderiza la nomina de apercibimientos y gestion de sanciones.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando apercibimientos disciplinarios...');

        const usuario = Auth.obtenerUsuario();
        const rol = usuario ? usuario.rol : '';
        const esEntrenador = (rol === 'ENTRENADOR' || rol === 'ADMINISTRADOR');

        try {
            const lista = await Api.get('/apercibimientos');

            let filas = '';
            if (lista.length === 0) {
                filas = `
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--color-texto-suave); padding: 2.5rem;">
                            No se registran apercibimientos disciplinarios.
                        </td>
                    </tr>
                `;
            } else {
                filas = lista.map(a => {
                    const badgeGravedad = a.gravedad === 'GRAVE' ? 'etiqueta-peligro' : 'etiqueta-advertencia';
                    const socioTexto = a.socio_nombre ? `${a.socio_apellido}, ${a.socio_nombre} (${a.numero_socio})` : `Socio #${a.socio_id}`;
                    const entrenadorTexto = a.entrenador_nombre ? `${a.entrenador_nombre} ${a.entrenador_apellido}` : 'Cuerpo Tecnico';

                    return `
                        <tr>
                            <td style="font-family: var(--fuente-mono); font-weight: 600;">#${a.id}</td>
                            <td>${UI.formatearFecha(a.fecha)}</td>
                            <td><strong>${UI.escaparHtml(socioTexto)}</strong></td>
                            <td><span class="etiqueta ${badgeGravedad}">${UI.escaparHtml(a.gravedad)}</span></td>
                            <td>${UI.escaparHtml(a.motivo)}</td>
                            <td style="font-size: 0.85rem; color: var(--color-texto-suave);">${UI.escaparHtml(entrenadorTexto)}</td>
                        </tr>
                    `;
                }).join('');
            }

            contenedor.innerHTML = `
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Apercibimientos Disciplinarios</h2>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Registro institucional de sanciones y llamados de atencion (Regla R10).
                            </p>
                        </div>
                        ${esEntrenador ? `
                            <button type="button" class="btn btn-acento" id="btn-nuevo-apercibimiento">
                                ${UI.icono('sancion')} Cargar Apercibimiento
                            </button>
                        ` : ''}
                    </div>

                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Registro Disciplinario</h3>
                        </div>
                        <div class="tabla-contenedor">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Socio Sancionado</th>
                                        <th>Gravedad</th>
                                        <th>Motivo</th>
                                        <th>Entrenador Responsable</th>
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

            // Vincular modal de creacion de apercibimiento
            const btnNuevo = contenedor.querySelector('#btn-nuevo-apercibimiento');
            if (btnNuevo) {
                btnNuevo.addEventListener('click', () => {
                    mostrarModalApercibimiento(contenedor);
                });
            }

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al consultar apercibimientos:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    /**
     * Modal interactivo para cargar una nueva sancion disciplinaria (Regla R10).
     */
    async function mostrarModalApercibimiento(contenedor) {
        const usuario = Auth.obtenerUsuario();
        const esAdmin = usuario && usuario.rol === 'ADMINISTRADOR';

        // Obtener divisiones y socios del entrenador
        let divisiones = await Api.get('/divisiones');
        if (!esAdmin) {
            divisiones = divisiones.filter(d => parseInt(d.entrenador_id, 10) === parseInt(usuario.id, 10));
        }

        if (divisiones.length === 0) {
            UI.notificar('No tienes planteles asignados para cargar sanciones.', 'error');
            return;
        }

        // Obtener planteles de las divisiones del entrenador
        let sociosElegibles = [];
        const sociosMap = new Map();

        for (const div of divisiones) {
            try {
                const plantel = await Api.get(`/divisiones/${div.id}/plantel`);
                plantel.forEach(p => {
                    if (!sociosMap.has(p.socio_id)) {
                        sociosMap.set(p.socio_id, {
                            id: p.socio_id,
                            nombre: `${p.apellido}, ${p.nombre} (${p.numero_socio}) - ${div.nombre}`
                        });
                    }
                });
            } catch (e) {
                // Continuar con siguientes divisiones
            }
        }

        sociosElegibles = Array.from(sociosMap.values());

        if (sociosElegibles.length === 0) {
            UI.notificar('Tus divisiones aun no cuentan con socios inscriptos en el plantel.', 'alerta');
            return;
        }

        const opcionesSocios = sociosElegibles.map(s => `
            <option value="${s.id}">${UI.escaparHtml(s.nombre)}</option>
        `).join('');

        const hoyStr = new Date().toISOString().split('T')[0];

        const modalFondo = document.createElement('div');
        modalFondo.className = 'modal-fondo';
        modalFondo.innerHTML = `
            <div class="modal-caja" style="max-width: 500px;">
                <div class="modal-cabecera">
                    <h3 class="modal-titulo">Cargar Apercibimiento Disciplinario</h3>
                </div>
                <div class="modal-cuerpo">
                    <p style="font-size: 0.85rem; color: var(--color-texto-suave); margin-bottom: 1rem;">
                        Solo es posible sancionar a socios pertenecientes a los planteles a tu cargo (Regla R10).
                    </p>

                    <form id="form-modal-apercibimiento">
                        <div class="campo-grupo">
                            <label class="etiqueta-campo" for="apercibimiento-socio">Socio del Plantel *</label>
                            <select id="apercibimiento-socio" class="input-control" required>
                                ${opcionesSocios}
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="campo-grupo">
                                <label class="etiqueta-campo" for="apercibimiento-fecha">Fecha del Hecho *</label>
                                <input type="date" id="apercibimiento-fecha" class="input-control" value="${hoyStr}" required>
                            </div>

                            <div class="campo-grupo">
                                <label class="etiqueta-campo" for="apercibimiento-gravedad">Gravedad *</label>
                                <select id="apercibimiento-gravedad" class="input-control" required>
                                    <option value="LEVE">LEVE (Llamado de atencion)</option>
                                    <option value="GRAVE">GRAVE (Falta de conducta severa)</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo-grupo">
                            <label class="etiqueta-campo" for="apercibimiento-motivo">Motivo Detallado *</label>
                            <textarea id="apercibimiento-motivo" class="input-control" rows="3" placeholder="Describa claramente el incidente o falta disciplinaria ocurrida..." required minlength="5" maxlength="500"></textarea>
                        </div>

                        <div id="error-modal-apercibimiento" style="color: var(--color-error); font-size: 0.85rem; margin-top: 0.5rem; display: none;"></div>
                    </form>
                </div>
                <div class="modal-pie">
                    <button type="button" class="btn btn-secundario" id="btn-cancelar-apercibimiento">Cancelar</button>
                    <button type="button" class="btn btn-acento" id="btn-guardar-apercibimiento">
                        ${UI.icono('sancion')} Registrar Sancion
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modalFondo);

        const btnCancelar = modalFondo.querySelector('#btn-cancelar-apercibimiento');
        const btnGuardar = modalFondo.querySelector('#btn-guardar-apercibimiento');
        const errorDiv = modalFondo.querySelector('#error-modal-apercibimiento');

        btnCancelar.addEventListener('click', () => modalFondo.remove());

        btnGuardar.addEventListener('click', async () => {
            const socioId = parseInt(modalFondo.querySelector('#apercibimiento-socio').value, 10);
            const fecha = modalFondo.querySelector('#apercibimiento-fecha').value;
            const gravedad = modalFondo.querySelector('#apercibimiento-gravedad').value;
            const motivo = modalFondo.querySelector('#apercibimiento-motivo').value.trim();

            if (!socioId || !fecha || !gravedad || motivo.length < 5) {
                errorDiv.textContent = 'Por favor complete todos los campos (motivo minimo 5 caracteres).';
                errorDiv.style.display = 'block';
                return;
            }

            btnGuardar.disabled = true;
            btnGuardar.textContent = 'Guardando...';

            try {
                const res = await Api.post('/apercibimientos', {
                    socio_id: socioId,
                    fecha: fecha,
                    gravedad: gravedad,
                    motivo: motivo
                });

                UI.notificar(res.mensaje || 'Apercibimiento registrado exitosamente.', 'exito');
                modalFondo.remove();
                await render(contenedor);

            } catch (error) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = `${UI.icono('sancion')} Registrar Sancion`;
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';
            }
        });
    }

    return {
        render
    };
})();
