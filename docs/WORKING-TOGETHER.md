# Working on Moka with two people

Two developers, one staging server. The code side is straightforward; the
deploy side needs an agreement, because the server has a single checkout on a
single branch and whoever deploys last wins.

## The short version

1. Never work directly on `main`, and never deploy a feature branch.
2. Branch → push → pull request → merge to `main`.
3. Staging always runs `main`: `sudo bash scripts/deploy.sh --branch main`.
4. Say in chat before you deploy. One at a time.

## Branches

Work on a branch named for who you are and what you are doing:

    git checkout main
    git pull origin main
    git checkout -b john/ezee-auto-assign

Push it and open a pull request. The other person reviews and it merges into
`main`. Small and frequent beats large and occasional — two people editing the
same 1,000-line Blade template a week apart is where the painful conflicts come
from.

## Deploying

Staging deploys `main` and nothing else:

    cd /var/www/moka
    sudo bash scripts/deploy.sh --branch main

Deploying your own branch works, and it is exactly how the two of you end up
overwriting each other: the server is then running your branch, not `main`, and
the next person's deploy silently reverts your work. If you need to see a branch
on the server before merging, tell the other person first and put it back
afterwards.

The script refuses to run when the server's working tree is dirty, reports the
commits it pulled, and reloads PHP-FPM gracefully — in-flight requests finish,
so a deploy is invisible to owners using the site.

Migrations are the exception. They are opt-in:

    sudo bash scripts/deploy.sh --branch main --migrate

A deploy that needed migrations and did not get them fails later, in someone
else's browser, as a missing-column error. The script tells you when migrations
are pending; do not ignore that line.

## Using Claude Code

Both of you can run Claude Code against this repository at the same time. The
sessions know nothing about each other, so the discipline is the same as any
two developers sharing a repo:

- Start each session by telling it which branch to work on.
- Keep to your own area where you can. Two sessions editing the same file will
  conflict, and neither will know until the second one pushes.
- Pull before you start: `git checkout main && git pull origin main`.
- Read what it changed before pushing. It commits and pushes when asked, and
  those commits go to a branch the other person will read.

## Server access

Both accounts can SSH to `moka-staging` and both have `sudo`. That means either
of you can deploy, restart services, read `.env`, and reach the database.

`.env` holds the EZEE auth keys and the database password. Treat the server as
production-adjacent: the data in it came from the live system, including real
owner and guest records.

## What not to do on the server

- Do not edit files directly in `/var/www/moka`. The next deploy will refuse to
  run (dirty tree) or overwrite the change. Edit locally, push, deploy.
- Do not run `git checkout` or `git reset --hard` there to look at something.
  Whatever you leave behind is what the site serves.
- Do not run migrations casually. The database holds imported production data.

## If a deploy goes wrong

Find the commit that was live before and deploy it:

    cd /var/www/moka
    git log --oneline -5
    sudo git checkout <previous-commit>
    php artisan view:clear && php artisan route:clear
    sudo systemctl reload php8.2-fpm

That leaves the checkout detached, which is fine as an emergency measure. Fix
`main` properly, then `sudo bash scripts/deploy.sh --branch main` to get back
onto a branch.
