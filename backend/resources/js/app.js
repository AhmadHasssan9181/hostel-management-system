import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const root = document.documentElement;
const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const applyTheme = (theme) => {
    const dark = theme === 'dark';

    root.classList.toggle('dark', dark);
    root.style.colorScheme = dark ? 'dark' : 'light';

    document
        .querySelectorAll('meta[name="theme-color"]')
        .forEach((meta) => meta.setAttribute('content', dark ? '#0b0d0f' : '#faf9f5'));
};

const currentTheme = () => (root.classList.contains('dark') ? 'dark' : 'light');

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');
    if (!toggle) {
        return;
    }

    event.preventDefault();

    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    localStorage.setItem('hms-theme', next);
    applyTheme(next);
});

window.addEventListener('storage', (event) => {
    if (event.key === 'hms-theme' && event.newValue) {
        applyTheme(event.newValue);
    }
});

applyTheme(currentTheme());

function animateNumber(el) {
    const raw = (el.dataset.count ?? el.textContent ?? '').trim();
    const match = raw.match(/^([^0-9-]*)(-?\d[\d,]*(?:\.\d+)?)(.*)$/);

    if (!match) {
        return;
    }

    const [, prefix, numPart, suffix] = match;
    const target = parseFloat(numPart.replace(/,/g, ''));

    if (!Number.isFinite(target) || target === 0) {
        return;
    }

    const decimals = numPart.includes('.') ? numPart.split('.')[1].length : 0;
    const useCommas = numPart.includes(',');
    const duration = 680;
    const start = performance.now();

    const format = (val) => {
        const fixed = val.toFixed(decimals);

        if (!useCommas) {
            return fixed;
        }

        const [intPart, decPart] = fixed.split('.');
        const withCommas = Number(intPart).toLocaleString('en-US');

        return decPart ? `${withCommas}.${decPart}` : withCommas;
    };

    const tick = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 4);

        el.textContent = `${prefix}${format(target * eased)}${suffix}`;

        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            el.textContent = raw;
        }
    };

    requestAnimationFrame(tick);
}

if (!prefersReduced) {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-count]').forEach(animateNumber);
    });
}
