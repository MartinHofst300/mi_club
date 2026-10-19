/**
 * Vista de Toma y Consulta de Asistencia a Entrenamientos (Regla R9).
 * Capa: Presentacion / Vistas.
 */

const VistaAsistencia = {
    render: async function (contenedor, parametro) {
        const entrenamientoId = (parametro && typeof parametro === 'object') ? parametro.id : parametro;
        if (!entrenamientoId) {
            // Mostrar listado de entrenamientos del entrenador
            await VistaAsistencia.renderizarListadoEntrenamientos(contenedor);
        } else {
            // Mostrar planilla de toma de asistencia para el entrenamiento especifico
            await VistaAsistencia.renderizarPlanillaAsistencia(contenedor, parseInt(entrenamientoId, 10));
        }
    },

    /**
     * Renderiza el listado de sesiones de entrenamiento asignadas al entrenador.
     */
    renderizarListadoEntrenamientos: async function (contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando entrenamientos de sus planteles...');

        try {
            const entrenamientos = await Api.get('/entrenamientos');

            let filas = '';
            if (entrenamientos.length === 0) {
                filas = `<tr><td colspan="6" style="text-align: center; color: var(--color-texto-suave); padding: 2rem;">No posee sesiones de entrenamiento programadas.</td></tr>`;
            } else {
                entrenamientos.forEach(e => {
                    filas += `
                        <tr>
                            <td><strong>${UI.escaparHtml(e.division_nombre)}</strong></td>
                            <td>${UI.formatearFecha(e.fecha)}</td>
                            <td>${UI.escaparHtml(e.hora.substring(0, 5))} hs</td>
                            <td>${UI.escaparHtml(e.lugar)}</td>
                            <td>
                                <span class="etiqueta ${parseInt(e.total_asistencias_cargadas, 10) > 0 ? 'etiqueta-activo' : 'etiqueta-pendiente'}">
                                    ${parseInt(e.total_asistencias_cargadas, 10) > 0 ? `${e.total_asistencias_cargadas} registros` : 'Sin registrar'}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="#asistencia/${e.id}" class="btn btn-sm btn-primario">
                                    ${UI.icono('asistencia')} Tomar / Editar Asistencia
                                </a>
                            </td>
                        </tr>
                    `;
                });
            }

            contenedor.innerHTML = `
                <div class="ancho-contenido">
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Entrenamientos de Mis Divisiones (Regla R9)</h3>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <p>Seleccione un entrenamiento para registrar o actualizar la asistencia del plantel:</p>

                            <div class="tabla-responsiva" style="margin-top: 1.25rem;">
                                <table class="tabla">
                                    <thead>
                                        <tr>
                                            <th>Division</th>
                                            <th>Fecha</th>
                                            <th>Horario</th>
                                            <th>Lugar</th>
                                            <th>Estado Asistencia</th>
                                            <th style="text-align: right;">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody>${filas}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            contenedor.innerHTML = `
                <div class="aviso aviso-error">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>${UI.escaparHtml(error.message)}</div>
                </div>
            `;
        }
    },

    /**
     * Renderiza la planilla interactiva de toma de asistencia para el plantel.
     */
    renderizarPlanillaAsistencia: async function (contenedor, entrenamientoId) {
        UI.mostrarCargando(contenedor, 'Cargando planilla de asistencia del plantel...');

        try {
            const datos = await Api.get(`/entrenamientos/${entrenamientoId}/asistencia`);
            const entrenamiento = datos.entrenamiento;
            const plantel = datos.plantel || [];

            let filasPlantel = '';
            if (plantel.length === 0) {
                filasPlantel = `<tr><td colspan="5" style="text-align: center; color: var(--color-texto-suave); padding: 2rem;">No hay socios inscriptos activos en este plantel.</td></tr>`;
            } else {
                plantel.forEach(socio => {
                    const presenteChecked = socio.estado === 'PRESENTE' ? 'checked' : '';
                    const ausenteChecked = socio.estado === 'AUSENTE' ? 'checked' : '';
                    const justificadoChecked = socio.estado === 'JUSTIFICADO' ? 'checked' : '';

                    filasPlantel += `
                        <tr data-socio-id="${socio.socio_id}">
                            <td><strong>${UI.escaparHtml(socio.numero_socio)}</strong></td>
                            <td>${UI.escaparHtml(socio.apellido)}, ${UI.escaparHtml(socio.nombre)}</td>
                            <td>${UI.escaparHtml(socio.dni)}</td>
                            <td>
                                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                    <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                        <input type="radio" name="asistencia_${socio.socio_id}" value="PRESENTE" ${presenteChecked}>
                                        <span class="etiqueta etiqueta-presente">Presente</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                        <input type="radio" name="asistencia_${socio.socio_id}" value="AUSENTE" ${ausenteChecked}>
                                        <span class="etiqueta etiqueta-ausente">Ausente</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                        <input type="radio" name="asistencia_${socio.socio_id}" value="JUSTIFICADO" ${justificadoChecked}>
                                        <span class="etiqueta etiqueta-justificado">Justificado</span>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="control-formulario control-obs-asistencia" style="padding: 0.35rem 0.6rem; font-size: 0.85rem;" placeholder="Aclaracion opcional" value="${UI.escaparHtml(socio.observaciones || '')}">
                            </td>
                        </tr>
                    `;
                });
            }

            contenedor.innerHTML = `
                <div class="ancho-contenido">
                    <div style="margin-bottom: 1.25rem;">
                        <a href="#asistencia" class="btn btn-secundario btn-sm">
                            Volver a Entrenamientos
                        </a>
                    </div>

                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <div>
                                <h3 class="tarjeta-titulo">
                                    Planilla de Asistencia: ${UI.escaparHtml(entrenamiento.division_nombre)}
                                </h3>
                                <div style="font-size: 0.85rem; color: var(--color-texto-suave); margin-top: 0.25rem;">
                                    Fecha: <strong>${UI.formatearFecha(entrenamiento.fecha)}</strong> a las <strong>${UI.escaparHtml(entrenamiento.hora.substring(0, 5))} hs</strong> | Lugar: ${UI.escaparHtml(entrenamiento.lugar)}
                                </div>
                            </div>
                        </div>

                        <div class="tarjeta-cuerpo">
                            <form id="form-guardar-asistencias">
                                <div class="tabla-responsiva" style="margin-bottom: 1.5rem;">
                                    <table class="tabla">
                                        <thead>
                                            <tr>
                                                <th>N° Socio</th>
                                                <th>Apellido y Nombre</th>
                                                <th>DNI</th>
                                                <th>Condicion de Asistencia</th>
                                                <th>Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>${filasPlantel}</tbody>
                                    </table>
                                </div>

                                ${plantel.length > 0 ? `
                                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                                        <button type="submit" class="btn btn-primario" id="btn-guardar-asistencias">
                                            ${UI.icono('asistencia')} Guardar Asistencias del Plantel (R9)
                                        </button>
                                    </div>
                                ` : ''}
                            </form>
                        </div>
                    </div>
                </div>
            `;

            const form = contenedor.querySelector('#form-guardar-asistencias');
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const listaAsistencias = [];
                    contenedor.querySelectorAll('tbody tr[data-socio-id]').forEach(tr => {
                        const socioId = parseInt(tr.getAttribute('data-socio-id'), 10);
                        const radioSeleccionado = tr.querySelector(`input[name="asistencia_${socioId}"]:checked`);
                        const estado = radioSeleccionado ? radioSeleccionado.value : 'PRESENTE';
                        const obs = tr.querySelector('.control-obs-asistencia').value.trim();

                        listaAsistencias.push({
                            socio_id: socioId,
                            estado: estado,
                            observaciones: obs || null
                        });
                    });

                    const btnGuardar = contenedor.querySelector('#btn-guardar-asistencias');
                    btnGuardar.disabled = true;
                    btnGuardar.textContent = 'Guardando...';

                    try {
                        await Api.put(`/entrenamientos/${entrenamientoId}/asistencia`, {
                            asistencias: listaAsistencias
                        });
                        UI.notificar('Asistencias registradas correctamente.', 'exito');
                    } catch (error) {
                        UI.notificar(error.message, 'error');
                    } finally {
                        btnGuardar.disabled = false;
                        btnGuardar.innerHTML = `${UI.icono('asistencia')} Guardar Asistencias del Plantel (R9)`;
                    }
                });
            }
        } catch (error) {
            contenedor.innerHTML = `
                <div class="aviso aviso-error">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>${UI.escaparHtml(error.message)}</div>
                </div>
            `;
        }
    }
};
