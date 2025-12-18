// Небольшие улучшения UX: подсветка readonly полей и авто-увеличение текстовых областей.
document.addEventListener('input', (event) => {
    if (event.target.tagName === 'TEXTAREA') {
        event.target.style.height = 'auto';
        event.target.style.height = event.target.scrollHeight + 'px';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const readonlyAreas = document.querySelectorAll('textarea[readonly]');
    readonlyAreas.forEach((area) => {
        area.classList.add('bg-gray-50');
    });
});
