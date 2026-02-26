#!/bin/bash
set -euo pipefail

MYSQL_BIN="${MYSQL_BIN:-/usr/local/mysql/bin/mysql}"
MYSQLD_SAFE_BIN="${MYSQLD_SAFE_BIN:-/usr/local/mysql/bin/mysqld_safe}"
MYSQL_DAEMON_PLIST="${MYSQL_DAEMON_PLIST:-/Library/LaunchDaemons/com.oracle.oss.mysql.mysqld.plist}"

ATLAS_DB_NAME="${ATLAS_DB_NAME:-atlas_hrm}"
ATLAS_DB_USER="${ATLAS_DB_USER:-atlas_user}"
ATLAS_DB_PASSWORD="${ATLAS_DB_PASSWORD:-}"
MYSQL_ROOT_PASSWORD_NEW="${MYSQL_ROOT_PASSWORD_NEW:-}"

print_header() {
  echo "=========================================="
  echo "  ATLAS HRM: MySQL Database Setup Script  "
  echo "=========================================="
  echo "This script avoids hardcoded credentials."
  echo "You will be prompted for secrets if env vars are not set."
  echo ""
}

prompt_secret() {
  local var_name="$1"
  local prompt_text="$2"
  local current_value="${!var_name:-}"
  if [ -n "$current_value" ]; then
    return
  fi

  if [ ! -t 0 ]; then
    echo "Missing required secret env var: $var_name" >&2
    exit 1
  fi

  local value
  read -r -s -p "$prompt_text" value
  echo ""
  printf -v "$var_name" '%s' "$value"
}

validate_identifier() {
  local value="$1"
  local label="$2"
  if [[ ! "$value" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "Invalid $label '$value'. Use letters, numbers, or underscore only." >&2
    exit 1
  fi
}

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

generate_password() {
  LC_ALL=C tr -dc 'A-Za-z0-9@#%+=._-' < /dev/urandom | head -c 24
}

print_header

prompt_secret MYSQL_ROOT_PASSWORD_NEW "Enter NEW MySQL root password: "
if [ -z "$ATLAS_DB_PASSWORD" ]; then
  if [ -t 0 ]; then
    read -r -s -p "Enter password for MySQL user '$ATLAS_DB_USER' (leave blank to auto-generate): " ATLAS_DB_PASSWORD
    echo ""
  fi
  if [ -z "$ATLAS_DB_PASSWORD" ]; then
    ATLAS_DB_PASSWORD="$(generate_password)"
    echo "Generated a strong password for MySQL user '$ATLAS_DB_USER'."
  fi
fi

validate_identifier "$ATLAS_DB_NAME" "database name"
validate_identifier "$ATLAS_DB_USER" "database user"

ROOT_PASSWORD_ESCAPED="$(sql_escape "$MYSQL_ROOT_PASSWORD_NEW")"
DB_PASSWORD_ESCAPED="$(sql_escape "$ATLAS_DB_PASSWORD")"

echo "Stopping MySQL service..."
sudo launchctl unload -F "$MYSQL_DAEMON_PLIST" 2>/dev/null || true
sudo killall mysqld 2>/dev/null || true
sleep 4

echo "Restarting MySQL in safe mode..."
sudo "$MYSQLD_SAFE_BIN" --skip-grant-tables > /dev/null 2>&1 &
sleep 5

echo "Setting new root password..."
"$MYSQL_BIN" -u root -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED BY '$ROOT_PASSWORD_ESCAPED';"

echo "Shutting down safe mode..."
sudo killall mysqld 2>/dev/null || true
sleep 4

echo "Starting normal MySQL service..."
sudo launchctl load -F "$MYSQL_DAEMON_PLIST" 2>/dev/null
sleep 4

echo "Creating database and app user..."
"$MYSQL_BIN" -u root "-p$MYSQL_ROOT_PASSWORD_NEW" -e "CREATE DATABASE IF NOT EXISTS \`$ATLAS_DB_NAME\`;"
"$MYSQL_BIN" -u root "-p$MYSQL_ROOT_PASSWORD_NEW" -e "CREATE USER IF NOT EXISTS '$ATLAS_DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD_ESCAPED'; GRANT ALL PRIVILEGES ON \`$ATLAS_DB_NAME\`.* TO '$ATLAS_DB_USER'@'localhost'; FLUSH PRIVILEGES;"

if [ -f "./backend/artisan" ]; then
  echo "Running Laravel migrations..."
  (cd ./backend && php artisan migrate --force)

  echo "Seeding Laravel default users/data..."
  (cd ./backend && php artisan db:seed --force)
else
  echo "backend/artisan not found. Skipping Laravel migrate/seed."
fi

echo ""
echo "=========================================="
echo "SUCCESS: MySQL is ready for ATLAS HRM"
echo "=========================================="
echo "Database: $ATLAS_DB_NAME"
echo "User: $ATLAS_DB_USER"
echo "Password: [hidden]"
echo "=========================================="
