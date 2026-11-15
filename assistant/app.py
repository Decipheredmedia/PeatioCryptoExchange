"""Peatio Control Room - talk to your exchange in plain English.

Run inside Docker (see docker-compose.yml). Open it through nginx or an SSH
tunnel; it only listens on 127.0.0.1 of the server.
"""
import hmac
import os
import secrets
import socket
import threading
import time
import urllib.error
import urllib.request

from flask import Flask, abort, jsonify, redirect, render_template, request, session, url_for

import brain
import mail_client as mailbox
from peatio_db import PeatioDB


def _env(key, default=""):
    value = os.environ.get(key, "")
    return value if value.strip() else default


def create_app(db=None, ollama=None, mail=None):
    app = Flask(__name__)
    app.secret_key = _env("ASSISTANT_SECRET") or secrets.token_hex(32)
    app.config.update(
        SESSION_COOKIE_HTTPONLY=True,
        SESSION_COOKIE_SAMESITE="Strict",
        SESSION_COOKIE_SECURE=_env("URL_SCHEMA", "http") == "https" and _env("ASSISTANT_COOKIE_SECURE", "true") == "true",
        PERMANENT_SESSION_LIFETIME=8 * 3600,
    )
    app.db = db or PeatioDB()
    app.ollama = ollama or brain.Ollama()
    app.mail = mail or mailbox.Mailbox()
    app.pending = {}            # token -> proposed action waiting for "Confirm"
    app.failed_logins = {}      # ip -> [timestamps]
    lock = threading.Lock()

    # ------------------------------------------------------------------ auth
    def logged_in():
        return session.get("ok") is True

    def check_csrf():
        token = request.headers.get("X-CSRF-Token", "")
        if not logged_in() or not hmac.compare_digest(token, session.get("csrf", "")):
            abort(403)

    @app.get("/healthz")
    def healthz():
        return "ok"

    @app.get("/")
    def index():
        if not logged_in():
            return redirect(url_for("login"))
        return render_template("index.html", csrf=session["csrf"],
                               model=app.ollama.model, name=_env("EXCHANGE_NAME", "Peatio"))

    @app.route("/login", methods=["GET", "POST"])
    def login():
        error = None
        if request.method == "POST":
            ip = request.headers.get("X-Real-IP", request.remote_addr or "?")
            now = time.time()
            with lock:
                recent = [t for t in app.failed_logins.get(ip, []) if now - t < 900]
                app.failed_logins[ip] = recent
            expected = _env("ASSISTANT_PASSWORD")
            if len(recent) >= 5:
                error = "Too many wrong passwords. Wait 15 minutes."
            elif not expected:
                error = "ASSISTANT_PASSWORD is not set in .env on the server."
            elif hmac.compare_digest(request.form.get("password", "").encode(), expected.encode()):
                session.clear()
                session.permanent = True
                session["ok"] = True
                session["csrf"] = secrets.token_hex(16)
                return redirect(url_for("index"))
            else:
                with lock:
                    app.failed_logins[ip].append(now)
                error = "Wrong password."
        return render_template("login.html", error=error), (401 if error else 200)

    @app.post("/logout")
    def logout():
        check_csrf()
        session.clear()
        return jsonify(ok=True)

    # ------------------------------------------------------------ chat API
    @app.post("/api/chat")
    def api_chat():
        check_csrf()
        text = (request.get_json(silent=True) or {}).get("message", "").strip()[:2000]
        if not text:
            return jsonify(reply="Type a question or a command, for example: show open tickets")
        try:
            intent = brain.understand(text, app.ollama)
            return jsonify(run(intent, text))
        except RuntimeError as exc:
            return jsonify(reply=f"⚠️ {exc}")
        except Exception as exc:  # keep the chat alive, show a readable error
            app.logger.exception("chat failed")
            return jsonify(reply=f"⚠️ Something went wrong: {type(exc).__name__}: {exc}")

    @app.post("/api/confirm")
    def api_confirm():
        check_csrf()
        body = request.get_json(silent=True) or {}
        with lock:
            item = app.pending.pop(body.get("token", ""), None)
        if not item or item["owner"] != session["csrf"] or time.time() - item["at"] > 3600:
            return jsonify(reply="That request expired. Ask again.")
        text = body.get("text", item.get("text", ""))
        if isinstance(text, str):
            text = text.strip()[:5000]
        try:
            return jsonify(reply=execute(item, text))
        except Exception as exc:
            app.logger.exception("confirm failed")
            return jsonify(reply=f"⚠️ Could not finish: {type(exc).__name__}: {exc}")

    @app.post("/api/cancel")
    def api_cancel():
        check_csrf()
        with lock:
            app.pending.pop((request.get_json(silent=True) or {}).get("token", ""), None)
        return jsonify(reply="Cancelled - nothing was changed.")

    # ----------------------------------------------------------- helpers
    def propose(kind, summary, label, text=None, **data):
        token = secrets.token_urlsafe(16)
        with lock:
            for k in [k for k, v in app.pending.items() if time.time() - v["at"] > 3600]:
                app.pending.pop(k, None)
            app.pending[token] = dict(kind=kind, text=text, owner=session["csrf"], at=time.time(), **data)
        return {"token": token, "summary": summary, "label": label,
                "editable": text is not None, "text": text or ""}

    def short(value, n=90):
        value = " ".join(str(value or "").split())
        return value if len(value) <= n else value[: n - 1] + "…"

    def when(value):
        return value.strftime("%Y-%m-%d %H:%M") if hasattr(value, "strftime") else str(value or "")

    # ----------------------------------------------------------- actions
    def run(intent, text):
        action = intent["action"]

        if action == "help":
            return {"reply": HELP}

        if action == "stats":
            s = app.db.stats()
            rows = [["Users (total)", s["members_total"]], ["New users, last 7 days", s["members_new_7d"]],
                    ["Open orders", s["open_orders"]], ["Trades, last 24 h", s["trades_24h"]],
                    ["Deposits, last 24 h", s["deposits_24h"]],
                    ["Withdrawals waiting for review", s["withdraws_waiting"]],
                    ["Open support tickets", s["open_tickets"]]]
            return {"reply": "Here are the latest numbers:",
                    "table": {"columns": ["What", "Count"], "rows": [{"cells": r} for r in rows]}}

        if action == "status":
            return {"reply": "System check:", "table": {"columns": ["Part", "State", "Notes"],
                                                        "rows": [{"cells": r} for r in status_rows()]}}

        if action in ("list_tickets", "list_closed_tickets"):
            state = "open" if action == "list_tickets" else "closed"
            tickets = app.db.list_tickets(state=state)
            if not tickets:
                return {"reply": f"No {state} tickets. 🎉" if state == "open" else "No closed tickets yet."}
            rows = []
            for t in tickets:
                buttons = [{"label": "Open", "command": f"show ticket {t['id']}"}]
                if state == "open":
                    buttons += [{"label": "Draft reply", "command": f"draft reply to ticket {t['id']}"},
                                {"label": "Close", "command": f"close ticket {t['id']}"}]
                else:
                    buttons.append({"label": "Reopen", "command": f"reopen ticket {t['id']}"})
                rows.append({"cells": [f"#{t['id']}", short(t["title"] or t["content"], 60),
                                       t["author_email"] or "?", when(t["created_at"]), t["replies"]],
                             "buttons": buttons})
            return {"reply": f"{len(tickets)} {state} ticket(s):",
                    "table": {"columns": ["#", "Subject", "From", "Opened", "Replies"], "rows": rows}}

        if action in ("show_ticket", "draft_ticket_reply", "close_ticket", "reopen_ticket"):
            ticket = app.db.get_ticket(intent["ticket_id"])
            if not ticket:
                return {"reply": f"There is no ticket #{intent['ticket_id']}."}
            tid = ticket["id"]
            if action == "show_ticket":
                lines = [f"Ticket #{tid} - {ticket['title'] or '(no title)'} [{ticket['state']}]",
                         f"From {ticket['author_email']} on {when(ticket['created_at'])}", "",
                         ticket["content"] or ""]
                for c in ticket["comments"]:
                    who = "Customer" if c["author_id"] == ticket["author_id"] else f"Support ({c['author_email']})"
                    lines += ["", f"— {who}, {when(c['created_at'])}:", c["content"] or ""]
                return {"reply": "\n".join(lines),
                        "buttons": [{"label": "Draft a reply", "command": f"draft reply to ticket {tid}"},
                                    {"label": "Close ticket", "command": f"close ticket {tid}"}]}
            if action == "draft_ticket_reply":
                draft = app.ollama.chat(brain.support_rules(), brain.ticket_prompt(ticket))
                return {"reply": f"Draft reply for ticket #{tid} ({ticket['author_email']}). "
                                 "Edit it if needed, then press Send.",
                        "confirm": propose("ticket_reply", f"Reply to ticket #{tid}", "Send reply",
                                           text=draft, ticket_id=tid, to=ticket["author_email"])}
            if action == "close_ticket":
                return {"reply": f"Close ticket #{tid} ({short(ticket['title'] or ticket['content'], 50)})?",
                        "confirm": propose("ticket_state", f"Close ticket #{tid}", "Close it",
                                           ticket_id=tid, state="closed")}
            return {"reply": f"Reopen ticket #{tid}?",
                    "confirm": propose("ticket_state", f"Reopen ticket #{tid}", "Reopen it",
                                       ticket_id=tid, state="open")}

        if action in ("list_emails", "show_email", "draft_email_reply"):
            if not mailbox.imap_configured():
                return {"reply": "The support inbox isn't connected yet. Fill in IMAP_HOST, IMAP_USERNAME "
                                 "and IMAP_PASSWORD in .env, then run: docker compose up -d assistant"}
            if action == "list_emails":
                mails = app.mail.list_unread()
                if not mails:
                    return {"reply": "No unread e-mails. 📭"}
                rows = [{"cells": [f"#{m['uid']}", short(m["from"], 40), short(m["subject"], 60), short(m["date"], 25)],
                         "buttons": [{"label": "Open", "command": f"show email {m['uid']}"},
                                     {"label": "Draft reply", "command": f"draft reply to email {m['uid']}"}]}
                        for m in mails]
                return {"reply": f"{len(mails)} unread e-mail(s):",
                        "table": {"columns": ["#", "From", "Subject", "Date"], "rows": rows}}
            mail = app.mail.get(intent["email_id"])
            if not mail:
                return {"reply": f"No e-mail #{intent['email_id']} in the inbox."}
            if action == "show_email":
                return {"reply": f"From: {mail['from']}\nSubject: {mail['subject']}\nDate: {mail['date']}\n\n{mail['body']}",
                        "buttons": [{"label": "Draft a reply", "command": f"draft reply to email {mail['uid']}"}]}
            draft = app.ollama.chat(brain.support_rules(), brain.email_prompt(mail))
            subject = mail["subject"] if mail["subject"].lower().startswith("re:") else "Re: " + mail["subject"]
            return {"reply": f"Draft answer to {mail['from']}. Edit it if needed, then press Send.",
                    "confirm": propose("email_reply", f"E-mail {mail['from']}: {subject}", "Send e-mail",
                                       text=draft, uid=mail["uid"], to=mail["from"], subject=subject,
                                       in_reply_to=mail["message_id"], references=mail["references"])}

        # Free conversation
        system = ("You are the helpful assistant inside the admin control room of a Peatio crypto exchange. "
                  "Answer briefly in plain, non-technical words. You cannot run commands yourself; if the admin "
                  "wants something done, tell them which of these to type: show open tickets, draft reply to "
                  "ticket 5, close ticket 5, check email, status, stats.")
        return {"reply": app.ollama.chat(system, text, temperature=0.5)}

    def execute(item, text):
        kind = item["kind"]
        if kind == "ticket_reply":
            if not text:
                return "The reply was empty, so nothing was sent."
            app.db.add_ticket_reply(item["ticket_id"], text)
            note = ""
            if item.get("to") and mailbox.smtp_configured():
                link = f"{_env('URL_SCHEMA', 'https')}://{_env('URL_HOST')}/tickets/{item['ticket_id']}"
                try:
                    mailbox.send_mail(item["to"], f"New reply to your ticket #{item['ticket_id']}",
                                      f"{text}\n\n---\nView the ticket: {link}")
                    note = f" The customer was e-mailed at {item['to']}."
                except Exception as exc:
                    note = f" (Saved, but the notification e-mail failed: {exc})"
            return f"✅ Reply added to ticket #{item['ticket_id']}.{note}"
        if kind == "ticket_state":
            app.db.set_ticket_state(item["ticket_id"], item["state"])
            return f"✅ Ticket #{item['ticket_id']} is now {item['state']}."
        if kind == "email_reply":
            if not text:
                return "The e-mail was empty, so nothing was sent."
            if not mailbox.smtp_configured():
                return "Sending needs SMTP_ADDRESS / SMTP_USERNAME / SMTP_PASSWORD in .env."
            mailbox.send_mail(item["to"], item["subject"], text, item["in_reply_to"], item["references"])
            try:
                app.mail.mark_read(item["uid"])
            except Exception:
                pass
            return f"✅ E-mail sent to {item['to']}."
        return "Unknown action - nothing was changed."

    def tcp_ok(host, port):
        try:
            with socket.create_connection((host, port), timeout=3):
                return True
        except OSError:
            return False

    def status_rows():
        rows = []
        try:
            app.db.ping()
            rows.append(["Database (MySQL)", "✅ OK", ""])
        except Exception as exc:
            rows.append(["Database (MySQL)", "❌ Down", short(exc, 80)])
        try:
            url = _env("PEATIO_INTERNAL_URL", "http://web:3000") + "/"
            with urllib.request.urlopen(url, timeout=8) as resp:
                rows.append(["Exchange website", "✅ OK", f"HTTP {resp.status}"])
        except urllib.error.HTTPError as exc:
            ok = exc.code < 500
            rows.append(["Exchange website", "✅ OK" if ok else "❌ Error", f"HTTP {exc.code}"])
        except Exception as exc:
            rows.append(["Exchange website", "❌ Down", short(exc, 80) + " (still starting? first start takes ~5 min)"])
        rhost, rport = brain.parse_redis_url(_env("REDIS_URL"))
        rows.append(["Cache (Redis)", "✅ OK" if tcp_ok(rhost, rport) else "❌ Down", ""])
        rows.append(["Message queue (RabbitMQ)",
                     "✅ OK" if tcp_ok(_env("RABBITMQ_HOST", "rabbitmq"), 5672) else "❌ Down", ""])
        try:
            models = app.ollama.models()
            have = any(m == app.ollama.model or m.split(":")[0] == app.ollama.model for m in models)
            rows.append(["AI (Ollama)", "✅ OK" if have else "⚠️ Model missing",
                         app.ollama.model if have else f"run: docker compose exec ollama ollama pull {app.ollama.model}"])
        except Exception as exc:
            rows.append(["AI (Ollama)", "❌ Down", short(exc, 80)])
        rows.append(["Support inbox (IMAP)", "✅ Set up" if mailbox.imap_configured() else "➖ Not set up", ""])
        rows.append(["Outgoing mail (SMTP)", "✅ Set up" if mailbox.smtp_configured() else "➖ Not set up", ""])
        return rows

    return app


HELP = """Things you can type (normal sentences work too):

• status – is everything running?
• stats – users, sign-ups, orders, trades, withdrawals waiting
• show open tickets / show closed tickets
• show ticket 12
• draft reply to ticket 12 – the AI writes an answer, you edit and approve it
• close ticket 12 / reopen ticket 12
• check email – unread mails in the support inbox
• draft reply to email 345
• or just ask a question, e.g. "how do I add a new coin?"

Nothing is ever sent or changed until you press the Confirm button."""


app = create_app() if os.environ.get("ASSISTANT_TESTING") != "1" else None
