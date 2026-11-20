/*
 * Similar words: the radar and the 2D map of the report, the hover tooltip and the vector fingerprints.
 * Used as x-data="similarWords(report)" in resources/views/similar-words.blade.php; the site imports this file once.
 * Colours come from CSS (.sw-synonym etc.), so the drawing follows the light and dark themes.
 */
window.similarWords = (report) => ({
    report,
    view: 'radar',
    hidden: [],
    highlight: null,

    init() {
        this.draw();
        this.$watch('view', () => this.draw());
        this.$watch('hidden', () => this.draw());
        this.$watch('highlight', () => this.mark());
    },

    toggle(relation) {
        this.hidden = this.hidden.includes(relation) ? this.hidden.filter((r) => r !== relation) : [...this.hidden, relation];
    },

    /** The word shown in the second fingerprint row: the one pointed at, or the nearest one. */
    shown() {
        return this.report.items[this.highlight ?? 0];
    },

    cells(print) {
        return print.map((v) => `<i class="${v < 0 ? 'sw-neg' : 'sw-pos'}" style="--a:${Math.abs(v) * 11}%"></i>`).join('');
    },

    pointAt(event) {
        const point = event.target.closest('.sw-pt');

        if (point) {
            this.highlight = Number(point.dataset.i);
        }
    },

    open(event) {
        const point = event.target.closest('.sw-pt');

        if (point) {
            this.$wire.searchFor(this.report.items[point.dataset.i].text);
        }
    },

    /** Points placed for the current view, labels pushed apart so they do not overlap. */
    positions() {
        const cx = 320, cy = 280, radius = 245;
        const sector = { synonym: -Math.PI / 2, association: Math.PI / 6, unrelated: Math.PI * 5 / 6 };
        const points = this.report.items.map((item, i) => ({ ...item, i })).filter((item) => !this.hidden.includes(item.relation)).map((item) => {
            if (this.view === 'radar') {
                const angle = sector[item.relation] + (random(item.text) - 0.5) * 1.6;
                const distance = this.distance(item.score);

                return { ...item, x: cx + Math.cos(angle) * distance, y: cy + Math.sin(angle) * distance };
            }

            return { ...item, x: cx + item.map[0] * radius * 1.15, y: cy + item.map[1] * radius };
        });

        for (let step = 0; step < 80; step++) {
            for (const a of points) {
                for (const b of points) {
                    const dx = b.x - a.x, dy = b.y - a.y;

                    if (a !== b && Math.abs(dx) < 70 && Math.abs(dy) < 17) {
                        const push = (17 - Math.abs(dy)) / 4, side = dy >= 0 ? 1 : -1, sideX = Math.sign(dx || 1);
                        b.y += side * push; a.y -= side * push; b.x += sideX * 0.8; a.x -= sideX * 0.8;
                    }
                }
            }
        }

        return points.map((p) => ({ ...p, x: Math.max(30, Math.min(610, p.x)), y: Math.max(20, Math.min(546, p.y)) }));
    },

    /**
     * The radar spans the scores of this report, not 0…1: real similarities of text-embedding-3 mostly fall
     * between 0.1 and 0.7, so a fixed scale would crowd every word into the middle rings.
     */
    distance(score) {
        const scores = this.report.items.map((item) => item.score);
        const top = Math.min(1, Math.max(...scores) + 0.05), bottom = Math.min(...scores) - 0.02;

        return 34 + 211 * Math.pow(Math.max(0, Math.min(1, (top - score) / (top - bottom))), 0.85);
    },

    /** Ring values every 0.1 inside the range of the report. */
    rings() {
        const scores = this.report.items.map((item) => item.score), rings = [];

        for (let s = Math.floor(Math.max(...scores) * 10) / 10; s > Math.min(...scores); s -= 0.1) {
            rings.push(Math.round(s * 10) / 10);
        }

        return rings;
    },

    draw() {
        const cx = 320, cy = 280, points = this.positions();
        let html = '';

        if (this.view === 'radar') {
            this.rings().forEach((s) => {
                const d = this.distance(s);
                html += `<circle class="sw-ring" cx="${cx}" cy="${cy}" r="${d}"/><text class="sw-ringl" x="${cx + 4}" y="${cy - d - 4}">${s.toFixed(1)}</text>`;
            });
            points.forEach((p) => html += `<line class="sw-link sw-${p.relation}" x1="${cx}" y1="${cy}" x2="${p.x}" y2="${p.y}"/>`);
        } else {
            html += `<line class="sw-axis" x1="20" y1="${cy}" x2="620" y2="${cy}"/><line class="sw-axis" x1="${cx}" y1="20" x2="${cx}" y2="540"/>`
                + `<text class="sw-ringl" x="600" y="${cy - 6}" text-anchor="end">PC1</text><text class="sw-ringl" x="${cx + 6}" y="32">PC2</text>`;
        }

        points.forEach((p) => {
            const r = 4 + Math.max(0, p.score) * 7, left = p.x > cx + 150;
            html += `<g class="sw-pt sw-${p.relation}" data-i="${p.i}" data-x="${p.x}" data-y="${p.y}"><circle cx="${p.x}" cy="${p.y}" r="${r}"/>`
                + `<text x="${left ? p.x - r - 5 : p.x + r + 5}" y="${p.y + 4}" text-anchor="${left ? 'end' : 'start'}">${escape(p.text)}</text></g>`;
        });

        const [qx, qy] = this.view === 'radar' ? [cx, cy] : [cx + this.report.query.map[0] * 245 * 1.15, cy + this.report.query.map[1] * 245];
        html += `<g class="sw-center"><circle cx="${qx}" cy="${qy}" r="9"/><text x="${qx}" y="${qy - 16}" text-anchor="middle">${escape(this.report.phrase)}</text></g>`;

        this.$refs.svg.innerHTML = html;
        this.mark();
    },

    /** Highlights the pointed word on the map and moves the tooltip to it. */
    mark() {
        const tip = this.$refs.tip;
        let current = null;

        this.$refs.svg.querySelectorAll('.sw-pt').forEach((g) => {
            const on = Number(g.dataset.i) === this.highlight;
            g.classList.toggle('sw-hl', on);
            g.classList.toggle('sw-dim', this.highlight !== null && !on);
            current = on ? g : current;
        });

        if (!current) {
            tip.classList.remove('sw-on');

            return;
        }

        const item = this.report.items[this.highlight], scale = this.$refs.svg.getBoundingClientRect().width / 640;
        const label = this.$root.querySelector(`.sw-legend .sw-${item.relation}`)?.textContent.split('·')[0].trim() ?? '';
        tip.innerHTML = `${escape(item.text)}<br><b>${Math.max(0, item.score).toFixed(2)}</b> · ${escape(label)}`;
        tip.style.left = `${current.dataset.x * scale}px`;
        tip.style.top = `${current.dataset.y * scale}px`;
        tip.classList.add('sw-on');
    },
});

/** The same word always lands at the same angle of its sector. */
function random(text) {
    let hash = 2166136261;

    for (const char of text) {
        hash = Math.imul(hash ^ char.codePointAt(0), 16777619);
    }

    return (hash >>> 0) / 4294967296;
}

function escape(text) {
    return String(text).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}
