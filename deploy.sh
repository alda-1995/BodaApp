#!/usr/bin/env bash
#
# Despliegue de Tamira a producción (tamira.app).
#
# Se corre desde tu máquina, en Git Bash, estando en la raíz del proyecto:
#
#     ./deploy.sh                 despliegue normal
#     ./deploy.sh --imagenes      además sube las imágenes de las plantillas
#     ./deploy.sh --preparar      prepara el servidor (una sola vez)
#     ./deploy.sh --volver        deshace el último despliegue
#
# CÓMO FUNCIONA
#
# El árbol nuevo se arma aparte y al final se intercambia de un golpe. Eso da
# tres cosas que la versión anterior no tenía:
#
#   · un árbol limpio   — lo que borraste del repo desaparece del servidor, en
#                         vez de quedarse ahí acumulándose
#   · vuelta atrás      — el árbol anterior se conserva; '--volver' lo restaura
#   · un solo apagón    — el cambio es un 'mv', no una subida de minutos
#
# El .env y storage/ viven fuera del árbol, en 'compartido/', y cada despliegue
# los enlaza. Así las fotos de los organizadores y las claves no viajan nunca ni
# se pierden al intercambiar.
#
# El detalle del montaje está en docs/produccion.md.
#
set -euo pipefail

readonly SERVIDOR="tamira"
readonly RAIZ="/home/tamira"
readonly APP="$RAIZ/app_tamira"
readonly ANTERIOR="$RAIZ/app_tamira.anterior"
readonly NUEVO="$RAIZ/app_tamira.nuevo"
readonly COMPARTIDO="$RAIZ/compartido"
readonly WEB="$RAIZ/public_html"
readonly SITIO="https://tamira.app"

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
paso()  { printf '\n\033[1m▸ %s\033[0m\n' "$*"; }

subir_imagenes=0
modo="desplegar"

for argumento in "$@"; do
    case "$argumento" in
        --imagenes) subir_imagenes=1 ;;
        --preparar) modo="preparar" ;;
        --volver)   modo="volver" ;;
        *) echo "Uso: ./deploy.sh [--imagenes] | --preparar | --volver"; exit 1 ;;
    esac
done

# -----------------------------------------------------------------------------
#  Preparar el servidor (una sola vez)
# -----------------------------------------------------------------------------
#
#  Saca el .env y storage/ del árbol de la aplicación y los deja en
#  'compartido/', con enlaces desde el árbol actual. A partir de ahí cada
#  despliegue puede tirar el árbol entero sin llevarse por delante las fotos de
#  los organizadores ni las claves del servidor.
#
if [[ "$modo" == "preparar" ]]; then
    paso "Preparando el servidor"

    ssh "$SERVIDOR" bash -s <<PREPARAR
set -euo pipefail
mkdir -p "$COMPARTIDO"

# El .env: se mueve una vez y se deja un enlace en su sitio.
if [[ -f "$APP/.env" && ! -L "$APP/.env" ]]; then
    mv "$APP/.env" "$COMPARTIDO/.env"
    ln -sfn "$COMPARTIDO/.env" "$APP/.env"
    echo "  .env movido a compartido/"
else
    echo "  .env ya estaba compartido"
fi
chmod 600 "$COMPARTIDO/.env"

# storage/: aquí viven las fotos que suben los organizadores. Se mueve entero.
if [[ -d "$APP/storage" && ! -L "$APP/storage" ]]; then
    rm -rf "$COMPARTIDO/storage"
    mv "$APP/storage" "$COMPARTIDO/storage"
    ln -sfn "$COMPARTIDO/storage" "$APP/storage"
    echo "  storage/ movido a compartido/"
else
    echo "  storage/ ya estaba compartido"
fi
chmod -R 775 "$COMPARTIDO/storage"

# public/ apunta a la raíz web: es lo que hace que public_path() resuelva bien.
ln -sfn "$WEB" "$APP/public"

echo
echo "  compartido/:"
ls -la "$COMPARTIDO" | tail -n +4 | awk '{print "    ", \$1, \$9, \$10, \$11}'
echo "  enlaces en el árbol:"
ls -la "$APP/.env" "$APP/storage" "$APP/public" | awk '{print "    ", \$9, \$10, \$11}'
PREPARAR

    verde ""
    verde "Servidor preparado. Ya puedes desplegar con ./deploy.sh"
    exit 0
fi

# -----------------------------------------------------------------------------
#  Volver atrás
# -----------------------------------------------------------------------------

if [[ "$modo" == "volver" ]]; then
    paso "Deshaciendo el último despliegue"

    read -rp "Esto restaura el árbol anterior en PRODUCCIÓN. ¿Seguro? (escribe: volver) " ok
    [[ "$ok" == "volver" ]] || { echo "Cancelado."; exit 1; }

    ssh "$SERVIDOR" bash -s <<VOLVER
set -euo pipefail
[[ -d "$ANTERIOR" ]] || { echo "No hay árbol anterior que restaurar."; exit 1; }

php "$APP/artisan" down --retry=60 || true

rm -rf "$NUEVO"
mv "$APP" "$NUEVO"
mv "$ANTERIOR" "$APP"
rm -rf "$NUEVO"

cd "$APP"
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
VOLVER

    verde ""
    verde "Restaurado. El sitio responde $(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$SITIO")"
    exit 0
fi

# -----------------------------------------------------------------------------
#  Red de seguridad
# -----------------------------------------------------------------------------

mantenimiento_puesto=0
paquete=""

al_salir() {
    local salida=$?

    [[ -n "$paquete" ]] && rm -f "$paquete" "${paquete%.gz}"

    if [[ $salida -ne 0 ]]; then
        rojo ""
        rojo "El despliegue falló (código $salida)."

        if [[ $mantenimiento_puesto -eq 1 ]]; then
            rojo "Levantando el sitio para no dejarlo caído..."
            ssh "$SERVIDOR" "php $APP/artisan up" 2>/dev/null \
                || rojo "NO SE PUDO. Entra y corre:  ssh $SERVIDOR 'php $APP/artisan up'"
        fi

        #
        # Sólo tiene sentido ofrecer la vuelta atrás si el intercambio llegó a
        # hacerse. Si falló antes, el árbol que está sirviendo es el de siempre
        # y '--volver' restauraría uno más viejo sin ninguna razón.
        #
        if [[ $mantenimiento_puesto -eq 1 ]]; then
            rojo ""
            rojo "Para deshacer el intercambio:  ./deploy.sh --volver"
        else
            rojo ""
            rojo "Falló antes de tocar el servidor: sigue sirviendo el árbol de siempre."
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
# servidor, Laravel pide el CSS y el JS a la máquina de quien desplegó y el
# sitio se queda sin estilos para todo el mundo.
#
if [[ -f public/hot ]]; then
    rojo "public/hot existe: el servidor de Vite está corriendo."
    rojo "Páralo (Ctrl+C donde corre 'npm run dev') y vuelve a intentar."
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    rojo "Hay cambios sin confirmar en git:"
    git status --short
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
ssh -o ConnectTimeout=15 "$SERVIDOR" "test -L $APP/.env && test -L $APP/storage" 2>/dev/null \
    || { rojo "El servidor no está preparado. Corre primero:  ./deploy.sh --preparar"; exit 1; }
verde "  conexión correcta y estado compartido en su sitio"

# -----------------------------------------------------------------------------
#  Confirmación
# -----------------------------------------------------------------------------

commit="$(git log -1 --format='%h %s')"

cat <<FIN

  ─────────────────────────────────────────────────
   Destino : $SITIO
   Commit  : $commit
   Imágenes: $([[ $subir_imagenes -eq 1 ]] && echo "sí (80 MB)" || echo "no (--imagenes para incluirlas)")
  ─────────────────────────────────────────────────

FIN

read -rp "Esto toca PRODUCCIÓN. ¿Continuar? (escribe: desplegar) " confirmacion
[[ "$confirmacion" == "desplegar" ]] || { echo "Cancelado."; exit 1; }

# -----------------------------------------------------------------------------
#  1. Compilar
# -----------------------------------------------------------------------------

paso "Compilando el front"
npm run build
[[ -f public/build/manifest.json ]] || { rojo "No se generó public/build/manifest.json."; exit 1; }
verde "  build listo"

# -----------------------------------------------------------------------------
#  2. Un solo paquete con todo
# -----------------------------------------------------------------------------
#
#  Se manda en un envío y no en dos: cada conexión SSH extra es una ocasión de
#  que el cortafuegos corte a media faena, que es justo lo que pasó la primera
#  vez que se usó este script.
#
paso "Armando el paquete"

excluir_imagenes=(--exclude=./images)
[[ $subir_imagenes -eq 1 ]] && excluir_imagenes=()

#
# Se arma sin comprimir y se comprime al final: 'tar r' —añadir a un archivo ya
# creado— no funciona sobre uno comprimido, y aquí hacen falta dos pasadas
# porque el código y los assets salen de carpetas distintas y van a destinos
# distintos.
#
# Fuera del paquete:
#   storage/ .env   viven en compartido/, no viajan nunca
#   vendor/         se instala allá
#   index.php
#   .htaccess       los del servidor están editados a mano; los del repo lo tumban
#
plano="$(mktemp -t tamira-XXXXXX.tar)"
paquete="$plano.gz"

tar cf "$plano" \
    --transform='s,^\./,codigo/,' \
    --exclude=.git --exclude=node_modules --exclude=vendor \
    --exclude=storage --exclude=public --exclude=tests \
    --exclude='.env*' --exclude='*.log' \
    --exclude=.phpunit.cache --exclude=.phpunit.result.cache \
    ./

tar rf "$plano" \
    --transform='s,^\./,publico/,' \
    --exclude=index.php --exclude=.htaccess --exclude=hot --exclude=storage \
    "${excluir_imagenes[@]}" \
    -C public ./

gzip -1 -f "$plano"

verde "  paquete listo ($(du -h "$paquete" | cut -f1))"

# -----------------------------------------------------------------------------
#  3. Una sola sesión SSH: subir, armar el árbol nuevo, intercambiar
# -----------------------------------------------------------------------------

paso "Desplegando"
mantenimiento_puesto=1

#
# Dos envíos y no uno: el paquete viaja por la entrada estándar de un comando
# simple, y el script por la de 'bash -s'. No se pueden mezclar —si se intenta,
# el heredoc gana y el servidor guarda el texto del script creyendo que es el
# tar—, así que van en dos conexiones. Dos, no siete: eso era lo que disparaba
# el cortafuegos.
#
remoto="/tmp/tamira-despliegue.tar.gz"

cat "$paquete" | ssh -o ServerAliveInterval=30 "$SERVIDOR" "cat > $remoto"
verde "  paquete recibido"

ssh -o ServerAliveInterval=30 "$SERVIDOR" bash -s <<DESPLIEGUE
set -euo pipefail

paquete="$remoto"
[[ -s "\$paquete" ]] || { echo "El paquete no llegó o llegó vacío."; exit 1; }
echo "  recibido: \$(du -h "\$paquete" | cut -f1)"

# --- El árbol nuevo, desde cero -------------------------------------------
#
# '--strip-components=1' deja el contenido de codigo/ directamente en el árbol,
# sin tener que mover nada después.
#
rm -rf "$NUEVO"
mkdir -p "$NUEVO"
tar xzf "\$paquete" -C "$NUEVO" --strip-components=1 codigo

# Los assets sí se superponen sobre la raíz web: ahí conviven con index.php y
# .htaccess, que son del servidor y no del repositorio.
temporal=\$(mktemp -d)
tar xzf "\$paquete" -C "\$temporal" --strip-components=1 publico
cp -a "\$temporal/." "$WEB/"
rm -rf "\$temporal" "\$paquete"

# --- Lo compartido, enlazado ----------------------------------------------
ln -sfn "$COMPARTIDO/.env"    "$NUEVO/.env"
ln -sfn "$COMPARTIDO/storage" "$NUEVO/storage"
ln -sfn "$WEB"                "$NUEVO/public"

# vendor/ del árbol vivo, para que composer sólo instale lo que cambió.
[[ -d "$APP/vendor" ]] && cp -a "$APP/vendor" "$NUEVO/vendor"

cd "$NUEVO"
echo "  composer..."
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3

# --- El cambio: aquí empieza el apagón, y dura un 'mv' ---------------------
php "$APP/artisan" down --retry=60 2>/dev/null || true

rm -rf "$ANTERIOR"
mv "$APP" "$ANTERIOR"
mv "$NUEVO" "$APP"

cd "$APP"
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up

echo "  árbol intercambiado"
DESPLIEGUE

mantenimiento_puesto=0
verde "  desplegado"

# -----------------------------------------------------------------------------
#  4. Comprobar
# -----------------------------------------------------------------------------

paso "Comprobando"

codigo="$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$SITIO")"
if [[ "$codigo" != "200" ]]; then
    rojo "  $SITIO responde $codigo."
    rojo "  Para deshacer:  ./deploy.sh --volver"
    rojo "  Para mirar:     ssh $SERVIDOR 'tail -40 $COMPARTIDO/storage/logs/laravel.log'"
    exit 1
fi
verde "  el sitio responde 200"

env_publico="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$SITIO/.env")"
if [[ "$env_publico" == "200" ]]; then
    rojo "  ¡ALERTA! $SITIO/.env se descarga desde internet."
    exit 1
fi
verde "  el .env no se lee desde fuera ($env_publico)"

paso "Despliegue terminado"
echo "  $commit"
echo "  $SITIO"
echo
echo "  Si algo sale mal en los próximos minutos:  ./deploy.sh --volver"
