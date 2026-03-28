// Филтриране на галерията
const filterBtns = document.querySelectorAll('.filter-btn');
const items = document.querySelectorAll('.masonry-item');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filterValue = btn.getAttribute('data-filter');

        items.forEach(item => {
            if (filterValue === 'all' || item.classList.contains(filterValue)) {
                item.classList.remove('hidden');
                item.style.display = 'block';
            } else {
                item.classList.add('hidden');
                setTimeout(() => {
                    if (item.classList.contains('hidden')) {
                        item.style.display = 'none';
                    }
                }, 400);
            }
        });
    });
});

// Функция за отваряне на лайтбокса
function openLightbox(element) {
    const link = element.querySelector('.glightbox');
    if (link) link.click();
}
