export function initResume() {
    const resume = document.querySelector('[data-resume]');
    if (!resume) return;

    const roles = [...resume.querySelectorAll('[data-role]')].map((el) => ({ el, id: el.dataset.role, stack: JSON.parse(el.dataset.stack) }));
    const rows = new Map([...resume.querySelectorAll('[data-bar-row]')].map((row) => [row.dataset.barRow, row]));
    const skillButtons = [...resume.querySelectorAll('[data-skill]')];
    const note = resume.querySelector('[data-filter-note]');

    // ----- Skill filter: highlight the roles that used a skill, dim the rest.
    let current = null;

    function filterBy(skill, { updateUrl = true } = {}) {
        current = skill && roles.some((role) => role.stack.includes(skill)) ? skill : null;
        let hits = 0;

        roles.forEach((role) => {
            const hit = current !== null && role.stack.includes(current);
            hits += hit;
            role.el.classList.toggle('is-hit', hit);
            role.el.classList.toggle('is-dim', current !== null && !hit);
            rows.get(role.id)?.classList.toggle('is-dim', current !== null && !hit);
        });

        skillButtons.forEach((button) => {
            const on = button.dataset.skill === current;
            button.classList.toggle('is-on', on);
            if (button.hasAttribute('aria-pressed')) button.setAttribute('aria-pressed', String(on));
        });

        note.hidden = current === null;
        if (current) {
            note.querySelector('[data-filter-skill]').textContent = current;
            note.querySelector('[data-filter-count]').textContent = `${hits} ${hits === 1 ? 'role' : 'roles'}`;
        }

        if (updateUrl) {
            const url = new URL(window.location);
            current ? url.searchParams.set('skill', current) : url.searchParams.delete('skill');
            history.replaceState(null, '', url);
        }
    }

    resume.addEventListener('click', (event) => {
        const button = event.target.closest('[data-skill]');
        if (button) filterBy(button.dataset.skill === current ? null : button.dataset.skill);
        if (event.target.closest('[data-filter-clear]')) filterBy(null);
    });

    // Arriving from a skill chip on the home page: /resume?skill=PHP
    filterBy(new URLSearchParams(window.location.search).get('skill'), { updateUrl: false });

    // ----- Brief / Full detail toggle, remembered between visits.
    const detailButtons = [...resume.querySelectorAll('[data-detail]')];

    function setDetail(mode) {
        resume.dataset.detailMode = mode;
        detailButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.detail === mode)));
        try {
            localStorage.setItem('resume-detail', mode);
        } catch {}
    }

    detailButtons.forEach((button) => button.addEventListener('click', () => setDetail(button.dataset.detail)));
    let saved = null;
    try {
        saved = localStorage.getItem('resume-detail');
    } catch {}
    setDetail(saved === 'brief' ? 'brief' : 'full');

    // ----- Timeline bars and role cards highlight each other on hover.
    const link = (id, on) => {
        rows.get(id)?.classList.toggle('is-hover', on);
        roles.find((role) => role.id === id)?.el.classList.toggle('is-hover', on);
    };
    rows.forEach((row, id) => {
        row.addEventListener('pointerenter', () => link(id, true));
        row.addEventListener('pointerleave', () => link(id, false));
    });
    roles.forEach((role) => {
        role.el.addEventListener('pointerenter', () => link(role.id, true));
        role.el.addEventListener('pointerleave', () => link(role.id, false));
    });

    // Keep each bar's tooltip under the pointer, without letting it leave the chart.
    const gantt = resume.querySelector('[data-gantt]');
    gantt?.querySelectorAll('[data-bar]').forEach((bar) => {
        const tip = bar.querySelector('.gantt-tip');
        bar.addEventListener('pointermove', (event) => {
            if (event.pointerType === 'touch') return;
            const chart = gantt.getBoundingClientRect();
            const half = tip.offsetWidth / 2;
            const centre = Math.min(Math.max(event.clientX, chart.left + half), chart.right - half);
            bar.style.setProperty('--tx', `${centre - bar.getBoundingClientRect().left}px`);
            bar.classList.add('has-pointer');
        });
        bar.addEventListener('pointerleave', () => bar.classList.remove('has-pointer'));
    });

    resume.querySelector('[data-print]')?.addEventListener('click', () => window.print());
}
