#!/usr/bin/env bash
set -euo pipefail

# reset_vpl.sh
# - Elimina contenedor 'vpl-service' y las imágenes relacionadas en podman
# - NO elimina .tar en disco
# - Ejecutar como el mismo usuario rootless que usa Podman (ej: plad)

PODMAN="$(command -v podman || true)"
if [[ -z "$PODMAN" ]]; then
  echo "❌ podman no encontrado en PATH."
  exit 1
fi

# patrones para detectar imágenes relacionadas con VPL
PATTERNS=( "vpl" "vpl-jail" "vpl_jail" "jcrodriguezvpl" "jail-debian" "jail-ubuntu" "jail-alpine" "localhost/vpl-jail-system" )

echo "==> Reset VPL (contenedor + imágenes relacionadas) - $(date)"

# =========================================================================
# --- SECCIÓN AJUSTADA (PASOS 1 Y 2) ---
# Lógica mejorada para encontrar *todos* los contenedores con 'vpl-service'
# en el nombre, no solo una coincidencia exacta.
# =========================================================================

# 1 & 2) Buscar, obtener imagen, detener y eliminar TODOS los contenedores que coincidan con 'vpl-service'
CONTAINERS_TO_DELETE=$("$PODMAN" ps -a --format '{{.Names}}' | grep 'vpl-service' || true)
IMG_FROM_CONTAINER=""

if [[ -n "$CONTAINERS_TO_DELETE" ]]; then
  echo "-> Contenedor(es) 'vpl-service' encontrado(s):"
  # Imprime los contenedores que se encontraron
  echo "$CONTAINERS_TO_DELETE" | sed 's/^/   - /'
  
  # Obtener imagen del *primer* contenedor para la limpieza de imágenes
  FIRST_CONTAINER=$(echo "$CONTAINERS_TO_DELETE" | head -n1)
  IMG_FROM_CONTAINER="$("$PODMAN" inspect --format '{{.Image}}' "$FIRST_CONTAINER" 2>/dev/null || true)"
  echo "-> Imagen base (desde $FIRST_CONTAINER): ${IMG_FROM_CONTAINER:-<no-data>}"

  # Detener todos los contenedores encontrados
  echo "-> Deteniendo contenedores..."
  echo "$CONTAINERS_TO_DELETE" | xargs -r "$PODMAN" stop >/dev/null 2>&1 || true
  sleep 1
  
  # Eliminar todos los contenedores encontrados
  echo "-> Eliminando contenedores..."
  echo "$CONTAINERS_TO_DELETE" | xargs -r "$PODMAN" rm -f >/dev/null 2>&1 || true
  echo "   ...contenedores eliminados."
else
  echo "-> Contenedor 'vpl-service' NO existe."
fi

# =========================================================================
# --- FIN DE LA SECCIÓN AJUSTADA ---
# El resto del script (pasos 3, 4, 5) funcionará ahora que 
# los contenedores han sido eliminados correctamente.
# =========================================================================

# 3) Construir lista de imágenes candidatas:
declare -A CANDIDATES=()

# 3.a añadir la imagen usada por el contenedor (si la obtuvimos)
if [[ -n "${IMG_FROM_CONTAINER:-}" ]]; then
  id_norm="${IMG_FROM_CONTAINER#sha256:}"
  CANDIDATES["$IMG_FROM_CONTAINER"]=1
  CANDIDATES["$id_norm"]=1
fi

# 3.b buscar imágenes por patrones en la lista de imágenes locales
while IFS= read -r line; do
  repo_tag="$(printf "%s" "$line" | awk '{print $1}')"
  img_id="$(printf "%s" "$line" | awk '{print $2}')"
  for pat in "${PATTERNS[@]}"; do
    if [[ "$repo_tag" == *"$pat"* ]] || [[ "$img_id" == *"$pat"* ]]; then
      CANDIDATES["$repo_tag"]=1
      CANDIDATES["$img_id"]=1
    fi
  done
done < <("$PODMAN" images --format '{{.Repository}}:{{.Tag}} {{.ID}}' || true)

# 4) Revisar y eliminar imágenes candidatas solo si NO están en uso
if [[ ${#CANDIDATES[@]} -eq 0 ]]; then
  echo "-> No se encontraron imágenes candidatas para eliminar."
else
  echo "-> Imágenes candidatas detectadas (comprobando uso por contenedores):"
  for key in "${!CANDIDATES[@]}"; do
    [[ -z "$key" ]] && continue

    matches=$("$PODMAN" images --format '{{.Repository}}:{{.Tag}} {{.ID}}' | awk -v k="$key" 'index($0,k){print $1 " " $2}' || true)
    if [[ -z "$matches" ]]; then
      matches=$("$PODMAN" images --format '{{.Repository}}:{{.Tag}} {{.ID}}' | grep -i "$key" || true)
    fi

    if [[ -z "$matches" ]]; then
      echo "   - $key  (no encontrada en 'podman images', se omite)"
      continue
    fi

    # para cada match, comprobar si está en uso por algún contenedor
    while IFS= read -r mline; do
      repo_tag="$(printf "%s" "$mline" | awk '{print $1}')"
      img_id="$(printf "%s" "$mline" | awk '{print $2}')"
      in_use=false

      # revisar lista de imágenes usadas por contenedores (coincidencia por ID o repo:tag)
      if "$PODMAN" ps -a --format '{{.Image}}' | grep -qF "$img_id"; then
        in_use=true
      elif "$PODMAN" ps -a --format '{{.Image}}' | grep -qF "$repo_tag"; then
        in_use=true
      fi

      if $in_use; then
        echo "   - OMITIENDO ${repo_tag} (${img_id}) → en uso por contenedores"
      else
        echo "   - Eliminando ${repo_tag} (${img_id}) ..."
        "$PODMAN" rmi -f "${img_id}" >/dev/null 2>&1 || {
          echo "     ⚠️ fallo al eliminar ${img_id} (quizá ya fue borrada)."
        }
      fi
    done <<< "$matches"
  done
fi

# 5) Opcional: eliminar imágenes dangling (si las hubiera)
dangling_ids=$("$PODMAN" images -q --filter "dangling=true" || true)
if [[ -n "${dangling_ids:-}" ]]; then
  echo "-> Eliminando imágenes huérfanas (dangling)..."
  echo "$dangling_ids" | xargs -r -n1 "$PODMAN" rmi -f >/dev/null 2>&1 || true
fi

echo "==> Operación finalizada."
exit 0