# Samuel Memorias

Mini app web móvil (PWA) **fuera de Lynxux Cash**: álbum familiar para Samuel. Login por **cédula**, muro, favoritos, carpetas y subida de fotos/videos a **Google Drive** (una o varias cuentas configurables).

UI basada en el shell móvil del portal de clientes (gym) de Cash.

## Requisitos

- PHP 8.2+ con `pdo_mysql`
- Composer
- **MariaDB/MySQL** (producción) — SQLite solo en tests locales
- Proyecto Google Cloud con **Drive API** y OAuth (refresh token)

## Instalación local

```bash
cd samuel-memorias
composer install
cp .env.example .env   # si hace falta
php artisan key:generate
php artisan migrate
php artisan db:seed --class=SamuelSeeder
php artisan serve
```

Local con MySQL: crea la base `samuel_memorias` y copia las variables `DB_*` del `.env.example`.

## Variables `.env` importantes

```env
APP_NAME="Samuel"
APP_URL=https://samuel.lynxux.com

SAMUEL_APP_NAME=Samuel
SAMUEL_TAGLINE="Nuestro álbum de recuerdos"
SAMUEL_THEME_PRIMARY=#f472b6
SAMUEL_THEME_SECONDARY=#a78bfa
SAMUEL_ADMIN_SECRET=elige-un-secreto-largo

# Primer admin (solo seed)
SAMUEL_ADMIN_CEDULA=1234567890
SAMUEL_ADMIN_PIN=2481
SAMUEL_ADMIN_NAME="Paula"

# JSON: una o más cuentas Drive
SAMUEL_DRIVE_ACCOUNTS_JSON=[{"key":"principal","label":"Drive Samuel","owner_email":"tu@gmail.com","client_id":"","client_secret":"","refresh_token":"","root_folder_id":"","is_default":true}]
```

**No guardes la contraseña de Gmail en el repo.** Google exige OAuth: Client ID, Client Secret y **refresh token** con scope `https://www.googleapis.com/auth/drive.file`.

### Obtener refresh token (resumen)

1. [Google Cloud Console](https://console.cloud.google.com/) → proyecto → APIs → activar **Google Drive API**.
2. Credenciales → OAuth client (tipo “Aplicación web”).
3. Pantalla de consentimiento → usuario de prueba = el Gmail del álbum.
4. Usa [OAuth Playground](https://developers.google.com/oauthplayground/) o el script `php artisan samuel:google-oauth-url` (si lo añades) para generar `refresh_token`.
5. En Drive, crea carpeta raíz “Samuel Memorias” y copia el **ID de carpeta** a `root_folder_id`.

Tras editar JSON:

```bash
php artisan samuel:sync-drive-accounts
```

### Alertas push (campana + celular)

In-app: campana centrada en el header. Push Web (como portal Gym): requiere claves VAPID en el servidor.

```bash
# Una vez (local o VPS, no commitear la clave privada)
./vendor/bin/web-push generate-vapid-keys
```

Copia `Public Key` y `Private Key` a `.env`:

```env
VAPID_SUBJECT=mailto:tu@email.com
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
```

Tras desplegar: `php artisan config:cache`. En el móvil, abre la campana → **Activar alertas en este celular**. En iPhone hace falta tener la PWA en pantalla de inicio.

Al publicar un recuerdo, el resto de familiares activos reciben aviso in-app y push (si están suscritos).

## Uso

1. Entra en `/login` con cédula + clave familiar.
2. **Inicio**: favoritos y últimas fotos.
3. **Muro**: publicaciones con descripción.
4. **Subir**: foto/video → carpeta opcional → Drive (o almacenamiento local si Drive no está configurado).
5. **Carpetas**: álbumes visuales.
6. Admin (`/admin`): solo usuarios con `is_admin` — agregar cédulas y carpetas.

## Despliegue VPS (dominio tierno)

Sugerencia: **`https://samuel.lynxux.com`**

```bash
# En el servidor
git clone ... /var/www/samuel-memorias
cd /var/www/samuel-memorias
composer install --no-dev --optimize-autoloader
cp .env.example .env   # editar
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=SamuelSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Base de datos **MariaDB** en el mismo VPS (base dedicada `samuel_memorias`). Fotos/videos siguen en **R2** o Drive; MySQL guarda usuarios, publicaciones y metadatos.

Migración desde SQLite (one-shot):

```bash
php artisan migrate --force --database=mysql   # esquema vacío en MySQL
php artisan samuel:migrate-sqlite-to-mysql --sqlite=/var/www/samuel-memorias/database/database.sqlite --backup
# Tras validar, DB_CONNECTION=mysql en .env y php artisan optimize:clear
```

Backup diario recomendado:

```bash
mysqldump -u samuel_memorias -p samuel_memorias | gzip > /var/backups/samuel-memorias-$(date +%F).sql.gz
```

Nginx: ver `deploy/nginx-samuel.lynxux.com.conf` y certificado Let's Encrypt (`certbot`).

Permisos:

```bash
bash deploy/fix-permissions.sh /var/www/samuel-memorias
```

## Seguridad

- App privada: solo cédulas dadas de alta.
- Rota cualquier clave que hayas compartido por chat.
- Usa HTTPS en producción.
- Varios Drive: añade objetos al JSON con distintas `key` y elige cuenta al subir (si hay más de una).
