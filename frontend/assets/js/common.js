// Common JavaScript functions used across multiple pages

// Function to apply theme based on preference
function applyTheme(theme) {
    if (theme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
}

// Load theme preference on page load
document.addEventListener('DOMContentLoaded', function() {
    // Always apply dark theme
    applyTheme('dark');
});

function toggleUserMenu() {
    const userDropdown = document.getElementById('userDropdown');
    userDropdown.classList.toggle('show');
}

// Close the dropdown if the user clicks outside of it
window.onclick = function(event) {
    if (!event.target.matches('.user-btn') && !event.target.closest('.user-btn')) {
        const dropdowns = document.getElementsByClassName('user-dropdown');
        for (let i = 0; i < dropdowns.length; i++) {
            const openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}