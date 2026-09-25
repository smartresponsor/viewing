# CMCP Execution Journal

## Task

- Task: `engine-20260911144233-viewing-7b3039`
- Component: `Viewing`
- Workspace: `D:\PhpstormProjects\www\Viewing`
- Authority: `WRITE_ALLOWED`

## Baseline

- Read repository guidance: `AGENTS.md`, `README.md`, `MANIFEST.json`, `composer.json`, Viewing canon and RC evidence.
- Confirmed local branch `master`; pre-task worktree contained untracked `.gating/` only.
- Composer gate passed with 48 tests / 107 assertions, PHPStan level 8 clean, and CS Fixer clean, but PHPUnit reported 1 warning and 4 notices.
- RC-critical workstream: remove PHPUnit test-double notices without changing runtime behavior, then identify the remaining warning.
- Growth workstream: presentation capability/UX maturity remains post-RC and must not move template design, CRUD, authorization, or producer business behavior into Viewing.

## Canonization Mapping

- `Canon001TechnicalRoleFirstRule.md`: current `Service/View`, `ServiceInterface/View`, `Subscriber/View`, and `Value/View` trees keep technical role first; this task does not introduce a new source role.
- `Canon002InterfaceTreeMirrorsImplementationRule.md`: `Service/View/*` and `ServiceInterface/View/*Interface` remain mirrored; this task does not alter public contracts.
- `Canon011NoSilentFailureRule.md`: invalid regex configuration remains explicitly observable as `InvalidArgumentException`; only the lower-level validation probe warning is suppressed.
- `Canon023DevelopmentComposerSymlinkRule.md`: development `composer.json` correctly uses the local `../Interfacing` path repository with `symlink: true`.
- `Canon024ProductionComposerBundleRule.md`: `composer.prod.json` now exists and uses the established SmartResponsor production VCS contract (`git@github.com:smartresponsor/interfacing.git`, `dev-master`) with no sibling path/symlink repository.
- `Canon025ComponentDualRuntimeModeRule.md`: existing standalone boot surfaces and bundle mode remain unchanged.
- `Canon033ComposerManifestIdentityParityRule.md`: development and production manifests preserve package name/type, `App\\Viewing\\` PSR-4 identity, PHP `^8.4`, and Symfony 8.1 baseline while differing only in environment-specific dependency resolution.
- Viewing canon: keep `kernel.view` as the active presentation boundary and `kernel.response` as defensive containment; no CRUD or template-design ownership changes.

## Risks

- Do not modify sibling repositories; Objecting, Cruding, Interfacing, Canonization, and Gating are reference-only for this task.
- Preserve the existing local `.gating/` state and do not stage unrelated files.
- Test-double cleanup must not weaken interaction assertions where communication is explicitly under test.

## Execution Result

- PHPUnit warning/notices baseline closed: 48 tests / 107 assertions pass cleanly.
- Expectation-less `ViewTemplateRendererTest` collaborators use stubs; the explicit Twig interaction remains a mock with `expects()`.
- Invalid configured bot user-agent regexes translate validation failure to the existing `InvalidArgumentException` without emitting a lower-level regex-engine warning.
- Full Composer `gate` passes: PHPUnit clean, PHPStan level 8 clean, and CS Fixer dry-run clean.
- Development Composer constraint is now `interfacing/interface: dev-master`; local path+symlink wiring remains intact and `composer validate --strict --check-lock` passes cleanly.
- `composer run-script validate:prod` passes strict validation for `composer.prod.json`; Canon033 identity parity is preserved for name, type, PSR-4, PHP baseline, and Symfony baseline.
- Changed PHP files lint clean. The pre-existing untracked `.gating/` tree was not modified or selected for Git integration.

## 2026-09-23 — Repository implementation and RC hardening

### Baseline

- Branch: `rc/viewing-master-sync-20260911`; reconnaissance HEAD: `108a01f71bbc6ac59d07414a681d2a762c97753f`.
- Existing dirty files before this run: `.gating/README.md`, `composer.json`, and `composer.prod.json`.
- PHPStan level 8 passed and PHPUnit passed 48 tests / 107 assertions before changes.
- Composer validation reported the lock was stale; the configured Gating executable was not installed in `vendor/bin`.

### Read contour

- Viewing: AGENTS, README/AsciiDoc, Composer manifests, MANIFEST, ADR/canon/migration/release documentation, source topology, tests, PHPUnit configuration, and current Git diff.
- Dependency contour: Objecting, Cruding, Interfacing, Collectioning, and Tabling package contracts relevant to Viewing.
- Gating: package contract plus executable Canon022/043/045/053 rules.
- Canonization: architecture README plus textual Canon007, Canon008, Canon017, Canon019, Canon020, Canon022, Canon023, Canon024, Canon033, Canon034, Canon039, Canon043, Canon045, and Canon053.

### Target-to-canon mapping

- Canon007/019/020: retain `App\\Viewing\\...` PSR-4 identity and technical-role Symfony topology; no Domain/Port/Adapter/Adaptor roots.
- Canon008: Interfacing production namespace use remains backed by `interfacing/interface`; standalone external platform packages are explicit runtime dependencies.
- Canon017: the runtime candidate order is aligned to Viewing's documented exact fallback chain; operation-specific and producer-supplied physical template candidates are removed.
- Canon022: Viewing exposes standalone Symfony boot surfaces, so external platform baseline packages are direct runtime dependencies. A literal `viewing/view` self-dependency is intentionally not added because a Composer root package cannot validly require itself; any executable requirement for that edge is a Canon022/Gating self-applicability defect outside Viewing.
- Canon023/043/045/053: local first-party development repositories use sibling path symlinks with explicit `dev-master` identity and only canonical helper/foundation siblings.
- Canon024/033: production stays path-independent and preserves package/type/PSR-4/PHP/Symfony identity.
- Canon034: local environment, generated coverage, and OS noise are ignored.
- Canon039: PHPUnit declares the production source population and persistent branch-aware coverage execution.

### RC-critical workstream

- Align template candidate resolution with the exact Viewing fallback chain.
- Normalize local Composer dependency/repository closure and remove the self path repository.
- Align production package repositories with the external standalone dependency contour.
- Add PHPUnit coverage tooling and re-run Composer validation, PHP lint/style/static analysis/tests/coverage/Gating/audit.

### Growth workstream

- Post-RC only: evaluate Symfony UX Twig Components / Live Components as optional consumer composition capabilities. Template design ownership stays in Interfacing; Viewing stays the representation-decision/rendering boundary.

### Material risks

- Canon022 currently includes `viewing/view` in the mandatory list for every standalone application and its Gating implementation has no root-package self exemption.
- Existing dirty Gating-adoption work predates this run and is preserved for validation rather than overwritten.

### Verification and integration preparation — 2026-09-24

- Canonical role-tree migration completed: controller flattened, subscribers moved to `EventSubscriber/`, factories/normalizers/resolvers/renderers moved to typed roots, values and service interfaces flattened, and the optional Interfacing bridge moved to `Contract/ViewInterfaceLocationComposerInterface.php`.
- Canon017 runtime candidate chain aligned to the documented folder-based fallback order; producer-controlled physical template selection and operation-specific template candidates were removed.
- Composer development and production dependency contours were normalized; local first-party path repositories use symlinks and `dev-master`; BrowserKit, CSS Selector, Panther, and Playwright tooling are present.
- Generated `config/reference.php` is ignored and untracked; `.gating/` was returned to canonical consumer artifact state.
- PHPUnit: PASS, 48 tests / 107 assertions. PHPStan level 8: PASS. PHP-CS-Fixer dry-run: PASS.
- Playwright: PASS for the standalone human HTML path. Headless Chromium is intentionally classified as bot by `/headless/i`, so the browser test uses a normal human browser UA; bot JSON behavior remains separately covered by PHPUnit.
- Canon042 behavioral/UI evidence is generated by repository script `test:behavioral-coverage` and passes at functional 2/2, behavioral 5/5, UI 1/1, critical 1/1.
- Composer validate/check-lock: PASS. Production Composer validate: PASS. Composer audit: 0 advisories. npm audit with committed `package-lock.json`: 0 vulnerabilities.
- Fresh Gating: every in-scope hard rule passes. The sole hard failure is Canon022 requiring root package `viewing/view` to require itself; this is an external Canonization/Gating self-applicability defect and is intentionally not implemented in Viewing.
- Remaining warnings are Canon031 PHPDoc coverage and Canon040 PHP coverage debt (lines 76.2%, methods 42.3%, branches 78.8%); these are recorded debt, not hidden or suppressed.
- Root `MANIFEST.json` remains historical Wave 4 metadata by explicit release-packaging policy and is not rewritten as a current RC manifest.

### Coverage debt closure — 2026-09-24

- Canon031 is fully closed: direct rule execution reports classes 47/47 (100.0%) and contract methods 49/49 (100.0%), above the 70% threshold.
- Public and protected production contracts now carry semantic PHPDoc that describes responsibilities instead of placeholder/tag-only blocks.
- PHPUnit regression coverage expanded from 48 tests / 107 assertions to 82 tests / 234 assertions.
- Canon040 is closed by direct rule execution: lines 99.2%, methods 80.8%, and branches 87.8% against 80/80/70 targets.
- Added regression coverage for infrastructure surfaces, request-context creation, payload/object normalization fallback sources, subscriber event registration, response-guard edge behavior, observability no-op behavior, traffic-classifier defensive inputs, and renderer failure-trace continuation.
- Renderer fallback now explicitly proves that existing render-failure traces are appended rather than overwritten; this closed the final Canon040 method/branch gap.
- Playwright remains green. Its local server port is now configurable through `VIEWING_PLAYWRIGHT_PORT` and defaults to component-specific port `19081`, avoiding collisions with sibling Symfony applications.
- Current sibling Gating default rule-set changed concurrently and now executes a smaller generic set; direct Canon022/031/040 rule execution was used to preserve canonical evidence.
- Direct Canon022 remains the sole external hard failure because it requires Composer root package `viewing/view` to require itself; no invalid self-dependency was introduced.

## 2026-09-25 — Canon055 RC terminology closure

### Baseline

- Task: `engine-20260925220345-viewing-26779a`; branch `rc/viewing-master-sync-20260911`; reconnaissance HEAD `50a07fccc04af40d85a8430b6af44f087adeefb4`.
- Pre-existing worktree state preserved: modified `.gating/README.md`; untracked `bin/cmcp-*-paths.*` helpers and `tests/Fixture/`.
- Repository/dependency contour read: Viewing, Objecting, Cruding, Interfacing, Gating, and Canonization contracts; Viewing docs/ADRs/canon/release surfaces and core presentation pipeline were inspected.
- Market/architecture baseline: Symfony `kernel.view` remains the framework-native response conversion boundary; Viewing therefore remains a presentation/responder boundary and does not absorb Interfacing design ownership or Cruding generic CRUD ownership.

### Canonization mapping

- `Canon019NoAlternativeLayerTaxonomyRule`: current role-first `App\\Viewing\\...` topology is compliant; no Domain/Application/Infrastructure/Port/Adapter/Adaptor roots introduced.
- `Canon043DevelopmentComposerDependencyVersionRule`: first-party sibling path dependencies remain exact `dev-master` with pinned path-repository identities.
- `Canon052GatingIntegrationRule`: `gating/gate` remains a development dependency and `@gate` remains part of `quality`; production stays free of local Gating path repositories.
- `Canon055PlatformIdentityTerminologyRule`: Gating surfaced four current human-facing consumer-identity candidates. The corresponding AGENTS/README/composer descriptions were neutralized; `composer.prod.json` was aligned to the same neutral package description.
- Historical records and machine locators remain unchanged where Canon055 explicitly permits them.

### RC-critical workstream

- Reproduce the current deterministic gate.
- Close only the Canon055 terminology failure with exact-text edits.
- Re-run Composer validation, PHP-CS-Fixer, PHPStan, PHPUnit, behavioral coverage, and Gating; inspect final Git state.
- No runtime rendering, navigation, form, or user-flow behavior is changed, so new visual screenshots are not applicable to this patch.

### Growth workstream

- Post-RC only: evaluate optional Symfony UX/Twig composition capabilities at consumer boundaries without moving template design ownership from Interfacing or generic CRUD ownership from Cruding.

### Verification

- `composer validate --strict`: PASS.
- `composer run-script validate:prod`: PASS.
- `composer run-script quality`: PASS; PHPStan level 8 clean, PHPUnit 82 tests / 234 assertions, Gating 0 failed / 0 warnings.
- `composer run-script test:behavioral-coverage`: PASS; evidence regenerated.
- `composer audit --format=summary`: PASS; no security vulnerability advisories.
- Canon055 now reports no consumer identity promoted to platform identity.
- No browser/mobile behavior or visual surface changed; runtime restart and new visual screenshots are not applicable.
- Pre-existing `.gating/README.md`, `bin/cmcp-*-paths.*`, and `tests/Fixture/` remain outside this task's integration set.

## 2026-09-25 — Work 3 value integration

### Intent

- Reassessed the remaining dirty Work 3 paths instead of committing them mechanically.
- Preserved the Xdebug path probes as explicit optional coverage diagnostics and exposed them through Composer as `diagnose:coverage-paths`.
- Promoted `tests/Fixture/ExternalViewObjectPayload.php` into an exercised regression fixture proving that a foreign producer contract remains outside `App\\<Component>\\...` component identity while preserving its explicit payload vocabulary.
- Restored `.gating/README.md` to the consumer-artifact boundary required by Canon052 rather than committing a copied owner-repository README.

### Verification

- First regression run intentionally failed on an incorrect expected surface (`external-view-object-payload`); the fixture's explicit `word=external` contract was then honored.
- `composer run-script diagnose:coverage-paths`: PASS; candidate, traffic-classifier, and route-exclusion path diagnostics all execute under Xdebug.
- `composer run-script quality`: PASS; PHPStan level 8 clean, PHPUnit 83 tests / 238 assertions, Gating 0 failed / 0 warnings.
- No runtime/UI implementation changed; new visual evidence remains not applicable.
