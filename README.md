# NovaTeam

Plataforma PHP + MySQL para el piloto de **lógica matemática e inglés** en cuarto de primaria. La landing original quedó como inicio; los datos viven en MySQL y se administran con **phpMyAdmin**.

## Requisitos

- XAMPP, WAMP o Laragon (Apache + PHP 8.1+ + MySQL/MariaDB)
- phpMyAdmin (viene con XAMPP)

## Instalación

1. Copia esta carpeta a `C:\xampp\htdocs\novateam` (o el `www` de tu stack).
2. Abre **http://localhost/phpmyadmin**
3. En la página de inicio de phpMyAdmin (sin entrar a otra base), pestaña **Importar** → elige `database/novateam.sql` → **Continuar**. El script crea `novateam_db`.
4. Copia `.env.example` a `.env` si no existe. En XAMPP suele bastar:

```
DB_HOST=127.0.0.1
DB_NAME=novateam_db
DB_USER=root
DB_PASS=
```

Si MySQL tiene contraseña, ponla en `DB_PASS`.

5. Entra a **http://localhost/novateam/**

## Cuentas de prueba

| Correo | Rol |
|---|---|
| admin@novateam.edu.co | Administrador |
| jenniffer.yepes@colegio.edu.co | Profesor (matemáticas) |
| carlos.malave@colegio.edu.co | Profesor (inglés) |
| ana.torres@colegio.edu.co | Estudiante 4° |
| luis.rodriguez@colegio.edu.co | Estudiante 4° |

Las contraseñas de la semilla no se documentan por seguridad. Para probar una cuenta: en el login usa **«Recuperar contraseña»** (requiere SMTP/Brevo en `.env`) o resétala directo en MySQL con `password_hash()`.

## Qué incluye

- Registro de estudiantes e inicio de sesión
- Guías con ejercicios de opción múltiple
- Puntos, ranking e insignias
- Panel de profesor (crear, publicar, ver avance)
- Panel de administrador (usuarios, altas de profesores, solicitudes de instituciones)
- Formulario de contacto en la landing

## Seguridad aplicada

- Contraseñas con `password_hash` (nunca texto plano)
- Consultas con PDO y sentencias preparadas
- Tokens CSRF en formularios
- Sesiones `HttpOnly` + `SameSite`
- Límite de intentos de login
- Cabeceras anti-clickjacking y `nosniff`
- Respuestas correctas ocultas hasta enviar el intento
- Roles: estudiante / profesor / administrador
- `.env` fuera del control de versiones

## Equipo

NovaTeam · SENA Análisis y Desarrollo de Software · Ficha 3235781 · Cúcuta.
