#!/bin/bash
# Import database.sql into MySQL on first deploy.
# Skips import if the database already contains tables.

set -euo pipefail

# ── Connection variables (provided by Railway's MySQL service) ──────────────
DB_HOST="${MYSQLHOST:?MYSQLHOST is not set}"
DB_PORT="${MYSQLPORT:-3306}"
DB_USER="${MYSQLUSER:?MYSQLUSER is not set}"
DB_PASS="${MYSQLPASSWORD:?MYSQLPASSWORD is not set}"
DB_NAME="${MYSQLDATABASE:?MYSQLDATABASE is not set}"

MYSQL_CMD="mysql -h \"$DB_HOST\" -P \"$DB_PORT\" -u \"$DB_USER\" -p\"$DB_PASS\""

# ── Locate database.sql relative to this script ─────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_FILE="$SCRIPT_DIR/../database.sql"

if [[ ! -f "$SQL_FILE" ]]; then
  echo "ERROR: database.sql not found at $SQL_FILE" >&2
  exit 1
fi

echo "Checking if database '$DB_NAME' is already initialised..."

# Count tables in the target database (returns 0 if DB doesn't exist yet)
TABLE_COUNT=$(mysql \
  -h "$DB_HOST" \
  -P "$DB_PORT" \
  -u "$DB_USER" \
  -p"$DB_PASS" \
  --silent \
  --skip-column-names \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME';" 2>/dev/null || echo "0")

if [[ "$TABLE_COUNT" -gt 0 ]]; then
  echo "Database '$DB_NAME' already contains $TABLE_COUNT table(s). Skipping import."
  exit 0
fi

echo "Database is empty. Importing database.sql into '$DB_NAME'..."

mysql \
  -h "$DB_HOST" \
  -P "$DB_PORT" \
  -u "$DB_USER" \
  -p"$DB_PASS" \
  "$DB_NAME" < "$SQL_FILE"

echo "Import complete."
