# Changelog

## 0.2.1

Worker-runtime safety.

### Fixed

- **A template that threw mid-capture leaked an output buffer.** `Slot::start()`
  opens a buffer that only `end()` closes, so an aborted render left it open —
  and every later response was swallowed into that stale buffer. Under PHP-FPM
  the process died and took the buffer with it; under a worker they accumulate.
  `Slot::reset()` closes them.

### Added

- `Slot::reset()` and `Slot::openCaptures()`.
- `ComponentManager::resetRequestState()` and `Performance::reset()`.
- `ComponentCache::flushMemory()` and `memoryCount()`.
- `NovaServiceProvider` is now discovered through `extra.luxid.providers` and
  registers Nova's per-request state with the engine's reset registry, so a
  worker clears it between requests automatically.

### Changed

- The in-process component cache is capped at 500 entries and evicts the oldest,
  rather than growing for the life of the process.
- `NovaServiceProvider` matches the engine's provider contract — a no-argument
  constructor with `register()` and `boot()` — while staying usable standalone;
  everything touching the engine is guarded by a class check.

## 0.2.0

Pre-release. Several fixes change behaviour on purpose; they are listed first.

### Breaking

- **Component actions require a CSRF token.** The endpoint accepted whatever the
  request body named; the client sent a `_token` but nothing compared it. Render
  `\Luxid\Nova\Csrf::metaTag()` in your layout's `<head>`. A deployment that
  authenticates action calls some other way can opt out with
  `ActionDispatcher::withoutCsrfVerification()`.
- **Component state is no longer published to the browser automatically.** Every
  state value was serialised into `data-nova-state`, so anything a component
  held — a loaded database row, a token — was readable in the page source.
  Name the keys the client may see with `expose([...])`.
- **Action calls are validated before dispatch.** The component must be
  registered, the action must exist on it, and the instance id must match the
  shape the server issues. Previously any action on any registered component was
  a public, unauthenticated entry point.
- **Templates compile to files instead of running through `eval()`.** Compiled
  output is written to the configured cache path, or the system temp directory
  when none is set, and included. That directory must be writable.
- **Minimum PHP is now 8.1.**

### Fixed

- `@foreach`, `@for` and `@slot` match balanced parentheses, so an expression
  containing a function call no longer truncates at the first closing bracket.
- `@echo` casts before escaping, so a null value renders empty instead of
  raising a deprecation, and uses `ENT_SUBSTITUTE` so malformed UTF-8 does not
  collapse the output to an empty string.
- Validation messages fill their placeholders positionally, so a single
  parameter rule like `max:50` renders `50` rather than leaving `:max` in place.
- Component state stored in the session is capped and evicted oldest-first. It
  previously grew one entry per component per page view for the life of the
  session.
- Instance ids are validated before they are used as session keys.
- The standalone bootstrap only shows errors when `NOVA_DEBUG` is set, and
  reports action failures without echoing internals.

### Added

- `Luxid\Nova\Csrf` for token issuing, constant-time verification, a meta tag
  and a hidden form field.
- `Luxid\Nova\ActionDispatcher`, which owns action-call validation so the
  framework front controller and the standalone bootstrap share one code path.
- `expose()` helper for declaring client-visible state.
- `Compiler::render()`, `compiledFile()` and `cachePath()`.
- A PHPUnit suite covering dispatch guards and template compilation.
