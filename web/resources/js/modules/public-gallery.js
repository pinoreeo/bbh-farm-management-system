export const initPublicGallery = () => {
    document.querySelectorAll('[data-gallery-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-gallery-track]');
        const slides = Array.from(track?.children ?? []);
        const previousButton = carousel.querySelector('[data-gallery-prev]');
        const nextButton = carousel.querySelector('[data-gallery-next]');
        const dotsContainer = carousel.querySelector('[data-gallery-dots]');
        let currentIndex = 0;
        let pointerStartX = null;
        let autoplayTimer = null;

        const visibleSlideCount = () => window.matchMedia('(min-width: 768px)').matches ? 2 : 1;
        const lastIndex = () => Math.max(0, slides.length - visibleSlideCount());

        const updateCarousel = () => {
            currentIndex = Math.min(currentIndex, lastIndex());
            const slideWidth = slides[0]?.getBoundingClientRect().width ?? 0;
            const trackStyle = window.getComputedStyle(track);
            const gap = Number.parseFloat(trackStyle.columnGap || trackStyle.gap) || 0;
            track.style.transform = `translate3d(-${currentIndex * (slideWidth + gap)}px, 0, 0)`;

            dotsContainer?.querySelectorAll('button').forEach((dot, index) => {
                const isActive = index === currentIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-current', isActive ? 'true' : 'false');
            });
        };

        const buildDots = () => {
            if (!dotsContainer) return;

            dotsContainer.replaceChildren();
            for (let index = 0; index <= lastIndex(); index += 1) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'bbh-gallery-dot';
                dot.setAttribute('aria-label', `Tampilkan galeri ${index + 1}`);
                dot.addEventListener('click', () => {
                    currentIndex = index;
                    updateCarousel();
                    startAutoplay();
                });
                dotsContainer.appendChild(dot);
            }

            updateCarousel();
        };

        const moveCarousel = (direction) => {
            const end = lastIndex();
            currentIndex = direction > 0
                ? (currentIndex >= end ? 0 : currentIndex + 1)
                : (currentIndex <= 0 ? end : currentIndex - 1);
            updateCarousel();
        };

        const stopAutoplay = () => {
            window.clearInterval(autoplayTimer);
            autoplayTimer = null;
        };

        const startAutoplay = () => {
            stopAutoplay();
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || lastIndex() === 0) return;

            autoplayTimer = window.setInterval(() => moveCarousel(1), 4500);
        };

        const moveFromControl = (direction) => {
            moveCarousel(direction);
            startAutoplay();
        };

        previousButton?.addEventListener('click', () => moveFromControl(-1));
        nextButton?.addEventListener('click', () => moveFromControl(1));

        track?.addEventListener('pointerdown', (event) => {
            pointerStartX = event.clientX;
            stopAutoplay();
        });

        track?.addEventListener('pointerup', (event) => {
            if (pointerStartX === null) return;

            const distance = event.clientX - pointerStartX;
            pointerStartX = null;
            if (Math.abs(distance) >= 48) moveCarousel(distance < 0 ? 1 : -1);
            startAutoplay();
        });

        track?.addEventListener('pointercancel', () => {
            pointerStartX = null;
            startAutoplay();
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        carousel.addEventListener('focusin', stopAutoplay);
        carousel.addEventListener('focusout', (event) => {
            if (!carousel.contains(event.relatedTarget)) startAutoplay();
        });

        document.addEventListener('visibilitychange', () => {
            document.hidden ? stopAutoplay() : startAutoplay();
        });

        let resizeFrame = null;
        window.addEventListener('resize', () => {
            window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(() => {
                buildDots();
                startAutoplay();
            });
        });

        buildDots();
        startAutoplay();
    });
};
