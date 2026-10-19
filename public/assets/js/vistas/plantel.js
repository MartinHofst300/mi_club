/**
 * Vista de Gestion del Plantel por el Entrenador (Caso de uso Gestionar plantel, Reglas R8 y R9).
 * Capa: Presentacion / Vistas.
 *
 * Permite al entrenador consultar la nomina de atletas asignados a sus divisiones,
 * dar de baja integrantes y formalizar nuevas inscripciones comprobando cupo y rango etario.
 */

const VistaPlantel = (function () {
    let divisionSeleccionadaId = null;

    /**
     * Renderiza la pantalla de gestion de plantel.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando planteles deportivos...');

        const usuario = Auth.obtenerUsuario();
        const usuarioId = Auth.obtenerUsuarioId();
        const esAdmin = usuario && usuario.rol === 'ADMINISTRADOR';

        try {
            const todasDivisiones = await Api.get('/divisiones');

            // Filtrar las divisiones del entrenador si no es admin
            const divisiones = esAdmin
                ? todasDivisiones
                : todasDivisiones.filter(d => usuarioId !== null && parseInt(d.entrenador_id, 10) === usuarioId);

            if (divisiones.length === 0) {
                contenedor.innerHTML = `
                    <div class="tarjeta" style="padding: 3rem 2rem; text-align: center; color: var(--color-texto-suave);">
                        <h3 style="color: var(--color-primario-oscuro); margin-bottom: 0.5rem;">Sin divisiones asignadas</h3>
                        <p>No tienes divisiones deportivas asignadas a tu cargo actualmente.</p>
                    </div>
                `;
                return;
            }

            // Seleccionar primera division por defecto si no hay seleccion previa valida
            if (!divisionSeleccionadaId || !divisiones.some(d => parseInt(d.id, 10) === divisionSeleccionadaId)) {
                divisionSeleccionadaId = parseInt(divisiones[0].id, 10);
            }

            const divisionActiva = divisiones.find(d => parseInt(d.id, 10) === divisionSeleccionadaId);
            const plantel = await Api.get(`/divisiones/${divisionSeleccionadaId}/plantel`);

            const cupoMax = parseInt(divisionActiva.cupo_maximo, 10);
            const inscriptosCount = plantel.length;
            const cupoLleno = inscriptosCount >= cupoMax;

            // Filas de socios del plantel
            let filasPlantel = '';
            if (plantel.length === 0) {
                filasPlantel = `
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--color-texto-suave); padding: 2.5rem;">
                            No hay socios inscriptos activos en este plantel todavia.
                        </td>
                    </tr>
                `;
            } else {
                filasPlantel = plantel.map(s => {
                    const badgeClase = s.estado === 'ACTIVO' ? 'etiqueta-exito' : 'etiqueta-peligro';
                    return `
                        <tr>
                            <td style="font-family: var(--fuente-mono); font-weight: 600;">${UI.escaparHtml(s.numero_socio)}</td>
                            <td><strong>${UI.escaparHtml(s.apellido)}, ${UI.escaparHtml(s.nombre)}</strong></td>
                            <td>${UI.escaparHtml(s.dni)}</td>
                            <td>${UI.formatearFecha(s.fecha_nacimiento)}</td>
                            <td>${UI.formatearFecha(s.fecha_inscripcion)}</td>
                            <td><span class="etiqueta ${badgeClase}">${UI.escaparHtml(s.estado)}</span></td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-sm btn-peligro btn-baja-plantel" data-socio-id="${s.socio_id}" data-nombre="${UI.escaparHtml(s.nombre + ' ' + s.apellido)}">
                                    ${UI.icono('anular')} Dar de Baja
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // Opciones de selector de divisiones
            const opcionesDivisiones = divisiones.map(d => `
                <option value="${d.id}" ${parseInt(d.id, 10) === divisionSeleccionadaId ? 'selected' : ''}>
                    ${UI.escaparHtml(d.nombre)} (${UI.escaparHtml(d.categoria_nombre)})
                </option>
            `).join('');

            contenedor.innerHTML = `
                <div>
                    <!-- Encabezado con selector -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Gestion de Plantel Deportivo</h2>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Administracion de atletas y nomina activa por division (Regla R8).
                            </p>
                        </div>

                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <label for="select-division-plantel" style="font-weight: 600; font-size: 0.9rem;">Division:</label>
                                <select id="select-division-plantel" class="input-control" style="width: auto;">
                                    ${opcionesDivisiones}
                                </select>
                            </div>

                            <button type="button" class="btn btn-primario" id="btn-abrir-incorporar-socio" ${cupoLleno ? 'disabled title="Cupo maximo alcanzado"' : ''}>
                                ${UI.icono('check')} Incorporar Socio
                            </button>
                        </div>
                    </div>

                    <!-- Metricas del plantel activo -->
                    <div class="rejilla-resumen">
                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('plantel')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Integrantes en Plantel</div>
                                <div class="metrica-valor">
                                    ${inscriptosCount} / ${cupoMax}
                                </div>
                            </div>
                        </div>

                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('asistencia')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Cupo Disponible</div>
                                <div class="metrica-valor" style="color: ${cupoLleno ? 'var(--color-error)' : 'var(--color-exito)'};">
                                    ${cupoMax - inscriptosCount} libres
                                </div>
                            </div>
                        </div>

                        <div class="caja-metrica">
                            <div class="metrica-icono">${UI.icono('carnet')}</div>
                            <div class="metrica-datos">
                                <div class="metrica-etiqueta">Categoria Asignada</div>
                                <div class="metrica-valor" style="font-size: 1.15rem;">
                                    ${UI.escaparHtml(divisionActiva.categoria_nombre)}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de plantel -->
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Nomina del Plantel: ${UI.escaparHtml(divisionActiva.nombre)}</h3>
                        </div>
                        <div class="tabla-contenedor">
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Numero Socio</th>
                                        <th>Nombre y Apellido</th>
                                        <th>DNI</th>
                                        <th>Nacimiento</th>
                                        <th>Inscripcion</th>
                                        <th>Estado Contable</th>
                                        <th style="text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filasPlantel}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            // Cambio de division en selector
            const selectDiv = contenedor.querySelector('#select-division-plantel');
            if (selectDiv) {
                selectDiv.addEventListener('change', async (e) => {
                    divisionSeleccionadaId = parseInt(e.target.value, 10);
                    await render(contenedor);
                });
            }

            // Accion: Dar de baja del plantel
            contenedor.querySelectorAll('.btn-baja-plantel').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const socioId = parseInt(btn.dataset.socioId, 10);
                    const nombre = btn.dataset.nombre;

                    const confirmado = await UI.confirmar(
                        `Esta seguro de dar de baja al socio ${nombre} del plantel de ${divisionActiva.nombre}?`,
                        'Dar de Baja del Plantel'
                    );

                    if (confirmado) {
                        try {
                            const res = await Api.delete(`/divisiones/${divisionSeleccionadaId}/inscripciones/${socioId}`);
                            UI.notificar(res.mensaje || 'Socio dado de baja exitosamente.', 'exito');
                            await render(contenedor);
                        } catch (error) {
                            UI.notificar(error.message, 'error');
                        }
                    }
                });
            });

            // Accion: Incorporar socio al plantel
            const btnIncorporar = contenedor.querySelector('#btn-abrir-incorporar-socio');
            if (btnIncorporar) {
                btnIncorporar.addEventListener('click', () => {
                    mostrarModalIncorporar(divisionActiva, contenedor);
                });
            }

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al gestionar plantel:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    /**
     * Modal interactivo para incorporar un socio al plantel deportivo buscando por DNI/nombre.
     */
    async function mostrarModalIncorporar(division, contenedor) {
        const modalFondo = document.createElement('div');
        modalFondo.className = 'modal-fondo';
        modalFondo.innerHTML = `
            <div class="modal-caja" style="max-width: 520px;">
                <div class="modal-cabecera">
                    <h3 class="modal-titulo">Incorporar Socio a ${UI.escaparHtml(division.nombre)}</h3>
                </div>
                <div class="modal-cuerpo">
                    <p style="font-size: 0.85rem; color: var(--color-texto-suave); margin-bottom: 1rem;">
                        Rango de edad requerido: <strong>${division.edad_minima} a ${division.edad_maxima} anos</strong>.
                        El socio debe estar en estado ACTIVO (no moroso) para poder ser incorporado (Regla R8).
                    </p>

                    <div class="campo-grupo">
                        <label class="etiqueta-campo" for="input-busqueda-socio-plantel">Buscar socio por DNI o Apellido</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" id="input-busqueda-socio-plantel" class="input-control" placeholder="Ingrese DNI o apellido...">
                            <button type="button" class="btn btn-secundario" id="btn-buscar-socio-plantel">
                                ${UI.icono('buscar')}
                            </button>
                        </div>
                    </div>

                    <div id="resultados-busqueda-plantel" style="max-height: 200px; overflow-y: auto; margin-top: 0.75rem;">
                        <div style="font-size: 0.85rem; color: var(--color-texto-suave); text-align: center; padding: 1rem;">
                            Ingrese un termino y presione buscar para seleccionar un socio.
                        </div>
                    </div>

                    <div id="error-incorporacion" style="color: var(--color-error); font-size: 0.85rem; margin-top: 0.75rem; display: none;"></div>
                </div>
                <div class="modal-pie">
                    <button type="button" class="btn btn-secundario" id="btn-cerrar-modal-inc">Cerrar</button>
                </div>
            </div>
        `;

        document.body.appendChild(modalFondo);

        const btnCerrar = modalFondo.querySelector('#btn-cerrar-modal-inc');
        const inputBuscar = modalFondo.querySelector('#input-busqueda-socio-plantel');
        const btnBuscar = modalFondo.querySelector('#btn-buscar-socio-plantel');
        const resultadosDiv = modalFondo.querySelector('#resultados-busqueda-plantel');
        const errorDiv = modalFondo.querySelector('#error-incorporacion');

        btnCerrar.addEventListener('click', () => modalFondo.remove());

        async function buscarSocios() {
            const q = inputBuscar.value.trim();
            if (!q) return;

            resultadosDiv.innerHTML = '<div style="text-align: center; padding: 1rem;">Buscando socios...</div>';
            errorDiv.style.display = 'none';

            try {
                const socios = await Api.get(`/socios?q=${encodeURIComponent(q)}`);
                if (socios.length === 0) {
                    resultadosDiv.innerHTML = '<div style="text-align: center; color: var(--color-texto-suave); padding: 1rem;">No se encontraron socios.</div>';
                    return;
                }

                resultadosDiv.innerHTML = socios.map(s => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; border-bottom: 1px solid var(--color-borde);">
                        <div>
                            <strong>${UI.escaparHtml(s.apellido)}, ${UI.escaparHtml(s.nombre)}</strong>
                            <div style="font-size: 0.75rem; color: var(--color-texto-suave);">
                                DNI: ${UI.escaparHtml(s.dni)} | Estado: ${UI.escaparHtml(s.estado)}
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primario btn-confirmar-agregar" data-id="${s.id}">
                            Seleccionar
                        </button>
                    </div>
                `).join('');

                resultadosDiv.querySelectorAll('.btn-confirmar-agregar').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const socioId = parseInt(btn.dataset.id, 10);
                        btn.disabled = true;
                        btn.textContent = 'Inscribiendo...';

                        try {
                            const res = await Api.post(`/divisiones/${division.id}/inscripciones`, {
                                socio_id: socioId
                            });

                            UI.notificar(res.mensaje || 'Socio incorporado al plantel exitosamente.', 'exito');
                            modalFondo.remove();
                            await render(contenedor);
                        } catch (error) {
                            btn.disabled = false;
                            btn.textContent = 'Seleccionar';
                            errorDiv.textContent = error.message;
                            errorDiv.style.display = 'block';
                        }
                    });
                });

            } catch (error) {
                resultadosDiv.innerHTML = `<div style="color: var(--color-error); text-align: center; padding: 1rem;">${UI.escaparHtml(error.message)}</div>`;
            }
        }

        btnBuscar.addEventListener('click', buscarSocios);
        inputBuscar.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') buscarSocios();
        });
    }

    return {
        render
    };
})();
