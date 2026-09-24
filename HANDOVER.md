# Handover

State of the BNWP WikiConnect v2 theme and the connect.bnwp.org site, as of
23 September 2026. Written so that someone picking this up — including a
future AI session — does not have to reconstruct the reasoning.

---

## Where things live

| | |
|---|---|
| Theme source | <https://github.com/bnwp/bnwp-wp-theme-v2> — branch `main` |
| v1 theme (superseded) | <https://github.com/bnwp/bnwp-wp-theme> — still running on production |
| Original static site | <https://github.com/bnwp/bnwp.github.io> — Hugo, source of much migrated content |
| Production | <https://connect.bnwp.org/> — **still on v1 (theme v1.1.0)** |
| Staging | <https://connect.bnwp.org/intrepid/> — **v2, login-gated, `noindex`** |
| Local checkout | `D:\bnwp-wikiconnect\bnwp-wp-theme` |

Staging was made with the **WP Staging** plugin. It is login-protected; the
switch to make it publicly viewable is **WP Staging → Settings →
`disableAdminLogin`**. It stays `noindex, nofollow` either way.

---

## The big outstanding decision: production is still on v1

v2 has never been deployed to production. Everything described here was built
and verified on staging. Going live means uploading the theme zip through
**Appearance → Themes → Add New → Upload Theme** and activating it, then
re-saving permalinks.

**Content does not transfer automatically.** The staging database is a
separate copy. All the content work below was done on staging; repeating it on
production means either re-running the scripts in `D:\bnwp-wikiconnect\build\`
against the production URL, or migrating the staging database wholesale. The
scripts are written to be idempotent and safe to re-run.

---

## Blocked on the host

Three things need the hosting provider. None can be fixed in the theme.

### 1. No image library — this is the important one

```
ImageMagick : Not available
GD          : Not available
```

WordPress therefore **cannot generate any thumbnails**. Every upload is served
at its original size and the browser does all the downscaling, which is why
logos looked soft. Verified by uploading a test image: zero intermediate sizes
were generated.

The host reported `nd_mysqli, pdo_mysql skipped as conflicting` and skipped the
image extension. **That message is about MySQL drivers and has nothing to do
with GD.** It appears whenever cPanel's PHP extension list is saved and it
keeps the existing MySQL driver. Enabling `gd` does not touch mysqli.

What to ask for, precisely:

> Please enable the **`gd`** PHP extension for our PHP 8.4 install (cPanel:
> Select PHP Version → Extensions → tick `gd`). The `nd_mysqli` / `pdo_mysql`
> notice is unrelated — those are database drivers and can be left as they are.
> If `gd` is genuinely unavailable, `imagick` would also work.

**Workaround in place until then:** upload images at the size they will be
shown. Wikimedia Commons can resize, so project logos were re-fetched at 250px.
`build/localise-logos.py` does this.

### 2. Intermittent database errors

"Error establishing a database connection" appears on **production as well as
staging**, roughly one request in four at times. Site Health also reports the
SQL server as outdated (MariaDB 10.6.28).

Ask the host to check the MySQL connection limit and server load, and to
confirm whether the version can be raised.

### 3. Small limits

`upload_max_filesize` is **2 MB**. Worth raising to 32 MB.

---

## Architecture decisions, and why

### One record holds both languages

Content used to be two posts — `yahya` and `yahya-en` — linked by a slug
convention. That was replaced with **paired fields**: one record whose native
title/body/excerpt hold the Bengali, and `_bnwp_*_en` meta holding the English.

Chosen over Polylang because the content is short (a project description is the
longest thing on the site), the volume is small (5 projects, 14 people), and
halving the number of records matters more to a volunteer team than a nicer
translation UI. Revisit if a third language is ever added — Polylang would then
be the right answer.

Reading happens through three filters in `functions.php`:
`bnwp_filter_title()`, `bnwp_filter_content()`, `bnwp_filter_excerpt()`. **An
empty English field falls back to the Bengali**, so a half-translated record is
never a blank page. Templates call `the_title()` and `the_content()` as normal.

The old `-en` records still exist as **drafts**. They were the safety net for
the migration. Delete them once you are confident.

### Language is chosen by URL only

A `/en/` path prefix. `bnwp_current_language()` reads nothing else — no
cookie, no session, no browser header.

`bnwp_parse_language_prefix()` runs on `do_parse_request` and strips the prefix
from `REQUEST_URI` before WordPress parses it, so every template, query and
rewrite rule sees the ordinary Bengali URL. `bnwp_lang_arg()` puts the prefix
back when building links, and it is attached to the permalink filters
(`post_link`, `term_link`, and the rest) so links stay in the reader's language
without each template asking.

The older `?lang=en` addresses 301-redirect to their `/en/` equivalent
(`bnwp_redirect_legacy_lang()`), so anything already linked or indexed keeps
working. Google lists URL parameters as *"Not recommended"* for multilingual
sites, which is why this changed.

Two things depend on the prefix and are easy to break: Yoast builds canonical,
`og:url` and `<title>` from the raw post object and knows nothing about `/en/`,
so `bnwp_yoast_url()` and `bnwp_yoast_title()` correct them — without those,
every English page canonicalises to its Bengali twin and asks Google not to
index it. And Yoast's sitemap lists only Bengali URLs, so the theme registers a
second one at `/en-sitemap.xml` and adds it to Yoast's index.

### Images

`bnwp_commons_thumb()` rewrites Wikimedia Commons URLs to thumbnails. Commons
**only serves a fixed set of widths** — `120, 250, 500, 960, 1280, 1920` —
and returns 400 for anything else. This was verified against four files; the
set is global. `bnwp_commons_width()` snaps any request up to the next allowed
size. Do not remove this.

`bnwp_commons_url()` also accepts a Commons file page URL or a bare
`File:Name.jpg` title and computes the upload path from `md5(filename)`.

Every external image carries an `onerror` fallback, because Commons files do
get deleted — MS Sakib's photo was, mid-project.

### Content that is editable without code

**Appearance → Customise** holds Impact numbers, Partners and Social channels.
Each is one row per line; the current values are the defaults, so an untouched
site looks unchanged. Icon SVG paths stay in the theme deliberately — nobody
should edit path data in a textarea.

Impact numbers take a **plain number** and scale it per language: English short
scale (K/M/B), Bengali South Asian scale (হাজার/লক্ষ/কোটি). A lakh is 10⁵, so
the two cannot share a divisor. `+` is appended automatically.

---

## Known gaps

- **`_bnwp_status` is empty on all five projects**, so no ongoing/completed
  chips appear. Set it per project.
- **Organisers and Jury are empty on all five projects.** The fields exist
  (multi-select, stored by wiki username so one setting serves both languages).
- Two people exist in the Hugo repo but were never migrated to WordPress:
  **Khattab** and **আফতাবুজ্জামান**.
- Three of the five people who lacked English still have no biography in
  either language — only a name and role.
- Production's nav menu items are stored as **root-relative custom links**
  (`/about/`). The theme rewrites them through `home_url()` at render time so
  they work anyway, but converting them to proper post-type menu items would be
  cleaner.

---

## Plugins

Active and worth keeping: **Yoast SEO**, **UpdraftPlus**, **Akismet**,
**Advanced Editor Tools**, **WordPress Importer**, **WP Staging** (until the
staging site is retired), **Ally**.

Inactive and safe to delete: **Elementor**, **Ultimate Addons for Elementor**,
**Hello Dolly**. The v2 theme uses none of them.

**LiteSpeed Cache** is now active and doing the heavy lifting — it took TTFB
from about a second to 120–270ms and stopped the intermittent "Error
establishing a database connection". Purge it after any theme change, or you
will spend an afternoon debugging cached HTML: **LiteSpeed Cache → Toolbox →
Purge All**.

**Wordfence** is installed but still switched off.

**Redirect Redirection** is not needed for the language URLs — the theme
handles the `?lang=en` redirects itself.

---

## Scripts

In `D:\bnwp-wikiconnect\build\`. All read credentials from
`D:\bnwp-wikiconnect\.wp-credentials` (a WordPress application password —
**not** in git) and target the staging URL; change `SITE` to point elsewhere.

| Script | What it does |
|---|---|
| `migrate-paired.py` | Folds `-en` records into paired fields. Dry-run by default; `--apply` writes. Idempotent. |
| `translate-projects.py` | Writes the English project translations. |
| `localise-logos.py` | Pulls Commons logos into the media library with attribution. |
| `import-en-personas.py` | Imported English profiles from the Hugo repo. |
| `make-privacy.py`, `make-en-pages.py` | Created the privacy pages and English page twins. |

All Cloudflare-fronted requests need a browser `User-Agent`; the default
Python one is blocked with error 1010.

---

## Licensing

GPL-2.0-or-later. The repository was created with an MIT `LICENSE` by mistake;
that was corrected, because v2 derives from the GPL v1 theme and cannot be
relicensed. See `COPYRIGHT.md`.

Images from Wikimedia Commons are mostly **CC BY-SA** and require attribution.
Each uploaded file carries its author and licence in the attachment caption and
a link to its Commons page in the description. Keep doing this.
