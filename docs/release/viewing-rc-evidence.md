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

R5-R6 require producer object payloads to implement `App\Viewing\ValueObjectInterface\ViewObjectPayloadInterface` and expose optional Interfacing location composition through `App\Viewing\Contract\ViewInterfaceLocationComposerInterface`.

R7-R9 added safe PSR-3 structured observability, strict JSON serialization, degraded serialization fallback, and response-guard rollout modes: `off`, `observe`, and `enforce`.

R10 proves the request, view, and response event chain with real Symfony event objects, real Viewing subscribers, real services, and Twig loaders.

R11 treats `config/reference.php` as generated output that is ignored and not tracked as repository source, disables PHPUnit result caching, and ignores PHPUnit timing cache output. Current RC hardening also adds canonical role-root placement, Playwright browser evidence, and reproducible behavioral/UI coverage evidence.

## Gate matrix

| Gate | Current result | Evidence note |
| --- | --- | --- |
| PHP lint | PASS | Changed PHP files lint clean. |
| PHPUnit | PASS | 48 tests, 107 assertions, no warnings or PHPUnit notices. |
| PHPStan | PASS | Level 8, no errors. |
| PHP-CS-Fixer | PASS | Dry-run diff reports no fixable files. |
| Composer validate/check-lock | PASS | `composer.json` valid. |
| Composer audit | PASS | No security vulnerability advisories found. |
| Playwright | PASS | Standalone human HTML path renders in Chromium; headless/bot JSON behavior remains separately covered by PHPUnit. |
| Behavioral/UI coverage | PASS | Canon042 evidence: functional 2/2, behavioral 5/5, UI 1/1, critical 1/1. |
| npm audit | PASS | `package-lock.json` present; 0 vulnerabilities. |
| Gating | BLOCKED externally | All in-scope hard rules pass; Canon022 still requires the root package `viewing/view` to require itself. Canon031 and Canon040 remain warnings. |

## PHPUnit baseline

The PHPUnit suite is clean for RC evidence: 48 tests and 107 assertions complete without failures, errors, warnings, risky tests, or PHPUnit notices. Expectation-less test doubles use stubs, while interaction verification remains on mocks with explicit expectations. Invalid configured bot user-agent patterns still fail fast through `InvalidArgumentException` without leaking a lower-level regex-engine warning.

## Clean-workspace expectation

Running the PHPUnit suite should not leave `.phpunit.result.cache` as source drift. `config/reference.php` may be generated locally by Symfony tooling but remains ignored and untracked; authoritative configuration stays in `src/DependencyInjection/Configuration.php` and repository config.

## Remaining RC work

- Reusable OpenAPI/Nelmio schemas are published in `docs/schema/viewing-openapi.yaml`.
- RC packaging posture, manifest/checksum policy, and host import guidance are published in `docs/release/viewing-rc-packaging.md`.
- PHPUnit warning/notices baseline is closed; keep the suite clean during packaging and GA hardening.
