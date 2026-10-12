# Producción — tamira.app

VPS con cPanel (`vps2.canalla.agency`). Esta guía está escrita contra lo que hay
realmente en el servidor, revisado el 12 de octubre de 2026.

## Cómo está montado

```
/home/tamira/app_tamira/      ← el proyecto Laravel (app, config, .env, storage)
/home/tamira/public_html/     ← la raíz web: index.php, .htaccess, build/, images/
```

El código vive **fuera** de la raíz web, que es lo correcto: desde internet
`/.env` y `/.git/config` responden 403.

**`public_html/index.php` está editado a mano** para apuntar a `../app_tamira`
en vez de a `__DIR__.'/../'`. Es lo que une las dos carpetas.

> **Nunca sobrescribas `public_html/index.php` con el del repositorio.** El del
> repo apunta al directorio de al lado y el sitio deja de cargar. Es la forma
> más fácil de tirar producción en un despliegue.
>
> La alternativa limpia, si algún día quieres quitarte el parche de encima, es
> cambiar el *document root* del dominio a `/home/tamira/app_tamira/public`
> desde WHM. Entonces `public_html` sobra y el `index.php` del repo sirve tal
> cual.

## Acceso

```bash
ssh tamira
```

Ya configurado en `~/.ssh/config` (usuario `tamira`, puerto **22022**, llave
`~/.ssh/tamira_vps`). La huella del servidor es:

```
SHA256:/tmx2DbeiHIbe9ywxxjD4EDnuV3Cz/2FXywESLI/LZI
```

Si algún día te la vuelve a preguntar y no coincide, **no aceptes**.

El shell de la cuenta es *jailed*: alcanza para `php`, `composer` y `crontab`.

> Cuidado con los intentos fallidos: cPHulk bloquea la IP y el síntoma es
> `Connection closed by ... port 22022` **antes** de pedir contraseña. Si pasa,
> hay que esperar o liberar la IP en WHM → *cPHulk* → *Whitelist Management*.

---

## Estado al 12 de octubre de 2026

Lo que está bien:

- `APP_ENV=production`, `APP_DEBUG=OFF`
- Laravel 10.48.29, PHP 8.2.34, Composer 2.10.1
- El `.env` no se lee desde internet
- Vistas cacheadas

Lo que falta, y por qué importa:

| pendiente | consecuencia |
|---|---|
| `QUEUE_CONNECTION=sync` | el correo de «no terminaste tu registro» sale al instante en vez de a las 3 h; los avisos a invitados se mandan dentro de la petición del organizador |
| no hay ningún cron | `events:deactivate-expired` nunca se ha ejecutado: ninguna invitación vence sola |
| no existe el enlace `storage` | las fotos que suben los organizadores dan 404 |
| `LOG_CHANNEL=single` | un archivo que crece sin tope hasta llenar el disco |
| `APP_URL` con barra final | URLs con `//` |
| Config y Rutas sin cachear | se reinterpreta la configuración en cada petición |
| permisos `777`/`666` en la raíz web | cualquier proceso puede reescribir `.htaccess` e `index.php` |
| `public_html/build` del 16 de febrero | los visitantes ven estilos de hace ocho meses |

---

## 1. Permisos (una vez)

```bash
chmod 644 ~/public_html/.htaccess ~/public_html/index.php
chmod 755 ~/public_html/build ~/public_html/images ~/public_html/js
chmod 600 ~/app_tamira/.env
chmod -R 775 ~/app_tamira/storage ~/app_tamira/bootstrap/cache
```

Conviene también sacar de la raíz web lo que no tiene que servirse:

```bash
mkdir -p ~/respaldos && mv ~/public_html/resp.zip ~/respaldos/
```

## 2. El enlace de `storage` (una vez)

Las fotos que suben los organizadores se guardan en
`app_tamira/storage/app/public` y se sirven desde `/storage/...`.

`php artisan storage:link` **no sirve aquí**: crearía el enlace en
`app_tamira/public/storage`, que no es la raíz web. Hay que hacerlo a mano:

```bash
ln -s /home/tamira/app_tamira/storage/app/public /home/tamira/public_html/storage
ls -la ~/public_html/storage      # debe aparecer como enlace
```

## 3. Colas y cron (una vez, y van juntos)

> El cambio de `sync` a `database` y el worker se ponen **en el mismo momento**.
> Cambiar la conexión sin un worker deja los trabajos encolados sin que nadie
> los atienda, que es peor que como está hoy.

**Antes de cambiar nada, comprobar que la tabla existe en la base de ese
entorno.** Que la migración esté en el repositorio no quiere decir que se haya
corrido allá: con la conexión en `database` y sin tabla, **todos** los trabajos
revientan, y eso es peor que `sync`.

```bash
cd /home/tamira/app_tamira
php artisan tinker --execute='echo Schema::hasTable("jobs") ? "sí" : "NO";'
```

Si dice `NO`: o se despliega el código con su migración y se corre
`php artisan migrate --force`, o se sube una base que ya la traiga. Hasta
entonces, la cola se queda en `sync`.

Con la tabla confirmada, en `app_tamira/.env`:

```
QUEUE_CONNECTION=database
```

### Editar el `.env` sin romperlo

El `.env` del servidor **no termina en salto de línea**. Un `echo ... >> .env`
pega la variable al final de la última y deja el archivo inválido: el sitio
responde 500 en el acto.

```bash
cp .env .env.bak-$(date +%Y%m%d-%H%M%S)      # siempre, antes de tocarlo

sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env   # si la línea existe
printf '\nQUEUE_CONNECTION=database\n' >> .env                    # si hay que añadirla

php artisan config:clear
php artisan tinker --execute='echo config("app.env");'            # comprobar que parsea
curl -s -o /dev/null -w '%{http_code}\n' https://tamira.app       # y que el sitio vive
```

Y los dos crons, en cPanel → *Trabajos cron*, cada minuto:

```cron
* * * * * cd /home/tamira/app_tamira && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/tamira/app_tamira && php artisan queue:work --stop-when-empty --tries=3 --max-time=55 >> /dev/null 2>&1
```

El primero hace que las invitaciones venzan solas. El segundo atiende la cola:
entra cada minuto, vacía lo pendiente y se sale.

Si en algún momento tienes root, Supervisor es mejor que el cron del worker
porque lo levanta solo si se cae:

```ini
[program:tamira-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/tamira/app_tamira/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/home/tamira/app_tamira
autostart=true
autorestart=true
user=tamira
numprocs=1
redirect_stderr=true
stdout_logfile=/home/tamira/app_tamira/storage/logs/worker.log
stopwaitsecs=3600
```

## 4. Stripe y Twilio

Webhook de Stripe, dado de alta en su panel:

```
https://tamira.app/stripe/webhook
```

De ahí sale `STRIPE_WEBHOOK_SECRET`. **Sin ese secreto las órdenes no se
completan**: el cobro pasa y la invitación no se crea.

Webhook de Twilio, en la consola de Twilio:

```
https://tamira.app/api/twilio/whatsapp-webhook
```

Sin él los mensajes salen, pero no se registra si llegaron o fallaron.

---

## Cada despliegue

Desde tu máquina, en Git Bash y en la raíz del proyecto:

```bash
./deploy.sh
```

Compila el front, sube el código a `app_tamira` y los assets a `public_html`,
deja el servidor listo y comprueba que el sitio sigue en pie. Pide confirmación
escrita antes de tocar nada.

Si añadiste o cambiaste imágenes de alguna plantilla:

```bash
./deploy.sh --imagenes
```

Por omisión no viajan: son 78 de los 80 MB de la raíz web y cambian muy de vez
en cuando, así que un despliegue normal mueve 1,2 MB en vez de 80.

### De qué te protege el script

| comprobación | por qué |
|---|---|
| se niega si existe `public/hot` | ese archivo apunta los assets al Vite de tu máquina; en producción deja el sitio sin estilos **para todo el mundo** |
| se niega si hay cambios sin confirmar en git | si hay que volver atrás, no habría a dónde |
| avisa si no estás en `main` | |
| no sube `index.php` ni `.htaccess` | los del servidor están personalizados; los del repo tumban el sitio |
| no sube `storage/` ni `.env` | ahí viven las fotos de los organizadores y las claves del servidor |
| levanta el sitio si algo falla a media faena | lo peor es dejarlo en mantenimiento sin querer |
| comprueba al final que responde 200 y que `/.env` no se lee | |

### A mano, si alguna vez hace falta

```bash
cd /home/tamira/app_tamira
php artisan down
composer install --no-dev --optimize-autoloader
php artisan config:clear && php artisan config:cache
php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

> `queue:restart` no es opcional una vez que haya worker: tiene el código viejo
> cargado en memoria y seguiría ejecutándolo hasta que alguien lo reinicie.

Si alguna vez hay que correr migraciones: `php artisan migrate --force`, y
**con respaldo de la base tomado antes**.

---

## Comprobar que quedó bien

```bash
# El .env no se lee desde fuera (403 o 404).
curl -s -o /dev/null -w "%{http_code}\n" https://tamira.app/.env

# La portada responde.
curl -s -o /dev/null -w "%{http_code}\n" https://tamira.app

# La cola ya no está en sync, y el resto de la configuración.
cd /home/tamira/app_tamira && php artisan about

# El programador está registrado.
php artisan schedule:list

# La cola se atiende: se encola algo y debe volver a cero.
php artisan tinker --execute="dispatch(function () { logger('cola viva'); });"
php artisan queue:monitor database:default
php artisan queue:failed

# El enlace de storage sirve una foto real (sustituye por una que exista).
curl -s -o /dev/null -w "%{http_code}\n" https://tamira.app/storage/...
```

---

## Respaldos

**GitHub guarda el código, no la base de datos.** Los eventos, invitados,
confirmaciones y órdenes viven en MySQL y de eso el repositorio no tiene nada.

Antes de que entren datos reales conviene encender un respaldo diario que
conserve siete días:

```cron
0 3 * * * mysqldump -u USUARIO -p'CONTRASENA' BASE | gzip > /home/tamira/respaldos/tamira-$(date +\%F).sql.gz && find /home/tamira/respaldos -name 'tamira-*.sql.gz' -mtime +7 -delete
```

Un respaldo que nunca se ha restaurado no es un respaldo: conviene bajar uno y
probar que importa bien antes de confiar en él.
