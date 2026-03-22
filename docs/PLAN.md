# Luxid Nova - Reactive Templating Engine

## Architecture & Implementation Plan

## Overview

Luxid Nova is a first-party templating/reactive engine for the Luxid ecosystem. It provides server-side rendered components with action-driven interactivity, bridging the gap between traditional PHP templating and modern reactive frameworks.

### Core Philosophy

* **Server-First**: All core rendering happens on the server
* **Action-Driven**: Components respond to user actions via server-side callbacks
* **Progressive Enhancement**: JavaScript is optional for enhanced interactivity
* **PHP-Native**: Leverages PHP syntax instead of inventing new templating languages

## Architecture Principles

### 1. Component Structure

Components follow a consistent pattern:

* **State**: Data that persists across requests (session-backed)
* **Actions**: Server-side functions that mutate state
* **View**: PHP code that renders HTML using state data

### 2. Rendering Pipeline

Request → Component Resolution → State Loading → Action Execution (if any) → View Compilation → HTML Output

### 3. State Management

* **Primary**: PHP sessions for user-specific state persistence
* **Alternative**: Stateless mode for APIs via payload-encoded state
* **Future**: Cache drivers (Redis, Memcached) for distributed environments

### 4. Action Flow

User Interaction → POST Request → Component Action → State Mutation → Re-render → DOM Update

## Technical Decisions

### Templating Syntax

We use `@echo()` instead of `{{ }}` to:

* Avoid conflicts with JavaScript templating
* Clearly indicate server-side output
* Maintain PHP syntax highlighting compatibility

### Directive Support

Core directives to implement:

* `@echo($var)` - Output escaped content
* `@if($condition)` / `@endif` - Conditional rendering
* `@for($i = 0; $i < 10; $i++)` / `@endfor` - Loops
* `@foreach($array as $item)` / `@endforeach` - Array iteration
* `@click="action"` - Event binding (client-side)

### Compiler Approach

The compiler will:

1. Parse PHP files for Nova directives
2. Convert directives to native PHP code
3. Cache compiled templates for performance
4. Support nested component inclusion

### JavaScript Strategy

* **Core**: Lightweight `nova.js` for action handling and DOM updates
* **Optional**: Alpine.js integration for advanced interactivity
* **Progressive**: Components work without JS; JS adds polish

## Integration with Luxid

### Compatibility

* Works alongside existing `Screen::renderScreen()`
* Uses same routing system via `Nova::render()`
* Respects existing middleware and security layers

### Service Provider

```php
class NovaServiceProvider
{
    public function register(Application $app): void
    {
        // Register component loader
        // Setup action routes
        // Configure state drivers
    }
    
    public function boot(Application $app): void
    {
        // Load component definitions
        // Register action endpoints
    }
}
```

### Component Lifecycle

1. Definition

```php
component('welcome-screen', function () {
    return [
        'state' => function () {
            return $_SESSION['nova']['welcome-screen'] ?? [
                'showBadge' => true,
                'version' => 'v0.1.7'
            ];
        },
        'actions' => [
            'toggleBadge' => function (&$state) {
                $state['showBadge'] = !$state['showBadge'];
                return Nova::render('welcome-screen');
            }
        ],
        'view' => function ($state) {
            // HTML with @echo() directives
        }
    ];
});
```

2. Mounting

* State initialized from session or defaults
* Props merged with state
* Component ready for rendering

3. Action Handling

* Action called via POST request
* State mutated
* Component re-rendered
* HTML returned for DOM replacement

4. Destruction

* State persists in session
* Cleanup of temporary data
* Optional event hooks

## Performance Considerations

### Caching Strategy

* Template Cache: Compiled PHP templates stored in storage/framework/nova/
* State Cache: Optional Redis backend for session alternative
* Component Cache: Pre-compiled component definitions

### Optimization Techniques

* Lazy loading of component definitions
* Partial re-rendering for nested components
* Debounced action requests
* Minified client-side JavaScript

## Security Considerations

### Input Sanitization

* All @echo() outputs automatically escaped
* Action parameters validated before processing
* State mutations checked for permissions

### CSRF Protection

* Action endpoints require CSRF tokens
* Tokens automatically added to forms and action links
* Session-based token validation

### XSS Prevention

* HTML escaping by default
* Strict content security policy headers
* No eval() usage in core

## Testing Strategy

### Unit Tests

* Component compilation
* State management
* Action execution
* Directive processing

### Integration Tests

* Full component rendering
* Action flow with real HTTP requests
* Session persistence

## Example Components

* Counter (simple state)
* Todo List (arrays, loops)
* Form Validation (user input)
* Nested Components (communication)

## Future Extensibility

### Plugin System

* Custom directives
* Custom state drivers
* Custom compilers
* Third-party integrations

### Features to Consider

* Component inheritance
* Slots/partials
* Lifecycle hooks (beforeMount, afterUpdate)
* Server-side events
* WebSocket integration

## Documentation Requirements

### User Documentation

* Getting started guide
* Component API reference
* Directive reference
* Examples gallery
* Best practices

### Developer Documentation

* Architecture overview
* Contributing guide
* Testing guide
* Performance tuning

## Versioning Strategy

### Semantic Versioning (SemVer)

* Major: Breaking changes to API or syntax
* Minor: New features, directives, or capabilities
* Patch: Bug fixes, performance improvements

### Release Candidates

* Alpha: Core functionality working
* Beta: Feature complete, needs testing
* RC: Stabilization and documentation
