#!/bin/bash
# Starts the Peatio website. Compiles the CSS/JS the first time (slow, ~5 min).
set -e
cd /app

if [ ! -f public/assets/manifest.json ] && ! ls public/assets/manifest-*.json >/dev/null 2>&1; then
  echo ">> Compiling website assets (first start only, please wait)..."
  bundle exec rake assets:precompile
fi

rm -f tmp/pids/server.pid
exec bundle exec puma -e production -b tcp://0.0.0.0:3000 \
     -t "${PUMA_MIN_THREADS:-1}:${PUMA_MAX_THREADS:-4}" config.ru
