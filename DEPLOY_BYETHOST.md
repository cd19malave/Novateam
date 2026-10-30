# Publicar NovaTeam en ByetHost

Guía para dejar el sitio y la base de datos funcionando en tu cuenta de ByetHost.
No subas `.env`, `.git` ni `Dockerfile`/`entrypoint.sh` (son solo para Railway).

## 0. Camino rápido (recomendado)

El proyecto ya trae un script que hace todo por FTP:

1. Copia `tools/byethost.config.example.json` a `.byethost.local.json` (en la raíz del proyecto).
2. Rellena ahí tus datos (URL, FTP, MySQL). Ese archivo está en `.gitignore`, no se publica.
3. Simula la subida para ver la lista de archivos:

   ```powershell
   powershell -ExecutionPolicy Bypass -File tools\deploy-byethost.ps1 -DryRun
   ```

4. Sube de verdad:

   ```powershell
   powershell -ExecutionPolicy Bypass -File tools\deploy-byethost.ps1
   ```

El script actualiza `robots.txt`/`sitemap.xml` con tu dominio real, genera el `.env` de producción, sube todo a `htdocs` y omite `.env` local, `.git`, `tools`, `_dev_login.php` y las fotos de prueba.

Lo único manual es **importar la base** desde phpMyAdmin (paso 2).

## 1. Datos que necesito de tu cuenta

Copia los valores **exactos** desde tu vPanel (`https://dash.byet.host`):

| Dato | Dónde verlo |
|---|---|
| URL del sitio | vPanel, tarjeta del dominio o `http://tu-cuenta.byethostX.com` |
| Host FTP | vPanel → FTP Accounts |
| Usuario FTP | vPanel → FTP Accounts |
| Contraseña FTP | vPanel → FTP Accounts |
| Host MySQL | vPanel → MySQL Databases |
| Nombre de la base | vPanel → MySQL Databases (ej. `b12_34567890_novateam`) |
| Usuario MySQL | vPanel → MySQL Databases |
| Contraseña MySQL | vPanel → MySQL Databases |
| Versión de PHP | vPanel → Select PHP Version (debe ser 8.1 o superior) |

Recomendación de seguridad: crea un **usuario FTP temporal** y un **usuario MySQL solo para NovaTeam**. Cuando terminemos, puedes cambiar ambas contraseñas.

## 2. Crear la base de datos

1. Entra a vPanel → **MySQL Databases**.
2. Crea una base llamada `novateam` (ByetHost le añadirá su prefijo).
3. Crea un usuario y asígnalo a esa base con **todos los privilegios**.
4. Anota host, base, usuario y contraseña.
5. Abre **phpMyAdmin**, selecciona esa base (no la del servidor) y entra a **Importar**.
6. Sube `database/novateam_byethost.sql` y pulsa **Continuar**.
   - Ese archivo ya viene sin `CREATE DATABASE` ni `USE`, porque la base la crea ByetHost.
   - Si phpMyAdmin muestra error por el `DROP VIEW`, ejecuta primero `DROP VIEW IF EXISTS vw_leaderboard;` y vuelve a importar.

## 3. Subir los archivos

Con el script del paso 0 no hay que hacer nada más. Si prefieres hacerlo a mano, sube el contenido del proyecto a la carpeta **`htdocs`** por FTP (FileZilla o similar):

```
htdocs/
  index.php
  login.php
  ...
  assets/
  api/
  includes/
  uploads/        <- debe existir y tener permisos de escritura
  database/
  robots.txt
  sitemap.xml
  .htaccess
```

No subas: `.git/`, `.env` local, `tools/`, `Dockerfile`, `entrypoint.sh`, `_dev_login.php`, ni los `.zip` de trabajo.

Permisos recomendados por FTP:

- Carpetas: `755`
- Archivos: `644`
- `uploads/` y sus subcarpetas: `755` (si la subida de fotos falla, prueba `775`)

## 4. Crear el `.env` de producción

Crea un archivo llamado `.env` en `htdocs/` con este contenido (sin comillas):

```env
DB_HOST=sqlXXX.byethostX.com
DB_NAME=bXX_XXXXXXXX_novateam
DB_USER=bXX_XXXXXXXX
DB_PASS=la-contraseña-de-esa-base
DB_CHARSET=utf8mb4
DB_PORT=3306

APP_NAME=NovaTeam
APP_ENV=production
SESSION_NAME=NOVATEAMSESS

# Correo: ByetHost suele bloquear SMTP saliente; usa Brevo por HTTPS.
BREVO_API_KEY=tu-api-key-de-brevo
SMTP_FROM=novateamrecuperacion@gmail.com
SMTP_FROM_NAME=NovaTeam
CONTACTO_EMAIL=novateamrecuperacion@gmail.com
```

Si no vas a usar recuperación de contraseña por correo, deja `BREVO_API_KEY` vacío; el resto de la app funciona igual.

## 5. Activar HTTPS

1. vPanel → **Free SSL** / **SSL Certificates** → emite el certificado para tu dominio.
2. Cuando esté activo, comprueba que `https://tu-dominio/` carga sin aviso.
3. Si quieres forzar HTTPS, agrega al final de `.htaccess`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTPS} !=on
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

No lo actives antes de que el certificado esté emitido.

## 6. Verificación después de subir

- `https://tu-dominio/` → landing con el estilo de cuaderno.
- `https://tu-dominio/login.php` → inicia sesión con una cuenta de prueba.
- `https://tu-dominio/estudiante.php` → panel del estudiante.
- `https://tu-dominio/robots.txt` y `https://tu-dominio/sitemap.xml` → responden 200.
- Sube una foto de perfil para confirmar que `uploads/` escribe.
- Prueba el conmutador claro/oscuro y el quiz en el móvil.

## 7. Google

1. Actualiza `robots.txt` y `sitemap.xml` con el dominio final (te lo dejo hecho cuando me pases la URL).
2. Entra a [Google Search Console](https://search.google.com/search-console) con la cuenta de Google del proyecto.
3. Agrega la propiedad con **Prefijo de URL** (`https://tu-dominio/`).
4. Verifica con el archivo HTML que te da Google (súbelo a `htdocs/`) o con un registro DNS TXT.
5. En **Sitemaps**, envía `https://tu-dominio/sitemap.xml`.
6. En **Inspección de URL**, pega la home y pulsa **Solicitar indexación**.

La indexación no es inmediata: Google suele tardar entre unos días y varias semanas. Search Console es lo que permite pedirla y ver errores.

## 8. Correos y secretos

- No compartas el `.env` por chat ni lo subas a GitHub.
- Si me pasas las claves de Brevo/SMTP, las uso solo para escribir el `.env` por FTP y luego puedes rotarlas.
- Al terminar, cambia la contraseña del FTP temporal y del usuario MySQL.
