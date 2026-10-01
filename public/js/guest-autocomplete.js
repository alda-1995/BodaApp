document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('name_autocomplete');
    const suggestions = document.getElementById('suggestions');
    let validOptions = [];

    if (!input || !suggestions) return;

    // Cargar todos los invitados al inicio
    fetch('/guests/autocomplete/all')
        .then(response => response.json())
        .then(data => {
            validOptions = data;
        })
        .catch(() => {
            console.error('Error al cargar invitados');
        });

    input.addEventListener('focus', function () {
        if (validOptions.length > 0) {
            renderSuggestions(validOptions);
        }
    });

    input.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();

        // Mostrar loading
        suggestions.classList.remove('hidden');
        suggestions.classList.add('loading');
        suggestions.innerHTML = '';

        setTimeout(() => {
            const filtered = validOptions.filter(option =>
                option.toLowerCase().includes(term)
            );

            suggestions.classList.remove('loading');
            suggestions.innerHTML = '';

            if (filtered.length === 0) {
                suggestions.classList.add('hidden');
                return;
            }

            renderSuggestions(filtered);
        }, 300); // puedes ajustar el tiempo según sensación de carga
    });

    function renderSuggestions(items) {
        suggestions.classList.remove('hidden');
        suggestions.innerHTML = '';
        items.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item;
            li.addEventListener('click', function () {
                input.value = item;
                suggestions.innerHTML = '';
                suggestions.classList.add('hidden');
            });
            suggestions.appendChild(li);
        });
    }

    document.addEventListener('click', function (e) {
        if (!suggestions.contains(e.target) && e.target !== input) {
            suggestions.innerHTML = '';
            suggestions.classList.add('hidden');
        }
    });
});