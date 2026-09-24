# Viewing

Viewing is the central view boundary and rendering manager for the Smart Responsor platform. Hooking directly into the Symfony `kernel.view` event, it intercept controllers returning neutral data payloads and processes them into final HTTP responses using template fallback chains, guardrails, and JSON formats.

This bundle is **not** a direct template design catalog (which belongs in the Interfacing layer). It owns the rendering boundary logic, fallback decisions, and template dispatching.

## Current Posture

### What the component already does
- Intercepts controller payloads on `kernel.view` to unify response generation.
- Enforces template fallback chains (resolving template paths dynamically by locale, resource, or layout).
- Operates a self-processing connectable view architecture.
- Enforces guardrails and traffic policies (e.g. blocking template engine rendering for crawler/bot requests to serve lightweight formats).

### What this repository does not claim yet
- Directly holding HTML layouts, styles, or stylesheets.

## Runtime Surface & Entrypoints

The bundle acts as a Symfony event-driven presentation boundary:
- `App\Viewing\ViewingBundle` wires the bundle and service configuration.
- `src/EventSubscriber/` contains the `kernel.request`, `kernel.view`, and `kernel.response` subscribers.
- `src/Service/` contains orchestration services whose dominant role is service logic; factories, normalizers, resolvers, and renderers live in their canonical typed roots.
- `src/ServiceInterface/` mirrors public service contracts; cross-component optional bridges live under `src/Contract/`.
- `src/Value/` contains immutable payload, request context, decision, actor, reason, and template-resolution values.

## Decision and Failure Contract

The decision order is explicit: configured bot actors, JSON request format, JSON payload format, controlled HTML routes, JSON-preferring `Accept`, XHR without HTML preference, configured unknown-actor policy, then HTML candidate allowance.

`unknown_actor_policy` accepts `html` or `json` and defaults to `html`. Traffic classification is presentation policy, not a security boundary. Invalid bot user-agent regular expressions fail during service construction instead of being silently ignored.

Template absence and template failure are distinct. Missing candidates may use structured JSON fallback with the payload status. Loader or render failures use distinct reason codes and force HTTP 500 when the payload does not already provide an explicit error status. HTML and JSON responses share `ViewStatusCodeResolverInterface`.

Producer objects are accepted only when they implement `App\Viewing\ValueObjectInterface\ViewObjectPayloadInterface`; method-name duck typing is not supported. Optional Interfacing location composition is exposed through `ViewInterfaceLocationComposerInterface`. Standalone mode injects `null`; host applications may alias their Interfacing implementation to the local bridge contract.

Structured observability uses optional PSR-3 logging with stable event and metric fields; payload content, stack traces, and filesystem paths are not logged. JSON serialization is all-or-nothing with UTF-8 substitution and explicit `serialization_degraded` HTTP 500 fallback. Response guard rollout is configured with `response_guard_mode: off|observe|enforce`; observe preserves the original response and adds `X-Viewing-Guard`.

## Local Setup

Install dependencies:
```bash
composer install
```

Run test suite:
```bash
vendor/bin/phpunit
```

## Local Composer Path Installation

To integrate Viewing in your Symfony host application:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../Viewing",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "viewing/view": "dev-master"
  }
}
```

## Documentation Map

- [ADR 0001: Central View Boundary](docs/adr/0001-central-view-boundary.adoc)
- [ADR 0002: Template Fallback Chain](docs/adr/0002-template-fallback-chain.adoc)
- [ADR 0003: Connectable and Self-Processing Viewing](docs/adr/0003-connectable-and-self-processing.adoc)
- [ADR 0004: Guardrails and Traffic Policy](docs/adr/0004-guardrails-and-traffic-policy.adoc)
- [Viewing Host Integration Checklist](docs/migration/host-integration-checklist.adoc)
- [Viewing RC Evidence Report](docs/release/viewing-rc-evidence.md)
- [Viewing OpenAPI/Nelmio Schemas](docs/schema/viewing-openapi.yaml)
- [Viewing RC Packaging Posture](docs/release/viewing-rc-packaging.md)
