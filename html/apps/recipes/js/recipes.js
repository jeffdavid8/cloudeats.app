// Recipe Manager JavaScript
class RecipeManager {
    constructor() {
        this.baseUrl = window.location.pathname;
        this.initializeEventListeners();
    }
    
    initializeEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.handleSearch.bind(this));
        }
        
        // Category filter
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', this.handleCategoryFilter.bind(this));
        }
    }
    
    handleSearch(event) {
        const query = event.target.value.toLowerCase();
        this.filterRecipes(query);
    }
    
    handleCategoryFilter(event) {
        const category = event.target.value;
        this.filterRecipes(null, category);
    }
    
    filterRecipes(searchQuery = null, categoryFilter = null) {
        const recipeItems = document.querySelectorAll('.recipe-item');
        
        recipeItems.forEach(item => {
            const title = item.querySelector('.card-title').textContent.toLowerCase();
            const description = item.querySelector('.card-content p:last-of-type').textContent.toLowerCase();
            const category = item.dataset.category;
            
            let matchesSearch = true;
            let matchesCategory = true;
            
            if (searchQuery !== null) {
                matchesSearch = title.includes(searchQuery) || description.includes(searchQuery);
            }
            
            if (categoryFilter !== null && categoryFilter !== '') {
                matchesCategory = category === categoryFilter;
            }
            
            item.style.display = (matchesSearch && matchesCategory) ? 'block' : 'none';
        });
    }
    
    async deleteRecipe(id) {
        if (!confirm('Are you sure you want to delete this recipe?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}?action=delete&id=${id}`, {
                method: 'POST'
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.reload();
            } else {
                alert('Error deleting recipe');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting recipe');
        }
    }
    
    async saveRecipe(formData, isEdit = false) {
        const data = this.parseFormData(formData);
        const url = isEdit ? `${this.baseUrl}?action=update&id=${data.id}` : `${this.baseUrl}?action=add`;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.error) {
                alert('Error: ' + result.error);
                return false;
            } else {
                return true;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving recipe');
            return false;
        }
    }
    
    parseFormData(formData) {
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
        
        return data;
    }
}

// Text-to-Speech functionality for cook mode
class RecipeTTS {
    constructor(recipe) {
        this.recipe = recipe;
        this.currentReading = null;
        this.isReading = false;
        this.initializeTTS();
    }
    
    initializeTTS() {
        const readButton = document.getElementById('read-recipe');
        const stopButton = document.getElementById('stop-reading');
        const currentStepButton = document.getElementById('read-current-step');
        
        if (readButton) readButton.addEventListener('click', () => this.readEntireRecipe());
        if (stopButton) stopButton.addEventListener('click', () => this.stopReading());
        if (currentStepButton) currentStepButton.addEventListener('click', () => this.readCurrentStep());
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            switch(e.key.toLowerCase()) {
                case 'r':
                    e.preventDefault();
                    this.readEntireRecipe();
                    break;
                case 's':
                    e.preventDefault();
                    this.stopReading();
                    break;
                case 'n':
                    e.preventDefault();
                    this.readCurrentStep();
                    break;
            }
        });
    }
    
    async speakText(text) {
        if (this.isReading) {
            this.stopReading();
        }
        
        try {
            // Use the existing TTS API
            const formData = new FormData();
            formData.append('action', 'text_to_speech');
            formData.append('words', text);
            
            const response = await fetch('/api.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const audioBlob = await response.blob();
                const audio = new Audio(URL.createObjectURL(audioBlob));
                this.currentReading = audio;
                this.isReading = true;
                this.updateReadingButtons(true);
                
                audio.play();
                audio.onended = () => {
                    this.isReading = false;
                    this.currentReading = null;
                    this.updateReadingButtons(false);
                };
                audio.onerror = () => {
                    console.error('Error playing audio');
                    this.fallbackToSpeechSynthesis(text);
                };
            } else {
                throw new Error('TTS API failed');
            }
        } catch (error) {
            console.error('TTS API error:', error);
            this.fallbackToSpeechSynthesis(text);
        }
    }
    
    fallbackToSpeechSynthesis(text) {
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.8;
            utterance.pitch = 1;
            
            utterance.onstart = () => {
                this.isReading = true;
                this.updateReadingButtons(true);
            };
            
            utterance.onend = () => {
                this.isReading = false;
                this.updateReadingButtons(false);
            };
            
            speechSynthesis.speak(utterance);
            this.currentReading = utterance;
        } else {
            alert('Text-to-speech not supported in this browser');
        }
    }
    
    stopReading() {
        if (this.currentReading) {
            if (this.currentReading instanceof Audio) {
                this.currentReading.pause();
                this.currentReading.currentTime = 0;
            } else {
                speechSynthesis.cancel();
            }
            this.currentReading = null;
            this.isReading = false;
            this.updateReadingButtons(false);
        }
    }
    
    updateReadingButtons(reading) {
        const readButton = document.getElementById('read-recipe');
        const stopButton = document.getElementById('stop-reading');
        
        if (readButton) readButton.style.display = reading ? 'none' : 'inline-block';
        if (stopButton) stopButton.style.display = reading ? 'inline-block' : 'none';
    }
    
    readEntireRecipe() {
        let text = `Recipe: ${this.recipe.title}. `;
        
        if (this.recipe.description) {
            text += `Description: ${this.recipe.description}. `;
        }
        
        if (this.recipe.ingredients && this.recipe.ingredients.length > 0) {
            text += `Ingredients: ${this.recipe.ingredients.join(', ')}. `;
        }
        
        if (this.recipe.steps && this.recipe.steps.length > 0) {
            text += 'Cooking steps: ';
            this.recipe.steps.forEach((step, index) => {
                text += `Step ${index + 1}: ${step}. `;
            });
        }
        
        if (this.recipe.notes) {
            text += `Notes: ${this.recipe.notes}`;
        }
        
        this.speakText(text);
    }
    
    readStep(stepIndex) {
        if (this.recipe.steps && this.recipe.steps[stepIndex]) {
            const text = `Step ${stepIndex + 1}: ${this.recipe.steps[stepIndex]}`;
            this.speakText(text);
            
            // Highlight the step
            document.querySelectorAll('.recipe-step').forEach(step => {
                step.style.backgroundColor = '';
            });
            const stepElement = document.querySelector(`[data-step="${stepIndex + 1}"]`);
            if (stepElement) {
                stepElement.style.backgroundColor = '#fff3e0';
            }
        }
    }
    
    readCurrentStep() {
        const steps = document.querySelectorAll('.step-check');
        for (let i = 0; i < steps.length; i++) {
            if (!steps[i].checked) {
                this.readStep(i);
                return;
            }
        }
        // If all steps are checked, read the last step
        if (this.recipe.steps && this.recipe.steps.length > 0) {
            this.readStep(this.recipe.steps.length - 1);
        }
    }
}

// Global functions for backwards compatibility
window.RecipeManager = RecipeManager;
window.RecipeTTS = RecipeTTS;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.recipeManager = new RecipeManager();
});