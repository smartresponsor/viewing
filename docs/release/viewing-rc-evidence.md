# Viewing RC Evidence Report

## Scope

This report captures release-candidate evidence for the `viewing/view` Symfony bundle after the R1-R11 hardening track. It is limited to the Viewing presentation boundary and does not move template ownership, endpoint ownership, authorization, CRUD behavior, or UI design responsibility into this component.

## Current posture

- Component: `Viewing`.
- Package: `viewing/view`.
- Runtime role: Symfony event-driven presentation boundary.
- Primary flow: `kernel.request -> kernel.view -> kernel.response`.
- Current RC posture: R1-R11 hardened and gate-verified; packaging and schema publication remain.

## Hardened contract surface

R1-R4 hardened decision reasons, actor classification, unknown-traffic policy, template failure taxonomy, and shared status-code resolution.

R5-R6 require producer object payloads to implement `App\Viewing\ValueInterface\View\ViewObjectPayloadInterface` and expose optional Interfacing location composition through `App\Viewing\ServiceInterface\View\ViewInterfaceLocationComposeServiceInterface`.

R7-R9 added safe PSR-3 structured observability, strict JSON serialization, degraded serialization fallback, and response-guard rollout modes: `off`, `observe`, and `enforce`.

R10 proves the request, view, and response event chain with real Symfony event objects, real Viewing subscribers, real services, and Twig loaders.

R11 treats `config/reference.php` as generated output, excludes it from php-cs-fixer strict-type rewriting, disables PHPUnit result caching, and ignores PHPUnit timing cache output.

## Gate matrix

| Gate | Current result | Evidence note |
| --- | --- | --- |
| PHP lint | PASS | Changed PHP files lint clean. |
| PHPUnit | PASS with accepted baseline | 47 tests, 104 assertions, 1 warning, 4 PHPUnit notices. |
| PHPStan | PASS | Level 8, no errors. |
| PHP-CS-Fixer | PASS | Dry-run diff reports no fixable files. |
| Composer validate/check-lock | PASS | `composer.json` valid. |
| Composer audit | PASS | No security vulnerability advisories found. |
| RC validate | PASS checks/canon, pre-commit dirty expected | Composer validate, PHPStan, PHPUnit pass; canon issue count is 0. |

## Accepted PHPUnit baseline

The current PHPUnit result is accepted for RC evidence because the suite completes without failures, errors, or risky tests. The known baseline remains one PHPUnit warning and four PHPUnit notices. These should either be closed or explicitly re-accepted during final GA hardening.

## Clean-workspace expectation

After R11, running the PHPUnit suite should not leave `.phpunit.result.cache` as source drift. `config/reference.php` is committed in Symfony-generated form and excluded from style rewriting to avoid conflict between generated output and project style policy.

## Remaining RC work

- Reusable OpenAPI/Nelmio schemas are published in `docs/schema/viewing-openapi.yaml`.
- RC packaging posture, manifest/checksum policy, and host import guidance are published in `docs/release/viewing-rc-packaging.md`.
- Decide whether the accepted PHPUnit warning/notices baseline should be closed before GA.
