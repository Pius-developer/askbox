// Character counter for the ask box
document.querySelectorAll('[data-counter]').forEach((ta) => {
    const out = document.getElementById(ta.dataset.counter);
    const update = () => (out.textContent = ta.value.length);
    ta.addEventListener('input', update);
    update();
});

// Copy-link buttons: copy the nearest .js-copy-src input
document.querySelectorAll('.js-copy').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const input = btn.parentElement.querySelector('.js-copy-src');
        const label = btn.textContent;
        try {
            await navigator.clipboard.writeText(input.value);
            btn.textContent = 'Copied!';
        } catch {
            input.select();
            btn.textContent = 'Press Ctrl/⌘+C';
        }
        setTimeout(() => (btn.textContent = label), 1600);
    });
});

// Upvote without reloading the page (falls back to a normal form post)
document.querySelectorAll('.js-upvote').forEach((form) => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button');
        if (btn.disabled) return;
        btn.disabled = true;
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });
            const data = await res.json();
            btn.querySelector('span').textContent = data.upvotes;
            btn.classList.add('voted');
        } catch {
            form.submit();
        }
    });
});

// Confirm destructive actions
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!confirm(form.dataset.confirm)) e.preventDefault();
    });
});

// Live username preview on the signup page
const u = document.getElementById('username');
const preview = document.getElementById('u-preview');
if (u && preview) {
    u.addEventListener('input', () => {
        preview.textContent = u.value.replace(/^@/, '').toLowerCase() || 'yourname';
    });
}

// Hero: type out example questions one after another (static if reduced motion)
const typer = document.querySelector('.js-typer');
if (typer && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const lines = JSON.parse(typer.dataset.lines);
    let i = 0;
    const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
    (async function loop() {
        await sleep(2600);
        for (;;) {
            const text = typer.textContent;
            for (let n = text.length; n >= 0; n--) { typer.textContent = text.slice(0, n); await sleep(18); }
            i = (i + 1) % lines.length;
            await sleep(300);
            for (let n = 1; n <= lines[i].length; n++) { typer.textContent = lines[i].slice(0, n); await sleep(45 + Math.random() * 40); }
            await sleep(2600);
        }
    })();
}
