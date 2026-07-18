# Viewing RC Packaging Posture

## Scope

This document defines the release-candidate packaging posture for the `viewing/view` Symfony bundle after the R1-R13 hardening track. It is a packaging and release-readiness note, not a runtime contract expansion.

## Package identity

- Package name: `viewing/view`.
- Component: `Viewing`.
- Namespace root: `App\\Viewing`.
- Runtime role: Symfony presentation boundary for neutral producer payloads.
- Endpoint ownership: host and producer components.
- Template/layout ownership: Interfacing or host components.

## RC content families

The RC source package should include these families:

- `src/` runtime classes, subscribers, services, interfaces, values, and bundle wiring;
- `config/` Symfony bundle configuration and route samples;
- `templates/` Viewing-owned diagnostic/self-processing fallback templates only;
- `tests/` PHPUnit coverage for the hardened contract;
- `docs/adr/`, `docs/canon/`, `docs/migration/`, `docs/schema/`, and `docs/release/`;
- Composer metadata and local development gate configuration.

The RC source package should exclude these families:

- `vendor/`;
- `var/`;
- `.phpunit.cache/` and `.phpunit.result.cache`;
- IDE metadata;
- generated package archives;
- machine-local temporary files.

## Manifest and checksum policy

The existing root `MANIFEST.json` is historical wave metadata and must not be treated as a fresh RC package manifest.

The final RC package manifest must be generated from a clean working tree immediately before packaging. It should record every package file with at least:

- relative path;
- byte size;
- SHA-256 checksum;
- package name;
- component name;
- source branch;
- source commit;
- generated timestamp in America/Chicago.

The package archive itself should receive a SHA-256 checksum beside the archive. Signature material, when available, should be stored beside the checksum and not embedded as mutable source content.

## Required gates before packaging

The packaging gate must run from a clean working tree and pass:

- `composer validate --strict --check-lock`;
- `composer audit`;
- `composer run-script test`;
- `composer run-script phpstan`;
- `composer run-script php-cs-fixer`;
- RC validate with canon issue count equal to zero.

The current accepted test baseline is one PHPUnit warning and four PHPUnit notices, with no failures, errors, or risky tests. This baseline must be either re-accepted in RC notes or closed before GA.

## Host import posture

