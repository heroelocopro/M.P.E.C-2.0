# M.P.E.C 2.0

M.P.E.C 2.0 es una plataforma web para administrar una institución educativa desde un solo lugar. Reúne estructura académica, matrículas, docentes, estudiantes, acudientes, actividades, evaluaciones, notas, asistencia, horarios, comunicaciones y notificaciones.

La aplicación ofrece espacios separados por rol y usa Laravel con Inertia para mantener el backend y la interfaz React dentro del mismo proyecto.

## Funcionalidades

### Administración

- Gestión de usuarios y profesores.
- Configuración de años lectivos, periodos, sedes, jornadas, grados, cursos y materias.
- Visualización de los periodos del año académico activo.
- Configuración institucional de la nota mínima, máxima y aprobatoria.
- Creación individual e importación masiva de matrículas.
- Creación o vinculación del acudiente al matricular un estudiante.
- Generación de correos institucionales y envío de credenciales al correo personal.
- Consulta de estudiantes activos, egresados, información médica e historial académico.
- Asignación de profesores por curso y materia.
- Consulta consolidada de notas por año y periodo.
- Panel administrativo con estudiantes, docentes, promedio y asistencia calculados desde datos reales.
- Cierre manual de periodos como respaldo del proceso automático.
- Publicación de avisos y envío de comunicaciones.
- Registro de asistencia institucional mediante el código QR del estudiante.

### Profesores

- Consulta del horario y las asignaciones académicas.
- Creación de tareas y proyectos con archivos y fechas de vencimiento editables.
- Consulta de actividades disponibles y vencidas mediante filtros.
- Revisión de entregas y registro de notas.
- Evaluaciones con preguntas de selección múltiple y respuesta abierta.
- Calificación automática de preguntas cerradas y revisión manual de respuestas abiertas.
- Integración de las evaluaciones en el libro de calificaciones.
- Registro de asistencia por curso.
- Reportes de salud, convivencia u otras novedades de los estudiantes.
- Avisos para sus cursos y participación en el foro académico.
- Bloqueo automático de calificaciones después del cierre de cada periodo.

### Estudiantes

- Consulta de tareas y proyectos disponibles o vencidos.
- Descarga de archivos y envío de entregas.
- Presentación de evaluaciones y consulta de resultados.
- Consulta de notas por periodo, incluidas las evaluaciones.
- Descarga del reporte de notas en PDF.
- Consulta del horario y del carné con código QR.
- Avisos, recordatorios de evaluaciones y notificaciones.
- Participación en el foro académico.
- Historial de notas finales de periodos y años cerrados.
- Notificación del resultado anual, promoción o graduación.

### Acudientes

- Consulta de uno o varios estudiantes vinculados.
- Información básica y curso de cada estudiante.
- Promedio del periodo actual y promedio general de los periodos calificados del año activo.
- Totales de asistencia, tardanzas, ausencias y excusas.
- Reportes y novedades registrados por los profesores.
- Avisos institucionales y de los cursos vinculados.
- Notificaciones por ausencias, tardanzas, excusas y reportes.

## Tecnologías

- PHP 8.3 o superior y Laravel 13.
- Laravel Fortify para autenticación y recuperación de contraseña.
- Eloquent ORM y Pest 5.
- React 19, TypeScript e Inertia.js 3.
- Tailwind CSS 4, Radix UI, Lucide React y Sonner.
- Laravel Wayfinder para rutas y acciones TypeScript tipadas.
- Vite+ para desarrollo, comprobación y compilación del frontend.
- Dompdf para reportes académicos en PDF.
- SQLite como base de datos predeterminada.

## Requisitos

- PHP 8.3 o superior con las extensiones requeridas por Laravel.
- Composer 2.
- Node.js y npm.
- SQLite habilitado en PHP, o MySQL/PostgreSQL configurado en `.env`.

## Instalación local

```bash
git clone https://github.com/heroelocopro/M.P.E.C-2.0
cd M.P.E.C-2.0
composer run setup
```

`composer run setup` instala las dependencias PHP y JavaScript, crea `.env`, genera `APP_KEY`, ejecuta las migraciones y compila el frontend.

Carga la estructura escolar y los usuarios de demostración:

```bash
php artisan db:seed
```

También puedes recrear la base de datos local desde cero:

```bash
php artisan migrate:fresh --seed
```

> Este comando elimina todas las tablas existentes. Úsalo solamente en desarrollo o pruebas.

Inicia la aplicación:

```bash
composer run dev
```

La aplicación estará disponible normalmente en [http://localhost:8000](http://localhost:8000).

## Usuarios de demostración

Todas las cuentas usan la contraseña `password`.

| Rol           | Correo                 |
| ------------- | ---------------------- |
| Administrador | `admin@aula.test`      |
| Profesor      | `profesor@aula.test`   |
| Estudiante    | `estudiante@aula.test` |
| Acudiente     | `acudiente@aula.test`  |

Los datos demo incluyen estructura académica, matrículas, un estudiante vinculado con su acudiente, asignaciones docentes, horario, actividades, notas, avisos y notificaciones. Estas credenciales son únicamente para desarrollo.

## Configuración del entorno

Si no usaste `composer run setup`, copia `.env.example` como `.env` y genera la clave:

```bash
cp .env.example .env
php artisan key:generate
```

| Variable                 | Uso                                                 |
| ------------------------ | --------------------------------------------------- |
| `APP_NAME`               | Nombre mostrado por Laravel y el frontend.          |
| `APP_URL`                | URL base de la aplicación.                          |
| `APP_TIMEZONE`           | Zona horaria; por defecto `America/Bogota`.         |
| `INSTITUTION_DOMAIN`     | Dominio usado para generar correos institucionales. |
| `DB_CONNECTION`          | Motor de base de datos.                             |
| `DB_DATABASE`            | Nombre o ruta de la base de datos.                  |
| `SCHOOL_FILESYSTEM_DISK` | Disco para archivos académicos.                     |
| `MAIL_MAILER`            | Transporte usado para enviar credenciales.          |
| `QUEUE_CONNECTION`       | Controlador de colas.                               |
| `CACHE_STORE`            | Almacenamiento de caché.                            |
| `VITE_APP_NAME`          | Nombre mostrado por la interfaz.                    |

### Base de datos

La configuración inicial usa SQLite y `database/database.sqlite`. Ejemplo para MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mpec2
DB_USERNAME=root
DB_PASSWORD=
```

Después de cambiar la conexión ejecuta `php artisan migrate --seed`.

### Correo

En desarrollo, `MAIL_MAILER=log` escribe los mensajes en `storage/logs/laravel.log`. Para enviar credenciales reales a docentes y acudientes configura un servicio SMTP:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=usuario
MAIL_PASSWORD=contraseña
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Después de modificar `.env`, ejecuta `php artisan config:clear`.

## Comandos de desarrollo

```bash
# Servidor, cola, logs y frontend
composer run dev

# Compilación para producción
npm run build

# Rutas TypeScript de Wayfinder
php artisan wayfinder:generate

# Sincronizar evaluaciones existentes con el libro de notas
php artisan evaluations:sync-grades

# Procesar recordatorios y cierres académicos manualmente
php artisan academics:process-closures

# Limpiar cachés
php artisan optimize:clear
```

## Cierre automático de periodos

El proceso académico se ejecuta todos los días a las `00:05` y realiza estas acciones:

- Avisa a administradores, profesores, estudiantes y acudientes cuando faltan tres, dos y un día para el cierre.
- Consolida la nota final de cada materia y conserva el detalle de las actividades calificadas.
- Bloquea cambios en las notas y evaluaciones del periodo cerrado.
- Al cerrar el último periodo, calcula el promedio anual y determina si el estudiante aprobó.
- Completa la matrícula anual y, si corresponde a undécimo grado, registra al estudiante como egresado.
- Envía el resultado al estudiante y sus acudientes, incluyendo un reporte PDF por correo.

El administrador también puede cerrar un periodo abierto desde **Administración → Configuración académica → Periodos académicos**. El botón **Cerrar periodo** pide confirmación y ejecuta la misma consolidación transaccional del proceso automático. Esta acción no puede deshacerse desde la interfaz.

En producción debes mantener activo el programador de Laravel. Agrega una entrada como esta al cron del servidor:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Los procesos son repetibles: ejecutar el comando varias veces no duplica recordatorios, cierres ni notas finales.

## Pruebas y calidad

```bash
composer test
php artisan test --compact
npm run types:check
npm run check
composer run lint
composer run ci:check
```

Las pruebas usan SQLite en memoria, caché y sesiones en memoria, correo simulado y colas síncronas.

## Estructura principal

```text
app/
├── Actions/             Casos de uso del dominio
├── Enums/               Roles y estados
├── Http/Controllers/    Controladores por módulo y rol
├── Http/Middleware/     Protección de espacios por rol
├── Http/Requests/       Autorización y validación
├── Models/              Modelos Eloquent
├── Notifications/       Notificaciones internas
└── Services/            Cuentas institucionales y servicios auxiliares
database/
├── factories/           Datos para pruebas
├── migrations/          Esquema de la aplicación
└── seeders/             Estructura escolar y datos demo
resources/js/
├── actions/             Acciones generadas por Wayfinder
├── components/          Componentes reutilizables
├── layouts/             Diseños de autenticación y aplicación
├── pages/               Páginas Inertia por rol y módulo
└── routes/              Rutas TypeScript generadas
routes/                  Rutas web y de consola
tests/                   Pruebas Pest
```

## Rutas principales

| Área                   | Ruta                  |
| ---------------------- | --------------------- |
| Panel general          | `/dashboard`          |
| Administración         | `/admin/*`            |
| Panel del acudiente    | `/acudiente/panel`    |
| Tareas del estudiante  | `/estudiante/tareas`  |
| Notas del estudiante   | `/estudiante/notas`   |
| Horario del estudiante | `/estudiante/horario` |
| Evaluaciones           | `/evaluaciones`       |
| Foro                   | `/foro`               |
| Avisos                 | `/avisos`             |
| Notificaciones         | `/notificaciones`     |
| Espacio docente        | `/profesor/*`         |
| Perfil y seguridad     | `/settings/*`         |

## Preparación para producción

1. Configura `APP_ENV=production`, `APP_DEBUG=false` y la URL pública.
2. Usa una base de datos persistente y crea sus respaldos.
3. Configura SMTP para el envío de credenciales.
4. Configura almacenamiento persistente para archivos académicos.
5. Cambia o elimina las cuentas demo.
6. Ejecuta `php artisan migrate --force`.
7. Compila el frontend con `npm run build`.
8. Ejecuta `php artisan optimize`.
9. Mantén activo `php artisan queue:work` si usas colas asíncronas.

## Antes de publicar en GitHub

- No subas `.env`, bases de datos locales, registros ni credenciales.
- Conserva `.env.example` sin secretos y con valores de ejemplo.
- Comprueba que `storage/logs/laravel.log` no esté incluido en el commit.
- Ejecuta las pruebas y la compilación.
- Sustituye `URL_DEL_REPOSITORIO` y `NOMBRE_DEL_PROYECTO` en la instalación.

## Licencia

Este proyecto se distribuye bajo la licencia MIT.
