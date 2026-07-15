#!/usr/bin/env python3
"""
psql_to_mysql.py

Convert a PostgreSQL *data-only* pg_dump (COPY ... FROM stdin; text format)
into a MySQL/MariaDB population script for the migrated Laravel schema of the
TMI archive project.

Only 5 source tables are converted into their Laravel-migrated counterparts:

    auth_user            -> users
    talks_talk           -> talks
    talks_playlist       -> playlists
    talks_playlisttalk   -> playlist_talk
    talks_talkmetric     -> talk_metrics

The generated file (database/data/tmi-archive-data.sql) can be loaded into a
freshly-migrated Laravel MySQL database. It DELETEs existing rows and re-INSERTs
in FK-safe order with FOREIGN_KEY_CHECKS disabled, so it is idempotent.

Stdlib only. Run:  python3 tools/psql_to_mysql.py
"""

import os
import re
from datetime import datetime, timedelta

SRC = "/var/www/html/db-dump.psql"
OUT = "/var/www/html/database/data/tmi-archive-data.sql"

BATCH = 500  # rows per multi-row INSERT statement


# --------------------------------------------------------------------------
# COPY text-format decoding
# --------------------------------------------------------------------------

# Map of backslash escapes used by PostgreSQL COPY text format.
_ESCAPES = {
    "t": "\t",
    "n": "\n",
    "r": "\r",
    "\\": "\\",
    "b": "\b",
    "f": "\f",
    "v": "\v",
}


def decode_field(field):
    """Decode a single COPY text-format field.

    Returns None for the SQL NULL sentinel (\\N), otherwise the decoded string
    (an empty field decodes to an empty string).
    """
    if field == "\\N":
        return None
    out = []
    i = 0
    n = len(field)
    while i < n:
        ch = field[i]
        if ch == "\\" and i + 1 < n:
            nxt = field[i + 1]
            if nxt in _ESCAPES:
                out.append(_ESCAPES[nxt])
                i += 2
                continue
            # Unknown escape: keep next char literally (COPY passes it through).
            out.append(nxt)
            i += 2
            continue
        out.append(ch)
        i += 1
    return "".join(out)


def parse_copy_block(lines, start_idx):
    """Given the file lines and the index of a 'COPY ... FROM stdin;' line,
    return (list_of_decoded_rows, index_after_terminator).

    Each decoded row is a list of fields (str or None).
    """
    rows = []
    i = start_idx + 1
    while i < len(lines):
        line = lines[i]
        if line == "\\.":
            i += 1
            break
        # Split on TAB, then decode each field.
        rows.append([decode_field(f) for f in line.split("\t")])
        i += 1
    return rows, i


def read_tables(path):
    """Read the dump and return {table_name: [rows]} for the 5 tables we need."""
    wanted = {
        "auth_user",
        "talks_talk",
        "talks_playlist",
        "talks_playlisttalk",
        "talks_talkmetric",
    }
    with open(path, "r", encoding="utf-8", newline="") as fh:
        # Read the whole file; strip only the trailing newline of each line so
        # that escaped content (already \n-escaped) is preserved intact.
        lines = fh.read().split("\n")

    tables = {}
    copy_re = re.compile(r"^COPY public\.(\w+) \(.*\) FROM stdin;$")
    i = 0
    while i < len(lines):
        m = copy_re.match(lines[i])
        if m and m.group(1) in wanted:
            name = m.group(1)
            rows, i = parse_copy_block(lines, i)
            tables[name] = rows
        else:
            i += 1
    return tables


# --------------------------------------------------------------------------
# Value transformations
# --------------------------------------------------------------------------

_TS_RE = re.compile(
    r"^(\d{4})-(\d{2})-(\d{2}) "
    r"(\d{2}):(\d{2}):(\d{2})(?:\.(\d+))?"
    r"([+-]\d{2})(?::(\d{2}))?$"
)


def to_utc_timestamp(val):
    """Convert a tz-aware Postgres timestamp string to a UTC
    'YYYY-MM-DD HH:MM:SS' string (microseconds dropped). None -> None."""
    if val is None:
        return None
    m = _TS_RE.match(val)
    if not m:
        raise ValueError("Unrecognised timestamp: %r" % val)
    year, mon, day, hh, mm, ss, _frac, off_h, off_m = m.groups()
    dt = datetime(int(year), int(mon), int(day), int(hh), int(mm), int(ss))
    off_min = int(off_h) * 60
    if off_m:
        # Preserve sign of the hour part for the minutes component.
        off_min += (int(off_m) if off_h.startswith("+") else -int(off_m))
    # Local = UTC + offset  =>  UTC = Local - offset
    dt_utc = dt - timedelta(minutes=off_min)
    return dt_utc.strftime("%Y-%m-%d %H:%M:%S")


def to_date(val):
    """Pass through a YYYY-MM-DD date. None -> None."""
    if val is None:
        return None
    return val


def interval_to_seconds(val):
    """Convert a Postgres interval 'HH:MM:SS.ffffff' to whole seconds (rounded).
    None -> None."""
    if val is None:
        return None
    parts = val.split(":")
    if len(parts) != 3:
        raise ValueError("Unrecognised interval: %r" % val)
    hours = int(parts[0])
    minutes = int(parts[1])
    seconds = float(parts[2])
    total = hours * 3600 + minutes * 60 + seconds
    return int(round(total))


def empty_to_null(val):
    """Map empty string to None (SQL NULL); keep other values as-is."""
    if val is None or val == "":
        return None
    return val


def bool_true(val):
    """Return True if a Postgres boolean field is 't'."""
    return val == "t"


# --------------------------------------------------------------------------
# SQL literal emission
# --------------------------------------------------------------------------

def sql_str(val):
    """Emit a MySQL string literal (or NULL) for a str/None value."""
    if val is None:
        return "NULL"
    escaped = val.replace("\\", "\\\\").replace("'", "\\'")
    return "'" + escaped + "'"


def sql_int(val):
    """Emit an integer literal (or NULL). Accepts int, numeric str, or None."""
    if val is None:
        return "NULL"
    return str(int(val))


# --------------------------------------------------------------------------
# Row builders: source row -> list of SQL literals in TARGET column order
# --------------------------------------------------------------------------

def build_user(r):
    # auth_user columns:
    # 0 id, 1 password, 2 last_login, 3 is_superuser, 4 username,
    # 5 first_name, 6 last_name, 7 email, 8 is_staff, 9 is_active, 10 date_joined
    is_admin = 1 if (bool_true(r[8]) or bool_true(r[3])) else 0
    date_joined = to_utc_timestamp(r[10])
    return [
        sql_int(r[0]),                       # id
        sql_str(r[4]),                       # name = username
        sql_str(empty_to_null(r[7])),        # email (empty -> NULL)
        "NULL",                              # email_verified_at
        sql_str(r[1]),                       # password
        str(is_admin),                       # is_admin
        sql_str(empty_to_null(r[5])),        # first_name (empty -> NULL)
        sql_str(empty_to_null(r[6])),        # last_name (empty -> NULL)
        sql_str(to_utc_timestamp(r[2])),     # last_login
        "NULL",                              # remember_token
        sql_str(date_joined),                # created_at
        sql_str(date_joined),                # updated_at
    ]


def build_talk(r):
    # talks_talk columns:
    # 0 id, 1 title, 2 description, 3 audio_original, 4 audio_cleaned,
    # 5 created_at, 6 updated_at, 7 created_by_id, 8 updated_by_id,
    # 9 transcription, 10 original_file_name, 11 recorded_date,
    # 12 whisper_transcription, 13 audio_length
    return [
        sql_int(r[0]),                            # id
        sql_str(r[1]),                            # title
        sql_str(r[2]),                            # description
        sql_str(empty_to_null(r[3])),             # audio_original (empty->NULL)
        sql_str(empty_to_null(r[4])),             # audio_cleaned  (empty->NULL)
        sql_str(to_date(r[11])),                  # recorded_date
        sql_str(r[10]),                           # original_file_name
        sql_str(r[9]),                            # transcription
        sql_str(r[12]),                           # whisper_transcription
        sql_int(interval_to_seconds(r[13])),      # audio_length (seconds)
        sql_int(r[7]),                            # created_by_id
        sql_int(r[8]),                            # updated_by_id
        sql_str(to_utc_timestamp(r[5])),          # created_at
        sql_str(to_utc_timestamp(r[6])),          # updated_at
    ]


def build_playlist(r):
    # talks_playlist columns:
    # 0 id, 1 title, 2 description, 3 first_recording_date, 4 created_at,
    # 5 created_by_id, 6 updated_at, 7 updated_by_id
    return [
        sql_int(r[0]),                        # id
        sql_str(r[1]),                        # title
        sql_str(r[2]),                        # description
        sql_str(to_date(r[3])),               # first_recording_date
        sql_int(r[5]),                        # created_by_id
        sql_int(r[7]),                        # updated_by_id
        sql_str(to_utc_timestamp(r[4])),      # created_at
        sql_str(to_utc_timestamp(r[6])),      # updated_at
    ]


def build_playlist_talk(r):
    # talks_playlisttalk columns:
    # 0 id, 1 order, 2 playlist_id, 3 talk_id, 4 created_at, 5 updated_at
    return [
        sql_int(r[0]),                        # id
        sql_int(r[2]),                        # playlist_id
        sql_int(r[3]),                        # talk_id
        sql_int(r[1]),                        # position = order
        sql_str(to_utc_timestamp(r[4])),      # created_at
        sql_str(to_utc_timestamp(r[5])),      # updated_at
    ]


def build_talk_metric(r):
    # talks_talkmetric columns:
    # 0 id, 1 created_at, 2 metric_type, 3 user_id, 4 talk_id, 5 ip
    return [
        sql_int(r[0]),                        # id
        sql_int(r[4]),                        # talk_id
        sql_int(r[3]),                        # user_id
        sql_int(r[2]),                        # metric_type
        sql_str(r[5]),                        # ip
        "NULL",                               # user_agent
        "0",                                  # is_bot
        sql_str(to_utc_timestamp(r[1])),      # created_at
    ]


# Target table definitions: (table, column-list, builder, source-name)
TARGETS = [
    (
        "users",
        ["id", "name", "email", "email_verified_at", "password", "is_admin",
         "first_name", "last_name", "last_login", "remember_token",
         "created_at", "updated_at"],
        build_user,
        "auth_user",
    ),
    (
        "talks",
        ["id", "title", "description", "audio_original", "audio_cleaned",
         "recorded_date", "original_file_name", "transcription",
         "whisper_transcription", "audio_length", "created_by_id",
         "updated_by_id", "created_at", "updated_at"],
        build_talk,
        "talks_talk",
    ),
    (
        "playlists",
        ["id", "title", "description", "first_recording_date", "created_by_id",
         "updated_by_id", "created_at", "updated_at"],
        build_playlist,
        "talks_playlist",
    ),
    (
        "playlist_talk",
        ["id", "playlist_id", "talk_id", "position", "created_at",
         "updated_at"],
        build_playlist_talk,
        "talks_playlisttalk",
    ),
    (
        "talk_metrics",
        ["id", "talk_id", "user_id", "metric_type", "ip", "user_agent",
         "is_bot", "created_at"],
        build_talk_metric,
        "talks_talkmetric",
    ),
]


HEADER = """-- ---------------------------------------------------------------------------
-- tmi-archive-data.sql
--
-- Data population script for the TMI archive Laravel application (MySQL /
-- MariaDB). Generated by tools/psql_to_mysql.py from a PostgreSQL data-only
-- pg_dump of the legacy Django database.
--
-- Load this into a database whose schema has already been created by the
-- Laravel migrations (tables: users, talks, playlists, playlist_talk,
-- talk_metrics). It DELETEs any existing rows and re-INSERTs the converted
-- data in FK-safe order with FOREIGN_KEY_CHECKS disabled, so it is safe to
-- run repeatedly (idempotent).
--
-- DO NOT EDIT BY HAND -- regenerate with: python3 tools/psql_to_mysql.py
-- ---------------------------------------------------------------------------
"""


def emit_table(out, table, columns, builder, rows):
    """Write DELETE + batched multi-row INSERTs for one table."""
    collist = ", ".join("`%s`" % c for c in columns)
    out.write("\n--\n-- Data for table `%s` (%d rows)\n--\n" % (table, len(rows)))
    out.write("DELETE FROM `%s`;\n" % table)
    if not rows:
        return
    for start in range(0, len(rows), BATCH):
        chunk = rows[start:start + BATCH]
        out.write("INSERT INTO `%s` (%s) VALUES\n" % (table, collist))
        values = []
        for r in chunk:
            values.append("(" + ",".join(builder(r)) + ")")
        out.write(",\n".join(values))
        out.write(";\n")


def main():
    tables = read_tables(SRC)

    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    with open(OUT, "w", encoding="utf-8") as out:
        out.write(HEADER)
        out.write("\nSET FOREIGN_KEY_CHECKS=0;\n")
        out.write("SET NAMES utf8mb4;\n")
        # All timestamp values in this file are already converted to UTC. Force
        # the session time zone to UTC so MySQL/MariaDB interprets the literals
        # as UTC and does not reject DST spring-forward gaps (e.g. local
        # 02:xx during the March transition, which does not exist in a DST zone).
        out.write("SET time_zone = '+00:00';\n")

        for table, columns, builder, src_name in TARGETS:
            rows = tables.get(src_name, [])
            emit_table(out, table, columns, builder, rows)

        out.write("\nSET FOREIGN_KEY_CHECKS=1;\n")

    # Report counts to stdout for the operator.
    for table, _c, _b, src_name in TARGETS:
        print("%-16s %6d rows" % (table, len(tables.get(src_name, []))))
    print("Wrote %s" % OUT)


if __name__ == "__main__":
    main()
