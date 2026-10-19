/**
 * Vista de Apariencia del Club para el Administrador (Adelanto Fase 3, Caso de uso Apariencia del club).
 * Capa: Presentacion / Vistas.
 *
 * Provee selectores de color hexadecimal con vista previa en vivo en tiempo real,
 * boton de restauracion a la paleta institucional original y persistencia en la base de datos.
 */

const VistaApariencia = (function () {
    // Paleta institucional por defecto (Valores de inicio limpios y contrastados)
    const PALETA_ORIGINAL = {
        nombre_club: 'Club Social y Deportivo MiClub',
        color_primario: '#1F5C3B',
        color_primario_oscuro: '#14402A',
        color_secundario: '#E4EFE7',
        color_acento: '#C98A1B',
        color_fondo: '#F6F4EC',
        color_texto: '#1E2A24'
    };

    /**
     * Renderiza el panel de configuracion visual y apariencia.
     *
     * @param {HTMLElement} contenedor Elemento DOM principal
     */
    async function render(contenedor) {
        UI.mostrarCargando(contenedor, 'Cargando configuracion visual...');

        try {
            const config = await Api.get('/configuracion');

            const valores = {
                nombre_club: config.nombre_club || PALETA_ORIGINAL.nombre_club,
                color_primario: config.color_primario || PALETA_ORIGINAL.color_primario,
                color_primario_oscuro: config.color_primario_oscuro || PALETA_ORIGINAL.color_primario_oscuro,
                color_secundario: config.color_secundario || PALETA_ORIGINAL.color_secundario,
                color_acento: config.color_acento || PALETA_ORIGINAL.color_acento,
                color_fondo: config.color_fondo || PALETA_ORIGINAL.color_fondo,
                color_texto: config.color_texto || PALETA_ORIGINAL.color_texto
            };

            contenedor.innerHTML = `
                <div style="max-width: 900px; margin: 0 auto;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: var(--color-primario-oscuro);">Apariencia Institucional del Club</h2>
                            <p style="margin: 0.25rem 0 0 0; color: var(--color-texto-suave); font-size: 0.9rem;">
                                Personaliza la identidad grafica, colores corporativos y nombre oficial del club.
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            <button type="button" class="btn btn-secundario" id="btn-restaurar-paleta">
                                ${UI.icono('paleta')} Restaurar Paleta Original
                            </button>
                            <button type="button" class="btn btn-primario" id="btn-guardar-apariencia">
                                ${UI.icono('check')} Guardar Cambios
                            </button>
                        </div>
                    </div>

                    <!-- Formulario de nombre institucional -->
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Nombre Institucional</h3>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <div class="campo-grupo" style="margin-bottom: 0;">
                                <label class="etiqueta-campo" for="input-nombre-club">Nombre Oficial del Club *</label>
                                <input type="text" id="input-nombre-club" class="input-control" value="${UI.escaparHtml(valores.nombre_club)}" required maxlength="150">
                                <span style="font-size: 0.8rem; color: var(--color-texto-suave);">
                                    Se actualizara en encabezados, carnet oficial, recibos y barra lateral.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Selectores de color -->
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Paleta Cromatica Institucional</h3>
                            <span style="font-size: 0.8rem; color: var(--color-texto-suave);">Formatos hexadecimales #RRGGBB</span>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <div class="grid-colores-tema">
                                <!-- Primario -->
                                <div class="selector-color-caja">
                                    <label class="etiqueta-campo" for="hex-primario">Color Primario</label>
                                    <div class="selector-color-control">
                                        <input type="color" id="picker-primario" class="input-color-nativo" value="${valores.color_primario}">
                                        <input type="text" id="hex-primario" class="input-control" value="${valores.color_primario}" maxlength="7" style="font-family: var(--fuente-mono);">
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Botones de accion, encabezados</span>
                                </div>

                                <!-- Primario Oscuro -->
                                <div class="selector-color-caja">
                                    <label class="etiqueta-campo" for="hex-primario-oscuro">Primario Oscuro</label>
                                    <div class="selector-color-control">
                                        <input type="color" id="picker-primario-oscuro" class="input-color-nativo" value="${valores.color_primario_oscuro}">
                                        <input type="text" id="hex-primario-oscuro" class="input-control" value="${valores.color_primario_oscuro}" maxlength="7" style="font-family: var(--fuente-mono);">
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Fondo de barra lateral, contrastes</span>
                                </div>

                                <!-- Secundario -->
                                <div class="selector-color-caja">
                                    <label class="etiqueta-campo" for="hex-secundario">Color Secundario</label>
                                    <div class="selector-color-control">
                                        <input type="color" id="picker-secundario" class="input-color-nativo" value="${valores.color_secundario}">
                                        <input type="text" id="hex-secundario" class="input-control" value="${valores.color_secundario}" maxlength="7" style="font-family: var(--fuente-mono);">
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Superficies suaves, bandas de tablas</span>
                                </div>

                                <!-- Acento -->
                                <div class="selector-color-caja">
                                    <label class="etiqueta-campo" for="hex-acento">Color de Acento</label>
                                    <div class="selector-color-control">
                                        <input type="color" id="picker-acento" class="input-color-nativo" value="${valores.color_acento}">
                                        <input type="text" id="hex-acento" class="input-control" value="${valores.color_acento}" maxlength="7" style="font-family: var(--fuente-mono);">
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Botones de pago, llamados de atencion</span>
                                </div>

                                <!-- Fondo -->
                                <div class="selector-color-caja">
                                    <label class="etiqueta-campo" for="hex-fondo">Fondo General</label>
                                    <div class="selector-color-control">
                                        <input type="color" id="picker-fondo" class="input-color-nativo" value="${valores.color_fondo}">
                                        <input type="text" id="hex-fondo" class="input-control" value="${valores.color_fondo}" maxlength="7" style="font-family: var(--fuente-mono);">
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Lienzo y fondo de aplicacion</span>
                                </div>

                                <!-- Texto -->
                                <div class="selector-color-caja">
                                    <label class="etiqueta-campo" for="hex-texto">Texto Principal</label>
                                    <div class="selector-color-control">
                                        <input type="color" id="picker-texto" class="input-color-nativo" value="${valores.color_texto}">
                                        <input type="text" id="hex-texto" class="input-control" value="${valores.color_texto}" maxlength="7" style="font-family: var(--fuente-mono);">
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--color-texto-suave);">Tipografia y titulos generales</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vista previa interactiva en vivo -->
                    <div class="tarjeta">
                        <div class="tarjeta-cabecera">
                            <h3 class="tarjeta-titulo">Vista Previa Interactiva en Vivo</h3>
                            <span style="font-size: 0.8rem; color: var(--color-texto-suave);">Se actualiza en tiempo real al modificar cualquier color</span>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <div id="contenedor-preview-vivo" class="preview-apariencia-caja">
                                <!-- Cabecera simulada -->
                                <div id="preview-cabecera" style="padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div id="preview-escudo" style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.95rem;">
                                            MC
                                        </div>
                                        <div>
                                            <div id="preview-nombre-club" style="font-weight: 700; font-size: 1.1rem;"></div>
                                            <div style="font-size: 0.75rem; opacity: 0.85;">Panel de Administracion Institucional</div>
                                        </div>
                                    </div>
                                    <div id="preview-tag-rol" style="padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                        ADMINISTRADOR
                                    </div>
                                </div>

                                <!-- Botones y muestras de color -->
                                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem;">
                                    <button type="button" id="preview-btn-primario" class="btn" style="pointer-events: none;">
                                        ${UI.icono('check')} Boton Primario
                                    </button>
                                    <button type="button" id="preview-btn-acento" class="btn" style="pointer-events: none;">
                                        ${UI.icono('pago')} Boton de Acento
                                    </button>
                                    <div id="preview-caja-secundaria" style="padding: 0.55rem 1.15rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem;">
                                        Superficie Secundaria
                                    </div>
                                </div>

                                <!-- Mini carnet de muestra -->
                                <div id="preview-mini-carnet" style="max-width: 420px; border-radius: 12px; padding: 1.25rem; color: var(--color-texto-inverso); box-shadow: var(--sombra-media);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.25); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                                        <span id="preview-carnet-titulo" style="font-weight: 700; font-size: 0.95rem;"></span>
                                        <span style="font-family: var(--fuente-mono); font-size: 0.75rem;">SOC-00000001</span>
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem;">Carlos Benitez</div>
                                    <div style="font-size: 0.8rem; opacity: 0.85; margin-bottom: 0.75rem;">Categoria: Mayores | Division: Basquet Mayores</div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="background-color: var(--color-exito); color: var(--color-texto-inverso); font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 20px;">
                                            VIGENTE
                                        </span>
                                        <span style="font-size: 0.75rem; opacity: 0.85;">Alta: 10/01/2026</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Enlazar inputs
            const inputNombre = contenedor.querySelector('#input-nombre-club');

            const pares = [
                { picker: '#picker-primario', hex: '#hex-primario', clave: 'color_primario' },
                { picker: '#picker-primario-oscuro', hex: '#hex-primario-oscuro', clave: 'color_primario_oscuro' },
                { picker: '#picker-secundario', hex: '#hex-secundario', clave: 'color_secundario' },
                { picker: '#picker-acento', hex: '#hex-acento', clave: 'color_acento' },
                { picker: '#picker-fondo', hex: '#hex-fondo', clave: 'color_fondo' },
                { picker: '#picker-texto', hex: '#hex-texto', clave: 'color_texto' }
            ];

            function sincronizar(par, origen) {
                const elPicker = contenedor.querySelector(par.picker);
                const elHex = contenedor.querySelector(par.hex);

                if (origen === 'picker') {
                    elHex.value = elPicker.value.toUpperCase();
                } else {
                    let val = elHex.value.trim();
                    if (!val.startsWith('#')) val = '#' + val;
                    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                        elPicker.value = val;
                    }
                }
                actualizarVistaPrevia();
            }

            pares.forEach(p => {
                const elPicker = contenedor.querySelector(p.picker);
                const elHex = contenedor.querySelector(p.hex);

                elPicker.addEventListener('input', () => sincronizar(p, 'picker'));
                elHex.addEventListener('input', () => sincronizar(p, 'hex'));
            });

            inputNombre.addEventListener('input', actualizarVistaPrevia);

            /**
             * Actualiza dinamicamente la caja de vista previa en tiempo real
             */
            function actualizarVistaPrevia() {
                const cPrimario = contenedor.querySelector('#hex-primario').value.trim();
                const cPrimarioOscuro = contenedor.querySelector('#hex-primario-oscuro').value.trim();
                const cSecundario = contenedor.querySelector('#hex-secundario').value.trim();
                const cAcento = contenedor.querySelector('#hex-acento').value.trim();
                const cFondo = contenedor.querySelector('#hex-fondo').value.trim();
                const cTexto = contenedor.querySelector('#hex-texto').value.trim();
                const nombreClub = inputNombre.value.trim() || 'Club Social y Deportivo MiClub';

                const cajaPreview = contenedor.querySelector('#contenedor-preview-vivo');
                cajaPreview.style.backgroundColor = cFondo;
                cajaPreview.style.color = cTexto;

                // Cabecera preview
                const cabecera = contenedor.querySelector('#preview-cabecera');
                cabecera.style.backgroundColor = cPrimarioOscuro;
                cabecera.style.color = 'var(--color-texto-inverso)';

                const escudo = contenedor.querySelector('#preview-escudo');
                escudo.style.backgroundColor = cAcento;
                escudo.style.color = 'var(--color-texto-inverso)';

                const tagRol = contenedor.querySelector('#preview-tag-rol');
                tagRol.style.backgroundColor = cSecundario;
                tagRol.style.color = cPrimarioOscuro;

                contenedor.querySelector('#preview-nombre-club').textContent = nombreClub;

                // Boton primario
                const btnPri = contenedor.querySelector('#preview-btn-primario');
                btnPri.style.backgroundColor = cPrimario;
                btnPri.style.color = 'var(--color-texto-inverso)';
                btnPri.style.borderColor = cPrimario;

                // Boton acento
                const btnAce = contenedor.querySelector('#preview-btn-acento');
                btnAce.style.backgroundColor = cAcento;
                btnAce.style.color = 'var(--color-texto-inverso)';
                btnAce.style.borderColor = cAcento;

                // Caja secundaria
                const cajaSec = contenedor.querySelector('#preview-caja-secundaria');
                cajaSec.style.backgroundColor = cSecundario;
                cajaSec.style.color = cTexto;

                // Mini carnet
                const miniCarnet = contenedor.querySelector('#preview-mini-carnet');
                miniCarnet.style.background = `linear-gradient(135deg, ${cPrimarioOscuro} 0%, ${cPrimario} 100%)`;
                contenedor.querySelector('#preview-carnet-titulo').textContent = nombreClub;
            }

            actualizarVistaPrevia();

            // Boton: Restaurar Paleta Original
            contenedor.querySelector('#btn-restaurar-paleta').addEventListener('click', async () => {
                const confirmado = await UI.confirmar(
                    'Desea restaurar todos los selectores a la paleta institucional original?',
                    'Restaurar Paleta Original'
                );

                if (confirmado) {
                    inputNombre.value = PALETA_ORIGINAL.nombre_club;
                    contenedor.querySelector('#picker-primario').value = PALETA_ORIGINAL.color_primario;
                    contenedor.querySelector('#hex-primario').value = PALETA_ORIGINAL.color_primario;
                    contenedor.querySelector('#picker-primario-oscuro').value = PALETA_ORIGINAL.color_primario_oscuro;
                    contenedor.querySelector('#hex-primario-oscuro').value = PALETA_ORIGINAL.color_primario_oscuro;
                    contenedor.querySelector('#picker-secundario').value = PALETA_ORIGINAL.color_secundario;
                    contenedor.querySelector('#hex-secundario').value = PALETA_ORIGINAL.color_secundario;
                    contenedor.querySelector('#picker-acento').value = PALETA_ORIGINAL.color_acento;
                    contenedor.querySelector('#hex-acento').value = PALETA_ORIGINAL.color_acento;
                    contenedor.querySelector('#picker-fondo').value = PALETA_ORIGINAL.color_fondo;
                    contenedor.querySelector('#hex-fondo').value = PALETA_ORIGINAL.color_fondo;
                    contenedor.querySelector('#picker-texto').value = PALETA_ORIGINAL.color_texto;
                    contenedor.querySelector('#hex-texto').value = PALETA_ORIGINAL.color_texto;

                    actualizarVistaPrevia();
                    UI.notificar('Paleta original cargada en los selectores. Presione "Guardar Cambios" para aplicarla permanentemente.', 'alerta');
                }
            });

            // Boton: Guardar Cambios
            const btnGuardar = contenedor.querySelector('#btn-guardar-apariencia');
            btnGuardar.addEventListener('click', async () => {
                const payload = {
                    nombre_club: inputNombre.value.trim(),
                    color_primario: contenedor.querySelector('#hex-primario').value.trim(),
                    color_primario_oscuro: contenedor.querySelector('#hex-primario-oscuro').value.trim(),
                    color_secundario: contenedor.querySelector('#hex-secundario').value.trim(),
                    color_acento: contenedor.querySelector('#hex-acento').value.trim(),
                    color_fondo: contenedor.querySelector('#hex-fondo').value.trim(),
                    color_texto: contenedor.querySelector('#hex-texto').value.trim()
                };

                // Validacion basica de formato hex
                const regexHex = /^#[0-9A-Fa-f]{6}$/;
                for (const [clave, val] of Object.entries(payload)) {
                    if (clave !== 'nombre_club' && !regexHex.test(val)) {
                        UI.notificar(`El color ${clave} no posee un formato hexadecimal valido (#RRGGBB).`, 'error');
                        return;
                    }
                }

                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando...';

                try {
                    const res = await Api.put('/configuracion', payload);
                    Tema.aplicarVariablesCss(res);
                    UI.notificar('Apariencia institucional guardada y aplicada con exito.', 'exito');
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = `${UI.icono('check')} Guardar Cambios`;
                } catch (error) {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = `${UI.icono('check')} Guardar Cambios`;
                    UI.notificar(error.message, 'error');
                }
            });

        } catch (error) {
            contenedor.innerHTML = `
                <div class="alerta alerta-error">
                    <div class="alerta-icono">${UI.icono('alerta')}</div>
                    <div>
                        <strong>Error al cargar configuracion:</strong> ${UI.escaparHtml(error.message)}
                    </div>
                </div>
            `;
        }
    }

    return {
        render
    };
})();
