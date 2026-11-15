#!/bin/bash
# Runs Peatio's background workers (matching engine, trade executor, ...).
#
# The original setup started ~12 separate Ruby processes (one per worker),
# each needing 100-150 MB of RAM. On a 2 GB server that does not fit, so the
# message-queue workers share ONE process here. If any worker dies the whole
# container exits and Docker restarts it automatically.
set -e
cd /app

BINDINGS=${DAEMON_BINDINGS:-"matching order_processor trade_executor slave_book market_ticker pusher_market pusher_member email_notification"}

echo ">> Starting queue workers: $BINDINGS"
# shellcheck disable=SC2086
ruby lib/daemons/amqp_daemon.rb $BINDINGS &

echo ">> Starting market ticker (global_state)"
ruby lib/daemons/global_state.rb &

if [ "${ENABLE_KLINE:-true}" = "true" ]; then
  echo ">> Starting chart data (k-line)"
  ruby lib/daemons/k.rb &
fi

trap 'kill -TERM $(jobs -p) 2>/dev/null; wait; exit 0' TERM INT

# Stop as soon as any worker stops, so Docker restarts all of them.
wait -n
echo "!! A worker stopped - restarting the daemons container." >&2
kill -TERM $(jobs -p) 2>/dev/null || true
exit 1
