# Registro de Desvios y Decisiones de Diseno - MiClub

Este documento registra los acuerdos, precisiones y desvios tecnicos tomados respecto del modelo conceptual UML y el documento de requisitos inicial para el sistema MiClub (TPI N°2 - Modelos y Sistemas II).

---

## Tabla Resumen de Decisiones y Atribucion de Origen

| # | Fecha | Elemento / Asunto | Descripcion Sintetica | Origen de la Decision |
|---|---|---|---|---|
| 1 | 2026-10-18 | Entorno y Directorio | Ubicacion en `C:\xampp\htdocs\dashboard\miclub` y URL `/dashboard/miclub/public/` | Decision explicita del usuario |
| 2 | 2026-10-18 | Modelo de Roles | Un rol por usuario referenciando tabla `roles` | Propuesta del asistente aceptada por el usuario |
| 3 | 2026-10-18 | Relacion Socio-Usuario | `usuario_id` nullable y UNIQUE en `socios` para menores sin usuario web | Propuesta del asistente aceptada por el usuario |
| 4 | 2026-10-18 | Entidad Categoria | Unifica rango de edades y precio mensual en `categorias` | Propuesta del asistente aceptada por el usuario |
| 5 | 2026-10-18 | Precios vs Categorias | Gestion de precios preserva cuotas historicas emitidas | Propuesta del asistente aceptada por el usuario |
| 6 | 2026-10-18 | Periodo y Vencimiento | Periodo `AAAA-MM` y dia de vencimiento parametrizable | Propuesta del asistente aceptada por el usuario |
| 7 | 2026-10-18 | Regla R5 Descuento Familiar | Socios ACTIVO y MOROSO computan para el descuento; SUSPENDIDO y BAJA no | Propuesta del asistente aceptada por el usuario |
| 8 | 2026-10-18 | Estado SUSPENDIDO | Socio suspendido puede loguearse para pagar pero no inscribirse ni carnet vigente | Propuesta del asistente aceptada por el usuario |
| 9 | 2026-10-18 | Regla R7 Imputacion | Imputacion cronologica ascendente a cuotas con saldo | Propuesta del asistente aceptada por el usuario |
| 10 | 2026-10-18 | Regla R2 Cuotas Vencidas | Actualizacion automatica del estado VENCIDA en caliente | Propuesta del asistente aceptada por el usuario |
| 11 | 2026-10-18 | Emision de Recibos | Recibos correlativos obligatorios `R-XXXXXXXX` | Propuesta del asistente aceptada por el usuario |
| 12 | 2026-10-18 | Regla R8 Gestion Plantel | Entrenador restringido exclusivamente a sus divisiones asignadas | Prompt original |
| 13 | 2026-10-18 | Validacion de Edad | Edad cumplida a la fecha de la inscripcion segun nacimiento | Propuesta del asistente aceptada por el usuario |
| 14 | 2026-10-18 | Caso Editar mis datos | Socio solo edita email, telefono, domicilio y contrasena | Propuesta del asistente aceptada por el usuario |
| 15 | 2026-10-18 | Entrenamientos Fase 1 | Uso de entrenamientos precargados para verificar asistencias en Fase 1 | Propuesta del asistente aceptada por el usuario |
| 16 | 2026-10-18 | Archivo mi_club.sql | Archivo unico consolidado para esquema y datos semilla | Propuesta del asistente aceptada por el usuario |
| 17 | 2026-10-19 | Pasarela Simulada | Pago autogestionado con seleccion de medio y validacion de deuda total | Prompt de Fase 2 |
| 18 | 2026-10-19 | Anulacion de Cuotas | Motivo obligatorio, solo impagas y recalculo inmediato de morosidad | Prompt de Fase 2 |
| 19 | 2026-10-19 | Apariencia del Club | Adelanto de configuracion de identidad visual a la Fase 2 | Decision explicita del usuario |
| 20 | 2026-10-19 | Dominio de Entrenador | Verificacion estricta de asignacion en entrenamientos y apercibimientos | Prompt de Fase 2 |
| 21 | 2026-10-19 | Error HTTP Fuera de Rango | Inscripcion fuera de rango etario devuelve HTTP 422 (Validacion) | Decision explicita del usuario |
| 22 | 2026-10-19 | Delimitacion Casos de Uso | Total exacto de 24 casos (19 en Fase 2, 5 en Fase 3; grupos familiares dentro de socios) | Decision explicita del usuario |
| 23 | 2026-10-19 | Sesion por Inactividad | Expiracion configurable en `config.php`, respuesta HTTP 401 `SESION_EXPIRADA` | Prompt de Fase 3 |

---

## Detalle Exhaustivo de Decisiones y Desvios

### Decision 1: Ubicacion del Proyecto y Entorno de Ejecucion
- **Fecha:** 2026-10-18
- **Origen:** Decision explicita del usuario (en respuesta a consulta previa de la Fase 1).
- **Modelo original:** El requerimiento original mencionaba `C:\xampp\htdocs\miclub` con acceso en `http://localhost/miclub/public/`.
- **Implementacion:** Se fija el proyecto en `C:\xampp\htdocs\dashboard\miclub` y se accede desde `http://localhost/dashboard/miclub/public/`.
- **Justificacion:** Adaptacion al directorio del espacio de trabajo del usuario. Se garantiza portabilidad total implementando calculo dinamico de la ruta base en el frontend (rutas relativas) y soporte para `php -S localhost:8000 -t public`.

### Decision 2: Asignacion de Roles por Usuario
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Mencionaba asignacion de roles a usuarios como caso de uso.
- **Implementacion:** Cada usuario tiene exactamente un rol asignado a traves de `rol_id` referenciando a la tabla `roles` (ADMINISTRADOR, TESORERO, ENTRENADOR, SOCIO).
- **Justificacion:** Mantiene estricta correspondencia con la especializacion del actor abstracto Usuario del diagrama de clases UML.

### Decision 3: Relacion entre Socio y Usuario
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Socio asociado a un Usuario.
- **Implementacion:** La columna `usuario_id` en la tabla `socios` es nullable y con restriccion UNIQUE.
- **Justificacion:** Permite que socios infantiles o menores de edad no requieran credenciales de inicio de sesion web si son administrados en el club por sus tutores, mientras que los socios con usuario web conservan acceso uno a uno.

### Decision 4: Doble Funcion de la Entidad Categoria
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** La regla R3 asocia categoria al precio de cuota, mientras que R8 asocia categoria a la edad minima y maxima de la division.
- **Implementacion:** Se unifica en la entidad `Categoria` con columnas `edad_minima`, `edad_maxima` y `precio_cuota`. La categoria es asignada al socio por administracion.
- **Justificacion:** Simplifica el esquema manteniendo consistencia entre tarifas y franjas etarias formativas.

### Decision 5: Gestion de Precios frente a Gestion de Categorias
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Casos de uso separados en Administrador: Gestionar categorias y Gestionar precios.
- **Implementacion:** "Gestionar precios" actualiza el `precio_cuota` de las categorias y los parametros economicos en la tabla `configuracion` (recargos, descuento familiar, dias de vencimiento). Las cuotas generadas previamente conservan su valor historico en sus propias columnas.
- **Justificacion:** Audibilidad financiera: cambiar un precio futuro no debe alterar retroactivamente las cuotas ya devengadas.

### Decision 6: Representacion del Periodo y Calculo de Vencimiento
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Mencionaba periodo mensual.
- **Implementacion:** El periodo se almacena como `CHAR(7)` con formato canonico `AAAA-MM`. La fecha de vencimiento se calcula con el dia configurable `dias_vencimiento` del mes respectivo (ej. dia 10 de cada mes).
- **Justificacion:** Facilita ordenamiento cronologico directo, indexacion eficiente y unicidad `UNIQUE(socio_id, periodo)`.

### Decision 7: Definicion de Integrantes Activos para Descuento Familiar (Regla R5)
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Descuento si al menos 2 integrantes del grupo familiar son activos.
- **Implementacion:** Se computan como integrantes activos aquellos socios pertenecientes al grupo cuyo estado sea `ACTIVO` o `MOROSO`. Los socios en estado `SUSPENDIDO` o `BAJA` no computan.
- **Justificacion:** Un socio moroso sigue siendo miembro del grupo familiar con obligacion de cuota devengada, a diferencia de suspendidos o dados de baja.

### Decision 8: Permisos del Socio Suspendido
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Socio en estado SUSPENDIDO.
- **Implementacion:** Puede autenticarse en el sistema para consultar su historial y abonar sus deudas, pero tiene bloqueada la inscripcion a nuevas divisiones y el carnet figura no vigente. Un socio en estado `BAJA` tiene bloqueo total de inicio de sesion.
- **Justificacion:** Favorece la regularizacion de la situacion del socio al permitirle pagar mientras restringe las actividades deportivas.

### Decision 9: Criterio de Imputacion de Pagos (Regla R7)
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Imputar a cuotas mas antiguas.
- **Implementacion:** Se imputa cronologicamente por periodo ascendente y por id a todas las cuotas que tengan saldo adeudado (`monto_final - monto_pagado > 0`) y cuyo estado no sea `ANULADA` ni `PAGADA`, incluyendo cuotas `PENDIENTES` aun no vencidas si el socio decide adelantar el pago. El monto a saldar incluye recargos aplicados.
- **Justificacion:** Claridad contable: saldar siempre las deudas acumuladas primero, permitiendo amortizaciones parciales.

### Decision 10: Actualizacion del Estado Vencida en Cuotas (Regla R2)
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** La cuota pasa a VENCIDA si supera su fecha de vencimiento y tiene saldo.
- **Implementacion:** La comprobacion y actualizacion se ejecuta de forma automatica en los siguientes momentos: al generar cuotas, al aplicar recargos, al consultar morosos, en cada operacion de pago y al consultar el estado de cuenta.
- **Justificacion:** Garantiza que los estados mostrados al usuario y evaluados por las reglas de negocio esten siempre al dia sin depender de cron jobs externos.

### Decision 11: Emision de Recibos y Numeracion Correlativa
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** "Pagar cuota" incluye "Emitir recibo".
- **Implementacion:** Cada transaccion de pago genera obligatoriamente un unico registro en la tabla `recibos` con formato `R-00000001` correlativo y fecha de emision. El caso de uso "Emitir recibo" permite consultar e imprimir el recibo generado.
- **Justificacion:** Cumple con la relacion "include" del caso de uso UML y asegura trazabilidad documental.

### Decision 12: Gestion del Plantel por el Entrenador (Regla R8)
- **Fecha:** 2026-10-18
- **Origen:** Prompt original (Regla de negocio R8).
- **Modelo original:** El entrenador solo opera sobre sus divisiones.
- **Implementacion:** Cada division tiene asignado un unico `entrenador_id`. El servicio valida que el entrenador autenticado coincida con el responsable de la division antes de registrar inscripciones o tomar asistencias.
- **Justificacion:** Cumplimiento estricto de la regla de autorizacion basada en propiedad de recursos en la capa de servicios.

### Decision 13: Calculo de la Edad para Inscripcion en Divisiones
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Rango de edad de la categoria.
- **Implementacion:** La edad se calcula como la edad cumplida en anos a la fecha exacta de la inscripcion respecto de la `fecha_nacimiento` del socio.
- **Justificacion:** Precision en el cumplimiento de los limites etarios de cada categoria formativa.

### Decision 14: Alcance de "Editar mis datos" para el Socio
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Caso de uso del socio "Editar mis datos".
- **Implementacion:** El socio puede modificar unicamente su email, telefono, domicilio y contrasena (ingresando la clave actual para verificacion). Atributos sensibles como nombre, apellido, DNI, categoria y estado son de potestad exclusiva del Administrador.
- **Justificacion:** Principio de seguridad institucional y preservacion de la identidad civil en los registros del club.

### Decision 15: Entrenamientos para Asistencia en la Fase 1
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario.
- **Modelo original:** Registro de asistencia sobre entrenamientos.
- **Implementacion:** En la Fase 1 la toma de asistencia se opera sobre los entrenamientos precargados en los datos de prueba (`entrenamientos` id 1, 2 y 3). La creacion de nuevos entrenamientos se formalizo en la Fase 2.
- **Justificacion:** Permite verificar la totalidad del circuito de asistencia en la Fase 1 respetando la division por fases acordada.

### Decision 16: Archivo Unico mi_club.sql para Esquema y Datos Semilla
- **Fecha:** 2026-10-18
- **Origen:** Propuesta del asistente aceptada por el usuario (acuerdo previo de la Fase 1).
- **Modelo original:** Concurrencia de scripts de definicion de datos e insercion de semillas en archivos separados (`schema.sql` y `seed.sql`).
- **Implementacion:** Se utilizo un unico archivo consolidado `database/mi_club.sql` que realiza la creacion de la base de datos, las 15 tablas relacionales con sus restricciones de integridad y la carga de datos de ejemplo de Octubre 2026 en una sola ejecucion directa.
- **Justificacion:** Facilita la importacion manual directa desde phpMyAdmin (pestana Importar o SQL) sin errores de dependencias de claves foraneas ni orden de ejecucion.

### Decision 17: Pasarela Simulada de Pagos Autogestionados (Regla R7)
- **Fecha:** 2026-10-19
- **Origen:** Prompt de Fase 2.
- **Modelo original:** Pago de cuotas desde el portal de socios sin pasarela real externa.
- **Implementacion:** El formulario del portal permite seleccionar medio de pago (tarjeta simulada, transferencia bancaria declarada o efectivo declarado) e ingresa un monto. Invoca el mismo `PagoServicio` utilizado por Tesoreria garantizando imputacion en cascada a las cuotas mas antiguas, generacion de recibo correlativo y asignacion de `autogestionado = 1`. Se rechaza con HTTP 409 si el monto supera la deuda total exigible.
- **Justificacion:** Cumplimiento de R7 sin dependencias externas de pasarelas propietarias ni credenciales bancarias.

### Decision 18: Anulacion de Cuotas Impagas con Motivo Obligatorio (Regla R2)
- **Fecha:** 2026-10-19
- **Origen:** Prompt de Fase 2 (Regla R2).
- **Modelo original:** Transicion de estado de cuotas y anulacion.
- **Implementacion:** Se implemento el endpoint `POST /api/cuotas/{id}/anular` exclusivo de Tesoreria y Administracion. Se valida que la cuota exista y no este PAGADA (rechazo con 409). Se exige obligatoriamente un texto justificativo del motivo. Al cambiar a ANULADA, se recalcula inmediatamente el estado moroso del socio (R1) restando la cuota del conteo de cuotas con saldo vencido.
- **Justificacion:** Proteccion contra cancelaciones indebidas y mantenimiento de la consistencia contable del padron de socios.

### Decision 19: Adelanto de la Pantalla de Apariencia del Club a la Fase 2
- **Fecha:** 2026-10-19
- **Origen:** Decision explicita del usuario (solicitud en el prompt de la Fase 2).
- **Modelo original:** Gestion de configuracion institucional pautada inicialmente en Fase 3.
- **Implementacion:** A solicitud del usuario en el prompt de la Fase 2, se implemento el endpoint `PUT /api/configuracion` y la pantalla de Apariencia en el frontend. Permite configurar nombre del club y 6 variables de color (#RRGGBB validados estrictamente por regex en backend). Ofrece vista previa en vivo en el cliente mediante CSS custom properties sin persistir y boton de restauracion a la paleta predeterminada.
- **Justificacion:** Solicitud explicita del usuario para adelantar el modulo visual y asegurar cero uso de colores estaticos fuera de `tema.css`.

### Decision 20: Control Estricto de Dominio para Entrenadores (Reglas R9 y R10)
- **Fecha:** 2026-10-19
- **Origen:** Prompt de Fase 2 (Reglas R9 y R10).
- **Modelo original:** El entrenador opera sobre sus divisiones y atletas.
- **Implementacion:** Tanto la creacion de entrenamientos (`POST /api/entrenamientos`) como la carga de apercibimientos disciplinarios (`POST /api/apercibimientos`) validan a nivel de servicio que la division o el socio pertenezcan fehacientemente a las divisiones asignadas al entrenador autenticado, respondiendo con HTTP 403 en caso contrario.
- **Justificacion:** Cumplimiento de la regla de autorizacion por propiedad de recursos en el dominio deportivo.

### Decision 21: Codigo de Respuesta HTTP para Inscripcion Fuera de Rango Etario
- **Fecha:** 2026-10-19
- **Origen:** Decision explicita del usuario (en correcciones previas a Fase 3).
- **Modelo original:** Rechazo de inscripcion por no cumplir rango etario de la categoria asignada a la division.
- **Implementacion:** La excepcion lanzada por no cumplir el rango etario (`ValidacionException`) devuelve codigo de estado HTTP 422 (Unprocessable Entity), mientras que el cupo agotado y el socio moroso continúan devolviendo HTTP 409 (Conflict).
- **Justificacion:** La edad del socio es un dato de entrada que no satisface la validacion semantica de la regla de inscripcion (422), diferenciandose de conflictos del estado del recurso o capacidad fisica de la division (409).

### Decision 22: Delimitacion y Conteo Exacto de los 24 Casos de Uso
- **Fecha:** 2026-10-19
- **Origen:** Decision explicita del usuario (en correcciones previas a Fase 3).
- **Modelo original:** Diagrama de casos de uso del diseno UML.
- **Implementacion:** Se fija el conteo riguroso en exactamente 24 casos de uso: 19 operativos finalizada la Fase 2 y 5 implementados en la Fase 3 correspondientes al Administrador:
  1. Gestionar usuarios (con asignacion de roles)
  2. Gestionar socios (que incluye integralmente la gestion de grupos familiares)
  3. Gestionar divisiones
  4. Gestionar categorias
  5. Gestionar precios
  Gestionar grupos familiares no constituye un caso de uso independiente sino que se resuelve operativamente dentro de Gestionar socios. La pantalla de Apariencia del club constituye un componente de configuracion visual institucional.
- **Justificacion:** Alineacion matematica estricta con el diagrama de casos de uso y la trazabilidad del Trabajo Practico.

### Decision 23: Control de Sesion Vencida por Inactividad
- **Fecha:** 2026-10-19
- **Origen:** Prompt de Fase 3.
- **Modelo original:** Control de sesion autenticada en el Router.
- **Implementacion:** Se incorpora el parametro `tiempo_inactividad` (1800 segundos / 30 minutos) en `config/config.php`. El Router compara la marca de tiempo de `ultimo_acceso` en cada peticion contra el tiempo limite; si expira, destruye la sesion y arroja `NoAutorizadoException` con codigo de error `SESION_EXPIRADA` (HTTP 401). El cliente frontend (`api.js`) captura esta respuesta, limpia el almacenamiento local y redirige a `#inicio-sesion` informando al usuario que la sesion ha expirado por inactividad.
- **Justificacion:** Garantiza seguridad en terminales compartidas cumpliendo con las mejores practicas de gestion de sesiones.
