# Handover

State of the BNWP WikiConnect v2 theme and connect.bnwp.org, as of
25 September 2026. Written so that someone picking this up — including a
future AI session — does not have to reconstruct the reasoning.

Read `README.md` for how to *use* the site. This file is why it is the way it
is, and where the traps are.

---

## Where things live

| | |
|---|---|
| Theme source | <https://github.com/bnwp/bnwp-wp-theme-v2> — branch `main` |
| v1 theme (superseded) | <https://github.com/bnwp/bnwp-wp-theme> |
| Original static site | <https://github.com/bnwp/bnwp.github.io> — Hugo, source of much migrated content |
| Production | <https://connect.bnwp.org/> — v2, live, indexed |
| Local checkout | `D:\bnwp-wikiconnect\bnwp-wp-theme` |
| Scripts | `D:\bnwp-wikiconnect\build` |
| Credentials | `D:\bnwp-wikiconnect\.wp-credentials` — a WordPress application password, **never** in git |

There is no staging site any more. Changes go to production, so read the
deploy note below before uploading.

---

## Deploying

The live theme directory is **`bnwp-wikiconnect-v2`**, and the zip's root
folder must match that name — otherwise WordPress installs a *second*,
unrelated theme and the live one is untouched. This has happened once; the
symptom is "nothing changed" with no error anywhere.

Upload through **Appearance → Themes → Add New → Upload Theme**, tick *replace
current with uploaded*, then purge LiteSpeed (**Toolbox → Purge All**).
Skipping the purge means an afternoon debugging cached HTML.

Confirm what landed rather than inferring it from the page. The version in
`style.css` is served over the API:

    /?rest_route=/wp/v2/themes&status=active

Bump it on every upload and that check stays honest.

---

## The rule that matters most

**Two code paths write the same data, and they must agree.** Meta is written
by the admin save handler *and* by the REST API, and every time those two have
disagreed, data was destroyed silently:

- `sanitize_text_field` on `_bnwp_body_en` flattened **every** English body on
  the site to one paragraph. Nobody noticed for weeks.
- `esc_url_raw` on the image fields discarded every Commons `File:…` title
  typed into the editor, because `file:` is not an allowed protocol.

Both are fixed by routing both paths through one function —
`bnwp_meta_sanitizer()`, built from `bnwp_richtext_meta_keys()`,
`bnwp_multiline_meta_keys()` and `bnwp_media_meta_keys()`. **Add a new meta key
to those lists, not to a branch in one handler.**

Corollary: after any scripted write, **read the value back**. A REST write that
returns 200 has not necessarily stored what you sent.

---

## Writing content over the REST API

**The database is `utf8`, not `utf8mb4`.** Anything outside the Basic
Multilingual Plane — in practice, emoji — cannot be stored and the write fails
with `rest_meta_database_error`. Bengali is unaffected. Encode emoji as hex
entities (`&#x1f4a1;`), as the existing posts do.

**REST saves a post before its meta.** So `save_post` runs while the old meta
values are still in place. Anything derived from meta must also hook
`added_post_meta` / `updated_post_meta` — `bnwp_sortkey_after_meta()` exists
because every sort key was built with a start date of `00000000` otherwise.

**Cloudflare blocks Python's default User-Agent** with error 1010. Every script
sends a browser UA.

---

## Architecture decisions, and why

### One record holds both languages

Bengali lives in the native title/editor; English lives in `_bnwp_*_en` meta.
Three filters (`the_title`, `the_content`, `get_the_excerpt`) swap them, so
templates call `the_title()` normally. An empty English field falls back to the
Bengali, so a half-translated record is never a blank page.

Chosen over Polylang because the content is short and the team is small: two
records per item doubles the maintenance and drifts. It did drift when it was
two records, which is what prompted the change.

### Language is chosen by URL only

An `/en/` path prefix, nothing else — no cookie, no session, no browser
header. `bnwp_parse_language_prefix()` strips it from `REQUEST_URI` on
`do_parse_request`, so every template, query and rewrite rule sees the ordinary
Bengali URL. `bnwp_lang_arg()` puts it back when building links and is attached
to the permalink filters. Old `?lang=en` URLs 301 to their `/en/` equivalent.

**Yoast knows nothing about `/en/`.** It builds canonical, `og:url` and
`<title>` from the raw post object, so without `bnwp_yoast_url()` and
`bnwp_yoast_title()` every English page canonicalises to its Bengali twin and
asks Google not to index itself. Yoast's sitemap is Bengali-only too, hence
`/en-sitemap.xml`, registered via `bnwp_en_sitemap_body()` — note that Yoast
fires that as an *action* and reads the XML back off its own object; returning
a string does nothing.

### Status is derived, not chosen

There is no status dropdown. `bnwp_project_status()` reads the **Timeline**
against today's date: upcoming before the start, ongoing between the dates
inclusive, completed after the end, nothing without dates. A dropdown was a
second source of truth and went stale — a contest that ended on 31 August was
still advertising itself as চলমান four weeks later.

Because the answer moves with the calendar, so does the sort key
(`_bnwp_sortkey`). It is rebuilt on save and daily on cron
(`bnwp_refresh_sortkeys`). The key encodes the whole ordering rule in one
descending pass: ongoing first (earliest start, so longest-running, at the top),
then upcoming (soonest first), then finished (most recent first). The two
ascending groups store `99999999 - start` to reverse inside a descending sort.

### Crediting people

`_bnwp_organisers` and `_bnwp_jury` are one person per line. A bare wiki
username resolves to that team member; `username | bn description | en
description` overrides what they did *on this project*; a longer line is a
guest with no record on the site. `bnwp_people_entries()` tells them apart by
whether the first field names somebody. A username that matches nobody renders
as plain text rather than vanishing, so typos are visible.

A team member's standing role is deliberately **not** shown on a project — what
somebody does on a contest is rarely their job title.

### Images

`bnwp_commons_url()` accepts a Commons file-page URL or a bare `File:Name.jpg`
title and derives the path from `md5(filename)`. Commons **only serves widths
120, 250, 500, 960, 1280, 1920** and returns 400 for anything else;
`bnwp_commons_width()` snaps to the next allowed size. **Do not remove this** —
every Commons image on the site was broken before it existed.

Cover images live in the **body text**, not a meta field, so the writer chooses
where they sit and captions them per language. They are `[caption]` shortcodes
referencing a Commons thumbnail by URL. Two consequences: the URL is literal
text, so a file renamed on Commons breaks it; and WordPress writes the pixel
width inline on the figure, which is overridden in CSS — a bare `auto` grid
track will otherwise size to it and drag the page sideways on a phone.

Every external image carries an `onerror` fallback, because Commons files do
get deleted. One did, mid-project.

### Two CSS traps specific to this site

- **Bengali needs headroom.** The ই-কার paints well above the font's declared
  ascent — 21px against 17px at a 22px size. Any element combining
  `overflow: hidden` with a line-height under about **1.4** will crop it. Latin
  tolerates 1.1, which is why it is easy to miss.
- **`.brand__name` is shared** by the header and the footer. Rules written for
  one must be scoped, or they silently affect the other.

### Content that is editable without code

**Appearance → Customise** holds the whole front page (Home page text), Impact
numbers, Partners and Social channels. Every row is `Bengali | English`; leave
the English half empty and it falls back. Icon SVG paths stay in the theme
deliberately — nobody should edit path data in a textarea.

Impact numbers take a **plain number** and scale per language: English short
scale (K/M/B), Bengali South Asian (হাজার/লক্ষ/কোটি). A lakh is 10⁵, so the two
cannot share a divisor. `+` is appended automatically.

The navigation is **one menu** with an English label per item, so the two
languages cannot drift. They did, when there were two menu locations: only the
Bengali menu existed and the English side was quietly rendering a hard-coded
list from `functions.php`. Leave *Primary Menu (English override)* unassigned.

---

## Blocked on the host

**GD and ImageMagick are both unavailable**, so WordPress cannot generate any
thumbnail sizes. Uploads are served at full size. The host previously reported
`nd_mysqli, pdo_mysql skipped as conflicting` — that is a MySQL driver notice
and has nothing to do with GD. What to ask for: *enable the `gd` PHP extension
(or `imagick`) for this site's PHP version.*

`upload_max_filesize` is **2 MB**. Worth raising to 32 MB.

**Database errors are papered over, not fixed.** "Error establishing a database
connection" used to hit roughly one request in four. LiteSpeed Cache removed it
by keeping most requests away from the database; the weakness underneath is
untouched, so a purge at a busy moment could still show it. MariaDB 10.6.28 is
flagged as outdated.

---

## Known gaps

- **Organisers are empty on all ten projects.** Jury is filled where the wiki
  page listed reviewers; the wiki credits Bangla WikiConnect collectively as
  organiser rather than naming people, so this needs someone who knows.
- **Wikiquote 2025 has no jury** — its wiki page does not list reviewers in the
  `{{U|…}}` form the others use.
- **`hello-world` has English in its Bengali fields**, so Bengali readers get an
  English page — the reverse of the usual gap.
- **Two posts store raw Markdown** (`bangla-wikipedia`,
  `quote-contest-2025-started`): their Bengali bodies render literal `###` and
  `**`. Their English translations are clean HTML, so only Bengali looks wrong.
- Two people exist in the Hugo repo but were never migrated: **Khattab** and
  **আফতাবুজ্জামান**.
- Three people still have no biography in either language.
- Nav menu items are root-relative custom links (`/about/`). The theme rewrites
  them at render time so they work; proper post-type menu items would be tidier.

---

## Plugins

Nine, all active: **Yoast SEO**, **LiteSpeed Cache**, **UpdraftPlus**,
**Akismet**, **Advanced Editor Tools**, **Wordfence**, **WP Staging**,
**Redirect Redirection**, **Web Accessibility**.

**Advanced Editor Tools** matters more than it looks: projects and team members
use the classic editor, and it supplies the formatting controls.

**Wordfence is active but unlicensed**, so it is not scanning. Finish it at
**Wordfence → Install**; the free licence needs an email address.

**Redirect Redirection** is not needed for the language URLs — the theme does
those redirects. Check whether it holds other rules before removing it.

### Removed, and why

**Elementor** and **Ultimate Addons** — nothing used them; every page, post,
project and person was checked. **Custom Fonts** — unused; the theme loads Tiro
Bangla and Anek Bangla from Google itself. **Hello Dolly**, **WordPress
Importer** — a joke plugin and a finished one-off. **Jetpack** — its entire
front-end contribution was the `stats.wp.com` pixel, duplicating the Cloudflare
beacon already loading, and its Photon proxy rewrote zero images because the
heavy ones are Commons hotlinks it will not touch.

That took the homepage from 66 KB to 58 KB and left the front end loading
nothing from `wp-content/plugins`.

---

## Scripts

In `D:\bnwp-wikiconnect\build`. All read `.wp-credentials`, send a browser
User-Agent, default to a dry run and take `--apply` to write. All are
idempotent and verify by reading back.

| Script | What it does |
|---|---|
| `refresh-sortkeys.py` | Re-saves every project so `_bnwp_sortkey` picks up a changed ordering rule. **Run after any theme change that touches ordering.** |
| `add-2025-projects.py` | Created the four 2025 contests and wrote timelines onto all projects. |
| `fix-tense-add-2026.py` | Put the 2025 records into the present tense; created Wikivoyage 2026. |
| `cover-into-content.py` | Moved cover images out of meta into the body, resolving Commons titles to 960px thumbnails. |
| `captions-to-shortcode.py` | Converted hand-written `<figure>` blocks to `[caption]` shortcodes. |
| `migrate-paired.py` | Folded the old `-en` twin records into paired fields. |
| `translate-projects.py`, `import-en-personas.py` | Original English content imports. |
| `localise-logos.py` | Pulled Commons logos into the media library with attribution. |
| `make-privacy.py`, `make-en-pages.py` | Created the privacy/cookie pages and English page twins. |
| `sync-to-production.py` | Staging→production delta sync. Obsolete; staging is gone. |

---

## Licensing

GPL-2.0-or-later. The repository was created with an MIT `LICENSE` by mistake;
that was corrected, because v2 derives from the GPL v1 theme and cannot be
relicensed. See `COPYRIGHT.md`.

Images from Wikimedia Commons are mostly **CC BY-SA** and require attribution.
Captions in the body carry the author, licence and a link to the Commons file
page. Keep doing this — it is a licence condition, not a nicety.
