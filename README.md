# Registro de activos

Aplicacion local para consultar y administrar activos de la Universidad San Juan de la Cruz.

## Estructura

```text
.
|-- app/
|   |-- assets/
|   |   `-- img/
|   |-- login/
|   |-- menu_admin/
|   |-- menu_usuario/
|   `-- seleccion_inicio/
|-- config/
|   |-- conexion.example.php
|   `-- usuarios.example.php
|-- tools/
|   `-- consultas.php
|-- Abrir pagina.bat
`-- README.md
```

## Requisitos

- PHP disponible desde PowerShell (`php` en el `PATH`).
- MySQL en ejecucion y la base de datos `usjc activos` creada.
- La tabla `activos` debe existir con las columnas usadas por la aplicacion.

## Iniciar la aplicacion

En Windows, haz doble clic en `Abrir pagina.bat`. Se inicia el servidor PHP local y se abre la seleccion de perfil. Desde alli, elige un perfil e inicia sesion.

No abras las paginas PHP con doble clic ni uses Live Server: esos metodos no ejecutan PHP. La aplicacion se sirve en `http://127.0.0.1:8000`.

## Base de datos

Antes de iniciar, copia las plantillas y configura la conexion y las cuentas locales:

```powershell
Copy-Item config/conexion.example.php config/conexion.php
Copy-Item config/usuarios.example.php config/usuarios.php
```

Configura las credenciales de MySQL y las cuentas en los archivos copiados. Genera el hash de la contrasena con `php -r "echo password_hash('TU_CLAVE', PASSWORD_DEFAULT), PHP_EOL;"` y pegalo en `password_hash` de `config/usuarios.php`. Los archivos `config/conexion.php` y `config/usuarios.php` se excluyen de Git para no publicar credenciales ni datos personales.

Los scripts SQL locales que contienen registros del inventario no se publican en este repositorio.

`tools/consultas.php` es una herramienta de desarrollo para consultar e imprimir los registros de la tabla.

