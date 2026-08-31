#!/usr/bin/env python3
"""
Turn the mirrored production HTML into Blade templates.

The mirror is what wget saved, so every link points at a local .html file and
every asset at a relative path. Both get rewritten: links to the routes the app
already defines, assets to asset() so they resolve wherever the app is hosted.

Head, nav and footer are byte-identical across the mirrored pages, so they are
lifted into a shared layout and each page keeps only its own content.
"""
import io
import os
import re

MIRROR = 'storage/prod-mirror'
OUT = 'resources/views/auth/newTheme'

# wget rewrote in-site links to local files; map them back to routes.
LINKS = [
    (r'\.\./www\.homemoka\.com/index\.html', '/'),
    (r'\.\./homemoka\.com/', '/'),
    (r'\bindex\.html', '/'),
    (r'\bhomepage\.html', '/homepage'),
    (r'\bservice\.html', '/service'),
    (r'\bdesigns\.html', '/designs'),
    (r'\babout\.html', '/about'),
    (r'\bcontact\.html', '/contact'),
    (r'\bpolicy\.html', '/policy'),
    (r'\bterms\.html', '/terms'),
    (r'get/estimate\.html', '/get/estimate'),
    (r'location/search\.html', '/location/search'),
    (r'[./]*login/social/(?:google|facebook)\.html', '/login'),
    (r'\bestimate\.html', '/get/estimate'),
]

ASSET_DIRS = ('new-theme23', 'new-theme', 'images', 'css', 'js', 'plugins')


def rewrite(html: str) -> str:
    # Absolute references to the live site would keep staging pointing at
    # production, so they are stripped to root-relative first.
    html = re.sub(r'https?://(www\.)?homemoka\.com/', '/', html)

    # Pages nested a directory down (get/estimate) reference assets as ../
    html = re.sub(r'\.\./(?=(?:%s)[\\\\/])' % '|'.join(ASSET_DIRS), '/', html)

    # The mirror is rendered output, so every form carries the CSRF token
    # that was valid at crawl time. Left in place it never matches the
    # visitor's session and every POST fails with 419.
    html = re.sub(r'<input[^>]*name="_token"[^>]*>', '@csrf', html)

    for pattern, target in LINKS:
        html = re.sub(pattern, target, html)

    # //foo collapses to /foo after the prefix rewrites above.
    html = re.sub(r'(?<=["\'])//(?=[a-z])', '/', html)

    # Assets go through asset() so they follow APP_URL rather than assuming
    # the document lives at the web root.
    def to_asset(m):
        quote, path = m.group(1), m.group(2).lstrip('/')
        path = path.replace('\\', '/').replace('&#32;', '%20')
        if not path.startswith(ASSET_DIRS):
            return m.group(0)
        return "%s{{ asset('%s') }}%s" % (quote, path, quote)

    html = re.sub(r'(["\'])(/?(?:%s)[\\\\/][^"\']*)\1' % '|'.join(ASSET_DIRS), to_asset, html)
    return html


def read(path: str) -> list:
    with io.open(os.path.join(MIRROR, path), encoding='utf-8', errors='replace') as fh:
        return fh.read().split('\n')


def find(lines, needle, start=0):
    for i in range(start, len(lines)):
        if needle in lines[i]:
            return i
    raise LookupError(needle)


def write(relpath: str, body: str):
    full = os.path.join(OUT, relpath)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    with io.open(full, 'w', encoding='utf-8') as fh:
        fh.write(body)
    print('  wrote %s (%d lines)' % (full, body.count('\n') + 1))


home = read('www.homemoka.com/index.html')
head_end = find(home, '</head>')
nav_start = find(home, '<header')
nav_end = find(home, '</header>')
foot_start = find(home, '<footer>')
foot_end = find(home, '</footer>')
body_end = find(home, '</body>')

# ---- shared layout -------------------------------------------------------
head = rewrite('\n'.join(home[:head_end]))
# Scripts sit on the same line as </footer>; keep only what follows it.
tail_first = home[foot_end].split('</footer>', 1)[1]
tail = rewrite('\n'.join([tail_first] + home[foot_end + 1:body_end]))

write('layout.blade.php', '\n'.join([
    head,
    '',
    "    {{-- Page-specific styles --}}",
    "    @stack('styles')",
    '</head>',
    '<body>',
    '<div class="site-wrap">',
    '',
    "    @yield('content')",
    '',
    "    @sectionMissing('hide-footer')",
    "        @include('auth.newTheme.partials.footer')",
    '    @endif',
    tail,
    '',
    "@stack('scripts')",
    '</body>',
    '</html>',
    '']))

write('partials/header.blade.php', rewrite('\n'.join(home[nav_start:nav_end + 1])) + '\n')
write('partials/footer.blade.php', rewrite('\n'.join(home[foot_start:foot_end]).rsplit('</footer>', 1)[0] + '</footer>') + '\n')

# ---- pages ---------------------------------------------------------------
PAGES = [
    ('www.homemoka.com/index.html', 'home.blade.php', 'home-banner-outer'),
    ('homemoka.com/homepage.html', 'homepage.blade.php', 'home-banner-outer'),
    ('homemoka.com/about.html', 'about.blade.php', 'about-banner-outer'),
    ('homemoka.com/service.html', 'service.blade.php', 'service-banner-outer'),
    ('homemoka.com/designs.html', 'design23.blade.php', 'design-banner-outer'),
    ('homemoka.com/get/estimate.html', 'estimate.blade.php', 'estimate-banner-outer'),
]

for source, target, banner in PAGES:
    lines = read(source)
    nav_open = find(lines, 'banner-outer')
    nav_close = find(lines, '</header>')
    try:
        end = find(lines, '<footer')
        has_footer = True
    except LookupError:
        # No footer on this page; content runs to the first external script.
        end = next(i for i, l in enumerate(lines) if '<script src=' in l)
        has_footer = False

    body = rewrite('\n'.join(lines[nav_close + 1:end]))

    write(target, '\n'.join([
        "@extends('auth.newTheme.layout')",
        '',
    ] + ([] if has_footer else ["@section('hide-footer', true)", '']) + [
        "@section('content')",
        rewrite(lines[nav_open]),
        "    @include('auth.newTheme.partials.header')",
        body,
        '@endsection',
        '']))

print('\ndone')
