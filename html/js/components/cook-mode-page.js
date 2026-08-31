/**
 * Cook Mode Page Component
 * Handles text-to-speech functionality, step management, audio controls, and keyboard shortcuts
 */

mb.registerComponent('cook-mode-page', function($element, data) {
    const recipe = data.recipe || {};
    let currentReading = null;
    let isReading = false;

    // Stop reading
    function stopReading() {
        if (currentReading) {
            if (currentReading instanceof Audio) {
                currentReading.pause();
                currentReading.currentTime = 0;
            } else {
                speechSynthesis.cancel();
            }
            currentReading = null;
        }
        
        // Also stop the TTS audio element if it's playing
        if (mb.ttsAudio && !mb.ttsAudio.paused) {
            mb.ttsAudio.pause();
            mb.ttsAudio.currentTime = 0;
        }
        
        isReading = false;
        updateReadingButtons(false);
    }

    // Update button states
    function updateReadingButtons(reading) {
        document.getElementById('read-recipe').style.display = reading ? 'none' : 'inline-block';
        document.getElementById('stop-reading').style.display = reading ? 'inline-block' : 'none';
    }

    // Read entire recipe
    function readEntireRecipe() {
        let text = `Recipe: ${recipe.title}. `;
        
        if (recipe.description) {
            text += `Description: ${recipe.description}. `;
        }
        
        if (recipe.ingredients && recipe.ingredients.length > 0) {
            text += `Ingredients: ${recipe.ingredients.join(', ')}. `;
        }
        
        if (recipe.steps && recipe.steps.length > 0) {
            text += 'Cooking steps: ';
            recipe.steps.forEach((step, index) => {
                text += `Step ${index + 1}: ${step}. `;
            });
        }
        
        if (recipe.notes) {
            text += `Notes: ${recipe.notes}`;
        }
        
        currentReading = true;
        updateReadingButtons(currentReading);
        loading(1);
        speak(text, function(utterance) {
            loading(0);
        });
    }

    // Read specific step
    function readStep(stepIndex) {
        if (recipe.steps && recipe.steps[stepIndex]) {
            const text = `Step ${stepIndex + 1}: ${recipe.steps[stepIndex]}`;
            speak(text);
            
            // Highlight the step
            document.querySelectorAll('.recipe-step').forEach(step => {
                step.style.backgroundColor = '';
            });
            const highlightColor = document.body.classList.contains('nightMode') ? '#616161' : '#fff3e0';
            document.querySelector(`[data-step="${stepIndex + 1}"]`).style.backgroundColor = highlightColor;
        }
    }

    // Read current step (first unchecked step)
    function readCurrentStep() {
        const steps = document.querySelectorAll('.step-check');
        for (let i = 0; i < steps.length; i++) {
            if (!steps[i].checked) {
                readStep(i);
                return;
            }
        }
        // If all steps are checked, read the last step
        if (recipe.steps && recipe.steps.length > 0) {
            readStep(recipe.steps.length - 1);
        }
    }

    // Event listeners
    $('#read-recipe').on('click', readEntireRecipe);
    $('#stop-reading').on('click', stopReading);
    $('#read-current-step').on('click', readCurrentStep);
    
    // Auto-highlight first step
    if (recipe.steps && recipe.steps.length > 0) {
        const highlightColor = document.body.classList.contains('nightMode') ? '#616161' : '#fff3e0';
        $('[data-step="1"]')[0].style.backgroundColor = highlightColor;
    }

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch(e.key.toLowerCase()) {
            case 'r':
                e.preventDefault();
                readEntireRecipe();
                break;
            case 's':
                e.preventDefault();
                stopReading();
                break;
            case 'n':
                e.preventDefault();
                readCurrentStep();
                break;
        }
    });

    // Make readStep function available globally for onclick handlers
    window.readStep = readStep;

}, ['loading', 'speak']);