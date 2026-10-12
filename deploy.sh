#!/usr/bin/env bash
#
# Despliegue de Tamira a producción (tamira.app).
#
# Se corre desde tu máquina, en Git Bash, estando en la raíz del proyecto:
#
#     ./deploy.sh
#
# Compila el front, sube el código y los assets a sus dos destinos y deja el
# servidor listo. No toca la base de datos ni corre migraciones: si alguna vez
# hacen falta, se hacen a mano y con respaldo tomado antes.
#
# El detalle del montaje está en docs/produccion.md.
#
set -euo pipefail

# -----------------------------------------------------------------------------
#  Dónde va cada cosa
# -----------------------------------------------------------------------------
#
#  El proyecto vive fuera de la raíz web y 'public_html' hace de public/. Son
#  dos destinos distintos y no se pueden mezclar.
#
readonly SERVIDOR="tamira"
readonly APP="/home/tamira/app_tamira"
readonly WEB="/home/tamira/public_html"
readonly SITIO="https://tamira.app"

#
# Las imágenes de diseño de las plantillas pesan 78 de los 80 MB de la raíz web
# y cambian de higos a brevas, así que por omisión no viajan: un despliegue
# normal mueve 2 MB en vez de 80. Si tocaste o añadiste alguna:
#
#     ./deploy.sh --imagenes
#
subir_imagenes=0
for argumento in "$@"; do
    case "$argumento" in
        --imagenes) subir_imagenes=1 ;;
        *) echo "Opción desconocida: $argumento"; echo "Uso: ./deploy.sh [--imagenes]"; exit 1 ;;
    esac
done

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
paso()  { printf '\n\033[1m▸ %s\033[0m\n' "$*"; }

# -----------------------------------------------------------------------------
#  Red de seguridad
# -----------------------------------------------------------------------------
#
#  Si algo falla después de poner el sitio en mantenimiento, hay que levantarlo
#  igual: lo peor de un despliegue a medias es dejarlo caído sin querer.
#
mantenimiento_puesto=0

al_salir() {
    local salida=$?

    if [[ $salida -ne 0 ]]; then
        rojo ""
        rojo "El despliegue falló (código $salida)."

        if [[ $mantenimiento_puesto -eq 1 ]]; then
            rojo "Quitando el cartel de mantenimiento para no dejar el sitio caído..."
            ssh "$SERVIDOR" "cd $APP && php artisan up" || \
                rojo "NO SE PUDO. Entra y corre a mano:  ssh $SERVIDOR 'cd $APP && php artisan up'"
        fi
    fi
}
trap al_salir EXIT

# -----------------------------------------------------------------------------
#  Comprobaciones antes de tocar nada
# -----------------------------------------------------------------------------

paso "Revisando que se pueda desplegar"

[[ -f artisan ]] || { rojo "Esto no es la raíz del proyecto."; exit 1; }

#
# 'public/hot' lo escribe el servidor de desarrollo de Vite. Si viaja al
# servidor, Laravel pide el CSS y el JS a http://[::1]:5173 —la máquina de
# quien desplegó— y el sitio se queda sin estilos para todo el mundo. Es el
# error más caro y el más fácil de cometer, así que aquí se para en seco.
#
if [[ -f public/hot ]]; then
    rojo "public/hot existe: el servidor de Vite está corriendo."
    rojo "Párarlo (Ctrl+C donde corre 'npm run dev') y volver a intentar."
    exit 1
fi

# Desplegar con cambios sin guardar es desplegar algo que no está en ninguna
# parte: si hay que volver atrás, no hay a dónde.
if [[ -n "$(git status --porcelain)" ]]; then
    rojo "Hay cambios sin confirmar en git:"
    git status --short
    rojo ""
    rojo "Confírmalos o guárdalos antes de desplegar."
    exit 1
fi

rama="$(git branch --show-current)"
if [[ "$rama" != "main" ]]; then
    rojo "Estás en la rama '$rama', no en main."
    read -rp "¿Desplegar igual? (escribe: si) " respuesta
    [[ "$respuesta" == "si" ]] || exit 1
fi

# Un solo intento: cada fallo suma para el cortafuegos del servidor.
paso "Probando la conexión"
ssh -o ConnectTimeout=15 "$SERVIDOR" "test -d $APP && test -d $WEB && command -v tar >/dev/null" \
    || { rojo "No se pudo entrar, o faltan $APP / $WEB / tar en el servidor."; exit 1; }
verde "  conexión y rutas correctas"

# -----------------------------------------------------------------------------
#  Confirmación
# -----------------------------------------------------------------------------

commit="$(git log -1 --format='%h %s')"

cat <<FIN

  ─────────────────────────────────────────────────
   Destino : $SITIO
   Código  : $APP
   Assets  : $WEB
   Commit  : $commit
  ─────────────────────────────────────────────────

FIN

read -rp "Esto toca PRODUCCIÓN. ¿Continuar? (escribe: desplegar) " confirmacion
[[ "$confirmacion" == "desplegar" ]] || { echo "Cancelado."; exit 1; }

# -----------------------------------------------------------------------------
#  1. Compilar el front
# -----------------------------------------------------------------------------

paso "Compilando el front"
npm run build

[[ -f public/build/manifest.json ]] || { rojo "No se generó public/build/manifest.json."; exit 1; }
verde "  build listo"

# -----------------------------------------------------------------------------
#  2. Subir el código de la aplicación
# -----------------------------------------------------------------------------
#
#  Fuera de la maleta:
#    storage/   lo que subieron los organizadores y los logs viven ahí; subirlo
#               borraría sus fotos
#    .env       el del servidor manda, y cada entorno tiene el suyo
#    vendor/    se instala allá con composer, pesa menos que mandarlo
#    public/    va aparte, al otro destino
#
paso "Subiendo el código"

tar czf - \
    --exclude=.git \
    --exclude=node_modules \
    --exclude=vendor \
    --exclude=storage \
    --exclude=public \
    --exclude=tests \
    --exclude='.env*' \
    --exclude='*.log' \
    --exclude=.phpunit.cache \
    --exclude=.phpunit.result.cache \
    . | ssh "$SERVIDOR" "tar xzf - -C $APP"

verde "  código arriba"

# -----------------------------------------------------------------------------
#  3. Subir los assets a la raíz web
# -----------------------------------------------------------------------------
#
#  Fuera de la maleta, y esto es lo delicado:
#    index.php   el del servidor está editado para apuntar a ../app_tamira; el
#                del repo apunta al directorio de al lado y tumba el sitio
#    .htaccess   el del servidor está personalizado (909 bytes contra 603)
#    hot         el marcador de Vite, que ya se comprobó arriba
#    storage     es un enlace a app_tamira/storage/app/public
#
excluir_imagenes=(--exclude=./images)

if [[ $subir_imagenes -eq 1 ]]; then
    excluir_imagenes=()
    paso "Subiendo los assets, imágenes incluidas (78 MB, tarda)"
else
    paso "Subiendo los assets"
fi

tar czf - \
    --exclude=index.php \
    --exclude=.htaccess \
    --exclude=hot \
    --exclude=storage \
    "${excluir_imagenes[@]}" \
    -C public . | ssh "$SERVIDOR" "tar xzf - -C $WEB"

verde "  assets arriba"

if [[ $subir_imagenes -eq 0 ]]; then
    echo "  (las imágenes de las plantillas no viajaron; si añadiste o cambiaste"
    echo "   alguna, vuelve a desplegar con:  ./deploy.sh --imagenes)"
fi

# -----------------------------------------------------------------------------
#  4. Dejar el servidor listo
# -----------------------------------------------------------------------------

paso "Preparando el servidor"
mantenimiento_puesto=1

ssh "$SERVIDOR" "cd $APP && php artisan down --retry=60"

ssh "$SERVIDOR" "cd $APP && composer install --no-dev --optimize-autoloader --no-interaction"

#
# Cachear después de limpiar, nunca sólo cachear: si queda caché vieja de la
# configuración, el .env nuevo no se lee y los cambios no tienen efecto.
#
ssh "$SERVIDOR" "cd $APP && php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"

# El worker tiene el código viejo cargado en memoria hasta que se le avisa.
ssh "$SERVIDOR" "cd $APP && php artisan queue:restart"

ssh "$SERVIDOR" "cd $APP && php artisan up"
mantenimiento_puesto=0

verde "  servidor listo"

# -----------------------------------------------------------------------------
#  5. Comprobar que quedó en pie
# -----------------------------------------------------------------------------

paso "Comprobando"

codigo="$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$SITIO")"
if [[ "$codigo" != "200" ]]; then
    rojo "  $SITIO responde $codigo (se esperaba 200)."
    rojo "  Revisa:  ssh $SERVIDOR 'tail -40 $APP/storage/logs/laravel.log'"
    exit 1
fi
verde "  el sitio responde 200"

# El .env nunca debe poder leerse desde internet.
env_publico="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$SITIO/.env")"
if [[ "$env_publico" == "200" ]]; then
    rojo "  ¡ALERTA! $SITIO/.env se descarga desde internet."
    exit 1
fi
verde "  el .env no se lee desde fuera ($env_publico)"

paso "Despliegue terminado"
echo "  $commit"
echo "  $SITIO"
