# Maneuvrez Maintenance Studio SVN Mapping Plan

This document prepares the WordPress.org SVN layout for `maneuvrez-maintenance-studio` without creating any SVN directories or commits.

## Target SVN Layout

```text
svn-root/
├── assets/
│   ├── banner-772x250.png
│   ├── banner-1544x500.png
│   ├── icon-128x128.png
│   ├── icon-256x256.png
│   └── icon.svg
├── trunk/
│   └── plugin files
└── tags/
    └── 1.0.2/
        └── plugin files
```

## Source To SVN Mapping

### SVN `/assets`

These files should come from the ignored local `media/` directory and should not be moved into the plugin runtime package.

| Local source | SVN destination | Required | Notes |
|---|---|---:|---|
| `media/banner-772x250.png` | `assets/banner-772x250.png` | Yes | Standard banner |
| `media/banner-1544x500.png` | `assets/banner-1544x500.png` | Yes | Retina banner |
| `media/icon-128x128.png` | `assets/icon-128x128.png` | Yes | Standard icon |
| `media/icon-256x256.png` | `assets/icon-256x256.png` | Yes | Retina icon |
| `media/icon.svg` | `assets/icon.svg` | Yes | Vector icon |
| `media/icon-512x512.png` | Not used | No | Optional source only |
| `media/banner.svg` | Not used | No | Optional source only |

### SVN `/trunk`

These are the releasable plugin files confirmed by the packaging pass.

| Local source | SVN destination |
|---|---|
| `maneuvrez-maintenance-studio.php` | `trunk/maneuvrez-maintenance-studio.php` |
| `readme.txt` | `trunk/readme.txt` |
| `uninstall.php` | `trunk/uninstall.php` |
| `LICENSE` | `trunk/LICENSE` |
| `admin/` | `trunk/admin/` |
| `assets/` | `trunk/assets/` |
| `includes/` | `trunk/includes/` |
| `languages/` | `trunk/languages/` |
| `templates/` | `trunk/templates/` |

### SVN `/tags/1.0.2`

`tags/1.0.2/` should match the contents of `trunk/` for the 1.0.2 release snapshot.

## Files That Must Stay Out Of The Plugin Package

These should not go into SVN `trunk/` or the release ZIP.

- `.git/`
- `.github/`
- `.agents/`
- `.codex/`
- `docs/`
- `specs/`
- `media/`
- `public/`
- `README.md`
- `build-zip.sh`
- `.distignore`
- `.gitignore`
- `package.json`
- `package-lock.json`
- `composer.json`
- `composer.lock`
- `phpcs.xml.dist`
- `phpunit.xml.dist`
- `node_modules/`
- `vendor/`
- `src/`
- `tools/`
- `tests/`
- `*.log`
- `*.zip`

## Current Release Assumptions

- Plugin slug and text domain: `maneuvrez-maintenance-studio`
- Main plugin file: `maneuvrez-maintenance-studio.php`
- Stable tag: `1.0.2`
- Author branding kept as `Maneuvrez` for now

## Pre-SVN Checklist

- Confirm `readme.txt` still matches the intended release version.
- Confirm `maneuvrez-maintenance-studio.php` remains the final plugin entry filename.
- Confirm the ignored `media/` assets are the files to copy into SVN `/assets`.
- Build and inspect a clean release ZIP before copying files into SVN `trunk/`.
- Copy plugin runtime files into `trunk/`, not the whole repo root.
- Copy only the five required listing assets into SVN `/assets`.
- Create `tags/1.0.2/` only after `trunk/` is final for that release.

## Safe Local Commands

These commands are safe for preparing the copy plan locally and do not create SVN commits.

```bash
php -l maneuvrez-maintenance-studio.php
find media -maxdepth 1 -type f -printf '%f\t%s bytes\n' | sort
file media/*
bash build-zip.sh
unzip -l ../maneuvrez-maintenance-studio.zip
npm run release:svn
```

`npm run release:svn` copies the approved runtime files into the local WordPress.org SVN checkout at `/home/hurairah/work/wordpress/svn/maneuvrez-maintenance-studio`, creates the matching `tags/1.0.2/` working-copy snapshot, refreshes listing assets from `media/` when present, and stops before commit. After it finishes, continue from the SVN checkout with:

```bash
cd /home/hurairah/work/wordpress/svn/maneuvrez-maintenance-studio
svn status
svn diff
svn commit -m "Release 1.0.2"
```
