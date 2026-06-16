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

The bundle acts as a Symfony middleware/listener:
- `App\Viewing\ViewingBundle` - Wire the compiler passes and event listeners.
- `src/EventListener/` - Contains the `kernel.view` and `kernel.response` interceptors.
- `src/Fallback/` - Fallback chain evaluation strategy.
- `src/Payload/` - Core DTO wrappers for returning data from controllers.

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
    "viewing/view": "*@dev"
  }
}
```

## Documentation Map

- [ADR 0001: Central View Boundary](docs/adr/0001-central-view-boundary.adoc)
- [ADR 0002: Template Fallback Chain](docs/adr/0002-template-fallback-chain.adoc)
- [ADR 0003: Connectable and Self-Processing Viewing](docs/adr/0003-connectable-and-self-processing.adoc)
- [ADR 0004: Guardrails and Traffic Policy](docs/adr/0004-guardrails-and-traffic-policy.adoc)
- [Viewing Host Integration Checklist](docs/migration/host-integration-checklist.adoc)
