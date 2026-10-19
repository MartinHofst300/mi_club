/**
 * Vista de Mis Entrenamientos para el Socio (Caso de uso Ver entrenamientos).
 * Capa: Presentacion / Vistas.
 *
 * Muestra las sesiones de entrenamiento programadas para las divisiones en las
 * que el socio esta inscripto activo, junto con su estado de asistencia individual.
 */

const VistaMisEntrenamientos = (function () {
    /**
     * Renderiza el cronograma de entrenamientos del socio.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando sesiones de entrenamiento...');

        const socioId = Auth.obtenerSocioId();

        if (!socioId) {
            contenedor.innerHTML = `
                <div class="aviso aviso-advertencia">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Perfil de socio no disponible:</strong>
                        <div style="margin-top: 0.25rem;">Solo los socios con perfil vinculado pueden consultar sus entrenamientos programados.</div>
                    </div>
                </div>
            `;
            return;
        }

        try {
            const sesiones = await Api.get(`/socios/${socioId}/entrenamientos`);

            let tarjetasHtml = '';
            if (sesiones.length === 0) {
                tarjetasHtml = `
                    <div class="tarjeta" style="padding: 3rem 2rem; text-align: center; color: var(--color-texto-suave);">
                        <div style="width: 48px; height: 48px; margin: 0 auto 1rem auto; opacity: 0.5;">
                            ${UI.icono('entrenamiento')}
                        </div>
                        <h3 style="color: var(--color-primario-oscuro); margin-bottom: 0.5rem;">Sin entrenamientos programados</h3>
                        <p style="margin-bottom: 1.5rem;">
                            No tienes entrenamientos asignados actualmente. Asegurate de estar inscripto en una division deportiva activa.
                        </p>
                        <a href="#socio-inscripcion" class="btn btn-primario">
                            ${UI.icono('check')} Ver Divisiones Disponibles
                        </a>
                    </div>
                `;
            } else {
                tarjetasHtml = `
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        ${sesiones.map(s => {
                            let badgeAsistencia = '<span class="etiqueta etiqueta-secundaria">PENDIENTE</span>';
                            if (s.mi_asistencia === 'PRESENTE') {
                                badgeAsistencia = '<span class="etiqueta etiqueta-exito">PRESENTE</span>';
                            } else if (s.mi_asistencia === 'AUSENTE') {
                                badgeAsistencia = '<span class="etiqueta etiqueta-peligro">AUSENTE</span>';
                            } else if (s.mi_asistencia === 'JUSTIFICADO') {
                                badgeAsistencia = '<span class="etiqueta etiqueta-advertencia">JUSTIFICADO</span>';
                            }

                            const horaFormateada = s.hora ? s.hora.substring(0, 5) + ' hs' : '';

                            return `
                                <div class="tarjeta" style="margin-bottom: 0;">
                                    <div class="tarjeta-cabecera">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 36px; height: 36px; border-radius: 6px; background-color: var(--color-secundario); color: var(--color-primario); display: flex; align-items: center; justify-content: center;">
                                                ${UI.icono('entrenamiento')}
                                            </div>
                                            <div>
                                                <h3 class="tarjeta-titulo">${UI.escaparHtml(s.division_nombre)}</h3>
                                                <div style="font-size: 0.8rem; color: var(--color-texto-suave);">
                                                    Entrenador: ${UI.escaparHtml(s.entrenador_nombre + ' ' + s.entrenador_apellido)}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            ${badgeAsistencia}
                                        </div>
                                    </div>
                                    <div class="tarjeta-cuerpo">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 0.75rem; font-size: 0.9rem;">
                                            <div>
                                                <span style="color: var(--color-texto-suave);">Fecha:</span>
                                                <strong>${UI.formatearFecha(s.fecha)}</strong> a las <strong>${horaFormateada}</strong>
                                            </div>
                                            <div>
                                                <span style="color: var(--color-texto-suave);">Lugar:</span>
                                                <strong>${UI.escaparHtml(s.lugar)}</strong>
                                            </div>
                                        </div>
                                        ${s.observaciones ? `
                                            <div style="background-color: var(--color-fondo); padding: 0.75rem; border-radius: 6px; font-size: 0.85rem; color: var(--color-texto);">
                                                <strong>Plan / Observaciones:</strong> ${UI.escaparHtml(s.observaciones)}
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            contenedor.innerHTML = `
                <div style="max-width: 800px; margin: 0 auto;">
                    <div style="margin-bottom: 1.5rem;">
                        <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Mis Entrenamientos</h2>
                        <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                            Cronograma de practicas y registro de asistencia individual en tus divisiones.
                        </p>
                    </div>

                    ${tarjetasHtml}
                </div>
            `;

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al consultar entrenamientos:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
