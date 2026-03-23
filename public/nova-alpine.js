// Alpine.js integration for enhanced reactivity

(function() {
  if (typeof Alpine === 'undefined') {
    console.warn('[Nova] Alpine.js not loaded. Skipping integration.');
    return;
  }

  /**
   * Nova Alpine Plugin
   * Adds reactive data binding to Nova components
   */
  Alpine.plugin(function(Alpine) {
    Alpine.directive('nova', (el, { expression }, { evaluate }) => {
      // Get component ID from parent
      const componentEl = el.closest('[data-nova-component]');
      if (!componentEl) return;

      const componentId = componentEl.dataset.novaId;
      const [action, ...args] = expression.split('|');

      // Bind to Alpine's event system
      el.addEventListener('click', async (e) => {
        e.preventDefault();

        // Call Nova action
        const response = await fetch('/nova/action', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            component: componentId,
            action: action.trim(),
            params: args.length ? evaluate(args[0]) : {}
          })
        });

        const html = await response.text();
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const newElement = temp.querySelector(`[data-nova-id="${componentId}"]`);
        if (newElement) {
          componentEl.parentNode.replaceChild(newElement, componentEl);
        }
      });
    });
  });
})();
