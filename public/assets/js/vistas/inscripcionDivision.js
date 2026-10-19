/**
 * Vista de Inscripcion a Divisiones Deportivas (Caso de uso Inscribirse a division, Regla R8).
 * Capa: Presentacion / Vistas.
 *
 * Muestra el catalogo de actividades y divisiones deportivas del club,
 * validando en tiempo real cupo maximo disponible, correspondencia etaria
 * y estado contable del socio (bloquea inscripciones si esta MOROSO, SUSPENDIDO o BAJA).
 */

const VistaInscripcionDivision = (function () {
    /**
     * Renderiza el catalogo de inscripciones deportivas.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando catalogo de divisiones deportivas...');

        const socioId = Auth.obtenerSocioId();

        if (!socioId) {
            contenedor.innerHTML = `
                <div class="aviso aviso-advertencia">
                    <div class="aviso-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Perfil de socio no disponible:</strong>
                        <div style="margin-top: 0.25rem;">Solo los socios con perfil vinculado pueden autogestionar su inscripcion a divisiones deportivas.</div>
                    </div>
                </div>
            `;
            return;
        }

        try {
            // Cargar datos del socio y catalogo de divisiones en paralelo
            const [socio, divisiones] = await Promise.all([
                Api.get(`/socios/${socioId}`),
                Api.get('/divisiones')
            ]);

            // Calcular edad del socio
            const hoy = new Date();
            const fechaNac = new Date(socio.fecha_nacimiento);
            let edadSocio = hoy.getFullYear() - fechaNac.getFullYear();
            const mesDiff = hoy.getMonth() - fechaNac.getMonth();
            if (mesDiff < 0 || (mesDiff === 0 && hoy.getDate() < fechaNac.getDate())) {
                edadSocio--;
            }

            const esMoroso = (socio.estado === 'MOROSO');
            const esSuspendido = (socio.estado === 'SUSPENDIDO');
            const esBaja = (socio.estado === 'BAJA');
            const puedeInscribirse = !esMoroso && !esSuspendido && !esBaja;

            let bannerEstado = '';
            if (esMoroso) {
                bannerEstado = `
                    <div class="alerta alerta-peligro" style="margin-bottom: 1.5rem;">
                        <div class="alerta-icono">${UI.icono('alerta')}</div>
                        <div>
                            <strong>Acceso Restringido (Regla R8):</strong> Te encuentras en estado <strong>MOROSO</strong> por cuotas impagas.
                            No es posible inscribirse en nuevas divisiones deportivas hasta regularizar tu situacion contable.
                            <div style="margin-top: 0.5rem;">
                                <a href="#socio-pagar" class="btn btn-sm btn-acento">${UI.icono('pago')} Pagar Cuotas Pendientes</a>
                            </div>
                        </div>
                    </div>
                `;
            } else if (esSuspendido || esBaja) {
                bannerEstado = `
                    <div class="alerta alerta-peligro" style="margin-bottom: 1.5rem;">
                        <div class="alerta-icono">${UI.icono('alerta')}</div>
                        <div>
                            <strong>Acceso Inhabilitado:</strong> Tu estado actual es <strong>${UI.escaparHtml(socio.estado)}</strong>.
                            Comunicate con la Comision Directiva para regularizar tu situacion.
                        </div>
                    </div>
                `;
            }

            // Tarjetas de divisiones
            let tarjetasHtml = '';
            if (divisiones.length === 0) {
                tarjetasHtml = `
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--color-texto-suave); padding: 3rem;">
                        No hay divisiones deportivas activas disponibles en este momento.
                    </div>
                `;
            } else {
                tarjetasHtml = divisiones.map(d => {
                    const inscriptos = parseInt(d.inscriptos_actuales, 10) || 0;
                    const cupoMax = parseInt(d.cupo_maximo, 10);
                    const cupoLleno = inscriptos >= cupoMax;
                    const porcentaje = Math.min(100, Math.round((inscriptos / cupoMax) * 100));

                    const edadMin = parseInt(d.edad_minima, 10);
                    const edadMax = parseInt(d.edad_maxima, 10);
                    const edadValida = (edadSocio >= edadMin && edadSocio <= edadMax);
                    const yaInscripto = Boolean(d.ya_inscripto);

                    let botonHtml = '';
                    if (yaInscripto) {
                        botonHtml = `<span class="etiqueta etiqueta-exito">${UI.icono('check')} Ya inscripto</span>`;
                    } else if (!puedeInscribirse) {
                        botonHtml = `<button type="button" class="btn btn-sm btn-secundario" disabled>No habilitado</button>`;
                    } else if (cupoLleno) {
                        botonHtml = `<span class="etiqueta etiqueta-peligro">Cupo agotado</span>`;
                    } else if (!edadValida) {
                        botonHtml = `<span class="etiqueta etiqueta-secundaria">Fuera de rango (${edadMin}-${edadMax} anos)</span>`;
                    } else {
                        botonHtml = `
                            <button type="button" class="btn btn-sm btn-primario btn-inscribirse" data-id="${d.id}" data-nombre="${UI.escaparHtml(d.nombre)}">
                                ${UI.icono('check')} Inscribirme
                            </button>
                        `;
                    }

                    return `
                        <div class="tarjeta" style="margin-bottom: 0; display: flex; flex-direction: column;">
                            <div class="tarjeta-cabecera">
                                <h3 class="tarjeta-titulo">${UI.escaparHtml(d.nombre)}</h3>
                                <span class="etiqueta etiqueta-secundaria">${UI.escaparHtml(d.categoria_nombre)}</span>
                            </div>
                            <div class="tarjeta-cuerpo" style="flex: 1;">
                                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem; margin-bottom: 1rem;">
                                    <div><strong>Entrenador:</strong> ${UI.escaparHtml(d.entrenador_nombre + ' ' + d.entrenador_apellido)}</div>
                                    <div><strong>Rango etario:</strong> ${edadMin} a ${edadMax} anos</div>
                                    <div><strong>Disponibilidad:</strong> ${inscriptos} de ${cupoMax} cupos ocupados</div>
                                </div>

                                <!-- Barra de progreso de cupo -->
                                <div style="background-color: var(--color-borde); height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 0.5rem;">
                                    <div style="background-color: ${cupoLleno ? 'var(--color-error)' : 'var(--color-primario)'}; width: ${porcentaje}%; height: 100%;"></div>
                                </div>
                                <div style="text-align: right; font-size: 0.75rem; color: var(--color-texto-suave);">
                                    ${cupoMax - inscriptos} cupos libres
                                </div>
                            </div>
                            <div class="tarjeta-pie" style="justify-content: flex-end;">
                                ${botonHtml}
                            </div>
                        </div>
                    `;
                }).join('');
            }

            contenedor.innerHTML = `
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Inscripcion a Divisiones Deportivas</h2>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Selecciona una actividad deportiva para incorporarte al plantel (Regla R8).
                            </p>
                        </div>
                        <div style="font-size: 0.85rem; background-color: var(--color-superficie); padding: 0.5rem 1rem; border-radius: var(--radio-pequeno); border: 1px solid var(--color-borde);">
                            Edad calculada: <strong>${edadSocio} anos</strong> | Categoria: <strong>${UI.escaparHtml(socio.categoria_nombre)}</strong>
                        </div>
                    </div>

                    ${bannerEstado}

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                        ${tarjetasHtml}
                    </div>
                </div>
            `;

            // Vincular botones de inscripcion
            contenedor.querySelectorAll('.btn-inscribirse').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const divisionId = parseInt(btn.dataset.id, 10);
                    const nombreDiv = btn.dataset.nombre;

                    const confirmado = await UI.confirmar(
                        `Desea confirmar su inscripcion formal en la division ${nombreDiv}?`,
                        'Confirmar Inscripcion Deportiva'
                    );

                    if (confirmado) {
                        btn.disabled = true;
                        btn.textContent = 'Inscribiendo...';

                        try {
                            const res = await Api.post(`/divisiones/${divisionId}/inscripciones`, {});
                            UI.notificar(res.mensaje || 'Inscripcion confirmada con exito.', 'exito');
                            // Recargar catalogo para reflejar cupo actualizado
                            await render(contenedor);
                        } catch (error) {
                            btn.disabled = false;
                            btn.innerHTML = `${UI.icono('check')} Inscribirme`;
                            UI.notificar(error.message, 'error');
                        }
                    }
                });
            });

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al consultar divisiones:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
