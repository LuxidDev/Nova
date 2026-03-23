<?php
require_once __DIR__ . '/../bootstrap.php';

// This should now work because components.php is loaded
$instanceId = 'working-counter_' . uniqid();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nova Working Test</title>
    <meta name="csrf-token" content="<?php echo bin2hex(random_bytes(32)); ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .demo-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        button {
            transition: transform 0.1s;
            cursor: pointer;
        }
        button:active {
            transform: scale(0.95);
        }
        .info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <h1>✨ Nova Working Demo</h1>
        <p>Click the buttons below - they should update the counter without page reload!</p>
        
        <?php echo nova('working-counter', ['_instance' => $instanceId]); ?>
        
        <div class="info">
            <strong>How it works:</strong><br>
            - @click directives are compiled to data-nova-click attributes<br>
            - Nova.js intercepts clicks and sends AJAX requests<br>
            - Server processes actions and returns updated HTML<br>
            - Component updates without page reload<br>
            <br>
            <strong>Debug:</strong><br>
            - Open browser console (F12) to see JavaScript logs<br>
            - Check nova-debug.log for server-side logs<br>
            <br>
            <strong>Component Info:</strong><br>
            <pre id="debug-info"></pre>
        </div>
    </div>
    
    <script src="/nova.js"></script>
    <script>
        // Debug: Log when the component is updated
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    console.log('Component updated!');
                    const count = document.querySelector('[data-nova-component] .count-value')?.innerText || 
                                   document.querySelector('[data-nova-component] div[style*="font-size: 72px"]')?.innerText;
                    if (count) {
                        document.getElementById('debug-info').innerText = 'Current count: ' + count;
                    }
                }
            });
        });
        
        // Start observing after the component is loaded
        setTimeout(() => {
            const component = document.querySelector('[data-nova-component]');
            if (component) {
                observer.observe(component, { childList: true, subtree: true });
                console.log('Observing component:', component.dataset.novaId);
            }
        }, 100);
    </script>
</body>
</html>
