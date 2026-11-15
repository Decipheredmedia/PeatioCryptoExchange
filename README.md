# Peatio Crypto Exchange + AI Control Room

This is an open-source cryptocurrency exchange (the website where people sign up, deposit, trade and withdraw) **plus a "Control Room"**: a private web page where you, the owner, type normal sentences like *"show open tickets"* or *"answer email 12"* and a local AI (Ollama, running on your own server) does the work. The AI only ever **suggests**. Nothing gets sent or changed until you press **Confirm**.

The guide is written for people who aren't programmers. You copy each command, paste it into the server's terminal and press **Enter**.

---

## Contents

1. [Read this first](#1-read-this-first)
2. [What you need](#2-what-you-need)
3. [Install it (about 45 minutes, mostly waiting)](#3-install-it)
4. [Configure your exchange](#4-configure-your-exchange)
5. [Use the AI Control Room](#5-use-the-ai-control-room)
6. [Web address and HTTPS (nginx)](#6-web-address-and-https-nginx)
7. [Everyday commands (cheat sheet)](#7-everyday-commands-cheat-sheet)
8. [Backups](#8-backups)
9. [Troubleshooting](#9-troubleshooting)
10. [Security notes and limits](#10-security-notes-and-limits)
11. [What was fixed in this version](#11-what-was-fixed-in-this-version)

---

## 1. Read this first

* **Running an exchange that holds other people's money is regulated** in most countries. In the EU, for example, you need a licence (MiCA). Talk to a lawyer before you let real customers in. Until then, use it as a test or demo exchange.
* **This software is old.** It's built on Rails 4.0 from 2014, which gets no more security updates. This version fixes the problems that made it impossible to install or dangerous out of the box. Even so, it shouldn't hold real funds unless a security professional has reviewed it.
* **A 2 GB server is the bare minimum.** It runs, but slowly, and the small AI model gives simple answers. With **4 GB** it's comfortable, and with **8 GB** you can use a smarter AI model. The installer adds a 4 GB "swap file" so a 2 GB server doesn't crash.
* **Real coin deposits and withdrawals need a coin node** (for example `bitcoind`). A full Bitcoin node needs over 600 GB of disk and several GB of RAM, so it won't fit on this server. Trading, accounts, tickets and the admin panel all work without one. See [4.5](#45-coins-and-markets).

## 2. What you need

| Thing | Why | Where to get it |
|---|---|---|
| An Ubuntu 22.04 or 24.04 server, 2 GB RAM or more, 20 GB free disk | Runs everything | Your VPS |
| A domain name, e.g. `myexchange.com` | The address people visit | Any domain registrar |
| Two DNS "A records" pointing to your server's IP: `myexchange.com` and `control.myexchange.com` | So the addresses reach your server | Your domain registrar's DNS page |
| A free **Pusher** account | Live price updates in the browser | [pusher.com](https://pusher.com) → Channels |
| An e-mail account with SMTP (and IMAP if the AI should read mail) | Sign-up mails, ticket replies | Your mail provider (Zoho, Gmail, Mailgun, …) |

> **What is SSH?** It's how you type commands on your server from your own computer. On Windows, open **PowerShell**. On a Mac, open **Terminal**. Then type `ssh root@YOUR_SERVER_IP` (or `ssh youruser@YOUR_SERVER_IP`) and enter your server password.

## 3. Install it

### 3.1 Log in to your server

```bash
ssh root@YOUR_SERVER_IP
```

If you log in as a normal user instead of `root`, that's fine. Every command below already starts with `sudo` where it needs to.

### 3.2 Download this project

```bash
sudo apt-get update && sudo apt-get install -y git
cd ~
git clone https://github.com/Decipheredmedia/PeatioCryptoExchange.git peatio
cd peatio
```

*What this does:* installs `git` (the download tool), copies the project into a folder called `peatio` in your home folder, and moves you into it.

### 3.3 Run the installer

```bash
sudo ./install.sh
```

It asks three questions. Press **Enter** to accept the suggestion shown in `[brackets]`:

1. **Exchange web address**: your domain, e.g. `myexchange.com` (no `http://`)
2. **Admin e-mail**: the e-mail you will log in with
3. **Name of your exchange**: used by the AI when it writes to customers

Then it works through 8 steps by itself:

| Step | What happens | Time |
|---|---|---|
| 1 | Adds 4 GB of swap (extra memory on disk) if needed | seconds |
| 2 | Installs Docker (the tool that runs every part in its own box) | 1–2 min |
| 3 | Creates the settings file `.env` with strong random passwords | seconds |
| 4 | Builds the exchange | **15–30 min** |
| 5 | Starts the database, cache, message queue and AI engine | 1 min |
| 6 | Creates the database and your admin account | 2–5 min |
| 7 | Downloads the AI model | 1–3 min |
| 8 | Starts everything | seconds, then ~5 min warm-up |

If nginx is already installed and your domain is set, the installer also offers to connect it and get free HTTPS certificates. Answer `y` only if your DNS records already point to the server.

At the end it prints a box with your **admin password** and **Control Room password**. Write them down. You can see them again any time with:

```bash
sudo grep -E 'ADMIN_EMAIL|ADMIN_PASSWORD|ASSISTANT_PASSWORD' ~/peatio/.env
```

### 3.4 Check that it's running

```bash
cd ~/peatio
sudo docker compose ps
```

Every line should say `running` or `Up`. The first time, the website (`web`) needs about 5 minutes to prepare its pages. To watch it:

```bash
sudo docker compose logs -f web
```

When you see `Listening on tcp://0.0.0.0:3000`, it's ready. Press **Ctrl + C** to stop watching. The website keeps running.

## 4. Configure your exchange

All settings live in **one file**, `~/peatio/.env`. To edit it:

```bash
cd ~/peatio
sudo nano .env
```

*Using nano:* move with the arrow keys and type to change text. Press **Ctrl + O** then **Enter** to save, and **Ctrl + X** to exit.

**After every change to `.env`, apply it with:**

```bash
sudo docker compose up -d
```

### 4.1 Your address

```
URL_HOST=myexchange.com
URL_SCHEMA=https
```

Use `https` once you have a certificate (part 6). Until then, use `http`.

### 4.2 Pusher (live prices)

1. Sign up at [pusher.com](https://pusher.com), choose **Channels**, then **Create app**. Pick the cluster closest to you (e.g. `eu`).
2. Open **App Keys** and copy the four values into `.env`:

```
PUSHER_APP=1234567
PUSHER_KEY=abcdef0123456789abcd
PUSHER_SECRET=0123456789abcdef0123
PUSHER_CLUSTER=eu
```

3. Delete the line `ALLOW_MISSING_PUSHER=true` (if present), then run `sudo docker compose up -d`.

### 4.3 E-mail

**Outgoing mail** (sign-up confirmations, password resets, ticket replies). Your mail provider shows these values under "SMTP settings":

```
SMTP_ADDRESS=smtp.zoho.eu
SMTP_PORT=587
SMTP_DOMAIN=myexchange.com
SMTP_USERNAME=support@myexchange.com
SMTP_PASSWORD=your-mail-password-or-app-password
SUPPORT_MAIL=support@myexchange.com
SYSTEM_MAIL_FROM=no-reply@myexchange.com
```

> Gmail needs an **App Password** (Google Account → Security → 2-Step Verification → App passwords), not your normal password.

**Incoming mail**, so the AI can read your support inbox and draft answers (optional):

```
IMAP_HOST=imap.zoho.eu
IMAP_PORT=993
IMAP_USERNAME=support@myexchange.com
IMAP_PASSWORD=your-mail-password-or-app-password
```

### 4.4 Admin accounts

`ADMIN_EMAIL` is the main admin. It was created during install with `ADMIN_PASSWORD`. To make more people admins, add their e-mails, separated by commas (they must first sign up on the site as normal users):

```
ADMIN_EMAILS=you@myexchange.com,partner@myexchange.com
```

Log in on the website, then open `https://myexchange.com/admin` for the admin panel. There you can approve withdrawals, verify IDs, answer tickets, and more.

> Changing `ADMIN_PASSWORD` in `.env` later does **not** change your login. Change it on the website instead (Settings → Password).

### 4.5 Coins and markets

* `config/markets.yml` lists the trading pairs (by default `ETH/BTC`).
* `config/currencies.yml` lists the coins and how to reach their nodes (`rpc:` lines).

> ⚠️ `config/currencies.yml` contains example **deposit addresses** under `assets: accounts:`. They belong to strangers. Replace them with your own addresses, or delete those lines.

After editing either file:

```bash
sudo docker compose restart web daemons
```

To accept real deposits you need a node for each coin, plus the extra workers in `.env`:

```
DAEMON_BINDINGS=matching order_processor trade_executor slave_book market_ticker pusher_market pusher_member email_notification deposit_coin deposit_coin_address withdraw_coin
```

Run coin nodes on a separate, bigger server (or use a pruned or testnet node) and put its address in the `rpc:` line, e.g. `rpc: http://rpcuser:rpcpass@10.0.0.5:8332`. More detail: [doc/currency.md](doc/currency.md) and [doc/eth.md](doc/eth.md).

### 4.6 SMS two-factor login (optional)

Fill in `TWILIO_NUMBER`, `TWILIO_SID` and `TWILIO_TOKEN` from your [Twilio](https://twilio.com) account. Then add `sms_notification` to `DAEMON_BINDINGS` and run `sudo docker compose up -d`. Google Authenticator two-factor works without this.

## 5. Use the AI Control Room

### 5.1 Open it

**Option A: with your own address** (set up by the installer, or by hand in part 6):
open `https://control.myexchange.com` and enter your `ASSISTANT_PASSWORD`.

**Option B: private tunnel, no domain needed.** This is the safest option, because the page is never on the internet. On **your own computer** (not the server), run:

```bash
ssh -L 8090:127.0.0.1:8090 root@YOUR_SERVER_IP
```

Leave that window open and visit **http://localhost:8090** in your browser.

### 5.2 What you can type

Normal sentences work. These are the most reliable:

| You type | What happens |
|---|---|
| `status` / `is everything running?` | Checks the database, website, cache, queue, AI and mail |
| `stats` / `how many users signed up?` | Users, new sign-ups, open orders, trades, deposits, withdrawals waiting, open tickets |
| `show open tickets` | A list of tickets, each with **Open / Draft reply / Close** buttons |
| `show ticket 12` | The whole conversation of ticket 12 |
| `draft reply to ticket 12` | The AI writes an answer. You can **edit it**, then press **Send reply** |
| `close ticket 12` / `reopen ticket 12` | Asks you to confirm, then changes it |
| `check email` | Unread mails in your support inbox |
| `draft reply to email 345` | The AI drafts an answer. Edit it and press **Send e-mail** |
| any other question | The AI answers in plain words |

The buttons at the top (Status, Stats, Tickets, Email, Help) are shortcuts for the same commands.

**Safety built in:**

* The AI cannot run server commands or touch money. It can only choose from the actions above.
* Replies, ticket closing and e-mails are only **proposals** until you press Confirm. You can always edit the text or press Cancel.
* The AI logs in to the database as a restricted user. It can read data, but it can only add ticket replies and open or close tickets.
* After 5 wrong passwords, logins are blocked for 15 minutes.

When you send a ticket reply, it appears on the customer's ticket page. If SMTP is set up, the customer also gets an e-mail with a link to the ticket.

### 5.3 Choosing the AI model

Set `OLLAMA_MODEL` in `.env`. Bigger models give better answers but need more memory:

| Server RAM | Model | Quality |
|---|---|---|
| 2 GB | `qwen2.5:0.5b` (default) | Basic, short answers. Always check them |
| 4 GB | `qwen2.5:1.5b` | Good for support replies |
| 8 GB+ | `qwen2.5:3b` or `llama3.2:3b` | Noticeably better |

To switch models, download the new one, then restart the Control Room:

```bash
cd ~/peatio
sudo docker compose exec ollama ollama pull qwen2.5:1.5b
sudo nano .env          # change OLLAMA_MODEL=qwen2.5:1.5b
sudo docker compose up -d assistant
```

The AI unloads itself 5 minutes after the last question, which frees the memory again. The first answer after a break takes a few seconds longer.

**Already running Ollama on this server** (outside Docker)? Set `OLLAMA_URL=http://host.docker.internal:11434` in `.env`, and run `sudo docker compose stop ollama` to avoid running it twice.

## 6. Web address and HTTPS (nginx)

You can skip this part if the installer already set up nginx for you.

The exchange listens privately on port `3000` and the Control Room on port `8090`. Your existing nginx passes visitors to them.

```bash
cd ~/peatio
sudo cp deploy/peatio-proxy.conf /etc/nginx/peatio-proxy.conf
sudo cp deploy/nginx-peatio.conf /etc/nginx/sites-available/peatio
# put your real addresses in (change the two domains!):
sudo sed -i 's/__DOMAIN__/myexchange.com/; s/__ASSISTANT_DOMAIN__/control.myexchange.com/; s/__ASSISTANT_PORT__/8090/' /etc/nginx/sites-available/peatio
sudo ln -s /etc/nginx/sites-available/peatio /etc/nginx/sites-enabled/peatio
sudo nginx -t && sudo systemctl reload nginx
```

`nginx -t` checks the configuration. Reload only happens if it says `syntax is ok`.

**Free HTTPS certificates** (DNS must already point to the server):

```bash
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d myexchange.com -d control.myexchange.com --redirect
```

Then set `URL_SCHEMA=https` in `.env` and run `sudo docker compose up -d`. Certificates renew automatically.

**Firewall:** allow only SSH and web traffic.

```bash
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable
```

## 7. Everyday commands (cheat sheet)

Always run these from the project folder first: `cd ~/peatio`

| What you want | Command |
|---|---|
| See what's running | `sudo docker compose ps` |
| Start everything | `sudo docker compose up -d` |
| Stop everything | `sudo docker compose down` (your data is kept) |
| Restart the website | `sudo docker compose restart web` |
| Restart the trading engine | `sudo docker compose restart daemons` |
| Live log of the website | `sudo docker compose logs -f web` (Ctrl + C to stop) |
| Live log of the trading engine | `sudo docker compose logs -f daemons` |
| Log of the Control Room | `sudo docker compose logs -f assistant` |
| Memory use per part | `sudo docker stats --no-stream` |
| Open a Rails console (advanced) | `sudo docker compose exec web bundle exec rails console` |
| Update to the newest version | `git pull && sudo ./install.sh` |

## 8. Backups

**Make a backup** (saves the database into `~/peatio/backups`):

```bash
cd ~/peatio && mkdir -p backups
sudo docker compose exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines "$MYSQL_DATABASE"' | gzip > backups/peatio-$(date +%F).sql.gz
```

**Make one automatically every night at 03:00:**

```bash
(sudo crontab -l 2>/dev/null; echo "0 3 * * * cd $HOME/peatio && mkdir -p backups && docker compose exec -T db sh -c 'mysqldump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --single-transaction \"\$MYSQL_DATABASE\"' | gzip > backups/peatio-\$(date +\%F).sql.gz") | sudo crontab -
```

**Restore a backup** (this replaces the current data!):

```bash
cd ~/peatio
gunzip -c backups/peatio-2026-01-31.sql.gz | sudo docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
sudo docker compose restart web daemons
```

Also copy `~/peatio/.env` somewhere safe. Without it, the backup can't be opened by the app.

## 9. Troubleshooting

| Problem | What to do |
|---|---|
| The website shows "502 Bad Gateway" | It is still starting. Wait 5 min, then check `sudo docker compose logs --tail 50 web` |
| `config missing` / Pusher warning in the web log | Fill in the Pusher values (4.2), or add `ALLOW_MISSING_PUSHER=true` to `.env` for now |
| `SECRET_KEY_BASE is missing` | Run `sudo ./install.sh` again, which fills in missing secrets |
| `Set ADMIN_PASSWORD` error during install | Put a password of 12+ characters in `ADMIN_PASSWORD` in `.env` and run the installer again |
| Build stops at `unread` / `github.com/InfraexDev` | That GitHub repo was deleted. Open an issue on this repo and the gem will be vendored in |
| Build stops at `eventmachine` or `mysql2` | Run `sudo docker compose build --no-cache web` once. If it still fails, open an issue with the last 30 lines |
| Everything is very slow / services restart | Not enough memory. Check `free -h`. Set `ENABLE_KLINE=false` in `.env`, use the smallest AI model, or upgrade to 4 GB RAM |
| Control Room: "model is not downloaded" | `sudo docker compose exec ollama ollama pull qwen2.5:0.5b` |
| Control Room: "Can't reach Ollama" | `sudo docker compose up -d ollama`, then `sudo docker compose logs ollama` |
| Control Room: "isn't connected" for e-mail | Fill in the `IMAP_…` lines (4.3) and run `sudo docker compose up -d assistant` |
| Forgot the Control Room password | `sudo grep ASSISTANT_PASSWORD ~/peatio/.env` |
| Orders are placed but never match | `sudo docker compose logs --tail 100 daemons`. The `daemons` box must be running |
| Port 3000 or 8090 already used by another app | Change the left number in `docker-compose.yml` (e.g. `127.0.0.1:3001:3000`) and in your nginx file |

To start completely fresh (⚠️ **deletes all exchange data**):

```bash
cd ~/peatio && sudo docker compose down -v && sudo ./install.sh
```

## 10. Security notes and limits

* Every service listens only on `127.0.0.1`. The only way in from the internet is through nginx.
* Passwords and secret keys are random per install and live only in `.env` (readable only by root). **Never commit `.env` to GitHub.**
* The old version shipped a public session secret, a public admin password (`algobasket`) and someone else's Pusher keys. Anyone who installed it that way should treat those installs as compromised.
* Rails 4.0 and Ruby 2.2 no longer get security fixes. For real money, budget for a professional audit or a move to a maintained exchange stack.
* The AI makes mistakes, especially the small models. Always read a draft before you press Send.
* Keep the server updated: `sudo apt-get update && sudo apt-get upgrade -y`.

## 11. What was fixed in this version

**Install blockers**
* The old installers depended on things that no longer exist: `git://` GitHub links (GitHub turned these off in 2022), Ubuntu 16.04 package names, a dead RabbitMQ repository, the retired Bitcoin PPA and Node 6. They also broke halfway because `exec $SHELL` stops the script. All five scripts are replaced by `install.sh` and Docker.
* Ruby 2.2 / Rails 4.0 can't be compiled on Ubuntu 24.04 (OpenSSL 3, MySQL 8). The app now runs in a Debian 9 container with MySQL 5.7, the versions it was written for.
* Gem sources moved from `git://` to `https://`. The unused `mina-slack` deploy gem is removed, and Puma is added as the app server.
* The database installer step wrote `username: root@` (a stray `@`), which made the database login fail.

**Security**
* `secret_key_base` (signs every login cookie) was hard-coded in the public repo, so anyone could forge an admin session. It now comes from `.env`.
* The seeded admin password `algobasket` is replaced by a random one from `.env`.
* The installer injected a third party's Pusher keys. They're removed.
* The services' ports aren't published to the internet any more.

**Bugs**
* Live updates used the Pusher **API** host as the browser's **websocket** host, so the browser couldn't connect. That's fixed, and `PUSHER_CLUSTER` support was added for new Pusher accounts.
* SMTP port is now a number, and STARTTLS is enabled.
* The 12 background workers (100–150 MB each) are combined into one process that fits a small server, and restart automatically if they crash.
* The GitHub "delete comments with links" workflow had a placeholder username and no permissions. It now uses the repo owner and has write permission.

**New**
* AI Control Room (`assistant/`): a chat interface on local Ollama for support tickets, e-mail, stats and health checks, with confirm-before-acting.

---

### Original project

Based on [Peatio](https://github.com/peatio/peatio) by the Peatio open-source group. Older manual-install guides are in [doc/](doc), but they describe the pre-Docker setup. Released under the MIT license; see [peatio.mit-license.org](http://peatio.mit-license.org).
