/**
 * Recipe Form Component
 * Handles dynamic ingredient/step management, image preview, form validation, and submission
 */

mb.registerComponent('recipe-form', function($element, data) {
    
    // Add ingredient
    window.addIngredient = function() {
        const ingredientList = document.getElementById('ingredient-list');
        const newItem = document.createElement('div');
        newItem.className = 'ingredient-item';
        newItem.innerHTML = `
            <input type="text" placeholder="Ingredient" name="ingredients[]">
            <button type="button" class="btn-remove" onclick="removeIngredient(this)">Remove</button>
        `;
        ingredientList.appendChild(newItem);
    };

    // Remove ingredient
    window.removeIngredient = function(button) {
        const ingredientList = document.getElementById('ingredient-list');
        if (ingredientList.children.length > 1) {
            button.parentElement.remove();
        }
    };

    // Add step
    window.addStep = function() {
        const stepList = document.getElementById('step-list');
        const stepNumber = stepList.children.length + 1;
        const newItem = document.createElement('div');
        newItem.className = 'step-item';
        newItem.innerHTML = `
            <textarea placeholder="Step ${stepNumber}" name="steps[]" class="materialize-textarea"></textarea>
            <button type="button" class="btn-remove" onclick="removeStep(this)">Remove</button>
        `;
        stepList.appendChild(newItem);
        
        // Initialize the new textarea
        $(newItem.find('textarea')).trigger('autoresize');
    };

    // Remove step
    window.removeStep = function(button) {
        const stepList = document.getElementById('step-list');
        if (stepList.children.length > 1) {
            button.parentElement.remove();
            updateStepNumbers();
        }
    };

    // Update step placeholders
    function updateStepNumbers() {
        const stepList = document.getElementById('step-list');
        Array.from(stepList.children).forEach((item, index) => {
            const textarea = item.querySelector('textarea');
            textarea.placeholder = `Step ${index + 1}`;
        });
    }

    // Update image preview
    function updateImagePreview(url) {
        let previewContainer = document.querySelector('.image-preview');
        
        if (!url.trim()) {
            if (previewContainer) {
                previewContainer.remove();
            }
            return;
        }
        
        if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.className = 'image-preview';
            document.getElementById('imageUrl').parentNode.appendChild(previewContainer);
        }
        
        // Test if the URL is valid by creating an image
        const testImg = new Image();
        testImg.onload = function() {
            previewContainer.innerHTML = `<img src="${url}" alt="Recipe preview" style="max-width: 200px; max-height: 150px; border-radius: 4px;">`;
        };
        testImg.onerror = function() {
            previewContainer.innerHTML = '<p class="red-text">Invalid image URL</p>';
        };
        testImg.src = url;
    }

    // Initialize Materialize select
    $('select').formSelect();
    
    // Image URL preview
    const imageUrlInput = document.getElementById('imageUrl');
    if (imageUrlInput) {
        imageUrlInput.addEventListener('input', function() {
            updateImagePreview(this.value);
        });
    }

    // Form submission
    document.getElementById('recipe-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        // Collect basic fields
        for (let [key, value] of formData.entries()) {
            if (key !== 'ingredients[]' && key !== 'steps[]') {
                data[key] = value;
            }
        }
        
        // Collect ingredients (filter empty)
        data.ingredients = Array.from(formData.getAll('ingredients[]')).filter(ing => ing.trim());
        
        // Collect steps (filter empty)
        data.steps = Array.from(formData.getAll('steps[]')).filter(step => step.trim());
        
        const isEdit = data.id;
        const url = isEdit ? `?app=recipes&action=update&id=${data.id}` : '?app=recipes&action=add';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.error) {
                alert('Error: ' + result.error);
            } else {
                window.location.href = '?app=recipes&p=list';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving recipe');
        });
    });

}, []);