"""Run with:  cd assistant && python -m pytest -q   (or python -m unittest)"""
import datetime
import os
import re
import sys
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.environ["ASSISTANT_TESTING"] = "1"
os.environ["ASSISTANT_PASSWORD"] = "correct horse battery"

import brain  # noqa: E402
from app import create_app  # noqa: E402

NOW = datetime.datetime(2026, 11, 15, 12, 0)


class FakeDB:
    def __init__(self):
        self.tickets = {
            7: {"id": 7, "title": "Deposit missing", "content": "I sent 0.1 BTC, where is it?",
                "state": "open", "created_at": NOW, "author_id": 2, "author_email": "bob@x.io",
                "comments": []},
        }
        self.replies = []

    def ping(self): return True

    def stats(self):
        return dict(members_total=10, members_new_7d=2, open_orders=3, trades_24h=4,
                    open_tickets=1, withdraws_waiting=0, deposits_24h=5)

    def list_tickets(self, state="open", limit=20):
        return [dict(t, replies=len(t["comments"])) for t in self.tickets.values() if t["state"] == state]

    def get_ticket(self, tid): return self.tickets.get(int(tid))

    def add_ticket_reply(self, tid, text): self.replies.append((tid, text))

    def set_ticket_state(self, tid, state): self.tickets[tid]["state"] = state


class FakeOllama:
    model = "qwen2.5:0.5b"

    def __init__(self): self.calls = []

    def chat(self, system, user, as_json=False, temperature=0.3):
        self.calls.append((system, user, as_json))
        if as_json:
            return '{"action": "stats"}' if "overview" in user else '{"action": "chat"}'
        return "Hello Bob, we are checking your deposit."

    def models(self): return ["qwen2.5:0.5b"]


class RouterTests(unittest.TestCase):
    def check(self, text, expected):
        self.assertEqual(brain.rule_based(text), expected, text)

    def test_phrasings(self):
        self.check("show open tickets", {"action": "list_tickets"})
        self.check("any closed tickets?", {"action": "list_closed_tickets"})
        self.check("show ticket 12", {"action": "show_ticket", "ticket_id": 12})
        self.check("Please draft a reply to ticket #7", {"action": "draft_ticket_reply", "ticket_id": 7})
        self.check("close ticket number 5", {"action": "close_ticket", "ticket_id": 5})
        self.check("reopen ticket 5", {"action": "reopen_ticket", "ticket_id": 5})
        self.check("check email", {"action": "list_emails"})
        self.check("answer email 345", {"action": "draft_email_reply", "email_id": 345})
        self.check("is everything running?", {"action": "status"})
        self.check("how many users signed up", {"action": "stats"})
        self.check("help", {"action": "help"})
        # a year is not a ticket number
        self.check("how many tickets came in during 2025", {"action": "list_tickets"})

    def test_llm_fallback_is_validated(self):
        class Evil(FakeOllama):
            def chat(self, *a, **k): return '{"action": "drop_database"}'
        self.assertEqual(brain.understand("do something odd", Evil()), {"action": "chat"})
        self.assertEqual(brain.understand("give me the overview", FakeOllama()), {"action": "stats"})


class AppTests(unittest.TestCase):
    def setUp(self):
        self.db, self.ai = FakeDB(), FakeOllama()
        self.app = create_app(db=self.db, ollama=self.ai, mail=object())
        self.app.testing = True
        self.c = self.app.test_client()

    def login(self):
        r = self.c.post("/login", data={"password": "correct horse battery"})
        self.assertEqual(r.status_code, 302)
        page = self.c.get("/").get_data(as_text=True)
        self.csrf = re.search(r'name="csrf" content="([^"]+)"', page).group(1)

    def chat(self, msg):
        return self.c.post("/api/chat", json={"message": msg}, headers={"X-CSRF-Token": self.csrf}).get_json()

    def test_login_required_and_lockout(self):
        self.assertEqual(self.c.get("/").status_code, 302)
        self.assertEqual(self.c.post("/api/chat", json={"message": "stats"}).status_code, 403)
        for _ in range(5):
            self.assertEqual(self.c.post("/login", data={"password": "nope"}).status_code, 401)
        r = self.c.post("/login", data={"password": "correct horse battery"})
        self.assertIn(b"Too many", r.data)

    def test_csrf_required(self):
        self.login()
        r = self.c.post("/api/chat", json={"message": "stats"}, headers={"X-CSRF-Token": "bad"})
        self.assertEqual(r.status_code, 403)

    def test_stats_and_tickets(self):
        self.login()
        self.assertEqual(self.chat("stats")["table"]["rows"][0]["cells"], ["Users (total)", 10])
        rows = self.chat("show open tickets")["table"]["rows"]
        self.assertEqual(rows[0]["cells"][0], "#7")
        self.assertIn("Deposit missing", self.chat("show ticket 7")["reply"])
        self.assertIn("no ticket #99", self.chat("show ticket 99")["reply"])

    def test_draft_then_confirm_with_edit(self):
        self.login()
        data = self.chat("draft reply to ticket 7")
        self.assertEqual(data["confirm"]["text"], "Hello Bob, we are checking your deposit.")
        self.assertEqual(self.db.replies, [], "nothing may be written before Confirm")
        # the customer's text went to the AI as data inside the prompt
        self.assertIn("I sent 0.1 BTC", self.ai.calls[-1][1])
        r = self.c.post("/api/confirm", json={"token": data["confirm"]["token"], "text": "Edited answer"},
                        headers={"X-CSRF-Token": self.csrf}).get_json()
        self.assertIn("Reply added to ticket #7", r["reply"])
        self.assertEqual(self.db.replies, [(7, "Edited answer")])
        # a token works only once
        r = self.c.post("/api/confirm", json={"token": data["confirm"]["token"]},
                        headers={"X-CSRF-Token": self.csrf}).get_json()
        self.assertIn("expired", r["reply"])

    def test_close_and_cancel(self):
        self.login()
        data = self.chat("close ticket 7")
        self.c.post("/api/cancel", json={"token": data["confirm"]["token"]}, headers={"X-CSRF-Token": self.csrf})
        self.assertEqual(self.db.tickets[7]["state"], "open")
        data = self.chat("close ticket 7")
        self.c.post("/api/confirm", json={"token": data["confirm"]["token"]}, headers={"X-CSRF-Token": self.csrf})
        self.assertEqual(self.db.tickets[7]["state"], "closed")

    def test_email_not_configured_message(self):
        self.login()
        os.environ.pop("IMAP_HOST", None)
        self.assertIn("isn't connected", self.chat("check email")["reply"])

    def test_free_chat_and_status(self):
        self.login()
        self.assertEqual(self.chat("tell me a joke")["reply"], "Hello Bob, we are checking your deposit.")
        parts = [r["cells"][0] for r in self.chat("status")["table"]["rows"]]
        self.assertIn("AI (Ollama)", parts)



class MailboxTests(unittest.TestCase):
    def test_parse_html_mail(self):
        import mail_client as mailbox
        raw = (b"From: Ann <ann@x.io>\r\nSubject: Withdrawal stuck\r\nMessage-ID: <m1@x.io>\r\n"
               b"Content-Type: text/html; charset=utf-8\r\n\r\n<p>Hi,<br>my withdrawal &amp; KYC</p>")
        m = mailbox.Mailbox()._parse("42", raw)
        self.assertEqual((m["uid"], m["subject"], m["message_id"]), ("42", "Withdrawal stuck", "<m1@x.io>"))
        self.assertIn("my withdrawal & KYC", m["body"])

    def test_send_mail_threads_reply(self):
        import mail_client as mailbox
        sent = {}

        class FakeSMTP:
            def __init__(self, host, port, timeout): sent["host"] = (host, port)
            def starttls(self): sent["tls"] = True
            def login(self, u, p): sent["login"] = u
            def send_message(self, msg): sent["msg"] = msg
            def quit(self): pass

        os.environ.update(SMTP_ADDRESS="smtp.mail.io", SMTP_PORT="587", SMTP_USERNAME="me@x.io",
                          SMTP_PASSWORD="pw", SUPPORT_MAIL="support@x.io")
        orig = mailbox.smtplib.SMTP
        mailbox.smtplib.SMTP = FakeSMTP
        try:
            mailbox.send_mail("ann@x.io", "Re: Withdrawal stuck", "We're on it.", "<m1@x.io>", "")
        finally:
            mailbox.smtplib.SMTP = orig
        self.assertTrue(sent["tls"])
        self.assertEqual(sent["msg"]["In-Reply-To"], "<m1@x.io>")
        self.assertEqual(sent["msg"]["From"], "support@x.io")


if __name__ == "__main__":
    unittest.main()
