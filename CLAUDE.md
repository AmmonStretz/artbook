# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the website for **artbook.berlin**, a multi-event art book fair. It manages events, participants (artists/publishers), portfolio images, and event schedules. It is a custom PHP application with no framework.

## Development Setup

**Start the app:**
```bash
docker-compose up
```
App runs at `http://localhost:8000`. Code in `app/` is volume-mounted — no container restart needed for changes.

**Prerequisites:** Create a `.env` file with `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`.

**Database access:** MySQL is on port 3306. See `app/db.php` for connection setup — it reads env vars and falls back to `app/db.local.php` for shared hosting.

**Admin login:** Credentials are in `app/config.php` (`AUTH_USER` / `AUTH_PASS` constants).

## Architecture

### Request Flow

Apache `.htaccess` rewrites language-prefixed URLs (`/de/`, `/en/`) and strips them. All pages are direct PHP files — there is no router or controller layer.

Each public page file:
1. Includes `app/src/bootstrap.php` (sets up DB, Twig, language, session auth check)
2. Queries the database via PDO
3. Renders a Twig template from `app/templates/`

Admin pages include `app/admin/bootstrap.php` which adds session-based authentication enforcement.

### Key Source Files

- [app/src/bootstrap.php](app/src/bootstrap.php) — Initializes config, DB, Twig, and language for public pages
- [app/admin/bootstrap.php](app/admin/bootstrap.php) — Same as above, plus admin auth enforcement
- [app/config.php](app/config.php) — Constants for auth credentials, site URL, upload paths, and image size constraints
- [app/db.php](app/db.php) — PDO factory with UTF-8MB4, exceptions enabled, emulation disabled
- [app/src/twig.php](app/src/twig.php) — Twig environment setup: globals, custom filters, template paths
- [app/src/helpers.php](app/src/helpers.php) — Image URL generation (with resized variants) and formatting utilities
- [app/src/lang.php](app/src/lang.php) — Language detection and translation loading

### Database

No ORM — all queries use prepared PDO statements directly in the page files.

Key tables:
- `veranstaltung` — Events/book fairs
- `veranstaltung_tag` — Event dates/hours
- `teilnehmer` — Participants (artists, publishers, etc.); `kategorie` enum: `kuenstler`, `verlag`, `edition`, `archiv`, `sonstiges`
- `veranstaltung_teilnahme` — Many-to-many: participants ↔ events, includes table assignments
- `teilnehmer_bild` — Portfolio images per participant
- `veranstaltung_programm` / `programm_teilnehmer` — Event schedule entries and their participants
- `bereich`, `tisch` — Event layout zones and tables
- `meta` — App-wide settings (active event, admission period)

### Image Handling

Images are uploaded, validated, and resized server-side using the Sharp Node.js CLI (called via `shell_exec`). Do not bypass or inline image processing — it generates multiple WebP variants at specified widths.

- Participant portraits → `app/uploads/teilnehmer/` — resized to multiple widths (max 1024px)
- Event pattern/banner images → `app/uploads/veranstaltungen/` — 8 WebP variants from 2560px down to 640px
- Upload constraints and size variants are defined as constants in `app/config.php`

### Templating

Twig 3.0. Templates are in `app/templates/`. The `base.twig` provides the HTML shell; admin templates extend `app/templates/admin/base.twig`. Twig cache is disabled in development (Docker).

Custom Twig globals and filters are registered in `app/src/twig.php`. Translation strings come from `app/lang/de.php` and `app/lang/en.php`.

### Admin Section

All admin functionality is in `app/admin/`. The admin section uses AJAX for many operations — partial-page forms submit to `*_ajax.php` files that return JSON or HTML fragments.

## Multilingual Support

The site supports German (`/de/`) and English (`/en/`). Language is detected from the URL prefix. All user-facing strings go through the translation system in `app/lang/`.

## Shared Hosting Compatibility

The app is designed to work on shared hosting (no Docker). `app/db.php` falls back to `app/db.local.php` if env vars are not set. Do not break this fallback.
