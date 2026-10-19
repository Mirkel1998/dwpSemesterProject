const dayButtons = document.querySelectorAll('[data-day]');
const dayPanels = document.querySelectorAll('[data-day-panel]');
const dateInput = document.querySelector('[data-show-date]');
const dateLabel = document.querySelector('[data-show-date-label]');
const timeInput = document.querySelector('[name="show_time"]');

function selectDay(key) {
    dayButtons.forEach((button) => {
        const active = button.dataset.day === key;
        button.classList.toggle('is-selected', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    dayPanels.forEach((panel) => {
        panel.hidden = panel.dataset.dayPanel !== key;
    });
    if (dateInput) dateInput.value = key;
    if (dateLabel) {
        const [year, month, day] = key.split('-').map(Number);
        dateLabel.textContent = new Date(year, month - 1, day)
            .toLocaleDateString('en-GB', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }
    history.replaceState(null, '', '?day=' + key);
}

dayButtons.forEach((button) => {
    button.addEventListener('click', () => selectDay(button.dataset.day));
});

document.querySelectorAll('[data-time]').forEach((chip) => {
    chip.addEventListener('click', () => {
        if (timeInput) timeInput.value = chip.dataset.time;
    });
});

const monthSelect = document.querySelector('[data-picker-month]');
const yearSelect = document.querySelector('[data-picker-year]');
[monthSelect, yearSelect].forEach((select) => {
    if (!select) return;
    select.addEventListener('change', () => {
        window.location.search = '?month=' + yearSelect.value + '-' + String(monthSelect.value).padStart(2, '0');
    });
});
