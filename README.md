# BNWP WikiConnect WordPress Theme

A Bengali-first WordPress theme for the Bangla WikiConnect community website.

![Theme preview](assets/uploads/bnwp-theme.png)

## Features

- Bengali and English views
- Project and team-member content types
- Team taxonomy and archive pages
- Media Library selectors for project and member images
- Responsive Bootstrap 5 layout
- Light and dark modes

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

Copy the theme into:

```text
wp-content/themes/bnwp-wikiconnect/
```

Activate **BNWP WikiConnect** from **Appearance → Themes**, then save **Settings → Permalinks** once to refresh archive routes.

For the local XAMPP installation used during development, the active copy is:

```text
C:\xampp\htdocs\bnwp\wp-content\themes\bnwp-wikiconnect
```

## Content

The theme registers:

| Type | WordPress key | Archive |
|---|---|---|
| Projects | `project` | `/projects/` |
| Team members | `persona` | `/persona/` |
| Teams | `team` | `/teams/{slug}/` |

Important team slugs are `cot`, `technical`, and `jury`.

### Project fields

- `_bnwp_logo`
- `_bnwp_cover`
- `_bnwp_wiki`
- `_bnwp_lead`
- `_bnwp_language`

### Team-member fields

- `_bnwp_name`
- `_bnwp_role`
- `_bnwp_username`
- `_bnwp_location`
- `_bnwp_email`
- `_bnwp_img`
- `_bnwp_bio`
- `_bnwp_language`

Image fields accept Media Library images or direct external image URLs.

## Languages

Bengali is the default. Add `?lang=en` for English views.

Projects, team members, posts, and pages need separate Bengali and English records. English translations use `_bnwp_language = en` and normally use an `-en` slug, for example:

```text
/about/       → /about-en/?lang=en
/projects/wlc/ → /projects/wlc-en/?lang=en
```

Theme interface labels and dates switch automatically. Bengali dates use Bengali month names and numerals.

## Main files

| File | Purpose |
|---|---|
| `functions.php` | Theme setup, content types, metadata, languages, and assets |
| `front-page.php` | Homepage |
| `header.php` / `footer.php` | Shared site layout |
| `archive-*.php` | Project and member listings |
| `single-*.php` | Project and member details |
| `style.css` | Theme metadata and custom styling |

## Development checks

Run PHP syntax checks before committing:

```powershell
Get-ChildItem -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
```

Do not commit a full WordPress installation, database files, `wp-config.php`, uploads, credentials, or `.git` directories from the XAMPP site.

## License

GPL-2.0-or-later.
