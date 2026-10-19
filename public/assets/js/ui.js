/**
 * Utilitarios de Interfaz de Usuario y Notificaciones de MiClub.
 * Capa: Presentacion / UI.
 *
 * Provee componentes dinamicos: modales de confirmacion, mensajes toast,
 * formateo de datos, escape contra XSS e iconos vectoriales SVG limpios (cero emojis).
 */

const UI = (function () {
    // Catalogo de iconos SVG limpios y sobrios (sin emojis ni caracteres decorativos unicode)
    const ICONOS = {
        usuario: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
        perfil: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
        cuota: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg>',
        pago: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>',
        asistencia: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
        carnet: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-6 10H6v-2h8v2zm4-4H6V8h12v2z"/></svg>',
        entrenamiento: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2v-7.5l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7"/></svg>',
        morosos: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
        recibo: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14l3-2 3 2 3-2 3 2 3-2 3 2V5c0-1.1-.9-2-2-2zm-3 10H8v-2h8v2zm0-4H8V7h8v2z"/></svg>',
        panel: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>',
        salir: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>',
        alerta: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
        check: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
        imprimir: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>',
        buscar: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
        configuracion: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>',
        escudo: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>',
        editar: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
        anular: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
        paleta: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 19.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l1.9-1.9C9.22 19.59 10.56 20 12 20c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-4 7c-.83 0-1.5-.67-1.5-1.5S7.17 7 8 7s1.5.67 1.5 1.5S8.83 10 8 10zm4-2c-.83 0-1.5-.67-1.5-1.5S11.17 5 12 5s1.5.67 1.5 1.5S12.83 8 12 8zm4 2c-.83 0-1.5-.67-1.5-1.5S15.17 7 16 7s1.5.67 1.5 1.5S16.83 10 16 10z"/></svg>',
        plantel: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>',
        sancion: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/></svg>',
        candado: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>',
        flechaIzquierda: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>',
        flechaDerecha: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>',
        agregar: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
        guardar: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>',
        eliminar: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
        cerrar: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
        info: '<svg class="icono-svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'
    };

    /**
     * Escapa caracteres especiales en cadenas para prevenir XSS.
     */
    function escaparHtml(cadena) {
        if (cadena === null || cadena === undefined) return '';
        const mapa = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(cadena).replace(/[&<>"']/g, m => mapa[m]);
    }

    /**
     * Muestra una notificacion flotante temporal (Toast).
     */
    function notificar(mensaje, tipo = 'exito') {
        const contenedor = document.getElementById('contenedor-notificaciones');
        if (!contenedor) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;

        const icono = tipo === 'exito' ? ICONOS.check : (tipo === 'error' ? ICONOS.alerta : ICONOS.alerta);
        toast.innerHTML = `
            <div class="aviso-icono">${icono}</div>
            <div class="toast-mensaje">${escaparHtml(mensaje)}</div>
        `;

        contenedor.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    /**
     * Cuadro de confirmacion modal asincronico basado en Promesas.
     */
    function confirmar(mensaje, titulo = 'Confirmar accion') {
        return new Promise((resolve) => {
            const modalFondo = document.createElement('div');
            modalFondo.className = 'modal-fondo';
            modalFondo.innerHTML = `
                <div class="modal-caja" style="max-width: 440px;">
                    <div class="modal-cabecera">
                        <h3 class="modal-titulo">${escaparHtml(titulo)}</h3>
                    </div>
                    <div class="modal-cuerpo">
                        <p>${escaparHtml(mensaje)}</p>
                    </div>
                    <div class="modal-pie">
                        <button type="button" class="btn btn-secundario" id="btn-modal-cancelar">Cancelar</button>
                        <button type="button" class="btn btn-primario" id="btn-modal-confirmar">Aceptar</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modalFondo);

            const btnCancelar = modalFondo.querySelector('#btn-modal-cancelar');
            const btnConfirmar = modalFondo.querySelector('#btn-modal-confirmar');

            function cerrar(resultado) {
                modalFondo.remove();
                resolve(resultado);
            }

            btnCancelar.addEventListener('click', () => cerrar(false));
            btnConfirmar.addEventListener('click', () => cerrar(true));
        });
    }

    /**
     * Formatea un importe decimal al estandar monetario argentino ($ 12.345,67).
     */
    function formatearMoneda(monto) {
        const numero = parseFloat(monto) || 0;
        return '$ ' + numero.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /**
     * Formatea una fecha YYYY-MM-DD a DD/MM/AAAA.
     */
    function formatearFecha(fechaStr) {
        if (!fechaStr) return '-';
        const partes = fechaStr.split(' ')[0].split('-');
        if (partes.length !== 3) return fechaStr;
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    /**
     * Muestra un indicador de carga en un contenedor.
     */
    function mostrarCargando(contenedor, mensaje = 'Cargando datos...') {
        if (!contenedor) return;
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: var(--color-texto-suave);">
                <div style="margin-bottom: 0.75rem; font-weight: 600;">${escaparHtml(mensaje)}</div>
            </div>
        `;
    }

    return {
        icono: (nombre) => ICONOS[nombre] || '',
        escaparHtml,
        notificar,
        confirmar,
        formatearMoneda,
        formatearFecha,
        mostrarCargando
    };
})();
