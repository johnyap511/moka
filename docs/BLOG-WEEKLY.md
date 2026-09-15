# Weekly blog drafts (news roundup + feature)

Read this whole file before drafting. It is the contract between the Monday drafting routine and the
person who approves and publishes. Drafts that ignore it get rejected.

## Purpose
MOKA (homemoka.com) manages short-stay and monthly-rental units for property owners in Malaysia and runs
Innspace, a renovation business (panel renovator for SkyWorld and Solution+). The blog exists to rank on
Google for owner, developer, agent and agency searches and to show that MOKA follows the industry closely.
Readers are unit owners, property investors, agents, developers and building managers, mostly in Malaysia.

## What to produce each week
Two posts, delivered as one pull request on a branch named `blog/drafts-YYYY-MM-DD` (the run date).

1. **Roundup**: "Short-stay news, Malaysia and Southeast Asia: week of D Month YYYY".
   5 to 7 stories from the past 7 days. Each story: bold one-line headline, 2 to 4 sentences of what happened
   with the figures, then one sentence starting "For owners:" on what it means. 600 to 900 words. Topic `News`.
2. **Feature**: one article on the week's most useful story for owners. 800 to 1,100 words, 4 to 6 h2 sections,
   one table or list where it helps, a "What to do now" section at the end. Topic `News`, or `Regulations`,
   `Hosting`, `Renovation` if it fits better.

If the week is genuinely quiet, write the roundup with what exists and make the feature an evergreen
explainer tied to the quietest real story. Never pad with rumours.

## Sources
Search the past 7 days only. Prefer, in this order:
- Malaysia: Bernama, The Star, New Straits Times, The Edge, Malay Mail, Free Malaysia Today, The Sun, Sinar
  Harian (BM is fine), Tourism Malaysia (tourism.gov.my), state tourism boards, KPKT, MOTAC, city councils
  (DBKL, MBPP, MBSP, MBPJ, MBJB, DBKK), Airbnb newsroom.
- Region: Channel NewsAsia, The Straits Times, Bangkok Post, The Jakarta Post, VnExpress, Philippine Daily
  Inquirer, Nikkei Asia, Skift.
- Data: AirDNA, AirROI, STR Global, Tourism Malaysia statistics, DOSM.
Do not use content farms or SEO blogs as the only source for a fact (Travel And Tour World, World of Buzz,
brand blogs). They can point you to a story; confirm it in a primary source before using the figure.

Every figure, date, fee or rule in a post must come from a source you actually opened. List the sources at
the end of the article body in a `<h2>Sources</h2>` followed by a `<ul>` of links with the outlet name and
date. If you cannot source a claim, cut it. Never invent quotes. If a rule is proposed, say "proposed"; if it
is gazetted, say when it came into force.

## Voice
Plain, direct, owner-to-owner. Short paragraphs. No hype, no exclamation marks, no "game-changer",
"crucial", "delve", "landscape", "robust". British spelling (licence, organisation, metres). Ringgit as
RM1,824.47. Dates as 1 November 2026. Name the council or ministry, not "authorities" alone. Where MOKA has a
view, say "we" and give the view in one sentence. Do not sell in the body; the CTA fields do that.

Never copy sentences from sources. Summarise in your own words; quote at most one short phrase per story
in quotation marks with the outlet named.

## How a post is stored
The blog is file-based. A post is one Blade view plus one entry in `config/blog.php`.

View: `resources/views/blog/posts/<slug>.blade.php`
```blade
@extends('blog.article')

@section('article')
    <p class="blog-lede">One-paragraph lede, 2 to 4 sentences.</p>

    <h2>Section heading</h2>
    <p>Body.</p>

    <div class="blog-callout"><p>Optional highlighted note or MOKA view.</p></div>

    <div class="blog-table-wrap">
        <table class="blog-table">
            <thead><tr><th>Col</th><th>Col</th></tr></thead>
            <tbody><tr><td>..</td><td>..</td></tr></tbody>
        </table>
    </div>

    <h2>Sources</h2>
    <ul>
        <li><a href="https://..." rel="noopener" target="_blank">Outlet, D Month YYYY: headline</a></li>
    </ul>
@endsection
```
Allowed markup: p, h2, h3, ul, ol, li, strong, em, a, the table block above, `.blog-callout`, `.blog-lede`.
No inline styles, no images (the template adds figures itself), no scripts.

Config entry, added at the TOP of the `'posts'` array in `config/blog.php` (newest first):
```php
[
    'slug'        => 'kebab-case-slug-with-year',
    'view'        => 'same-as-slug',
    'title'       => 'Up to 60 characters | MOKA',
    'heading'     => 'On-page H1, can be longer and more natural',
    'description' => '140 to 160 characters, the meta description and card excerpt',
    'published'   => 'YYYY-MM-DD',
    'updated'     => 'YYYY-MM-DD',
    'image'       => 'new-theme23/images/projects/<folder>/<n>.jpg',
    'topic'       => 'News',
    'read_time'   => 4,
    'cta_heading' => 'Short question to the reader',
    'cta_body'    => 'One or two sentences on what MOKA can do about it.',
    'cta_label'   => 'Button text',
    'cta_url'     => '/get/estimate',
],
```
Images: every post has a cover ('image') and two in-article figures ('figures', a list of [path, caption]).
**No photo may be used twice across the whole blog**, as a cover or a figure. Before choosing, list every
'image' and 'figures' path already in config/blog.php and exclude them. Choose from:
- Stock library `public/new-theme23/images/blog/` (Unsplash-licensed, provenance in manifest.txt there): city,
  travel, event, keys, planning and renovation subjects. Use the base name with .jpg, e.g.
  `new-theme23/images/blog/kk-beach-sunset.jpg`.
- Project photos `public/new-theme23/images/projects/<folder>/<n>.jpg` (folders the-valley, skyvogue,
  skyawani-4, skyawani-5, curvo), captioned "A MOKA-managed unit at ..." or "Innspace interior at ...".
Every path you use must have .webp, -thumb.jpg and -thumb.webp beside it (all library and project photos do).
Do not download new photos in the routine. If nothing unused fits the story, use the closest unused photo and
write "Needs a new photo: <subject>" under "Check before publishing" in the PR so a person adds one.
Config entry with figures:
```php
    'image'       => 'new-theme23/images/blog/kl-skyline-night.jpg',
    'figures'     => [
        ['new-theme23/images/blog/singapore-marina-bay.jpg', 'Caption in plain words'],
        ['new-theme23/images/projects/skyawani-5/2.jpg', 'A MOKA-managed unit at Sky Awani 5'],
    ],
```
CTA URLs: `/get/estimate` for owners, `/contact` for developers and agents, `/designs` for renovation.

Slugs must be unique; check the existing entries. Run `php -l` on the view and on config/blog.php before
committing.

## Pull request
Branch `blog/drafts-YYYY-MM-DD` from `main`. One commit: "Blog drafts: <roundup date> roundup, <feature
title>". PR title "Blog drafts for week of D Month YYYY". PR body: for each post, the title, a two-line
summary, the sources used, and any fact you were unsure about under a heading "Check before publishing".
Do not merge. Do not deploy. Do not touch any other file.

## Publishing (done by a person with the moka-blog-publish skill)
Merge the approved posts into main, deploy, submit the URLs to Google Search Console, share the links.
