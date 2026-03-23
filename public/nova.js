(function(global) {
  'use strict';

  class NovaCore {
    constructor(options = {}) {
      this.components = new Map();
      this.config = {
        debug: options.debug || false,
        // POST to the current page — bootstrap.php handles X-Requested-With there.
        // Can be overridden: new Nova({ actionEndpoint: '/api/nova' })
        actionEndpoint: options.actionEndpoint || window.location.pathname,
        loadingClass: 'nova-loading',
        errorClass: 'nova-error',
        ...options
      };

      this.init();
    }

    init() {
      this.log('Initializing...');
      this.scanComponents();
      this.setupEventDelegation();
      this.setupNavigation();
      this.log(`Initialized with ${this.components.size} components`);
    }

    scanComponents() {
      const elements = document.querySelectorAll('[data-nova-component]');
      elements.forEach(element => {
        const id = element.dataset.novaId;
        if (!this.components.has(id)) {
          const component = new NovaComponent(id, element, this);
          this.components.set(id, component);
          this.log(`Registered component: ${id}`);
        }
      });
    }

    setupEventDelegation() {
      document.addEventListener('click', (e) => {
        const target = this.findTargetWithAttribute(e.target, 'data-nova-click');
        if (target) {
          e.preventDefault();
          const component = this.findComponent(target);
          if (component) {
            component.callAction(target.dataset.novaClick, this.parseParams(target.dataset.novaParams));
          }
        }
      });

      document.addEventListener('submit', (e) => {
        const target = this.findTargetWithAttribute(e.target, 'data-nova-submit');
        if (target) {
          e.preventDefault();
          const component = this.findComponent(target);
          if (component) {
            const params = Object.fromEntries(new FormData(target).entries());
            component.callAction(target.dataset.novaSubmit, params);
          }
        }
      });

      document.addEventListener('input', (e) => {
        const target = this.findTargetWithAttribute(e.target, 'data-nova-input');
        if (target) {
          const component = this.findComponent(target);
          if (component) {
            component.callAction(target.dataset.novaInput, { value: target.value });
          }
        }
      });
    }

    findTargetWithAttribute(element, attribute) {
      while (element && element !== document) {
        if (element.hasAttribute && element.hasAttribute(attribute)) {
          return element;
        }
        element = element.parentElement;
      }
      return null;
    }

    findComponent(element) {
      const componentEl = element.closest('[data-nova-component]');
      if (componentEl && componentEl.dataset.novaId) {
        return this.components.get(componentEl.dataset.novaId);
      }
      return null;
    }

    parseParams(paramsString) {
      if (!paramsString) return {};
      try {
        return JSON.parse(paramsString);
      } catch (e) {
        console.warn('[Nova] Failed to parse params:', paramsString);
        return {};
      }
    }

    setupNavigation() {
      window.addEventListener('popstate', () => this.scanComponents());
    }

    async loadPage(url) {
      try {
        const response = await fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Nova-Turbo': 'true' }
        });
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        document.body.innerHTML = doc.body.innerHTML;
        document.title = doc.title;
        this.components.clear();
        this.init();
        history.pushState({}, '', url);
      } catch (error) {
        console.error('[Nova] Failed to load page:', error);
        window.location.href = url;
      }
    }

    refresh() {
      this.components.clear();
      this.init();
    }

    log(...args) {
      if (this.config.debug) {
        console.log('[Nova]', ...args);
      }
    }
  }

  class NovaComponent {
    constructor(id, element, core) {
      this.id = id;
      this.element = element;
      this.core = core;
      this.loading = false;
    }

    async callAction(action, params = {}) {
      if (this.loading) return;

      this.setLoading(true);
      this.core.log(`Calling action: ${action}`, params);

      try {
        const instanceId = this.element.dataset.novaId;
        const componentName = this.element.dataset.novaComponentName;

        const body = {
          component: componentName,
          instance: instanceId,
          action,
          params
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) body._token = csrfToken;

        const response = await fetch(this.core.config.actionEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(body)
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

        const html = await response.text();
        this.update(html);
        this.element.dispatchEvent(new CustomEvent('nova:action', { detail: { action, params, success: true }, bubbles: true }));

      } catch (error) {
        this.core.log(`Action '${action}' failed:`, error);
        this.showError(error.message);
      } finally {
        this.setLoading(false);
      }
    }

    update(html) {
      const temp = document.createElement('div');
      temp.innerHTML = html;

      const newElement = temp.querySelector(`[data-nova-id="${this.id}"]`);
      if (!newElement) {
        console.warn(`[Nova] Component ${this.id} not found in response, replacing inner HTML`);
        this.element.innerHTML = temp.innerHTML;
        return;
      }

      this.element.parentNode.replaceChild(newElement, this.element);
      this.element = newElement;
      this.core.components.set(this.id, this); // keep registry pointing at new element
      this.core.scanComponents(); // pick up any newly rendered child components
      this.animate();
    }

    setLoading(loading) {
      this.loading = loading;
      this.element.classList.toggle(this.core.config.loadingClass, loading);
      loading
        ? this.element.setAttribute('data-nova-loading', 'true')
        : this.element.removeAttribute('data-nova-loading');
    }

    showError(message) {
      this.element.classList.add(this.core.config.errorClass);
      const errorEl = document.createElement('div');
      errorEl.className = 'nova-error-message';
      errorEl.textContent = message;
      this.element.prepend(errorEl);
      setTimeout(() => {
        errorEl.remove();
        this.element.classList.remove(this.core.config.errorClass);
      }, 3000);
    }

    animate() {
      this.element.style.animation = 'nova-fade-in 0.3s ease-out';
      setTimeout(() => { this.element.style.animation = ''; }, 300);
    }
  }

  // CSS
  const style = document.createElement('style');
  style.textContent = `
    @keyframes nova-fade-in {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes nova-spin { to { transform: rotate(360deg); } }

    [data-nova-loading] {
      opacity: 0.6;
      pointer-events: none;
      position: relative;
    }
    [data-nova-loading]::after {
      content: '';
      position: absolute;
      top: 50%; left: 50%;
      width: 20px; height: 20px;
      margin: -10px 0 0 -10px;
      border: 2px solid rgba(0,0,0,0.1);
      border-top-color: #4CAF50;
      border-radius: 50%;
      animation: nova-spin 0.6s linear infinite;
    }
    .nova-error-message {
      background: #f44336;
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      margin-bottom: 10px;
      font-size: 14px;
    }
  `;
  document.head.appendChild(style);

  // FIX: initialise synchronously if DOM is already ready, otherwise wait.
  // Either way, assign window.nova AFTER the instance is actually created.
  function initNova(options) {
    const instance = new NovaCore(options);
    // Expose on window so external code can call window.nova.refresh() etc.
    global.nova = instance;
    return instance;
  }

  global.Nova = NovaCore;
  global.nova = null; // placeholder; set properly below

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initNova());
  } else {
    initNova();
  }

})(window);
