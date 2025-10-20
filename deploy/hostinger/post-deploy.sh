#!/usr/bin/env bash
set -euo pipefail

USER_NAME="${USER:-$(whoami)}"
APP_DIR="/home/${USER_NAME}/natstock_app/inventory-app"
LOG_DIR="${APP_DIR}/storage/logs"
TIMESTAMP="$(date +%F_%H%M)"
LOG_FILE="${LOG_DIR}/deploy_${TIMESTAMP}.log"

cd "${APP_DIR}"

if [[ ! -f composer.lock ]]; then
    echo "[ERROR] composer.lock ไม่พบใน ${APP_DIR} กรุณาตรวจสอบว่าอยู่ในโฟลเดอร์โปรเจกต์ที่ถูกต้อง" >&2
    exit 1
fi

mkdir -p "${LOG_DIR}"

echo "[INFO] เริ่ม deploy script ที่ ${TIMESTAMP}" | tee -a "${LOG_FILE}"

{
    echo "[STEP] composer install --no-dev --prefer-dist --optimize-autoloader"
    composer install --no-dev --prefer-dist --optimize-autoloader

    echo "[STEP] php artisan key:generate --force (สร้างกุญแจถ้ายังไม่มี)"
    php artisan key:generate --force

    echo "[STEP] php artisan migrate --force"
    php artisan migrate --force

    echo "[STEP] php artisan storage:link"
    php artisan storage:link || true

    echo "[STEP] php artisan config:cache"
    php artisan config:cache

    echo "[STEP] php artisan route:cache"
    php artisan route:cache

    echo "[STEP] php artisan view:cache"
    php artisan view:cache

    echo "[STEP] ตั้ง permission storage และ bootstrap/cache"
    find storage -type d -exec chmod 775 {} \;
    find storage -type f -exec chmod 664 {} \;
    chmod -R 775 bootstrap/cache

    echo "[INFO] เสร็จสิ้นการ deploy"
} | tee -a "${LOG_FILE}"

exit 0
