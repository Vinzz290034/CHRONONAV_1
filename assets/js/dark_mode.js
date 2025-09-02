document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;

    // Function to set the theme based on local storage
    function applyTheme() {
        const currentMode = localStorage.getItem('darkMode');
        if (currentMode === 'enabled') {
            body.classList.add('dark-mode');
            // Check the switch on the settings page if it exists
            const darkModeSwitch = document.getElementById('darkModeSwitch');
            if (darkModeSwitch) {
                darkModeSwitch.checked = true;
            }
        }
    }

    // Apply the theme on initial page load
    applyTheme();

    // Listen for changes on the settings page switch
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    if (darkModeSwitch) {
        darkModeSwitch.addEventListener('change', function() {
            if (this.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    }
});