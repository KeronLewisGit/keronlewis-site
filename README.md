# keronlewis.com

Portfolio and web résumé for Keron Lewis, built on Laravel 13 with Blade, Vite and plain CSS/JavaScript (no front-end framework).

## Run it locally

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate   # first time only
php artisan migrate
npm run build        # or `npm run dev` while editing CSS/JS
php artisan serve    # http://localhost:8000
```

`php artisan test` runs the feature tests.

## Editing content

Everything the site says lives in **`config/portfolio.php`**: profile, projects, experience, skills, education. The home page, `/resume`, the PDF, the vCard and `/resume.json` all read from that one file.

- `profile.seo` holds the page titles and descriptions search engines show; `services` holds the "Web development services" cards and the page each one has at `/services/{slug}`; its shape is described above `services` in the config file.
- A role can carry an `ended` block with a `from` date; from that day its values replace the role's own (Label House is set to become "Apr 2025 – Nov 2026" on 16 November 2026). Remove the block to cancel it.
- A role's `stack` entries must match names under `skills`; that is what links the skill chips to roles (a test checks this).
- A project with a `case_study` block gets its own page at `/work/{slug}`, linked from its card, the quick search and the sitemap. The block's shape is described above `projects` in the config file.
- A project with a `testimonial` block (`quote`, `name`, optional `role`) shows the client's words on its card and its case study. Only add quotes clients have really given you.
- Project screenshots are `public/img/work/{slug}.webp`. Refresh them with `php artisan portfolio:screenshots [slug] --force` (needs Node and Google Chrome on the machine you run it on).

## Routes

| Path | What it is |
| --- | --- |
| `/` | Portfolio: work, experience, skills, about, contact form |
| `/services/{slug}` | One page per service, written for search (title, summary, sections, related work) |
| `/work/{slug}` | Case study for a project that has a `case_study` block |
| `/book` | Book a call. The hours, call length and days off are set at `/admin/bookings` |
| `/testimonial/{token}` | Private page where an invited client writes a testimonial |
| `/resume` | Interactive résumé with a print stylesheet |
| `/resume.pdf` | PDF download (dompdf, template in `resources/views/resume/pdf.blade.php`) |
| `/resume.json` | Résumé in [JSON Resume](https://jsonresume.org) format |
| `/keron-lewis.vcf` | Contact card |
| `/sitemap.xml`, `/robots.txt` | Generated from `APP_URL` |
| `/privacy` | Privacy page |
| `/admin` | Private area: analytics, messages, testimonials and bookings (see below) |

## Contact form

Messages are validated, saved to the `contact_messages` table, then emailed to `CONTACT_TO`. If mail fails the message is still saved. Read, reply to and delete messages at `/admin/messages`; `php artisan portfolio:inbox` lists them in the terminal.

Out of the box `MAIL_MAILER=log`, so emails only go to `storage/logs/laravel.log`. Set real SMTP details in `.env` before going live.

A sender gets a short automatic reply saying the message arrived. When the topic is a project, the form also asks for a budget range and a timeline; the choices are `contact_budgets` and `contact_timelines` in `config/portfolio.php`.

## Testimonials

At `/admin/testimonials`, create a private link for a client and send it to them. What they write waits there until you approve it. The name and the project or company shown beside it are the ones you enter, and **Edit** lets you correct the wording and choose phrases to highlight: each is marked in the text and the first is set large above it. Long testimonials are cut to a few lines with a "Read more" button. Every approved testimonial shows under "What clients say" on the home page, and the newest three show on each service page. One whose project matches a portfolio project also shows on that project's case study.

## Booking a call

`/book` offers the times you set at `/admin/bookings`: which days, from when to when, how long a call lasts, how much notice you need and any days off. It stays closed until you switch it on there. A booking emails you and the person who booked; cancelling one from the admin emails them too.

## Share images

`php artisan portfolio:share-images` redraws the image shown when a page is shared (`public/og-cover.png`, plus one per service and case study in `public/img/share`). Run it after changing a service title or a case-study title; it needs Node and Google Chrome, like the screenshots command.

## Admin and Google Analytics

`/admin` is a private area showing Google Analytics for the site: visitors, sessions, page views, a visitors-per-day chart, top pages, traffic sources, countries, devices, and counts of résumé downloads, contact-card saves, project previews and contact messages.

1. Create your login: `php artisan portfolio:admin` (run it again to reset the password).
2. Sign in at `/admin` and open **Connection**. The page lists the steps: a GA4 property, its Measurement ID and Property ID, and a Google Cloud service-account key with Viewer access to the property.
3. Saving tests the connection first; a key or Property ID that doesn't work is not stored.

Once a Measurement ID is saved, visitors are asked whether to allow analytics. Google's script is loaded only after they agree (never for you while signed in), a browser Global Privacy Control or Do Not Track signal counts as a no, and "Cookie settings" in the footer lets them change their mind. The dashboard therefore counts only visitors who agreed. `/privacy` explains all of this and adjusts itself to whether analytics is on. The service-account key is stored encrypted in the `settings` table using `APP_KEY`, so changing `APP_KEY` means reconnecting. Reports are cached for ten minutes; **Refresh** fetches them again.

## Deploying to Hostinger

The site on the server is a git clone, so the usual way to publish is one command:

```sh
scripts/deploy.sh "what changed"
```

It builds the assets, runs the tests, commits and pushes, then over SSH pulls the change, installs dependencies, runs migrations and clears the caches. Copy `.deploy.env.example` to `.deploy.env` and fill in the server's SSH address first.

The zip packaging below is the older way and still works for a first install.

`scripts/package-hostinger.sh` builds a zip laid out the way Hostinger's Laravel guide describes: the whole project inside `public_html`, with a root `.htaccess` that sends every request into `public/`.

```sh
scripts/package-hostinger.sh           # first install  -> ~/Downloads/keronlewis-hostinger-install.zip
scripts/package-hostinger.sh update    # later releases -> ~/Downloads/keronlewis-hostinger-update.zip
```

The install zip includes production dependencies, built assets, a production `.env` with a fresh `APP_KEY`, and an empty migrated SQLite database, so nothing has to be run on the server for the site to work. The update zip leaves out `.env` and the database so extracting it over the top keeps your messages, admin login and Analytics connection.

First install:

1. In hPanel, check the site's PHP version is **8.3 or newer** (PHP Configuration). `composer.json` pins dependency resolution to PHP 8.3 (`config.platform.php`) so the same package runs on Hostinger's default, on the web and over SSH.
2. Make sure SSL is active and HTTPS is forced. The session cookie is HTTPS-only, so the contact form and admin login won't work over plain http.
3. In File Manager, empty `public_html` (the old `index.html`, `default.php` and so on), upload the zip there and extract it into `public_html` itself.
4. Check `https://your-domain/.env` returns "403 Forbidden", then delete the zip from the server.
5. Over SSH, in `public_html`: `php artisan portfolio:admin` to create the admin login.
6. To send contact-form email, edit `.env` in File Manager: set `MAIL_MAILER=smtp` and fill in the mailbox lines.

After an update zip, run `php artisan migrate --force` over SSH if the release added migrations.

### Backups

`php artisan portfolio:backup` saves a dated copy of the SQLite database (messages, the admin login and the Analytics connection) and keeps the newest 14. By default the copies go to `storage/app/backups`; on the server, pass `--to=` a folder outside `public_html` so they survive a redeploy, and run it daily from a cron job:

```sh
/usr/bin/php ~/domains/keronlewis.dev/public_html/artisan portfolio:backup --to=$HOME/backups/keronlewis-site
```

To restore, copy a backup over `database/database.sqlite`.

### Daily reminder

`php artisan portfolio:digest` emails you when messages have sat unread for a day, testimonials are waiting for approval, or calls are booked for the next 24 hours. It sends nothing when there is nothing waiting. Run it from a second daily cron job:

```sh
/usr/bin/php ~/domains/keronlewis.dev/public_html/artisan portfolio:digest
```
