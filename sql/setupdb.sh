#!/bin/sh

# run as postgres superuser

createuser -R -S -D synchrotron
createuser -R -S -D synchrotron_ro
createdb -O synchrotron synchrotron
./ddl.sql
./procedures.plsql

