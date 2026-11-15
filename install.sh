#!/usr/bin/env bash
# =============================================================================
#  Peatio one-command installer for Ubuntu 22.04 / 24.04
#
#  Usage (from inside the downloaded PeatioCryptoExchange folder):
#      sudo ./install.sh
#
#  Safe to run again: it keeps your .env, database and settings, and only
#  does the steps that are still missing.
# =============================================================================
set -euo pipefail
cd "$(dirname "$0")"
APP_DIR=$(pwd)

say()  { printf '\n\033[1;33m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m    ✔ %s\033[0m\n' "$*"; }
die()  { printf '\n\033[1;31m!! %s\033[0m\n' "$*" >&2; exit 1; }
ask()  { # ask "Question" "default" -> prints answer
  local reply
  if [ -t 0 ]; then read -r -p "    $1 [$2]: " reply; fi
  echo "${reply:-$2}"
}
rand() { openssl rand -hex "${1:-24}"; }

[ "$(id -u)" -eq 0 ] || die "Please run with sudo:  sudo ./install.sh"
grep -qi ubuntu /etc/os-release || echo "    (Not Ubuntu - continuing, but this script is tested on Ubuntu.)"

# --- .env helpers -------------------------------------------------------------
get_env() { grep -E "^$1=" .env 2>/dev/null | tail -1 | cut -d= -f2- || true; }
set_env() { # set_env KEY VALUE  (safe for any characters in VALUE)
  local tmp; tmp=$(mktemp)
  KEY="$1" VAL="$2" awk 'BEGIN{k=ENVIRON["KEY"]; v=ENVIRON["VAL"]; done=0}
    $0 ~ "^"k"=" { print k"="v; done=1; next } { print }
    END { if (!done) print k"="v }' .env > "$tmp"
  cat "$tmp" > .env && rm -f "$tmp"
}
fill_secret() { [ -n "$(get_env "$1")" ] || set_env "$1" "$(rand "${2:-24}")"; }

# --- 1. Swap: lets 2 GB of RAM behave like 6 GB (slower, but no crashes) ------
say "Step 1/8: Checking memory and swap"
SWAP_MB=$(free -m | awk '/^Swap:/ {print $2}')
if [ "${SWAP_MB:-0}" -lt 2000 ]; then
  if [ ! -f /swapfile ]; then
    fallocate -l 4G /swapfile || dd if=/dev/zero of=/swapfile bs=1M count=4096
    chmod 600 /swapfile && mkswap /swapfile >/dev/null
  fi
  swapon /swapfile 2>/dev/null || true
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  sysctl -q vm.swappiness=10
  grep -q '^vm.swappiness' /etc/sysctl.conf || echo 'vm.swappiness=10' >> /etc/sysctl.conf
  ok "4 GB swap file added"
else
  ok "Swap already present (${SWAP_MB} MB)"
fi

# --- 2. Docker -----------------------------------------------------------------
say "Step 2/8: Installing Docker (if needed)"
if ! command -v docker >/dev/null || ! docker compose version >/dev/null 2>&1; then
  apt-get update -qq
  apt-get install -y -qq docker.io docker-compose-v2 openssl curl >/dev/null
  systemctl enable --now docker
fi
ok "$(docker --version)"

# --- 3. Settings (.env) --------------------------------------------------------
say "Step 3/8: Settings"
if [ ! -f .env ]; then
  cp .env.example .env
  echo "    A few questions (press Enter to accept the suggestion in [brackets]):"
  DOMAIN=$(ask "Exchange web address (domain, without http)" "${DOMAIN:-exchange.example.com}")
  set_env URL_HOST "$DOMAIN"
  set_env ADMIN_EMAIL "$(ask "Admin e-mail address (your login)" "${ADMIN_EMAIL:-admin@$DOMAIN}")"
  set_env EXCHANGE_NAME "$(ask "Name of your exchange" "${EXCHANGE_NAME:-My Exchange}")"
  set_env ADMIN_PASSWORD "$(rand 10)"
  set_env ASSISTANT_PASSWORD "$(rand 10)"
fi
chmod 600 .env
fill_secret SECRET_KEY_BASE 64
fill_secret MYSQL_ROOT_PASSWORD
fill_secret DB_PASSWORD
fill_secret RABBITMQ_PASSWORD
fill_secret ASSISTANT_DB_PASSWORD
fill_secret ASSISTANT_SECRET 32
[ -n "$(get_env ASSISTANT_PASSWORD)" ] || set_env ASSISTANT_PASSWORD "$(rand 10)"
if [ "$(get_env ADMIN_PASSWORD)" = "CHANGE_ME_AT_LEAST_12_CHARS" ] || [ -z "$(get_env ADMIN_PASSWORD)" ]; then
  set_env ADMIN_PASSWORD "$(rand 10)"
fi
ok "Settings saved in $APP_DIR/.env (only root can read it)"

for f in currencies markets banks; do
  [ -f "config/$f.yml" ] || cp "config/$f.yml.example" "config/$f.yml"
done
ok "Coin and market lists ready (config/currencies.yml, config/markets.yml)"

if [ -z "$(get_env PUSHER_KEY)" ]; then
  echo "    NOTE: Pusher keys are empty. The site works, but prices won't update"
  echo "          live until you add them to .env (see README, part 4)."
  set_env ALLOW_MISSING_PUSHER true
fi

# --- 4. Build ------------------------------------------------------------------
say "Step 4/8: Building the app (first time takes 15-30 minutes)"
docker compose build
ok "Build finished"

# --- 5. Database ---------------------------------------------------------------
say "Step 5/8: Starting database, cache, message queue and AI"
docker compose up -d db redis rabbitmq ollama
ROOTPW=$(get_env MYSQL_ROOT_PASSWORD)
DBNAME=$(get_env DB_NAME); DBNAME=${DBNAME:-peatio_production}
for i in $(seq 1 60); do
  docker compose exec -T db mysqladmin ping -uroot -p"$ROOTPW" --silent >/dev/null 2>&1 && break
  [ "$i" -eq 60 ] && die "MySQL did not start. See: docker compose logs db"
  sleep 3
done
ok "MySQL is up"

TABLES=$(docker compose exec -T db mysql -uroot -p"$ROOTPW" -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DBNAME'" 2>/dev/null | tr -d '\r')
if [ "${TABLES:-0}" -eq 0 ]; then
  say "Step 6/8: Creating the exchange database and your admin account"
  docker compose run --rm web bundle exec rake db:setup
else
  say "Step 6/8: Database exists - applying any updates"
  docker compose run --rm web bundle exec rake db:migrate
fi

# Restricted MySQL login for the AI assistant: read everything, but only
# write ticket replies and ticket open/closed state.
docker compose exec -T db mysql -uroot -p"$ROOTPW" >/dev/null 2>&1 <<SQL
CREATE USER IF NOT EXISTS 'assistant'@'%' IDENTIFIED BY '$(get_env ASSISTANT_DB_PASSWORD)';
ALTER USER 'assistant'@'%' IDENTIFIED BY '$(get_env ASSISTANT_DB_PASSWORD)';
GRANT SELECT ON \`$DBNAME\`.* TO 'assistant'@'%';
GRANT INSERT ON \`$DBNAME\`.comments TO 'assistant'@'%';
GRANT UPDATE (aasm_state, updated_at) ON \`$DBNAME\`.tickets TO 'assistant'@'%';
FLUSH PRIVILEGES;
SQL
ok "Database ready (assistant has limited access)"

# --- 7. AI model ----------------------------------------------------------------
MODEL=$(get_env OLLAMA_MODEL); MODEL=${MODEL:-qwen2.5:0.5b}
say "Step 7/8: Downloading the AI model ($MODEL)"
docker compose exec -T ollama ollama pull "$MODEL"
ok "AI model ready"

# --- 8. Start everything ----------------------------------------------------------
say "Step 8/8: Starting the exchange, workers and Control Room"
docker compose up -d
ok "All services started (the website needs ~5 more minutes on first start)"

# --- Optional: nginx + HTTPS ------------------------------------------------------
DOMAIN=$(get_env URL_HOST)
if command -v nginx >/dev/null && [ ! -f /etc/nginx/sites-available/peatio ] && [ "$DOMAIN" != "exchange.example.com" ]; then
  say "Optional: connect nginx to $DOMAIN"
  ADOMAIN=$(ask "Address for the Control Room" "control.$DOMAIN")
  APORT=$(get_env ASSISTANT_PORT); APORT=${APORT:-8090}
  cp deploy/peatio-proxy.conf /etc/nginx/peatio-proxy.conf
  sed -e "s/__DOMAIN__/$DOMAIN/g" -e "s/__ASSISTANT_DOMAIN__/$ADOMAIN/g" -e "s/__ASSISTANT_PORT__/$APORT/g" \
      deploy/nginx-peatio.conf > /etc/nginx/sites-available/peatio
  ln -sf /etc/nginx/sites-available/peatio /etc/nginx/sites-enabled/peatio
  if nginx -t 2>/dev/null; then
    systemctl reload nginx
    ok "nginx now serves http://$DOMAIN and http://$ADOMAIN"
    if [ "$(ask "Get free HTTPS certificates now with certbot? (DNS must already point here) y/n" "y")" = "y" ]; then
      command -v certbot >/dev/null || apt-get install -y -qq certbot python3-certbot-nginx >/dev/null
      certbot --nginx -d "$DOMAIN" -d "$ADOMAIN" --redirect --agree-tos -m "$(get_env ADMIN_EMAIL)" -n \
        && set_env URL_SCHEMA https && docker compose up -d web assistant \
        || echo "    certbot failed - you can retry later (README, part 6)."
    fi
  else
    rm -f /etc/nginx/sites-enabled/peatio
    echo "    nginx config test failed - left your nginx untouched. See README, part 6."
  fi
fi

cat <<EOF

=====================================================================
  Done! Keep these details somewhere safe (they are also in .env):

  Exchange website : $(get_env URL_SCHEMA)://$(get_env URL_HOST)
     admin login   : $(get_env ADMIN_EMAIL)
     password      : $(get_env ADMIN_PASSWORD)

  AI Control Room  : see README part 5 (address or SSH tunnel)
     password      : $(get_env ASSISTANT_PASSWORD)

  Check everything : docker compose ps
=====================================================================
EOF
