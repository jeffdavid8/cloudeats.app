// Help Application JavaScript

$(document).ready(function() {
    initializeHelp();
});

function initializeHelp() {
    // Initialize search functionality
    initializeSearch();
    
    // Initialize smooth scrolling for anchor links
    initializeSmoothScrolling();
    
    // Initialize expandable sections
    //initializeExpandableSections();
    
    // Initialize code copy functionality
    initializeCodeCopy();
    
    // Initialize feedback system
    initializeFeedback();
}

function initializeSearch() {
    const searchInput = document.getElementById('help-search');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = this.value.toLowerCase().trim();
            searchHelpContent(query);
        }, 300);
    });
}

function searchHelpContent(query) {
    const contentSections = document.querySelectorAll('.help-content h2, .help-content h3, .help-content p, .help-content li');
    const sidebar = document.querySelector('.help-sidebar');
    
    if (!query) {
        // Show all content
        contentSections.forEach(section => {
            section.style.display = '';
            section.classList.remove('search-highlight');
        });
        return;
    }
    
    contentSections.forEach(section => {
        const text = section.textContent.toLowerCase();
        if (text.includes(query)) {
            $(section).closest('').style.display = '';
            section.classList.add('search-highlight');
        } else {
            section.style.display = 'none';
            section.classList.remove('search-highlight');
        }
    });
}

function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            if (href && href !== '#' && href.startsWith('#')) {
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
}

function initializeExpandableSections() {
    // Add expand/collapse functionality to certain sections
    const expandableSections = document.querySelectorAll('.help-expandable');
    
    expandableSections.forEach(section => {
        const header = $(section).find('h3, h4')[0];
        if (!header) return;
        
        // Add expand icon
        const icon = document.createElement('i');
        icon.className = 'material-icons expand-icon';
        icon.textContent = 'expand_more';
        icon.style.cursor = 'pointer';
        icon.style.float = 'right';
        $(header).append(icon);
        
        // Initially collapse content
        const content = section.querySelector('.expandable-content');
        if (content) {
            content.style.display = 'none';
        }
        
        // Add click handler
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            toggleExpandableSection(section, icon);
        });
    });
}

function toggleExpandableSection(section, icon) {
    const content = section.querySelector('.expandable-content');
    if (!content) return;
    
    const isExpanded = content.style.display !== 'none';
    
    content.style.display = isExpanded ? 'none' : 'block';
    icon.textContent = isExpanded ? 'expand_more' : 'expand_less';
    
    // Smooth animation
    if (!isExpanded) {
        content.style.maxHeight = '0';
        content.style.overflow = 'hidden';
        setTimeout(() => {
            content.style.maxHeight = content.scrollHeight + 'px';
            content.style.transition = 'max-height 0.3s ease';
        }, 10);
    }
}

function initializeCodeCopy() {
    // Add copy buttons to code blocks
    const codeBlocks = document.querySelectorAll('.help-code-block, pre, code');

    codeBlocks.forEach(block => {
        // Skip if it's inline code
        if (block.tagName === 'CODE' && block.parentElement.tagName !== 'PRE') {
            return;
        }
        // Only wrap <pre> or <code> blocks not already inside a .copy-code-container
        let alreadyWrapped = block.closest('.copy-code-container');
        let alreadyHasButton = block.parentElement.querySelector('.copy-code-btn');
        if (alreadyWrapped || alreadyHasButton) {
            return;
        }
        const container = document.createElement('div');
        container.className = 'copy-code-container';
        container.style.position = 'relative';
        container.style.display = 'inline-block';
        container.style.width = '100%';

        const copyButton = document.createElement('button');
        copyButton.className = 'btn-small copy-code-btn';
        copyButton.innerHTML = '<i class="material-icons">content_copy</i>';
        copyButton.style.position = 'absolute';
        copyButton.style.top = '10px';
        copyButton.style.right = '10px';
        copyButton.style.opacity = '0.7';
        copyButton.style.transition = 'opacity 0.3s ease';

        copyButton.addEventListener('click', () => {
            copyToClipboard(block.textContent);
            showCopyFeedback(copyButton);
        });

        block.parentNode.insertBefore(container, block);
        container.appendChild(block);
        container.appendChild(copyButton);

        // Show button on hover
        container.addEventListener('mouseenter', () => {
            copyButton.style.opacity = '1';
        });

        container.addEventListener('mouseleave', () => {
            copyButton.style.opacity = '0.7';
        });
    });
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
}

function showCopyFeedback(button) {
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="material-icons">check</i>';
    button.style.background = '#4caf50';
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.style.background = '';
    }, 2000);
}

function initializeFeedback() {
    // Add feedback buttons to sections
    const sections = document.querySelectorAll('.help-content h2');
    
    sections.forEach(section => {
        // Prevent duplicate feedback containers
        let sibling = section.nextElementSibling;
        while (sibling && sibling.nodeType === 1 && !sibling.matches('h2')) {
            if (sibling.classList && sibling.classList.contains('help-feedback')) {
                return; // Already has feedback container after this section
            }
            sibling = sibling.nextElementSibling;
        }
        const feedbackContainer = document.createElement('div');
        feedbackContainer.className = 'help-feedback';
        feedbackContainer.style.marginTop = '20px';
        feedbackContainer.style.padding = '15px';
        //feedbackContainer.style.background = '#f8f9fa';
        feedbackContainer.style.borderRadius = '6px';
        feedbackContainer.style.fontSize = '0.9em';
        feedbackContainer.innerHTML = `
            <p><strong>Was this section helpful?</strong></p>
            <button class="btn-small feedback-btn" data-feedback="yes" data-section="${section.id || section.textContent.trim()}">
                <i class="material-icons left">thumb_up</i>Yes
            </button>
            <button class="btn-small feedback-btn" data-feedback="no" data-section="${section.id || section.textContent.trim()}">
                <i class="material-icons left">thumb_down</i>No
            </button>
        `;
        // Insert after the section's content
        let nextElement = section.nextElementSibling;
        while (nextElement && !nextElement.matches('h2')) {
            nextElement = nextElement.nextElementSibling;
        }
        if (nextElement) {
            nextElement.parentNode.insertBefore(feedbackContainer, nextElement);
        } else {
            section.parentNode.appendChild(feedbackContainer);
        }
    });
    
    // Handle feedback clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('feedback-btn') || e.target.closest('.feedback-btn')) {
            const button = e.target.closest('.feedback-btn');
            const feedback = button.dataset.feedback;
            const section = button.dataset.section;
            
            submitFeedback(section, feedback, button);
        }
    });
}

function submitFeedback(section, feedback, button) {
    // In a real implementation, this would send feedback to the server
    console.log(`Feedback: ${feedback} for section: ${section}`);
    
    // Show thank you message
    const container = button.parentElement;
    container.innerHTML = `
        <p style="margin: 0;">
            <i class="material-icons" style="vertical-align: middle; font-size: 1.2em;">check_circle</i>
            Thank you for your feedback!
        </p>
    `;
    
    // You could send this to an analytics service or database
    // trackEvent('help_feedback', { section, feedback });
}

// Utility functions
function highlightCode() {
    // If you're using a syntax highlighter like Prism.js or highlight.js
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }
}

function updateTableOfContents() {
    const toc = document.querySelector('.help-toc ul');
    if (!toc) return;
    
    const headings = document.querySelectorAll('.help-content h2, .help-content h3');
    toc.innerHTML = '';
    
    headings.forEach((heading, index) => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        
        // Create ID if it doesn't exist
        if (!heading.id) {
            heading.id = 'heading-' + index;
        }
        
        a.href = '#' + heading.id;
        a.textContent = heading.textContent;
        //a.style.paddingLeft = heading.tagName === 'H3' ? '20px' : '0';
        
        li.appendChild(a);
        toc.appendChild(li);
    });
}

// Initialize table of contents on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(updateTableOfContents, 100);
});

// Export functions for use in other scripts
window.helpApp = {
    search: searchHelpContent,
    updateTOC: updateTableOfContents,
    highlightCode: highlightCode
};