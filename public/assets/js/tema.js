/**
 * Gestor de Tema y Apariencia Institucional de MiClub.
 * Capa: Presentacion / Tema visual.
 *
 * Carga la configuracion institucional desde el backend y aplica las variables CSS en tiempo de ejecucion.
 */

const Tema = (function () {
    let configuracionActual = null;

    /**
     * Aplica los colores institucionales a las variables CSS de la raiz del documento.
     *
     * @param {object} config Datos de la tabla configuracion
     */
    function aplicarVariablesCss(config) {
        if (!config) return;

        const raiz = document.documentElement;

        if (config.color_primario) raiz.style.setProperty('--color-primario', config.color_primario);
        if (config.color_primario_oscuro) raiz.style.setProperty('--color-primario-oscuro', config.color_primario_oscuro);
        if (config.color_secundario) raiz.style.setProperty('--color-secundario', config.color_secundario);
        if (config.color_acento) raiz.style.setProperty('--color-acento', config.color_acento);
        if (config.color_fondo) raiz.style.setProperty('--color-fondo', config.color_fondo);
        if (config.color_texto) raiz.style.setProperty('--color-texto', config.color_texto);

        // Actualizar textos del nombre del club en la interfaz
        if (config.nombre_club) {
            document.title = config.nombre_club + ' - MiClub';
            document.querySelectorAll('.nombre-club-dinamico').forEach(elem => {
                elem.textContent = config.nombre_club;
            });
        }
    }

    /**
     * Consulta la configuracion institucional al backend y aplica los estilos.
     */
    async function cargar() {
        try {
            const config = await Api.get('/configuracion');
            configuracionActual = config;
            aplicarVariablesCss(config);
            return config;
        } catch (error) {
            console.warn('No se pudo cargar la configuracion remota. Se utiliza el tema por defecto.', error);
            return null;
        }
    }

    return {
        cargar,
        aplicarVariablesCss,
        obtenerConfiguracion: () => configuracionActual
    };
})();
