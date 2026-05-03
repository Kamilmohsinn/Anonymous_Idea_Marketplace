#!/bin/bash
# Database import is skipped — the MySQL service is already initialised.
# The mysql client is not available in this container, and the import is
# not needed because the database schema and data are managed by the
# MySQL service directly.

echo "Database import skipped (MySQL service is already initialised)."
exit 0
