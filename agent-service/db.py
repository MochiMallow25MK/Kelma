import os
from decimal import Decimal
import pymysql

def _connect():
    return pymysql.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", "3306")),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASS", "Mk2005$#"),
        database=os.getenv("DB_NAME", "kelma"),
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True,
    )

def query(sql, params=()):
    """Run a SELECT and return a list of dicts (Decimals become floats)."""
    conn = _connect()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            rows = cur.fetchall()
    finally:
        conn.close()
    return [{k: float(v) if isinstance(v, Decimal) else v for k, v in r.items()} for r in rows]


def execute(sql, params=()):
    """Run an INSERT/UPDATE."""
    conn = _connect()
    try:
        with conn.cursor() as cur:
            cur.execute(sql, params)
    finally:
        conn.close()
