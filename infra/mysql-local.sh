#!/bin/sh
# Manage only this project's isolated local MySQL instance.
set -eu
project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
data_dir="$project_dir/storage/local-mysql"
case "${1:-status}" in
  start)
    if [ ! -d "$data_dir/mysql" ]; then
      echo 'The local database has not been initialized.' >&2
      exit 1
    fi
    if /opt/homebrew/bin/mysqladmin --defaults-extra-file="$data_dir/admin.cnf" ping >/dev/null 2>&1; then
      echo 'Project MySQL is already running.'
      exit 0
    fi
    exec /opt/homebrew/bin/mysqld --no-defaults --datadir="$data_dir" \
      --socket="$data_dir/mysql.sock" --pid-file="$data_dir/mysql.pid" \
      --log-error="$data_dir/server.log" --bind-address=127.0.0.1 \
      --port=3307 --mysqlx=OFF --daemonize
    ;;
  stop)
    exec /opt/homebrew/bin/mysqladmin --defaults-extra-file="$data_dir/admin.cnf" shutdown
    ;;
  status)
    exec /opt/homebrew/bin/mysqladmin --defaults-extra-file="$data_dir/admin.cnf" ping
    ;;
  *) echo 'Usage: sh infra/mysql-local.sh [start|stop|status]' >&2; exit 1 ;;
esac
