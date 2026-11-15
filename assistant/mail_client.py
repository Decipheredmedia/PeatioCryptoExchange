"""Read the support inbox (IMAP) and send mail (SMTP) - Python standard library only."""
import email
import email.policy
import email.utils
import imaplib
import os
import smtplib
from email.message import EmailMessage


def _env(key, default=""):
    value = os.environ.get(key, "")
    return value if value.strip() else default


def imap_configured():
    return bool(_env("IMAP_HOST") and _env("IMAP_USERNAME") and _env("IMAP_PASSWORD"))


def smtp_configured():
    host = _env("SMTP_ADDRESS")
    return bool(host and host not in ("localhost", "smtp.example.com"))


def _body_text(msg):
    """Best plain-text version of an e-mail body."""
    part = msg.get_body(preferencelist=("plain", "html")) if hasattr(msg, "get_body") else None
    if part is None:
        return ""
    text = part.get_content()
    if part.get_content_type() == "text/html":
        import html
        import re
        text = html.unescape(re.sub(r"<[^>]+>", " ", text))
    return "\n".join(line.rstrip() for line in text.strip().splitlines())[:6000]


class Mailbox:
    def _imap(self):
        conn = imaplib.IMAP4_SSL(_env("IMAP_HOST"), int(_env("IMAP_PORT", "993")))
        conn.login(_env("IMAP_USERNAME"), _env("IMAP_PASSWORD"))
        return conn

    def _parse(self, uid, raw):
        msg = email.message_from_bytes(raw, policy=email.policy.default)
        return {
            "uid": uid,
            "from": str(msg.get("Reply-To") or msg.get("From") or ""),
            "subject": str(msg.get("Subject") or "(no subject)"),
            "date": str(msg.get("Date") or ""),
            "message_id": str(msg.get("Message-ID") or ""),
            "references": str(msg.get("References") or ""),
            "body": _body_text(msg),
        }

    def list_unread(self, limit=10):
        conn = self._imap()
        try:
            conn.select(_env("IMAP_FOLDER", "INBOX"), readonly=True)
            _, data = conn.uid("search", None, "UNSEEN")
            uids = data[0].split()[-limit:][::-1]
            mails = []
            for uid in uids:
                _, parts = conn.uid("fetch", uid, "(BODY.PEEK[])")
                raw = next((p[1] for p in parts if isinstance(p, tuple)), b"")
                mails.append(self._parse(uid.decode(), raw))
            return mails
        finally:
            conn.logout()

    def get(self, uid):
        conn = self._imap()
        try:
            conn.select(_env("IMAP_FOLDER", "INBOX"), readonly=True)
            _, parts = conn.uid("fetch", str(uid).encode(), "(BODY.PEEK[])")
            raw = next((p[1] for p in parts if isinstance(p, tuple)), None)
            return self._parse(str(uid), raw) if raw else None
        finally:
            conn.logout()

    def mark_read(self, uid):
        conn = self._imap()
        try:
            conn.select(_env("IMAP_FOLDER", "INBOX"))
            conn.uid("store", str(uid).encode(), "+FLAGS", "(\\Seen)")
        finally:
            conn.logout()


def send_mail(to, subject, body, in_reply_to="", references=""):
    msg = EmailMessage()
    msg["From"] = _env("SUPPORT_MAIL", _env("SMTP_USERNAME"))
    msg["To"] = to
    msg["Subject"] = subject
    msg["Date"] = email.utils.formatdate(localtime=True)
    msg["Message-ID"] = email.utils.make_msgid()
    if in_reply_to:
        msg["In-Reply-To"] = in_reply_to
        msg["References"] = (references + " " + in_reply_to).strip()
    msg.set_content(body)

    host, port = _env("SMTP_ADDRESS"), int(_env("SMTP_PORT", "587"))
    if port == 465:
        server = smtplib.SMTP_SSL(host, port, timeout=30)
    else:
        server = smtplib.SMTP(host, port, timeout=30)
        server.starttls()
    try:
        if _env("SMTP_USERNAME"):
            server.login(_env("SMTP_USERNAME"), _env("SMTP_PASSWORD"))
        server.send_message(msg)
    finally:
        server.quit()
