#!/bin/bash
echo "=========================================="
echo "  ATLAS HRM: MySQL Database Setup Script  "
echo "=========================================="
echo "This updated script uses macOS 'launchctl' to safely restart MySQL."
echo ""
echo "You will be prompted to enter your Mac (OS) password."
echo ""

echo "⏳ Stopping MySQL service..."
sudo launchctl unload -F /Library/LaunchDaemons/com.oracle.oss.mysql.mysqld.plist 2>/dev/null
sudo killall mysqld 2>/dev/null
sleep 4

echo "⏳ Restarting MySQL in safe mode..."
sudo /usr/local/mysql/bin/mysqld_safe --skip-grant-tables > /dev/null 2>&1 &
sleep 5

echo "🔐 Setting new root password to: root123"
/usr/local/mysql/bin/mysql -u root -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED BY 'root123';"

echo "⏳ Shutting down safe mode..."
sudo killall mysqld 2>/dev/null
sleep 4

echo "🚀 Starting normal MySQL service..."
sudo launchctl load -F /Library/LaunchDaemons/com.oracle.oss.mysql.mysqld.plist 2>/dev/null
sleep 4

echo "📦 Creating 'atlas_hrm' database..."
/usr/local/mysql/bin/mysql -u root -proot123 -e "CREATE DATABASE IF NOT EXISTS atlas_hrm;"
/usr/local/mysql/bin/mysql -u root -proot123 -e "CREATE USER IF NOT EXISTS 'atlas_user'@'localhost' IDENTIFIED BY 'AtlasSecret123!'; GRANT ALL PRIVILEGES ON atlas_hrm.* TO 'atlas_user'@'localhost'; FLUSH PRIVILEGES;"

if [ -f "./backend/artisan" ]; then
  echo "🧱 Running Laravel migrations..."
  (cd ./backend && php artisan migrate --force)

  echo "🌱 Seeding Laravel default users/data..."
  (cd ./backend && php artisan db:seed --force)
else
  echo "⚠️  backend/artisan not found. Skipping Laravel migrate/seed."
fi

echo ""
echo "=========================================="
echo "✅ SUCCESS! MySQL is ready for ATLAS HRM"
echo "=========================================="
echo "Your new MySQL credentials:"
echo "User: atlas_user"
echo "Pass: AtlasSecret123!"
echo "Database: atlas_hrm"
echo "=========================================="
