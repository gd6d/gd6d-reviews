(() => {
	'use strict';

	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	document.querySelectorAll('[data-gd6d-reviews-carousel]').forEach((carousel) => {
		const viewport = carousel.querySelector('[data-gd6d-reviews-viewport]');
		const track = carousel.querySelector('.gd6d-reviews__list');
		const slides = [...carousel.querySelectorAll('[data-gd6d-reviews-slide]')];
		const dots = [...carousel.querySelectorAll('[data-gd6d-reviews-dot]')];
		const previous = carousel.querySelector('[data-gd6d-reviews-previous]');
		const next = carousel.querySelector('[data-gd6d-reviews-next]');

		if (!viewport || slides.length < 2) {
			return;
		}

		let activeIndex = 0;
		let timer = null;
		let paused = false;
		const delay = Number.parseInt(carousel.dataset.autoplayDelay || '6000', 10);

		const updateDots = (index) => {
			dots.forEach((dot, dotIndex) => {
				const isActive = dotIndex === index;
				dot.classList.toggle('is-active', isActive);
				dot.setAttribute('aria-current', isActive ? 'true' : 'false');
			});
		};

		const goTo = (index, smooth = true) => {
	activeIndex = (index + slides.length) % slides.length;

	track.scrollTo({
		left: slides[activeIndex].offsetLeft,
		behavior: smooth && !reducedMotion ? 'smooth' : 'auto',
	});

	updateDots(activeIndex);
};

		const stop = () => {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		};

		const start = () => {
			stop();
			if (!reducedMotion && !paused) {
				timer = window.setInterval(() => goTo(activeIndex + 1), delay);
			}
		};

		previous?.addEventListener('click', () => {
			goTo(activeIndex - 1);
			start();
		});

		next?.addEventListener('click', () => {
			goTo(activeIndex + 1);
			start();
		});

		dots.forEach((dot, index) => {
			dot.addEventListener('click', () => {
				goTo(index);
				start();
			});
		});

		carousel.addEventListener('mouseenter', () => {
			paused = true;
			stop();
		});

		carousel.addEventListener('mouseleave', () => {
			paused = false;
			start();
		});

		carousel.addEventListener('focusin', () => {
			paused = true;
			stop();
		});

		carousel.addEventListener('focusout', (event) => {
			if (!carousel.contains(event.relatedTarget)) {
				paused = false;
				start();
			}
		});

		track.addEventListener('scrollend', () => {
	const trackLeft = track.getBoundingClientRect().left;
	let closestIndex = 0;
	let closestDistance = Number.POSITIVE_INFINITY;

	slides.forEach((slide, index) => {
		const distance = Math.abs(
			slide.getBoundingClientRect().left - trackLeft
		);

		if (distance < closestDistance) {
			closestDistance = distance;
			closestIndex = index;
		}
	});

	activeIndex = closestIndex;
	updateDots(activeIndex);
});

		start();
	});
})();
