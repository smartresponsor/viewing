# Viewing RC Evidence Report

## Scope

This report captures release-candidate evidence for the `viewing/view` Symfony bundle after the R1-R11 hardening track. It is limited to the Viewing presentation boundary and does not move template ownership, endpoint ownership, authorization, CRUD behavior, or UI design responsibility into this component.

## Current posture

- Component: `Viewing`.
- Package: `viewing/view`.
- Runtime role: Symfony event-driven presentation boundary.
- Primary flow: `kernel.request -> kernel.view -> kernel.response`.
- Current RC posture: R1-R11 hardened; all in-scope Viewing quality/debt gates are green. The only remaining hard failure is the external Canon022 root-package self-dependency rule.

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
| PHPUnit | PASS | 82 tests, 234 assertions, no failures or errors. |
| PHPStan | PASS | Level 8, no errors. |
| PHP-CS-Fixer | PASS | Dry-run diff reports no fixable files. |
| Composer validate/check-lock | PASS | `composer.json` valid. |
| Composer audit | PASS | No security vulnerability advisories found. |
| Playwright | PASS | Standalone human HTML path renders in Chromium; headless/bot JSON behavior remains separately covered by PHPUnit. |
| Behavioral/UI coverage | PASS | Canon042 evidence: functional 2/2, behavioral 5/5, UI 1/1, critical 1/1. |
| npm audit | PASS | `package-lock.json` present; 0 vulnerabilities. |
| Canon031 PHPDoc coverage | PASS | Classes 47/47 (100.0%); contract methods 49/49 (100.0%). |
| Canon040 PHP coverage | PASS | Lines 99.2%; methods 80.8%; branches 87.8% against 80/80/70 targets. |
| Gating | BLOCKED externally | Direct Canon022 check still requires the root package `viewing/view` to require itself. Canon031 and Canon040 now pass. |

## PHPUnit baseline

The PHPUnit suite is clean for RC evidence: 82 tests and 234 assertions complete without failures or errors. Expectation-less test doubles use stubs, while interaction verification remains on mocks with explicit expectations. Invalid configured bot user-agent patterns still fail fast through `InvalidArgumentException` without leaking a lower-level regex-engine warning.

## Clean-workspace expectation

Running the PHPUnit suite should not leave `.phpunit.result.cache` as source drift. `config/reference.php` may be generated locally by Symfony tooling but remains ignored and untracked; authoritative configuration stays in `src/DependencyInjection/Configuration.php` and repository config.

## Remaining RC work

- Reusable OpenAPI/Nelmio schemas are published in `docs/schema/viewing-openapi.yaml`.
- RC packaging posture, manifest/checksum policy, and host import guidance are published in `docs/release/viewing-rc-packaging.md`.
- PHPUnit warning/notices baseline, Canon031 documentation debt, and Canon040 executable coverage debt are closed. Keep these gates green during packaging and GA hardening.
