#!/usr/bin/env bash
#
# Replace this environment's database with a production dump.
#
#   ./scripts/import_production.sh ~/production.sql
#   ./scripts/import_production.sh ~/production.sql.gz
#
# Backs up first, then drops and recreates the schema, because phpMyAdmin
# exports carry no DROP TABLE statements and would otherwise collide with the
# existing tables. Migrations run afterwards to layer on the schema changes
# production does not have; they are idempotent, so this is safe to repeat.
#
# DESTRUCTIVE. Requires typing the database name to continue.

set -euo pipefail

DUMP="${1:-}"
if [[ -z "$DUMP" || ! -f "$DUMP" ]]; then
    echo "usage: $0 <dump.sql|dump.sql.gz>" >&2
    exit 1
fi

cd "$(dirname "$0")/.."

DBNAME=$(sudo grep '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'"'')
DBUSER=$(sudo grep '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'"'')
DBPASS=$(sudo grep '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'"'')
export MYSQL_PWD="$DBPASS"

echo "database : $DBNAME (user $DBUSER)"
echo "dump     : $DUMP  ($(du -h "$DUMP" | cut -f1))"
echo

# ---- 1. back up what is there now -----------------------------------------
BACKUP=~/staging-backup-$(date +%F-%H%M).sql.gz
echo "==> backing up current database to $BACKUP"
mysqldump --no-tablespaces --single-transaction -u "$DBUSER" "$DBNAME" | gzip > "$BACKUP"
if ! zcat "$BACKUP" | tail -3 | grep -q "Dump completed"; then
    echo "backup did not complete — stopping before anything destructive" >&2
    exit 1
fi
echo "    ok ($(du -h "$BACKUP" | cut -f1))"
echo

# ---- 2. confirm ------------------------------------------------------------
echo "This will DROP and recreate '$DBNAME'."
read -r -p "Type the database name to continue: " CONFIRM
[[ "$CONFIRM" == "$DBNAME" ]] || { echo "aborted"; exit 1; }
echo

# ---- 3. recreate and import ------------------------------------------------
echo "==> recreating schema"
mysql -u "$DBUSER" -e "DROP DATABASE \`$DBNAME\`; CREATE DATABASE \`$DBNAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> importing (this takes a while on a large dump)"
if [[ "$DUMP" == *.gz ]]; then
    zcat "$DUMP" | mysql -u "$DBUSER" "$DBNAME"
else
    mysql -u "$DBUSER" "$DBNAME" < "$DUMP"
fi
echo "    imported"
echo

# ---- 4. bring the schema forward -------------------------------------------
echo "==> running migrations"
sudo php artisan migrate --force
echo

# ---- 5. re-apply settings the dump overwrites ------------------------------
# Production still carries the expired EZEE keys, so the sync would start
# failing again without this.
echo "==> restoring EZEE auth keys"
mysql -u "$DBUSER" "$DBNAME" <<'SQL'
UPDATE ezee_groups SET auth_key='3308876215ba11e9f2-9d27-11f1-8' WHERE hotel_code=19676;
UPDATE ezee_groups SET auth_key='0221335459bd2ee912-9ba0-11f1-8' WHERE hotel_code=20317;
UPDATE ezee_groups SET auth_key='9108361293bf05f7ed-9225-11f1-8' WHERE hotel_code=20318;
UPDATE ezee_groups SET auth_key='4298327690bf012e3e-9225-11f1-8' WHERE hotel_code=20319;
UPDATE ezee_groups SET auth_key='3170193604beffec5b-9225-11f1-8' WHERE hotel_code=20320;
SQL
echo

# ---- 6. clear caches -------------------------------------------------------
echo "==> clearing caches"
sudo php artisan view:clear
sudo php artisan config:clear
sudo php artisan cache:clear
sudo systemctl reload php8.2-fpm
echo

# ---- 7. report -------------------------------------------------------------
echo "==> verification"
mysql -u "$DBUSER" "$DBNAME" -t < scripts/verify_import.sql

echo
echo "done. rollback if needed:"
echo "  zcat $BACKUP | MYSQL_PWD=\"\$DBPASS\" mysql -u $DBUSER $DBNAME"
