#!/usr/bin/env python3
"""
Count the rows each INSERT in a MySQL dump would create, per table.

Compare against the live database to confirm an import captured everything.
Handles phpMyAdmin's extended-insert format, where one statement carries many
tuples:

    INSERT INTO `t` (a, b) VALUES (1,'x'),(2,'y'),(3,'z');

String literals are collapsed first so the tuple scan never has to reason about
commas, parentheses or escaped quotes appearing inside values. Collapsing with
a regex keeps the work in C rather than a per-character Python loop, which
matters on dumps of any size.

    python3 scripts/dump_row_counts.py dump.sql [table ...]
"""

import sys
import re

INSERT_RE = re.compile(r'INSERT\s+INTO\s+`([^`]+)`[^;]*?VALUES', re.IGNORECASE)
# Single- or double-quoted literal, honouring backslash escapes.
STRING_RE = re.compile(r"'(?:[^'\\]|\\.)*'|\"(?:[^\"\\]|\\.)*\"", re.DOTALL)


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        return 1

    path = sys.argv[1]
    wanted = set(sys.argv[2:])

    with open(path, encoding='utf-8', errors='replace') as fh:
        text = fh.read()

    # Collapse every string literal to '' so what remains is pure structure.
    text = STRING_RE.sub("''", text)

    counts = {}
    for m in INSERT_RE.finditer(text):
        table = m.group(1)
        if wanted and table not in wanted:
            continue

        # Walk from VALUES to the statement's semicolon, counting top-level
        # groups. No quotes survive the collapse, so depth alone is enough.
        depth = 0
        rows = 0
        i = m.end()
        n = len(text)
        while i < n:
            c = text[i]
            if c == '(':
                depth += 1
            elif c == ')':
                depth -= 1
                if depth == 0:
                    rows += 1
            elif c == ';' and depth == 0:
                break
            i += 1

        counts[table] = counts.get(table, 0) + rows

    if not counts:
        print('no matching INSERT statements found')
        return 1

    width = max(len(t) for t in counts)
    for table in sorted(counts):
        print('%-*s %d' % (width, table, counts[table]))
    return 0


if __name__ == '__main__':
    sys.exit(main())
