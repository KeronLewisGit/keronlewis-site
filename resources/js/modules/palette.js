import { copyText } from './copy';
import { toggleTheme } from './theme';
import { track } from './track';

export function initPalette() {
    const dialog = document.querySelector('[data-palette]');
    const data = document.getElementById('palette-data');
    if (!dialog || !data || !dialog.showModal) return;

    const commands = JSON.parse(data.textContent);
    const input = dialog.querySelector('[data-palette-input]');
    const list = dialog.querySelector('[data-palette-list]');
    const empty = dialog.querySelector('[data-palette-empty]');
    let matches = [];
    let active = 0;

    const isMac = /Mac|iPhone|iPad/.test(navigator.platform);
    document.querySelectorAll('[data-mod-key]').forEach((el) => (el.textContent = isMac ? '⌘K' : 'Ctrl K'));

    function render() {
        const words = input.value.toLowerCase().split(/\s+/).filter(Boolean);
        matches = commands.filter((command) => {
            const haystack = `${command.label} ${command.hint ?? ''} ${command.group}`.toLowerCase();
            return words.every((word) => haystack.includes(word));
        });
        active = Math.min(active, Math.max(matches.length - 1, 0));

        list.replaceChildren();
        let group = null;
        matches.forEach((command, i) => {
            if (command.group !== group) {
                group = command.group;
                list.append(Object.assign(document.createElement('li'), { className: 'palette-group', textContent: group, role: 'presentation' }));
            }
            const item = document.createElement('li');
            item.className = 'palette-item';
            item.role = 'option';
            item.dataset.index = i;
            item.append(Object.assign(document.createElement('span'), { textContent: command.label }));
            if (command.hint) item.append(Object.assign(document.createElement('small'), { textContent: command.hint }));
            list.append(item);
        });
        empty.hidden = matches.length > 0;
        highlight();
    }

    function highlight() {
        list.querySelectorAll('.palette-item').forEach((item) => {
            const on = Number(item.dataset.index) === active;
            item.classList.toggle('is-active', on);
            item.setAttribute('aria-selected', String(on));
            if (on) item.scrollIntoView({ block: 'nearest' });
        });
    }

    function run(command) {
        if (!command) return;
        dialog.close();

        if (command.copy) {
            copyText(command.copy, `${command.copy} copied`);
        } else if (command.action === 'theme') {
            toggleTheme();
        } else if (command.external) {
            window.open(command.href, '_blank', 'noopener');
        } else {
            if (command.href.endsWith('/resume.pdf')) track('resume_download');
            if (command.href.endsWith('.vcf')) track('contact_card_download');
            window.location.href = command.href;
        }
    }

    function open() {
        if (dialog.open) return;
        input.value = '';
        active = 0;
        render();
        dialog.showModal();
        input.focus();
    }

    document.querySelectorAll('[data-palette-open]').forEach((button) => button.addEventListener('click', open));

    document.addEventListener('keydown', (event) => {
        const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName ?? '');
        if ((event.key.toLowerCase() === 'k' && (event.metaKey || event.ctrlKey)) || (event.key === '/' && !typing)) {
            event.preventDefault();
            open();
        }
    });

    input.addEventListener('input', () => {
        active = 0;
        render();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            active = (active + step + matches.length) % Math.max(matches.length, 1);
            highlight();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            run(matches[active]);
        }
    });

    list.addEventListener('click', (event) => {
        const item = event.target.closest('.palette-item');
        if (item) run(matches[Number(item.dataset.index)]);
    });
    list.addEventListener('pointermove', (event) => {
        const item = event.target.closest('.palette-item');
        if (item && Number(item.dataset.index) !== active) {
            active = Number(item.dataset.index);
            highlight();
        }
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
}
