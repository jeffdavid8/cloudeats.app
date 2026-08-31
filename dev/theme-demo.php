<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaBrain Theme Utilities Demo</title>
    <?php
    require_once __DIR__ . '/includes/theme/ThemeManager.php';
    $themeManager = new ThemeManager();
    echo $themeManager->getThemeCSS();
    ?>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: var(--background-color, #f5f5f5);
            color: var(--text-color, #333);
            font-family: 'Roboto', Arial, sans-serif;
        }
        .demo-section {
            margin: 30px 0;
        }
        .demo-title {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 15px;
            color: var(--primary-color, #2196F3);
        }
    </style>
</head>
<body>
    <div class="mb-container">
        <h1 class="mb-heading mb-heading-1 mb-text-center">MediaBrain Theme Utilities Demo</h1>
        <p class="mb-text mb-text-center mb-text-muted">
            Use these utility classes in your HTML just like Materialize CSS
        </p>

        <!-- Theme Selector -->
        <div class="demo-section">
            <h2 class="demo-title">Theme Selection</h2>
            <div class="mb-row">
                <div class="mb-col-6">
                    <button class="mb-btn mb-btn-primary" onclick="switchTheme('default')">Default Theme</button>
                    <button class="mb-btn mb-btn-accent" onclick="switchTheme('startrek')">Star Trek LCARS</button>
                </div>
                <div class="mb-col-6">
                    <div class="mb-alert mb-alert-info">
                        <strong>Current Theme:</strong> <span id="current-theme"><?php echo $themeManager->getCurrentTheme(); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Button Examples -->
        <div class="demo-section">
            <h2 class="demo-title">Buttons (.mb-btn)</h2>
            <div class="mb-row">
                <div class="mb-col-12">
                    <button class="mb-btn">Default Button</button>
                    <button class="mb-btn mb-btn-primary">Primary Button</button>
                    <button class="mb-btn mb-btn-secondary">Secondary Button</button>
                    <button class="mb-btn mb-btn-accent">Accent Button</button>
                    <button class="mb-btn mb-btn-large mb-btn-primary">Large Button</button>
                </div>
            </div>
        </div>

        <!-- Card Examples -->
        <div class="demo-section">
            <h2 class="demo-title">Cards (.mb-card)</h2>
            <div class="mb-row">
                <div class="mb-col-4">
                    <div class="mb-card mb-hover-lift">
                        <div class="mb-card-content">
                            <h3 class="mb-card-title">Basic Card</h3>
                            <p class="mb-text">This is a basic card using .mb-card utility class.</p>
                        </div>
                        <div class="mb-card-action">
                            <button class="mb-btn mb-btn-flat">Action</button>
                        </div>
                    </div>
                </div>
                <div class="mb-col-4">
                    <div class="mb-card mb-card-stat mb-hover-glow">
                        <h3 class="mb-heading mb-heading-3">42</h3>
                        <p class="mb-text mb-text-muted">Statistics Card</p>
                    </div>
                </div>
                <div class="mb-col-4">
                    <div class="mb-card mb-hover-scale">
                        <div class="mb-card-content">
                            <h3 class="mb-card-title">Interactive Card</h3>
                            <p class="mb-text">Hover effects with .mb-hover-scale</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Examples -->
        <div class="demo-section">
            <h2 class="demo-title">Panels (.mb-panel)</h2>
            <div class="mb-row">
                <div class="mb-col-3">
                    <div class="mb-panel mb-panel-primary mb-p-2">
                        <h4 class="mb-heading mb-heading-3">Primary Panel</h4>
                        <p class="mb-text mb-text-small">Using .mb-panel-primary</p>
                    </div>
                </div>
                <div class="mb-col-3">
                    <div class="mb-panel mb-panel-secondary mb-p-2">
                        <h4 class="mb-heading mb-heading-3">Secondary Panel</h4>
                        <p class="mb-text mb-text-small">Using .mb-panel-secondary</p>
                    </div>
                </div>
                <div class="mb-col-3">
                    <div class="mb-panel mb-panel-accent mb-p-2">
                        <h4 class="mb-heading mb-heading-3">Accent Panel</h4>
                        <p class="mb-text mb-text-small">Using .mb-panel-accent</p>
                    </div>
                </div>
                <div class="mb-col-3">
                    <div class="mb-panel mb-panel-dark mb-p-2">
                        <h4 class="mb-heading mb-heading-3">Dark Panel</h4>
                        <p class="mb-text mb-text-small">Using .mb-panel-dark</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Examples -->
        <div class="demo-section">
            <h2 class="demo-title">Alerts (.mb-alert)</h2>
            <div class="mb-alert mb-alert-info mb-fade-in">
                <strong>Information:</strong> This is an info alert using .mb-alert-info
            </div>
            <div class="mb-alert mb-alert-success mb-fade-in">
                <strong>Success:</strong> This is a success alert using .mb-alert-success
            </div>
            <div class="mb-alert mb-alert-warning mb-fade-in">
                <strong>Warning:</strong> This is a warning alert using .mb-alert-warning
            </div>
            <div class="mb-alert mb-alert-error mb-fade-in">
                <strong>Error:</strong> This is an error alert using .mb-alert-error
            </div>
        </div>

        <!-- Form Examples -->
        <div class="demo-section">
            <h2 class="demo-title">Form Elements</h2>
            <div class="mb-row">
                <div class="mb-col-6">
                    <label class="mb-text">Text Input (.mb-input):</label>
                    <input type="text" class="mb-input" placeholder="Enter text here">
                </div>
                <div class="mb-col-6">
                    <label class="mb-text">Select (.mb-select):</label>
                    <select class="mb-select">
                        <option>Option 1</option>
                        <option>Option 2</option>
                        <option>Option 3</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Navigation Example -->
        <div class="demo-section">
            <h2 class="demo-title">Navigation (.mb-nav)</h2>
            <nav class="mb-nav">
                <div class="mb-nav-item">
                    <a href="#" class="mb-nav-link mb-nav-active">Home</a>
                </div>
                <div class="mb-nav-item">
                    <a href="#" class="mb-nav-link">Dashboard</a>
                </div>
                <div class="mb-nav-item">
                    <a href="#" class="mb-nav-link">Settings</a>
                </div>
                <div class="mb-nav-item">
                    <a href="#" class="mb-nav-link">Profile</a>
                </div>
            </nav>
        </div>

        <!-- Usage Instructions -->
        <div class="demo-section">
            <div class="mb-panel mb-panel-light mb-p-3">
                <h3 class="mb-heading mb-heading-2">How to Use</h3>
                <p class="mb-text">
                    These utility classes work just like <strong>Materialize CSS</strong>. 
                    Simply add the classes to your HTML elements:
                </p>
                <pre class="mb-text mb-text-small" style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;">
&lt;!-- Use utility classes in your HTML --&gt;
&lt;div class="mb-card"&gt;
    &lt;div class="mb-card-content"&gt;
        &lt;h3 class="mb-card-title"&gt;Card Title&lt;/h3&gt;
        &lt;p class="mb-text"&gt;Card content goes here.&lt;/p&gt;
    &lt;/div&gt;
    &lt;div class="mb-card-action"&gt;
        &lt;button class="mb-btn mb-btn-primary"&gt;Action&lt;/button&gt;
    &lt;/div&gt;
&lt;/div&gt;

&lt;!-- Theme-aware styling --&gt;
&lt;div class="mb-panel mb-panel-primary mb-hover-glow"&gt;
    &lt;h4 class="mb-heading"&gt;Panel Title&lt;/h4&gt;
    &lt;p class="mb-text mb-text-muted"&gt;Panel content&lt;/p&gt;
&lt;/div&gt;
                </pre>
                <div class="mb-alert mb-alert-info mb-m-2">
                    <strong>Cross-Theme Compatibility:</strong> These classes automatically 
                    adapt to the current theme (Default or Star Trek LCARS) while maintaining 
                    consistent behavior and structure.
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Switching JavaScript -->
    <script>
        function switchTheme(themeName) {
            fetch('/?app=admin&api=themes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'switch',
                    theme: themeName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('current-theme').textContent = themeName;
                    // Reload page to apply new theme
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Failed to switch theme: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Theme switch error:', error);
                alert('Failed to switch theme');
            });
        }

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add click sound effects for Star Trek theme (if available)
            const buttons = document.querySelectorAll('.mb-btn');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    // Add visual feedback
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>