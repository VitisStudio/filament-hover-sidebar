# Changelog

All notable changes to `filament-hover-sidebar` will be documented in this file.

## v0.1.2 - Plugin Directory Fix - 2026-09-01

Documentation only. The plugin's runtime behaviour, API and CSS are unchanged — v0.1.1 and v0.1.2 install and behave identically.

#### Fixed

- **The README hero no longer double-renders on the Filament plugin directory.** The directory shows its own hero from the plugin submission, so the README banner appeared a second time directly beneath it. It is now wrapped in an anchor carrying `filament-hidden`, the convention the established directory plugins use — GitHub ignores the class and renders the image, filamentphp.com strips the element. The banner is HTML rather than Markdown as a result, since `![]()` has nowhere to put a class.
  
- **The hero artwork now meets the directory's image spec.** It was 1672×941, which failed `16:9, at least 2560×1440` on both counts: under the size floor, and 1.77683 rather than an exact 1.77778. It is now 2560×1440 JPEG at exact 16:9.
  

### What's Changed

* Hide the README hero from the Filament plugin directory by @acepoblete in https://github.com/VitisStudio/filament-hover-sidebar/pull/2

**Full Changelog**: https://github.com/VitisStudio/filament-hover-sidebar/compare/v0.1.1...v0.1.2

## v0.1.1 - CI Fix and README Artwork - 2026-09-01

Housekeeping release. The plugin's runtime behaviour, API and CSS are unchanged — v0.1.0 and v0.1.1 install and behave identically.

#### Fixed

- **Dropped a private Composer repository from `composer.json`.** It pointed at `packages.filamentphp.com`, which returns HTTP 401 on any machine without credentials for it. Composer fetches a `type: composer` repository's `packages.json` before resolving anything, so every CI job died at the install step. Nothing needed it — all ten `filament/*` packages and `blade-phosphor-icons` resolve from public Packagist.
  
  This did not affect installing the package: Composer only honours `repositories` declared by the *root* package, so a dependency's declaration is ignored. The damage was confined to this repository's own test, PHPStan and code-style workflows, which could not run at all. Landed as a direct commit (0075af4) and so is absent from the generated list below.
  
- **README artwork now renders.** The hero and both screenshots were committed but never referenced from the README. The CHANGELOG, licence and contributors links were also relative, which broke them anywhere the README renders outside the repository — the Filament plugin directory and Packagist included. All are absolute URLs now.
  

### What's Changed

* Show the artwork in the README and fix its off-GitHub links by @acepoblete in https://github.com/VitisStudio/filament-hover-sidebar/pull/1

### New Contributors

* @acepoblete made their first contribution in https://github.com/VitisStudio/filament-hover-sidebar/pull/1

**Full Changelog**: https://github.com/VitisStudio/filament-hover-sidebar/compare/v0.1.0...v0.1.1

## v0.1.0 - Initial Release - 2026-09-01

**Full Changelog**: https://github.com/VitisStudio/filament-hover-sidebar/commits/v0.1.0

## 1.0.0 - 202X-XX-XX

- initial release
