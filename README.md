# SISPAM - Sistema de Gestión Farmacéutica, Transcripción, Entrega y Turnero TV

Sistema completo desarrollado en **Laravel 13**, **Bootstrap 5.3**, **MySQL** y **JavaScript ES6**, para el ingreso de pacientes, escaneo y categorización de documentos, transcripción de fórmulas con control de concurrencia, alistamiento semaforizado, entrega con firma digital en tableta táctil y pantallas de turnero TV con llamado por voz sintetizada en español.

Migrado en 2026 desde una versión en PHP nativo puro (sin framework). El código legacy sigue disponible en el historial de git (commit `Snapshot legacy pre-migración a Laravel` y siguientes) si hace falta comparar algo.

---

## 🚀 Instalación local (Laragon / XAMPP)

1. **Clonar/copiar** el proyecto dentro de tu carpeta de vhosts (ej. `C:\laragon\www\sispam`).
2. **Composer**: `composer install`.
3. **`.env`**: copiar `.env.example` a `.env` y ajustar `DB_*` con las credenciales de tu MySQL local. Luego `php artisan key:generate`.
4. **Base de datos**: apuntar `DB_DATABASE` a una base ya existente con los datos reales (la migración de este proyecto asume que el schema y los datos ya existen — ver sección siguiente). Si es una base nueva y vacía, correr `php artisan migrate` para crear el schema desde cero a partir de `database/schema/baseline.sql`.
5. **DocumentRoot / vhost**: el punto de entrada es `public/`, no la raíz del proyecto. En Laragon, el `DocumentRoot` del vhost debe apuntar a `.../sispam/public`.
6. Abrir `http://sispam.test/` (o el dominio configurado).

---

## ☁️ Despliegue en VPS (Hostinger u otro)

Este proyecto se despliega subiendo archivos manualmente (FTP/SSH), no hay CI/CD. Pasos:

1. **Subir el código** a una carpeta fuera de `public_html` si es posible (ej. `~/sispam`), y apuntar el DocumentRoot del dominio/subdominio a `~/sispam/public`. Si el hosting solo permite servir desde `public_html`, hay que ajustar el `DocumentRoot` en el panel de control para que apunte al subdirectorio `public/` del proyecto — **no** copiar el contenido de `public/` directamente a `public_html`, porque el resto de la app (`app/`, `.env`, etc.) necesita quedar fuera del árbol servido públicamente.
2. **Composer** en el servidor: `composer install --no-dev --optimize-autoloader` (si el VPS no tiene Composer, subir la carpeta `vendor/` ya instalada localmente).
3. **`.env` en el servidor**: crear uno propio con las credenciales reales de producción (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`). Nunca subir el `.env` de otro entorno. `php artisan key:generate` si es la primera vez.
4. **Base de datos**: si el servidor ya tiene los datos reales (caso típico al migrar desde la versión legacy), **no correr `migrate` a ciegas** — la migration `database/migrations/2026_09_15_000000_baseline_schema_sispam.php` solo crea tablas que no existan, pero conviene marcarla como aplicada manualmente (insertando la fila en la tabla `migrations`) igual que se hizo en desarrollo, para evitar sorpresas. Si es una base nueva vacía, `php artisan migrate` sí crea todo el schema desde cero.
5. **Uploads existentes**: la carpeta `public/assets/uploads/pacientes/` debe subirse con los archivos reales ya existentes (cédulas, órdenes médicas, firmas, fotos) conservando la misma estructura de carpetas — las rutas están guardadas como texto en la base de datos y deben coincidir exactamente.
6. **Permisos**: `storage/`, `bootstrap/cache/` y `public/assets/uploads/` necesitan permisos de escritura para el usuario del servidor web.
   ```bash
   chmod -R 775 storage bootstrap/cache public/assets/uploads
   ```
7. **Cachés de producción** (opcional pero recomendado): `php artisan config:cache && php artisan route:cache && php artisan view:cache`. Si luego cambiás el `.env` o las rutas, hay que correr `php artisan config:clear`/`route:clear` para que se refresquen.

---

## 🔑 Credenciales de Prueba por Defecto

| Rol / Perfil | Usuario | Contraseña | Funciones |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin` | `admin123` | Control total, parámetros de empresa y usuarios. |
| **Orientador** | `orientador` | `admin123` | Admisión de pacientes, subida de documentos y tiquetes. |
| **Transcripción** | `transcriptor` | `admin123` | Verificación de stock, visor PDF dual y estado FEFO. |
| **Alistamiento** | `alistador` | `admin123` | Picking semaforizado y asignación a ventanillas. |
| **Entrega** | `entregador` | `admin123` | Factura, acta de entrega y captura de firma táctil. |

*(Nota de seguridad: la versión legacy tenía un atajo de contraseña que dejaba entrar a estos 5 usuarios con `admin123`/`password` incluso si la contraseña real era otra. Ese atajo se eliminó en la migración a Laravel — ahora la autenticación es siempre por hash bcrypt real.)*

---

## 📺 Pantallas de Turnero TV

- **Turnero 1 (En Proceso)**: `https://tu-dominio/turnero1`
- **Turnero 2 (Listo para Entrega con Voz)**: `https://tu-dominio/turnero2`

Ambas son pantallas públicas (sin login), pensadas para quedar abiertas en una TV/monitor de sala de espera.
