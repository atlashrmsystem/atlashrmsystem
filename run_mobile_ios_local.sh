#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
APP_DIR="$ROOT_DIR/mobile_app"
PORT="${PORT:-8000}"
API_LOG="${API_LOG:-/tmp/atlas_api.log}"

if [[ ! -d "$BACKEND_DIR" || ! -d "$APP_DIR" ]]; then
  echo "Run this script from the Atlas HRM SYSTEM project root."
  exit 1
fi

LISTENERS="$(lsof -nP -iTCP:${PORT} -sTCP:LISTEN 2>/dev/null || true)"
NEEDS_RESTART=0
if [[ -z "$LISTENERS" ]]; then
  NEEDS_RESTART=1
elif echo "$LISTENERS" | grep -qE "127\\.0\\.0\\.1:${PORT}|\\[::1\\]:${PORT}"; then
  # Phone cannot reach localhost-only bind.
  NEEDS_RESTART=1
fi

if [[ "$NEEDS_RESTART" -eq 1 ]]; then
  if [[ -n "$LISTENERS" ]]; then
    echo "Stopping existing API listener on localhost-only bind..."
    PIDS="$(lsof -tiTCP:${PORT} -sTCP:LISTEN 2>/dev/null || true)"
    if [[ -n "$PIDS" ]]; then
      # macOS xargs does not support -r.
      echo "$PIDS" | xargs kill
    fi
    sleep 1
  fi

  echo "Starting Laravel API on 0.0.0.0:${PORT}..."
  nohup bash -lc "
    cd \"$BACKEND_DIR\"
    exec php \
      -d error_reporting='E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED' \
      -d post_max_size=20M \
      -d upload_max_filesize=20M \
      artisan serve --host=0.0.0.0 --port=\"$PORT\"
  " >"$API_LOG" 2>&1 </dev/null &

  for _ in {1..15}; do
    if curl -s -o /dev/null -m 2 "http://127.0.0.1:${PORT}/api/login"; then
      break
    fi
    sleep 1
  done

  if ! curl -s -o /dev/null -m 2 "http://127.0.0.1:${PORT}/api/login"; then
    echo "Failed to start Laravel API on port ${PORT}."
    echo "Last server log lines (${API_LOG}):"
    tail -n 40 "$API_LOG" || true
    exit 1
  fi
fi

if [[ -n "${API_BASE_URL:-}" ]]; then
  BASE_URL="$API_BASE_URL"
else
  HOST_IP="$(ifconfig en0 2>/dev/null | awk '/inet / {print $2; exit}')"
  if [[ -z "${HOST_IP:-}" ]]; then
    HOST_IP="$(ifconfig en1 2>/dev/null | awk '/inet / {print $2; exit}')"
  fi
  if [[ -z "${HOST_IP:-}" ]]; then
    HOST_IP="$(ifconfig | awk '/inet / && $2 != "127.0.0.1" && $2 !~ /^169\\.254\\./ {print $2; exit}')"
  fi

  if [[ -z "${HOST_IP:-}" ]]; then
    echo "Could not detect host LAN IP. Set manually: API_BASE_URL=http://<mac-ip>:${PORT}/api ./run_mobile_ios_local.sh"
    exit 1
  fi

  BASE_URL="http://${HOST_IP}:${PORT}/api"
fi

echo "Using API_BASE_URL=${BASE_URL}"
echo "Tip: On iPhone, open ${BASE_URL}/login in Safari. A 405/422 response means connectivity is OK."
if ! curl -s -o /dev/null -m 2 "${BASE_URL}/login"; then
  echo "Warning: ${BASE_URL}/login is not reachable from this Mac right now."
  echo "If iPhone also fails, ensure both devices are on same Wi-Fi and macOS firewall allows incoming connections for PHP."
fi

if [[ "${SKIP_FLUTTER_RUN:-0}" == "1" ]]; then
  echo "SKIP_FLUTTER_RUN=1 set. Exiting after API checks."
  exit 0
fi

cd "$APP_DIR"
flutter run --dart-define=API_BASE_URL="$BASE_URL" "$@"
