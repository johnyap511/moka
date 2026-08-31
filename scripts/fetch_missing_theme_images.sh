#!/bin/bash
# Fetch theme images the mirror could not reach.
#
# Production references these with Windows backslash separators
# (new-theme23\images\Asset 1.png). Browsers treat that as a path
# separator; wget did not, so it never followed them and they are absent
# from the mirror. They are fetched here by their real URLs.
#
# Run from the project root on a host that can reach production.
set -u

BASE="https://www.homemoka.com"
DEST="public/new-theme23/images"
mkdir -p "$DEST"

ok=0; fail=0
while IFS= read -r f; do
    [ -z "$f" ] && continue
    if [ -f "$DEST/$f" ]; then continue; fi
    # --get with --data-urlencode would re-encode the path; let curl do it.
    if curl -sSfL -A "Mozilla/5.0" --path-as-is -o "$DEST/$f" \
            "$BASE/new-theme23/images/$(printf %s "$f" | sed "s/ /%20/g")"; then
        ok=$((ok+1))
    else
        rm -f "$DEST/$f"
        echo "  could not fetch: $f"
        fail=$((fail+1))
    fi
    sleep 0.3
done <<'EOF'
1.png
2.png
3.png
4.png
5.png
Asset 1.png
Asset 10.png
Asset 2.png
Asset 3.png
Asset 4.png
Asset 44.png
Asset 45.png
Asset 47.png
Asset 48.png
Asset 5.png
Asset 6.png
Asset 7.png
Asset 8.png
Asset 9.png
assets 8.png
master bedroom 1.jpg
master bedroom 2.jpg
master bedroom 3.jpg
master bedroom 4.jpg
master bedroom 5.jpg
master bedroom 6.jpg
master bedroom 7.jpg
master bedroom 9.jpg
master bedroom.png
EOF

echo "fetched $ok, failed $fail"
