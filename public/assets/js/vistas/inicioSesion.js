/**
 * Vista de Inicio de Sesion de MiClub.
 * Capa: Presentacion / Vistas.
 */

const VistaInicioSesion = {
    render: async function (contenedor) {
        const config = Tema.obtenerConfiguracion() || {};
        const nombreClub = config.nombre_club || 'Club Social y Deportivo MiClub';

        contenedor.innerHTML = `
            <div class="contenedor-autenticacion">
                <div class="tarjeta" style="width: 100%; max-width: 440px; box-shadow: var(--sombra-elevada);">
                    <div class="tarjeta-cabecera" style="justify-content: center; flex-direction: column; text-align: center; padding: 2rem 1.5rem 1.25rem 1.5rem;">
                        <div class="icono-escudo" style="width: 52px; height: 52px; font-size: 1.5rem; margin-bottom: 0.75rem;">
                            MC
                        </div>
                        <h2 style="font-size: 1.35rem; color: var(--color-primario-oscuro); margin-bottom: 0.25rem;" class="nombre-club-dinamico">
                            ${UI.escaparHtml(nombreClub)}
                        </h2>
                        <div style="font-size: 0.85rem; color: var(--color-texto-suave);">
                            Sistema de Gestion Integral
                        </div>
                    </div>

                    <div class="tarjeta-cuerpo" style="padding: 1.75rem;">
                        <div id="error-inicio-sesion" style="display: none;"></div>

                        <form id="formulario-inicio-sesion">
                            <div class="grupo-formulario">
                                <label for="input-identificador" class="etiqueta-formulario">Correo Electronico o DNI</label>
                                <input type="text" id="input-identificador" class="control-formulario" placeholder="ejemplo@miclub.local o 35123456" required autofocus autocomplete="username">
                            </div>

                            <div class="grupo-formulario" style="margin-bottom: 1.75rem;">
                                <label for="input-password" class="etiqueta-formulario">Contrasena</label>
                                <input type="password" id="input-password" class="control-formulario" placeholder="Ingrese su clave" required autocomplete="current-password">
                            </div>

                            <button type="submit" class="btn btn-primario btn-bloque" id="btn-ingresar">
                                Iniciar Sesion
                            </button>
                        </form>
                    </div>

                    <div class="tarjeta-pie" style="justify-content: center; font-size: 0.8rem; color: var(--color-texto-suave); padding: 1rem;">
                        Acceso exclusivo para socios y autoridades del club
                    </div>
                </div>
            </div>
        `;

        const form = contenedor.querySelector('#formulario-inicio-sesion');
        const errorDiv = contenedor.querySelector('#error-inicio-sesion');
        const btnIngresar = contenedor.querySelector('#btn-ingresar');

        const mensajeExpirada = sessionStorage.getItem('mensaje_sesion_expirada');
        if (mensajeExpirada) {
            errorDiv.innerHTML = UI.alerta(mensajeExpirada, 'peligro');
            errorDiv.style.display = 'block';
            sessionStorage.removeItem('mensaje_sesion_expirada');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorDiv.style.display = 'none';
            btnIngresar.disabled = true;
            btnIngresar.textContent = 'Verificando...';

            const identificador = contenedor.querySelector('#input-identificador').value.trim();
            const password = contenedor.querySelector('#input-password').value;

            try {
                await Auth.iniciarSesion(identificador, password);
                UI.notificar('Inicio de sesion exitoso', 'exito');
                App.actualizarMenuLateral();
                window.location.hash = '#panel';
            } catch (error) {
                errorDiv.innerHTML = `
                    <div class="aviso aviso-error" style="margin-bottom: 1rem;">
                        <div class="aviso-icono">${UI.icono('alerta')}</div>
                        <div>${UI.escaparHtml(error.message)}</div>
                    </div>
                `;
                errorDiv.style.display = 'block';
                btnIngresar.disabled = false;
                btnIngresar.textContent = 'Iniciar Sesion';
            }
        });
    }
};
