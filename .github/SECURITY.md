# Security Policy

## Supported versions

Security fixes are applied to the latest minor release only. Older minors are not
backported, so upgrade before reporting an issue against an outdated version.

| Version | Supported |
| ------- | --------- |
| 1.x     | Yes       |

## Reporting a vulnerability

Please report security issues privately. Do not open a public issue, pull request, or
discussion, and do not disclose the problem publicly until a fix has been released.

Report either way:

- **GitHub** — open a private advisory from the repository's
  [Security tab](https://github.com/vitisstudio/filament-hover-sidebar/security/advisories/new).
- **Email** — dan@vitis.studio.

Include the package version, the Filament and Laravel versions you are running, and enough
detail to reproduce the issue.

## What to expect

You will get an acknowledgement within 5 working days. If the report is accepted, a fix and
a GitHub security advisory follow; if it is declined, you will get the reasoning. Either
way you will be credited in the advisory unless you would rather not be.

## Scope

This package is panel chrome: it registers render hooks, ships CSS and JavaScript, and
overrides Filament's own sidebar styling. Reports about the CSS or JavaScript it injects,
its render hooks, or its Blade view are in scope. Vulnerabilities in Filament itself belong
to [filamentphp/filament](https://github.com/filamentphp/filament/security/policy).
