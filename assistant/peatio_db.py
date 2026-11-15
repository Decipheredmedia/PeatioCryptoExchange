"""Read (and carefully write) Peatio's MySQL database.

Every query uses placeholders (%s) so customer text can never change the SQL.
The assistant logs in as a restricted MySQL user created by install.sh: it can
read everything, but may only add ticket replies and change a ticket's state.
"""
import datetime
import os


def _env(key, default=""):
    value = os.environ.get(key, "")
    return value if value.strip() else default


class PeatioDB:
    def __init__(self):
        password = _env("ASSISTANT_DB_PASSWORD")
        self.cfg = dict(
            host=_env("DB_HOST", "db"),
            port=int(_env("DB_PORT", "3306")),
            database=_env("DB_NAME", "peatio_production"),
            # Fall back to the main Peatio user if no restricted user was made.
            user=_env("ASSISTANT_DB_USER", "assistant") if password else _env("DB_USER", "peatio"),
            password=password or _env("DB_PASSWORD"),
        )
        self.admin_email = _env("ADMIN_EMAIL", "admin@peatio.info")

    # -- plumbing ---------------------------------------------------------
    def _connect(self):
        import pymysql  # imported lazily so tests run without it

        return pymysql.connect(
            host=self.cfg["host"], port=self.cfg["port"], user=self.cfg["user"],
            password=self.cfg["password"], database=self.cfg["database"],
            charset="utf8", autocommit=True, connect_timeout=5,
            cursorclass=pymysql.cursors.DictCursor,
        )

    def query(self, sql, args=()):
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                cur.execute(sql, args)
                return list(cur.fetchall())
        finally:
            conn.close()

    def execute(self, sql, args=()):
        conn = self._connect()
        try:
            with conn.cursor() as cur:
                return cur.execute(sql, args)
        finally:
            conn.close()

    # -- health -----------------------------------------------------------
    def ping(self):
        return self.query("SELECT 1 AS ok")[0]["ok"] == 1

    # -- numbers ----------------------------------------------------------
    def stats(self):
        now = datetime.datetime.utcnow()
        day, week = now - datetime.timedelta(days=1), now - datetime.timedelta(days=7)

        def one(sql, args=()):
            row = self.query(sql, args)[0]
            return int(list(row.values())[0] or 0)

        return {
            "members_total": one("SELECT COUNT(*) FROM members"),
            "members_new_7d": one("SELECT COUNT(*) FROM members WHERE created_at >= %s", (week,)),
            "open_orders": one("SELECT COUNT(*) FROM orders WHERE state = 100"),
            "trades_24h": one("SELECT COUNT(*) FROM trades WHERE created_at >= %s", (day,)),
            "open_tickets": one("SELECT COUNT(*) FROM tickets WHERE aasm_state = 'open'"),
            "withdraws_waiting": one(
                "SELECT COUNT(*) FROM withdraws WHERE aasm_state IN ('submitted','accepted','suspect')"),
            "deposits_24h": one("SELECT COUNT(*) FROM deposits WHERE created_at >= %s", (day,)),
        }

    # -- tickets ----------------------------------------------------------
    def list_tickets(self, state="open", limit=20):
        return self.query(
            "SELECT t.id, t.title, t.content, t.aasm_state AS state, t.created_at, "
            "m.email AS author_email, "
            "(SELECT COUNT(*) FROM comments c WHERE c.ticket_id = t.id) AS replies "
            "FROM tickets t LEFT JOIN members m ON m.id = t.author_id "
            "WHERE t.aasm_state = %s ORDER BY t.created_at DESC LIMIT %s",
            (state, int(limit)),
        )

    def get_ticket(self, ticket_id):
        rows = self.query(
            "SELECT t.id, t.title, t.content, t.aasm_state AS state, t.created_at, "
            "t.author_id, m.email AS author_email "
            "FROM tickets t LEFT JOIN members m ON m.id = t.author_id WHERE t.id = %s",
            (int(ticket_id),),
        )
        if not rows:
            return None
        ticket = rows[0]
        ticket["comments"] = self.query(
            "SELECT c.id, c.content, c.created_at, c.author_id, m.email AS author_email "
            "FROM comments c LEFT JOIN members m ON m.id = c.author_id "
            "WHERE c.ticket_id = %s ORDER BY c.created_at ASC",
            (int(ticket_id),),
        )
        return ticket

    def admin_member_id(self):
        rows = self.query("SELECT id FROM members WHERE email = %s", (self.admin_email,))
        if not rows:
            raise RuntimeError(
                f"No member with e-mail {self.admin_email} - check ADMIN_EMAIL in .env")
        return rows[0]["id"]

    def add_ticket_reply(self, ticket_id, text):
        now = datetime.datetime.utcnow()
        self.execute(
            "INSERT INTO comments (content, author_id, ticket_id, created_at, updated_at) "
            "VALUES (%s, %s, %s, %s, %s)",
            (text, self.admin_member_id(), int(ticket_id), now, now),
        )

    def set_ticket_state(self, ticket_id, state):
        assert state in ("open", "closed")
        return self.execute(
            "UPDATE tickets SET aasm_state = %s, updated_at = %s WHERE id = %s",
            (state, datetime.datetime.utcnow(), int(ticket_id)),
        )
