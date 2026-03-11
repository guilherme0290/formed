document.addEventListener('DOMContentLoaded', () => {
    const revealItems = Array.from(document.querySelectorAll('.cc-reveal'));
    if (!revealItems.length) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.18,
        rootMargin: '0px 0px -60px 0px',
    });

    revealItems.forEach((item, index) => {
        item.style.transitionDelay = `${Math.min(index * 40, 220)}ms`;
        observer.observe(item);
    });
});
