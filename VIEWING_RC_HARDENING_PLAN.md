# Viewing RC Hardening Plan

## Purpose

`Viewing` is the active Symfony presentation boundary between neutral producer output and the final HTTP representation. Producer components return neutral payloads. `Viewing` selects structured JSON or rendered HTML. `Interfacing` remains a passive owner of templates, layouts, shell fragments, and composed interface locations.

The objective is to harden this narrow responsibility without expanding `Viewing` into a UI framework, API owner, authorization layer, CRUD component, or generic bot-security system.

## Current effective flow

```text
kernel.request  -> classify obvious actor type
controller      -> return neutral View payload
kernel.view     -> normalize, decide JSON/HTML, render or fall back
kernel.response -> contain illegal controlled HTML rendering
```

The current behavior is not strictly `machine -> JSON, human -> HTML`. Explicit bot classification, JSON request format, JSON payload format, JSON-preferring `Accept`, and selected XHR conditions force JSON. Unknown or unclassified traffic normally reaches the HTML candidate path. This ambiguity is the first RC risk to close.

## RC-critical track

### R1. Freeze the decision contract

- Define canonical actor states: `bot`, `human`, `unknown`, and unclassified.
- Define explicit unknown-traffic policy and safe host default.
- Define precedence between actor type, request format, payload format, `Accept`, XHR, and controlled-route metadata.
- Replace ad hoc reason strings with typed constants or enum values.
- Add a complete decision matrix and branch tests.

Exit: every JSON/HTML outcome is explicit, documented, and test-covered.

### R2. Harden traffic classification

- Validate bot regex patterns during configuration/container build.
- Remove suppressed `@preg_match` failures.
- Validate `Sec-Fetch-*` values instead of trusting header presence.
- Test browser, WebView, curl, SDK, empty user agent, spoofed headers, and invalid regex cases.
- Document that classification is presentation policy, not security.

Exit: invalid configuration cannot pass silently and every classification outcome is observable.

### R3. Separate template absence from rendering failure

- Distinguish candidate missing, loader failure, candidate render failure, systemic Twig failure, and empty chain.
- Define which failures may continue to another candidate.
- Preserve producer-provided error status codes.
- Return controlled 5xx for systemic rendering failure when no explicit status exists.
- Prevent broken rendering from degrading into unexplained HTTP 200 JSON.

Exit: missing and broken templates have different reasons, logs, metrics, and status behavior.

### R4. Unify response status resolution

- Introduce one `ViewStatusCodeResolverInterface` and implementation.
- Use it for HTML, JSON, and degraded fallback responses.
- Test valid integers, numeric strings, invalid values, 4xx, and 5xx cases.

Exit: identical payloads produce identical status codes in every representation branch.

### R5. Formalize producer object payload support

- Replace method-name duck typing with an explicit `App\Viewing\...` contract.
- Require typed array returns for template context and fallback data.
- Retain canonical array and `ViewPayload` support.
- Add malformed-return and producer contract tests.

Exit: arbitrary objects cannot be captured accidentally and PHPStan can verify compatibility.

### R6. Make Interfacing integration explicit

- Document the optional interface-location composition contract.
- Validate service-present and service-absent modes.
- Add standalone and host integration tests.
- Align Composer metadata and integration documentation without moving template ownership into `Viewing`.

Exit: standalone and host-integrated modes are deterministic and proven.

### R7. Add structured observability

- Add PSR-3 logging for decisions, fallbacks, render failures, and guard violations.
- Include request ID, route, actor type, decision mode, reason, candidate depth, and exception class.
- Add counters for JSON, HTML, unknown actor, missing template, render failure, illegal render, and guard replacement.
- Add resolution and render timing.
- Prevent sensitive payload, filesystem path, and stack-trace leakage.

Exit: every degraded path emits production-safe operational evidence.

### R8. Harden JSON serialization

- Review or contain `JSON_PARTIAL_OUTPUT_ON_ERROR`.
- Normalize unsupported values where appropriate.
- Make UTF-8 substitution and degraded serialization explicit.
- Test invalid UTF-8, recursion, resources, closures, unsupported objects, and deep payloads.

Exit: syntactically valid but silently incomplete JSON cannot be mistaken for a healthy response.

### R9. Add response guard rollout modes

- Replace the boolean guard with `off`, `observe`, and `enforce`.
- In `observe`, log and mark violations without replacing responses.
- In `enforce`, retain controlled replacement.
- Test HTML, JSON, redirect, binary, stream, no-content, excluded route, and third-party responses.

Exit: hosts can migrate safely before strict enforcement.

### R10. Prove the complete Symfony event pipeline

Add integration coverage for human HTML, explicit JSON, bot JSON without Twig lookup, unknown policy, missing candidate, broken candidate, systemic Twig failure, local fallback, final JSON fallback, illegal direct HTML, guard modes, excluded paths, redirects/files/streams, status preservation, subrequests, and Interfacing service present/absent.

Exit: `kernel.request -> kernel.view -> kernel.response` is proven without relying only on mocks.

### R11. Close documentation and PHPDoc drift

- Correct stale references to nonexistent `EventListener`, `Fallback`, and `Payload` directories.
- Document actual `Subscriber/View`, `Service/View`, `ServiceInterface/View`, and `Value/View` structure.
- Add responsibility, invariant, exception, array-shape, reserved-key, and configuration documentation.
- Synchronize README, ADR, canon, configuration reference, and runtime behavior.

Exit: machine and human readers see one drift-free architecture.

### R12. Publish reusable OpenAPI/Nelmio schemas

- Define reusable schemas for `_view`, `_viewing`, `data`, `meta`, `debug`, `interface.locations`, degraded fallback, and guard violation responses.
- Provide host import guidance and examples.
- Keep endpoint ownership outside `Viewing`.

## Milestone progress

### R1-R4 Decision and Failure Contract

Status: implemented and gate-verified.

Actor and decision semantics are typed, unknown traffic policy is explicit, classifier patterns fail fast, template absence is separated from loader and render failures, status resolution is centralized, Symfony kernel pipeline coverage proves bot JSON and human HTML paths, and README, canon, ADR, and configuration reference are synchronized.

Remaining RC work starts at R5 and does not reopen the completed R1-R4 contract unless regression evidence requires it.

### R5-R6 Producer and Interfacing Contracts

Status: implemented and gate-verified.

Producer object payloads now require `ViewObjectPayloadInterface`; duck typing and reflection are removed. Optional Interfacing location composition now uses the local `ViewInterfaceLocationComposeServiceInterface`, with deterministic standalone-null and host-alias modes.

Remaining RC work starts at R7.

