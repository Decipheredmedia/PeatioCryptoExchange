#!/bin/bash
# Runs before every Peatio container command: writes the config files from
# the .env values, then waits until MySQL, Redis and RabbitMQ answer.
set -e
cd /app

ruby docker/write_config.rb

wait_for() {
  local name=$1 host=$2 port=$3 tries=60
  until nc -z "$host" "$port" >/dev/null 2>&1; do
    tries=$((tries - 1))
    if [ "$tries" -le 0 ]; then
      echo "!! $name ($host:$port) is not answering - giving up." >&2
      exit 1
    fi
    echo "   waiting for $name ($host:$port)..."
    sleep 2
  done
}

wait_for MySQL    "${DB_HOST:-db}"          "${DB_PORT:-3306}"
wait_for Redis    "${REDIS_HOST:-redis}"    6379
wait_for RabbitMQ "${RABBITMQ_HOST:-rabbitmq}" 5672

exec "$@"
