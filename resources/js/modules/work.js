import { track } from './track';

const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

// Animate layout changes where the browser supports view transitions.
function transition(change) {
    if (document.startViewTransition && !reduceMotion) {
        document.startViewTransition(change);
    } else {
        change();
    }
}

function initFilters(grid) {
    const filters = document.querySelectorAll('[data-filter]');
    const cards = [...grid.querySelectorAll('[data-project]')];

    filters.forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.dataset.filter;

            transition(() => {
                filters.forEach((other) => {
                    const on = other === button;
                    other.classList.toggle('is-on', on);
                    other.setAttribute('aria-pressed', String(on));
                });
                cards.forEach((card) => {
                    card.hidden = group !== 'all' && !card.dataset.groups.split(' ').includes(group);
                    card.classList.add('is-in');
                });
            });
        });
    });
}

function initGlow(grid) {
    grid.addEventListener('pointermove', (event) => {
        const card = event.target.closest('.card');
        if (!card) return;
        const box = card.getBoundingClientRect();
        card.style.setProperty('--mx', `${event.clientX - box.left}px`);
        card.style.setProperty('--my', `${event.clientY - box.top}px`);
    });
}

function initViewer(grid) {
    const dialog = document.querySelector('[data-viewer]');
    const data = document.getElementById('projects-data');
    if (!dialog || !data || !dialog.showModal) return;

    const projects = JSON.parse(data.textContent);
    const part = (name) => dialog.querySelector(`[data-viewer-${name}]`);
    let visible = projects;
    let index = 0;

    let img = null;
    let name = null;

    function show(next) {
        // The screenshot and heading are only created once a preview is opened, so the closed
        // dialog never puts an image with no source or an empty heading in the document.
        img ??= part('shot').insertAdjacentElement('afterbegin', document.createElement('img'));
        name ??= part('cat').insertAdjacentElement('afterend', document.createElement('h3'));

        index = (next + visible.length) % visible.length;
        const project = visible[index];
        const empty = part('empty');

        part('cat').textContent = project.category;
        name.textContent = project.name;
        part('summary').textContent = project.summary;
        part('tags').replaceChildren(
            ...project.stack.map((tag) => Object.assign(document.createElement('li'), { textContent: tag })),
        );
        part('url').href = project.url;
        part('case').hidden = !project.case_url;
        if (project.case_url) part('case').href = project.case_url;
        part('count').textContent = `${index + 1} / ${visible.length}`;

        img.hidden = !project.image;
        empty.hidden = Boolean(project.image);
        empty.textContent = project.host;
        if (project.image) {
            img.src = project.image;
            img.alt = `Full-page screenshot of the ${project.name} website`;
        }
        part('shot').scrollTop = 0;
    }

    grid.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-project-open]');
        // Let modified clicks (new tab, etc.) go straight to the live site.
        if (!opener || event.metaKey || event.ctrlKey || event.shiftKey) return;
        event.preventDefault();

        // Step through only the projects the current filter is showing.
        const shown = [...grid.querySelectorAll('[data-project]:not([hidden])')].map((card) => card.dataset.project);
        visible = projects.filter((project) => shown.includes(project.slug));
        show(shown.indexOf(opener.closest('[data-project]').dataset.project));
        dialog.showModal();
        track('project_preview', { project: visible[index].name });
    });

    part('prev').addEventListener('click', () => show(index - 1));
    part('next').addEventListener('click', () => show(index + 1));
    part('close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(index - 1);
        if (event.key === 'ArrowRight') show(index + 1);
    });
}

export function initWork() {
    const grid = document.querySelector('[data-work-grid]');
    if (!grid) return;

    initFilters(grid);
    initGlow(grid);
    initViewer(grid);
}
