#!/usr/bin/env python3
"""
Count the rows each INSERT in a MySQL dump would create, per table.

Compare against the live database to confirm an import captured everything.
Written for phpMyAdmin's extended-insert format, where one statement carries
many tuples:

    INSERT INTO `t` (a, b) VALUES (1,'x'),(2,'y'),(3,'z');

Counting tuples needs a real scan rather than a regex, because parentheses,
commas and escaped quotes all appear inside string values.

    python3 scripts/dump_row_counts.py dump.sql [table ...]
"""

import sys
import re

INSERT_RE = re.compile(r'INSERT\s+INTO\s+`([^`]+)`', re.IGNORECASE)


def count_tuples(text, start):
    """Count top-level (...) groups from `start` until the statement ends."""
    i, n = start, len(text)
    depth = 0
    rows = 0
    in_str = False
    quote = ''
    while i < n:
        c = text[i]
        if in_str:
            if c == '\\':
                i += 2                      # escaped char, skip both
                continue
            if c == quote:
                in_str = False
        elif c in ("'", '"'):
            in_str = True
            quote = c
        elif c == '(':
            depth += 1
        elif c == ')':
            depth -= 1
            if depth == 0:
                rows += 1
        elif c == ';' and depth == 0:
            break
        i += 1
    return rows, i


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        return 1

    path = sys.argv[1]
    wanted = set(sys.argv[2:])

    with open(path, encoding='utf-8', errors='replace') as fh:
        text = fh.read()

    counts = {}
    for m in INSERT_RE.finditer(text):
        table = m.group(1)
        if wanted and table not in wanted:
            continue
        vpos = text.upper().find('VALUES', m.end())
        if vpos == -1:
            continue
        rows, _ = count_tuples(text, vpos)
        counts[table] = counts.get(table, 0) + rows

    for table in sorted(counts):
        print("%-30s %d" % (table, counts[table]))
    return 0


if __name__ == '__main__':
    sys.exit(main())
