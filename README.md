# MiClub - Sistema de Gestion de Club de Barrio (Socios y Cuotas)

Trabajo Practico Integrador N°2 - Modelos y Sistemas II  
7° Ano - Especialidad Programacion  
Escuela de Educacion Secundaria Tecnica N°1 de Vicente Lopez ("Eduardo Ader")

---

## 1. Descripcion del Sistema

MiClub es una aplicacion web integral disenada para la gestion institucional, deportiva y financiera de un club social y deportivo chico o mediano administrado por una Comision Directiva. El sistema cubre con fidelidad los 24 casos de uso del modelo UML: 19 casos operativos al cierre de la Fase 2 y los 5 casos de administracion incorporados en la Fase 3.

El sistema administra el padron de socios con asignacion a categorias y grupos familiares, divisiones deportivas y cuerpos tecnicos, generacion mensual de cuotas sociales, control de morosidad, cobro e imputacion automatica de pagos en orden cronologico, emision de recibos oficiales correlativos, planificacion de entrenamientos con toma de asistencias, registro de apercibimientos disciplinarios y administracion centralizada de usuarios, roles, categorias y tarifas.

### Stack Tecnologico
- **Backend:** PHP 8.1+ (desarrollado y probado sobre PHP 8.2 en entorno XAMPP), arquitectura modular en 4 capas (Controladores, Servicios, Repositorios, Modelos) con enrutador central, validaciones semanticas y sin dependencias de frameworks externos.
- **Base de datos:** MariaDB 10.4+ / MySQL 8.0+, motor transaccional InnoDB, cotejamiento `utf8mb4_unicode_ci`, integridad referencial con claves foraneas y sentencias preparadas PDO contra inyecciones SQL.
- **Frontend:** SPA (Single Page Application) construida en HTML5 semantico, CSS3 modular basado en variables nativas y JavaScript puro (Vanilla JS, enrutador por hash, Fetch API), sin librerias externas, sin compiladores y sin dependencias de CDN.
- **Identidad visual:** Estetica sobria y calida de club barrial. La paleta de colores institucionales es totalmente parametrizable desde la base de datos y se aplica dinamicamente mediante variables CSS nativas y funciones `color-mix()`.

---

## 2. Requisitos de Instalacion

1. **XAMPP para Windows** con modulos de Apache 2.4+ y MariaDB 10.4+ (o MySQL 8.0+).
2. **Navegador web moderno** (Google Chrome, Mozilla Firefox, Microsoft Edge).
3. **Ubicacion obligatoria del proyecto en el servidor local:**
   `C:\xampp\htdocs\dashboard\miclub`

---

## 3. Guia de Instalacion y Puesta en Marcha Paso a Paso

### Paso 1: Iniciar Apache y MySQL en XAMPP
1. Abra el panel de control de XAMPP (**XAMPP Control Panel**).
2. Haga clic en el boton **Start** correspondiente al servicio **Apache**.
3. Haga clic en el boton **Start** correspondiente al servicio **MySQL**.
4. Compruebe que ambos modulos figuren en verde con sus puertos asignados por defecto (80 y 3306).

### Paso 2: Importar la Base de Datos en phpMyAdmin
1. Abra el navegador web e ingrese a: `http://localhost/phpmyadmin/`
2. En el menu superior, haga clic en la pestana **Importar** (o **SQL**).
3. Presione el boton para seleccionar archivo y elija:
   `C:\xampp\htdocs\dashboard\miclub\database\mi_club.sql`
4. Haga clic en el boton **Importar** (o **Continuar**) al pie de la pagina.
5. El script creara de manera automatica la base de datos `mi_club`, las 15 tablas relacionales con sus restricciones de clave primaria, unica y foranea, y sembrara el conjunto completo de datos de prueba para Octubre de 2026.

*(Alternativa por consola de comandos MySQL)*:
```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\dashboard\miclub\database\mi_club.sql
```

### Paso 3: Verificar la Configuracion del Servidor
Verifique la existencia del archivo de configuracion `config/config.php` (creado a partir de la plantilla `config/config.example.php`). Los parametros por omision para XAMPP son:
```php
'base_datos' => [
    'host' => '127.0.0.1',
    'puerto' => 3306,
    'nombre' => 'mi_club',
    'usuario' => 'root',
    'clave' => '',
    'charset' => 'utf8mb4'
],
'sesion' => [
    'tiempo_inactividad' => 1800 // Expiracion automatica tras 30 minutos de inactividad
]
```

### Paso 4: Abrir la Aplicacion Web
Abra su navegador web e ingrese a la direccion local:
**`http://localhost/dashboard/miclub/public/`**

### Paso 5: Alternativa de Ejecucion con Servidor Embebido de PHP
Si desea ejecutar la aplicacion sin iniciar el servidor Apache de XAMPP, abra una consola de comandos en la carpeta del proyecto y ejecute:
```bash
php -S localhost:8000 -t public public/router.php
```
Luego ingrese en el navegador a: `http://localhost:8000/`

---

## 4. Usuarios de Prueba Disponibles por Rol

Todas las credenciales fueron creadas con hashing `password_hash()` mediante algoritmo BCRYPT y verificadas contra la base de datos:

| Rol | Apellido y Nombre | Correo Electronico | DNI | Contrasena | Estado | Perfil en el Sistema |
|---|---|---|---|---|---|---|
| **ADMINISTRADOR** | Carlos Benitez | `admin@miclub.local` | `20111222` | `admin123` | Activo | Administrador general de usuarios, socios, divisiones, categorias y tarifas |
| **TESORERO** | Mariana Lopez | `tesoreria@miclub.local` | `25333444` | `tesorero123` | Activo | Cobranzas, emision de cuotas, recargos y padron de morosos |
| **ENTRENADOR** | Roberto Gomez | `roberto.gomez@miclub.local` | `28555666` | `entrenador123` | Activo | DT Primera Division Masculino y Juveniles |
| **ENTRENADOR** | Laura Fernandez | `laura.fernandez@miclub.local` | `30777888` | `entrenador456` | Activo | DT Voley Femenino Mayores |
| **SOCIO (Moroso)** | Marcos Moroso | `marcos.moroso@email.local` | `35123456` | `socio123` | Moroso | Posee 3 cuotas impagas (una de ellas con pago parcial) |
| **SOCIO (Familiar)** | Martin Gonzalez | `martin.gonzalez@email.local` | `38222333` | `socio123` | Activo | Titular de Grupo Familiar Gonzalez (descuento R5) |
| **SOCIO (Familiar)** | Lucas Gonzalez | `lucas.gonzalez@email.local` | `45444555` | `socio123` | Activo | Integrante de Grupo Familiar Gonzalez (descuento R5) |
| **SOCIO (Al dia)** | Sofia Martinez | `sofia.martinez@email.local` | `42111222` | `socio123` | Activo | Cuotas al dia, inscripta en Voley |
| **SOCIO (Al dia)** | Julian Alvarez | `julian.alvarez@email.local` | `46333444` | `socio123` | Activo | Cuotas al dia, inscripto en Futbol |
| **SOCIO (Sin division)** | Camila Diaz | `camila.diaz@email.local` | `41999888` | `socio123` | Activo | Cuotas al dia, habilitada para inscripcion deportiva |

---

## 5. Arquitectura en Capas y Estructura del Proyecto

```
miclub/
  .gitignore                Exclusion de logs, configuraciones locales y temporales
  config/
    config.example.php      Plantilla de configuracion versionable sin credenciales
    config.php              Configuracion activa local (ignorada por git)
  database/
    mi_club.sql             Archivo unico consolidado de esquema DDL y semillas DML
    generar_hash.php        Script CLI para generar hashes bcrypt para pruebas
  docs/
    registro_desvios.md     Registro exhaustivo de decisiones de diseno y desvios UML
  logs/
    .htaccess               Bloqueo de acceso web directo a archivos de registro
    errores.log             Registro centralizado de errores tecnicos de la aplicacion
  public/
    .htaccess               Reescritura de URLs limpias para la API REST bajo subdirectorios
    router.php              Enrutador de soporte para servidor de desarrollo php -S
    index.html              Documento raiz de la aplicacion SPA
    api/
      index.php             Controlador frontal (Front Controller) de la API REST
    assets/
      css/
        tema.css            Variables CSS del tema institucional y paleta calculada
        base.css            Estructura base, disposicion de panel lateral y tipografia
        componentes.css     Componentes visuales (tablas, tarjetas, carnet, recibo)
      js/
        api.js              Cliente HTTP Fetch con URLs relativas y token CSRF
        ui.js               Notificaciones visuales, modales de confirmacion y formato
        auth.js             Gestion del estado de sesion del usuario en el navegador
        tema.js             Carga e inyeccion reactiva de colores institucionales
        router.js           Enrutador SPA del lado del cliente por hash (#ruta)
        app.js              Inicializacion de la aplicacion y renderizado del menu lateral
        vistas/
          inicioSesion.js   Pantalla de autenticacion de usuarios
          panel.js          Tablero de control segun el rol del usuario autenticado
          usuarios.js       Gestion integral de usuarios, roles y claves (Administrador)
          socios.js         Gestion de padron social y grupos familiares (Administrador)
          categorias.js     Gestion de categorias y franjas etarias (Administrador)
          precios.js        Gestion de aranceles y parametros economicos (Administrador)
          divisiones.js     Gestion de divisiones deportivas y cupos (Administrador)
          generarCuotas.js  Emision masiva de cuotas y aplicacion de recargos (Tesorero)
          registrarPago.js  Cobranza presencial con imputacion automatica (Tesorero)
          recibo.js         Vista oficial de recibo de cobro imprimible
          asistencia.js     Planilla de asistencias a practicas (Entrenador)
          carnet.js         Carnet social digital con control de vigencia (Socio)
          estadoCuenta.js   Estado de cuenta corriente y anulacion de cuotas
          historialPagos.js Consulta de pagos efectuados y descarga de recibos
          pagarCuota.js     Pasarela simulada para pago autogestionado de cuotas
          inscripcionDivision.js Inscripcion a actividades con control de edad y cupo
          editarDatos.js    Actualizacion de datos de contacto y clave del socio
          misEntrenamientos.js Consulta de cronograma de entrenamientos del atleta
          morosos.js        Padron analitico de socios en mora
          plantel.js        Nomina de atletas por division (Entrenador)
          crearEntrenamiento.js Programacion de practicas del cuerpo tecnico
          apercibimientos.js Registro de sanciones disciplinarias deportivas
          apariencia.js     Personalizacion en vivo de los colores institucionales
  src/
    Core/                   BaseDatos (PDO Singleton), Peticion, Respuesta, Sesion, Csrf, Validador, ManejadorErrores, Router
    Excepciones/            Jerarquia de excepciones: ReglaNegocioException (409), ValidacionException (422), NoAutorizadoException (401), ProhibidoException (403), NoEncontradoException (404)
    Modelos/                Entidades de dominio (Usuario, Rol, Socio, GrupoFamiliar, Categoria, Division, Inscripcion, Entrenamiento, Asistencia, Apercibimiento, Cuota, Pago, PagoDetalle, Recibo, Configuracion)
    Repositorios/           Consultas SQL preparadas con PDO por cada entidad de persistencia
    Servicios/              Reglas de negocio R1 a R11, validaciones, calculos y transacciones
    Controladores/          Controladores REST de orquestacion entre peticiones y servicios
  README.md
```

---

## 6. Endpoints de la API REST

Todos los endpoints estan protegidos por autorizacion de roles en el Router central y validacion de token CSRF para operaciones mutantes (`POST`, `PUT`, `DELETE`):

| Metodo | Ruta de la API | Rol Minimo Requerido | Descripcion Funcional |
|---|---|---|---|
| `POST` | `/api/auth/iniciar-sesion` | Publico | Autentica usuario por email o DNI con contrasena, regenera sesion y devuelve CSRF |
| `POST` | `/api/auth/cerrar-sesion` | Todos los roles | Destruye la sesion activa en el servidor y limpia la cookie |
| `GET` | `/api/auth/sesion` | Publico | Consulta los datos del usuario autenticado en la sesion actual |
| `GET` | `/api/configuracion` | Publico | Obtiene datos institucionales y colores de la identidad visual |
| `PUT` | `/api/configuracion` | ADMINISTRADOR | Actualiza colores institucionales (#RRGGBB) y nombre del club |
| `GET` | `/api/usuarios` | ADMINISTRADOR | Nomina de usuarios del sistema con estado, rol y socio vinculado |
| `POST` | `/api/usuarios` | ADMINISTRADOR | Alta de usuario con contrasena hasheada y asignacion de rol |
| `GET` | `/api/usuarios/roles` | ADMINISTRADOR | Catalogo de roles de seguridad disponibles |
| `PUT` | `/api/usuarios/{id}` | ADMINISTRADOR | Modificacion de datos de usuario y cambio de rol asignado |
| `POST` | `/api/usuarios/{id}/activar` | ADMINISTRADOR | Habilitacion de usuario para inicio de sesion |
| `POST` | `/api/usuarios/{id}/desactivar` | ADMINISTRADOR | Bloqueo de acceso de usuario (protege auto-bloqueo del administrador) |
| `POST` | `/api/usuarios/{id}/restablecer-clave` | ADMINISTRADOR | Blanqueo forzado de clave de acceso |
| `GET` | `/api/socios` | TESORERO, ADMINISTRADOR | Busqueda y listado de socios con filtros por termino y estado |
| `POST` | `/api/socios` | ADMINISTRADOR | Alta administrativa de socio con numeracion SOC-XXXX y usuario opcional |
| `GET` | `/api/socios/morosos` | TESORERO, ADMINISTRADOR | Consulta de nomina de socios morosos con calculo de deuda total acumulada |
| `GET` | `/api/socios/{id}` | TESORERO, ADMINISTRADOR, SOCIO | Ficha completa del socio (con validacion de titularidad para SOCIO) |
| `PUT` | `/api/socios/{id}` | SOCIO, ADMINISTRADOR | Actualizacion de datos de contacto (Socio) o ficha civil/estado (Admin) |
| `GET` | `/api/socios/{id}/estado-cuenta` | TESORERO, ADMINISTRADOR, SOCIO | Estado contable con cuotas adeudadas, saldos y pagos |
| `GET` | `/api/socios/{id}/carnet` | TESORERO, ADMINISTRADOR, SOCIO | Carnet digital institucional con verificacion de vigencia (Regla R11) |
| `GET` | `/api/socios/{id}/pagos` | TESORERO, ADMINISTRADOR, SOCIO | Historial de pagos percibidos y vinculo a sus recibos |
| `GET` | `/api/socios/{id}/entrenamientos` | SOCIO, ADMINISTRADOR | Entrenamientos deportivos programados en divisiones del socio |
| `GET` | `/api/grupos-familiares` | ADMINISTRADOR | Catalogo de grupos familiares registrados |
| `POST` | `/api/grupos-familiares` | ADMINISTRADOR | Creacion de nuevo grupo familiar |
| `GET` | `/api/grupos-familiares/{id}/integrantes` | ADMINISTRADOR | Nomina de socios integrantes del grupo |
| `POST` | `/api/grupos-familiares/{id}/integrantes` | ADMINISTRADOR | Asignacion o remocion de socios dentro del grupo familiar |
| `GET` | `/api/categorias` | Todos los roles | Catalogo de categorias activas con edades y valor mensual de cuota |
| `POST` | `/api/categorias` | ADMINISTRADOR | Alta de categoria validando consistencia y no superposicion de edades |
| `PUT` | `/api/categorias/{id}` | ADMINISTRADOR | Modificacion de categoria y franja etaria |
| `DELETE` | `/api/categorias/{id}` | ADMINISTRADOR | Baja de categoria (rechazada si posee socios o divisiones asociadas) |
| `GET` | `/api/precios` | ADMINISTRADOR | Aranceles mensuales vigentes y parametros economicos globales |
| `PUT` | `/api/precios` | ADMINISTRADOR | Actualizacion de matriz de precios y parametros de recargo y mora |
| `GET` | `/api/divisiones` | Todos los roles | Catalogo de divisiones con disponibilidad de cupos y entrenador a cargo |
| `POST` | `/api/divisiones` | ADMINISTRADOR | Alta de division deportiva con cupo maximo y entrenador asignado |
| `PUT` | `/api/divisiones/{id}` | ADMINISTRADOR | Edicion de division (el cupo no puede ser menor a los inscriptos actuales) |
| `DELETE` | `/api/divisiones/{id}` | ADMINISTRADOR | Baja de division (rechazada si posee inscriptos o entrenamientos cargados) |
| `GET` | `/api/divisiones/{id}/plantel` | ENTRENADOR, ADMINISTRADOR | Nomina de atletas inscriptos activos en el plantel de una division |
| `POST` | `/api/divisiones/{id}/inscripciones` | SOCIO, ENTRENADOR, ADMINISTRADOR | Inscripcion de socio validando cupo (409) y rango etario (422) (Regla R8) |
| `DELETE` | `/api/divisiones/{id}/inscripciones/{socioId}` | ENTRENADOR, ADMINISTRADOR | Baja o desvinculacion de socio del plantel deportivo |
| `POST` | `/api/cuotas/generar` | TESORERO, ADMINISTRADOR | Emision transaccional de cuotas para el periodo AAAA-MM (Regla R6) |
| `POST` | `/api/cuotas/aplicar-recargos` | TESORERO, ADMINISTRADOR | Aplicacion de porcentaje de recargo a cuotas vencidas (Regla R4) |
| `POST` | `/api/cuotas/{id}/anular` | TESORERO, ADMINISTRADOR | Anulacion justificada de cuota impaga con recalculo de morosidad (Regla R2) |
| `POST` | `/api/pagos` | TESORERO, SOCIO, ADMINISTRADOR | Cobro e imputacion automatica en cascada a cuotas mas antiguas (Regla R7) |
| `GET` | `/api/pagos/{id}` | TESORERO, SOCIO, ADMINISTRADOR | Detalle del pago con sus amortizaciones a cuotas asociadas |
| `GET` | `/api/recibos/{id}` | TESORERO, SOCIO, ADMINISTRADOR | Comprobante oficial de recibo con numeracion correlativa R-XXXXXXXX |
| `GET` | `/api/entrenamientos` | ENTRENADOR, ADMINISTRADOR | Practicas correspondientes a las divisiones a cargo del entrenador |
| `POST` | `/api/entrenamientos` | ENTRENADOR, ADMINISTRADOR | Programacion de nueva sesion de entrenamiento deportivo |
| `GET` | `/api/entrenamientos/{id}/asistencia` | ENTRENADOR, ADMINISTRADOR | Planilla de asistencia del plantel para una sesion (Regla R9) |
| `PUT` | `/api/entrenamientos/{id}/asistencia` | ENTRENADOR | Registro o modificacion de la planilla de asistencia del plantel |
| `GET` | `/api/apercibimientos` | SOCIO, ENTRENADOR, ADMINISTRADOR | Registro historico de sanciones disciplinarias |
| `POST` | `/api/apercibimientos` | ENTRENADOR, ADMINISTRADOR | Carga de apercibimiento disciplinario sobre atleta del plantel (Regla R10) |

---

## 7. Mapa de Trazabilidad: Casos de Uso (24) -> Endpoint -> Servicio -> Pantalla

El sistema cubre con fidelidad los 24 casos de uso del modelo UML: 19 casos operativos al cierre de la Fase 2 y los 5 casos de administracion incorporados en la Fase 3.

| # | Caso de Uso (Modelo UML) | Endpoint API Principal | Servicio Involucrado | Pantalla / Vista Frontend |
|---|---|---|---|---|
| 1 | **Iniciar sesion** | `POST /api/auth/iniciar-sesion` | `AuthServicio` | `#inicio-sesion` (`inicioSesion.js`) |
| 2 | **Cerrar sesion** | `POST /api/auth/cerrar-sesion` | `AuthServicio` | Menu lateral / Global (`app.js`) |
| 3 | **Consultar carnet** | `GET /api/socios/{id}/carnet` | `SocioServicio` | `#socio-carnet` (`carnet.js`) |
| 4 | **Ver estado de cuenta** | `GET /api/socios/{id}/estado-cuenta` | `SocioServicio` / `EstadoSocioServicio` | `#socio-estado-cuenta` (`estadoCuenta.js`) |
| 5 | **Pagar cuota** | `POST /api/pagos` | `PagoServicio` | `#socio-pagar` (`pagarCuota.js`) |
| 6 | **Historial de pagos** | `GET /api/socios/{id}/pagos` | `PagoServicio` / `PagoRepositorio` | `#socio-historial` (`historialPagos.js`) |
| 7 | **Inscribirse a division** | `POST /api/divisiones/{id}/inscripciones` | `DivisionServicio` | `#socio-inscripcion` (`inscripcionDivision.js`) |
| 8 | **Ver entrenamientos** | `GET /api/socios/{id}/entrenamientos` | `SocioServicio` | `#socio-entrenamientos` (`misEntrenamientos.js`) |
| 9 | **Editar mis datos** | `PUT /api/socios/{id}` | `SocioServicio` | `#socio-mis-datos` (`editarDatos.js`) |
| 10 | **Generar cuotas** | `POST /api/cuotas/generar` | `CuotaServicio` | `#generar-cuotas` (`generarCuotas.js`) |
| 11 | **Aplicar recargos** | `POST /api/cuotas/aplicar-recargos` | `CuotaServicio` | `#generar-cuotas` / `#morosos` |
| 12 | **Registrar pago** | `POST /api/pagos` | `PagoServicio` | `#registrar-pago` (`registrarPago.js`) |
| 13 | **Emitir recibo** | `GET /api/recibos/{id}` | `PagoServicio` / `ReciboRepositorio` | `#recibo/:id` (`recibo.js`) |
| 14 | **Consultar morosos** | `GET /api/socios/morosos` | `CuotaRepositorio` / `EstadoSocioServicio` | `#morosos` (`morosos.js`) |
| 15 | **Anular cuota** | `POST /api/cuotas/{id}/anular` | `CuotaServicio` / `EstadoSocioServicio` | `#socio-estado-cuenta` (`estadoCuenta.js`) |
| 16 | **Gestionar plantel** | `GET /api/divisiones/{id}/plantel` | `DivisionServicio` | `#plantel` (`plantel.js`) |
| 17 | **Crear entrenamiento** | `POST /api/entrenamientos` | `EntrenamientoServicio` | `#crear-entrenamiento` (`crearEntrenamiento.js`) |
| 18 | **Registrar asistencia** | `PUT /api/entrenamientos/{id}/asistencia` | `AsistenciaServicio` | `#asistencia/:id` (`asistencia.js`) |
| 19 | **Cargar apercibimiento** | `POST /api/apercibimientos` | `ApercibimientoServicio` | `#apercibimientos` (`apercibimientos.js`) |
| 20 | **Gestionar usuarios** | `GET/POST/PUT /api/usuarios` | `UsuarioServicio` | `#usuarios` (`usuarios.js`) |
| 21 | **Gestionar socios** | `GET/POST/PUT /api/socios` y `/api/grupos-familiares` | `SocioServicio` | `#socios` (`socios.js`) |
| 22 | **Gestionar divisiones** | `GET/POST/PUT/DELETE /api/divisiones` | `DivisionServicio` | `#divisiones` (`divisiones.js`) |
| 23 | **Gestionar categorias** | `GET/POST/PUT/DELETE /api/categorias` | `CategoriaServicio` | `#categorias` (`categorias.js`) |
| 24 | **Gestionar precios** | `GET/PUT /api/precios` | `PrecioServicio` | `#precios` (`precios.js`) |

*Nota sobre grupos familiares y apariencia institucional:*  
- La administracion de grupos familiares (creacion de grupo, agregacion y remocion de integrantes) se encuentra plenamente implementada dentro del caso de uso **Gestionar socios**, respetando el modelo conceptual UML donde el grupo familiar conforma una agrupacion directa del padron social.
- La pantalla de **Apariencia del club** (`#apariencia`, `/api/configuracion`) constituye un modulo institucional complementario de parametrizacion visual anticipado en la Fase 2 a solicitud del usuario.

---

## 8. Limitaciones y Mejoras Futuras

El sistema MiClub implementa con precision y robustez el alcance academico del TPI N°2 centrado en socios, cobranzas, divisiones deportivas y control administrativo. Para una eventual puesta en produccion real a gran escala, se identifican las siguientes areas de expansion funcional:

1. **Finanzas Generales y Contabilidad Integral:**
   - Registro de asientos de ingresos extraordinarios y egresos operativos (alquiler de canchas, concesion del buffet, mantenimiento edilicio, compra de materiales deportivos).
   - Administracion de proveedores y liquidacion de honorarios docentes o sueldos del personal.
   - Informes contables consolidados de balance mensual, arqueo de caja chica y libro mayor.

2. **Sponsors y Patrocinios Publicitarios:**
   - Modulo de contratacion de patrocinadores para carteleria perimetral, publicidad estatica y banners de la sede.
   - Administracion de convenios comerciales, sponsors oficiales en indumentaria deportiva y facturacion recurrente a empresas aliadas.

3. **Marketing Institucional y Comunicacion:**
   - Servidor de difusion con envio automatizado de notificaciones por correo electronico y WhatsApp ante vencimiento de cuotas, suspensiones o novedades de cartelera.
   - Gestion de campanas de fidelizacion y captacion de nuevos socios en temporadas de verano / pretemporada.

4. **Gestion de Partidos y Torneos Deportivos:**
   - Planificacion de fixtures competitivos, calendario de partidos de local y visitante.
   - Carga de planillas arbitrales con resultados de partidos, tablas de posiciones y estadisticas individuales de desempeno atletico (goles, tantos, tarjetas).

5. **Pasarela de Pago Real Integrada:**
   - Sustitucion de la pasarela simulada por integraciones oficiales con APIs de cobro (MercadoPago Checkout Pro/API, MODO, Cuenta DNI o Stripe).
   - Configuracion de webhooks de notificacion asincronica para la conciliacion y acreditacion bancaria automatica en tiempo real con emision de factura electronica AFIP/ARCA.

6. **Subida Multimedia de Escudo Institucional y Fotos de Perfil:**
   - Soporte para carga de archivos binarios (PNG/JPG) con validacion estricta MIME, sanitizacion y almacenamiento en disco con hash unico.
   - Fotografia de perfil en el carnet digital del socio para control de ingreso por molinete o lectura de codigo QR/barras.
   - Personalizacion del escudo grafico del club desplegado en el encabezado, informes PDF y recibos oficiales.
