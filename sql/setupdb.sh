#!/bin/sh

# run as postgres superuser

createuser -R -S -D synchrotron
createuser -R -S -D synchrotron_ro
createdb -O synchrotron synchrotron
psql -U synchrotron synchrotron -f postgres.sql
psql -U synchrotron synchrotron -f procedures.plsql

