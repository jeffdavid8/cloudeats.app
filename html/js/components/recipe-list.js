/**
 * Recipe List Component
 * Handles search/filter functionality and recipe deletion
 */

mb.registerComponent('recipe-list', function($element, data) {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const recipeItems = document.querySelectorAll('.recipe-item');
    
    function filterRecipes() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;
        
        recipeItems.forEach(item => {
            const title = item.querySelector('.card-title').textContent.toLowerCase();
            const description = item.querySelector('p:last-of-type').textContent.toLowerCase();
            const category = item.dataset.category;
            
            const matchesSearch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);
            const matchesCategory = !selectedCategory || category === selectedCategory;
            
            if (matchesSearch && matchesCategory) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterRecipes);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterRecipes);
    }
    
    // Initialize Materialize select
    $('select').formSelect();

    // Delete recipe function - make it globally available for onclick handlers
    window.deleteRecipe = function(id) {
        if (confirm('Are you sure you want to delete this recipe?')) {
            fetch(`?app=recipes&action=delete&id=${id}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting recipe');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting recipe');
            });
        }
    };

}, []);