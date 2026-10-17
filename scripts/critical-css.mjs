// Usage: node scripts/critical-css.mjs http://127.0.0.1:8000   (Node >= 22 + Google Chrome; a seeded local site must be running; CHROME_PATH overrides the browser)
//
// Regenerates resources/views/partials/critical-css.blade.php, the <style id="critical-css"> block that
// layouts/app.blade.php inlines so bootstrap.min.css can load without blocking the first paint.
// Run it again after upgrading Bootstrap or changing above-the-fold markup (nav, heroes, cookie banner).
//
// How it works (no npm dependencies, Chrome is driven over the DevTools protocol):
//  1. every page below is rendered at each viewport with the full CSS; the "critical" elements are the ones
//     intersecting the first viewport, their ancestors, and hidden subtrees hanging off them (collapsed mobile
//     menu, dropdown, modals) - so their display:none rules are kept. The mobile menu + dropdown are also
//     opened and measured again;
//  2. a Bootstrap rule is kept when one of its selectors matches a critical element (pseudo-elements stripped,
//     :hover/:focus/:active-only rules dropped), inside its original @media/@supports wrapper and in the
//     original order; unused :root custom properties are dropped;
//  3. the Montserrat @font-face rules are inlined as well. Every weight file of a subset is the same variable
//     font (identical bytes), so each subset becomes one @font-face with a weight range: two files cover all text.

import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const BASE = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const CHROME = process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BOOTSTRAP = path.join(ROOT, 'public/vendor/bootstrap/bootstrap.min.css');
const MONTSERRAT = path.join(ROOT, 'public/fonts/montserrat/montserrat.css');
const OUT = path.join(ROOT, 'resources/views/partials/critical-css.blade.php');

// Every template that extends layouts.app (blog post / wedding story URLs are discovered from the listings).
const PAGES = [
    '/', '/weddings', '/proms', '/baptism', '/commercial', '/family', '/portrait', '/automotive', '/architectural',
    '/events', '/booking', '/blog', '/ceni', '/za-nas', '/kontakti', '/abiturientski-bal-varna', '/privacy',
    '/critical-css-404-probe',
];
const DISCOVER = [
    ['/blog', /\/blog\/(?!category\/)[^/?#]+$/],
    ['/blog', /\/blog\/category\/[^/?#]+$/],
    ['/weddings', /\/svatbi\/[^/?#]+$/],
];
const VIEWPORTS = [
    { width: 412, height: 823, mobile: true },
    { width: 1350, height: 940, mobile: false },
];
// JS-toggled states that may be needed before bootstrap.min.css arrives (always kept when present).
const SAFELIST = [
    /^\.(fade|collapsing|modal|modal-backdrop|dropdown-menu|visually-hidden)(\.(fade|show|collapse-horizontal))?$/,
    /^\.(fade|collapse):not\(\.show\)$/,
    /^\.(modal-open|dropdown-menu\.show|visually-hidden-focusable:not\(:focus\):not\(:focus-within\))$/,
];

// ---------------------------------------------------------------- CSS parsing (enough for minified vendor CSS)
function parse(css) {
    let i = 0;
    const skip = () => {
        for (;;) {
            while (i < css.length && /\s/.test(css[i])) i++;
            if (css.startsWith('/*', i)) i = css.indexOf('*/', i + 2) + 2;
            else return;
        }
    };
    const readUntil = (stops) => {
        const start = i;
        let depth = 0;
        while (i < css.length) {
            const c = css[i];
            if (c === '"' || c === "'") { i++; while (i < css.length && css[i] !== c) i += css[i] === '\\' ? 2 : 1; }
            else if (css.startsWith('/*', i)) { i = css.indexOf('*/', i + 2) + 1; }
            else if (c === '(' || c === '[') depth++;
            else if (c === ')' || c === ']') depth--;
            else if (depth === 0 && stops.includes(c)) break;
            i++;
        }
        return css.slice(start, i);
    };
    const rawBlock = () => { // i is just after '{'
        const start = i;
        let depth = 1;
        while (i < css.length && depth) {
            const c = css[i];
            if (c === '"' || c === "'") { i++; while (i < css.length && css[i] !== c) i += css[i] === '\\' ? 2 : 1; }
            else if (c === '{') depth++;
            else if (c === '}') depth--;
            i++;
        }
        return css.slice(start, i - 1);
    };
    const list = () => {
        const nodes = [];
        for (;;) {
            skip();
            if (i >= css.length) return nodes;
            if (css[i] === '}') { i++; return nodes; }
            const prelude = readUntil(['{', ';', '}']).trim();
            if (css[i] === ';') { i++; nodes.push({ type: 'stmt', text: prelude + ';' }); continue; }
            if (css[i] !== '{') continue;
            i++;
            if (/^@(media|supports|container|layer)\b/i.test(prelude)) nodes.push({ type: 'group', prelude, children: list() });
            else if (prelude.startsWith('@')) nodes.push({ type: 'at', prelude, body: rawBlock() });
            else nodes.push({ type: 'rule', selectors: splitList(prelude), body: rawBlock() });
        }
    };
    return list();
}

function splitList(s) {
    const out = [];
    let depth = 0, start = 0, quote = null;
    for (let i = 0; i < s.length; i++) {
        const c = s[i];
        if (quote) { if (c === '\\') i++; else if (c === quote) quote = null; continue; }
        if (c === '"' || c === "'") quote = c;
        else if (c === '(' || c === '[') depth++;
        else if (c === ')' || c === ']') depth--;
        else if (c === ',' && depth === 0) { out.push(s.slice(start, i).trim()); start = i + 1; }
    }
    out.push(s.slice(start).trim());
    return out;
}

const withoutNot = (sel) => {
    let out = '', i = 0;
    while (i < sel.length) {
        if (sel.startsWith(':not(', i)) {
            let depth = 0;
            for (; i < sel.length; i++) { if (sel[i] === '(') depth++; else if (sel[i] === ')' && !--depth) { i++; break; } }
        } else out += sel[i++];
    }
    return out;
};

// Pseudo-elements that only exist on some input types: the rule matters only when such an input is critical.
const INPUT_PSEUDO = [
    [/::?-webkit-(datetime-edit[\w-]*|calendar-picker-indicator|date-and-time-value)\b/, 'input:is([type=date],[type=time],[type=datetime-local],[type=month],[type=week])'],
    [/::?(file-selector-button|-webkit-file-upload-button)\b/, 'input[type=file]'],
    [/::?-webkit-(inner|outer)-spin-button\b/, 'input[type=number]'],
    [/::?-webkit-search-[\w-]+/, 'input[type=search]'],
    [/::?-webkit-color-swatch[\w-]*/, 'input[type=color]'],
];

// The selector used to test "does this rule style a critical element?" (null = interaction-only rule).
function matchForm(sel) {
    if (/:(hover|focus|focus-visible|focus-within|active|visited|target|-moz-focusring)\b/.test(withoutNot(sel))) return null;
    const input = INPUT_PSEUDO.find(([re]) => re.test(sel));
    let s = sel
        .replace(/::?(before|after|first-line|first-letter|marker|placeholder|selection|backdrop|file-selector-button)\b/g, '')
        .replace(/::?-(webkit|moz|ms)-[\w-]+(\([^)]*\))?/g, '')
        .trim();
    const bare = s === '' || /[>+~]$/.test(s);
    if (input) return bare ? s + input[1] : `${s}:is(${input[1]})`;
    return bare ? s + '*' : s;
}

// ---------------------------------------------------------------- minimal Chrome DevTools protocol client
async function launchChrome() {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'critical-css-'));
    const proc = spawn(CHROME, ['--headless=new', '--remote-debugging-port=0', `--user-data-dir=${dir}`, '--no-first-run',
        '--no-default-browser-check', '--hide-scrollbars', '--force-color-profile=srgb', 'about:blank'], { stdio: 'ignore' });
    const portFile = path.join(dir, 'DevToolsActivePort');
    for (let t = 0; !fs.existsSync(portFile) || !fs.readFileSync(portFile, 'utf8').includes('\n'); t++) {
        if (t > 200) throw new Error('Chrome did not start: ' + CHROME);
        await new Promise((r) => setTimeout(r, 50));
    }
    const [port, wsPath] = fs.readFileSync(portFile, 'utf8').trim().split('\n');
    const ws = new WebSocket(`ws://127.0.0.1:${port}${wsPath}`);
    await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
    let id = 0;
    const pending = new Map(), listeners = new Set();
    ws.onmessage = ({ data }) => {
        const msg = JSON.parse(data);
        if (msg.id && pending.has(msg.id)) {
            const { resolve, reject } = pending.get(msg.id);
            pending.delete(msg.id);
            msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
        } else listeners.forEach((fn) => fn(msg));
    };
    const send = (method, params = {}, sessionId) => new Promise((resolve, reject) => {
        pending.set(++id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params, sessionId }));
    });
    const waitFor = (method, sessionId) => new Promise((resolve) => {
        const fn = (msg) => { if (msg.method === method && msg.sessionId === sessionId) { listeners.delete(fn); resolve(msg.params); } };
        listeners.add(fn);
    });
    const close = async () => {
        ws.close();
        if (proc.exitCode === null) await new Promise((r) => { proc.once('exit', r); proc.kill(); });
        fs.rmSync(dir, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
    };
    return { send, waitFor, close };
}

async function openPage(cdp, url, vp) {
    // A fresh browser context per page: no stored cookie consent, so the cookie banner is measured too.
    const { browserContextId } = await cdp.send('Target.createBrowserContext');
    const { targetId } = await cdp.send('Target.createTarget', { url: 'about:blank', browserContextId });
    const { sessionId } = await cdp.send('Target.attachToTarget', { targetId, flatten: true });
    const s = (m, p) => cdp.send(m, p, sessionId);
    await s('Page.enable');
    await s('Emulation.setDeviceMetricsOverride', { width: vp.width, height: vp.height, deviceScaleFactor: 1, mobile: vp.mobile });
    await s('Emulation.setTouchEmulationEnabled', { enabled: vp.mobile });
    const loaded = cdp.waitFor('Page.loadEventFired', sessionId);
    await s('Page.navigate', { url });
    await loaded;
    await new Promise((r) => setTimeout(r, 1500)); // deferred scripts, cookie banner, AOS
    const evaluate = async (expression) => {
        const { result, exceptionDetails } = await s('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
        if (exceptionDetails) throw new Error(exceptionDetails.exception?.description || exceptionDetails.text);
        return result.value;
    };
    const close = () => cdp.send('Target.disposeBrowserContext', { browserContextId });
    return { evaluate, close };
}

// Runs inside the page: indexes of the selectors that match a critical element, in the base state and with
// the mobile menu / services dropdown opened.
function inPage(selectors) {
    const frame = () => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
    const matched = new Set();
    const measure = () => {
        const vh = innerHeight, crit = new Set([document.documentElement]), hidden = [];
        for (const el of document.body.querySelectorAll('*')) {
            if (el.closest('svg') && el.tagName.toLowerCase() !== 'svg') continue;
            if (el.getClientRects().length) {
                const r = el.getBoundingClientRect();
                if (r.top < vh && r.bottom >= 0) for (let a = el; a && !crit.has(a); a = a.parentElement) crit.add(a);
            } else hidden.push(el);
        }
        crit.add(document.body);
        hidden.filter((el) => crit.has(el.parentElement)).forEach((el) => crit.add(el)); // roots of display:none subtrees only
        selectors.forEach((sel, idx) => {
            if (matched.has(idx) || sel === null) return;
            let found;
            try { found = document.querySelectorAll(sel); } catch { return; }
            for (const el of found) if (crit.has(el)) { matched.add(idx); break; }
        });
    };
    return (async () => {
        measure();
        const nav = document.getElementById('navbarNav');
        if (nav && !nav.getClientRects().length) nav.classList.add('show');
        document.querySelectorAll('.navbar .dropdown-menu, .navbar .dropdown-toggle').forEach((el) => el.classList.add('show'));
        await frame();
        measure();
        return [...matched];
    })();
}

// ---------------------------------------------------------------- build
function bootstrapSubset(tree, keep) {
    const out = [];
    const walk = (nodes) => nodes.map((n) => {
        if (n.type === 'rule') {
            const sels = n.selectors.filter((s) => keep(s));
            return sels.length ? { ...n, selectors: sels } : null;
        }
        if (n.type === 'group') {
            if (/^@media\s+print\b/.test(n.prelude)) return null;
            const children = walk(n.children).filter(Boolean);
            return children.length ? { ...n, children } : null;
        }
        return null; // @charset, @keyframes (Bootstrap's only animate spinners/progress bars)
    }).filter(Boolean);
    out.push(...walk(tree));

    // Drop custom properties nothing in the subset uses (nor the site CSS / inline styles in the views): most of
    // Bootstrap's weight is component variables for hover/active/disabled states that are not critical.
    const used = new Set(['--bs-position']); // read by bootstrap.bundle.min.js (dropdown placement)
    const scan = (text) => { for (const m of text.matchAll(/var\(\s*(--[\w-]+)/g)) used.add(m[1]); };
    const visit = (nodes, fn) => nodes.forEach((n) => (n.type === 'group' ? visit(n.children, fn) : fn(n)));
    const defs = [];
    visit(out, (n) => splitDecls(n.body).forEach((d) => { const [k, v] = splitProp(d); k.startsWith('--') ? defs.push([k, v]) : scan(v); }));
    for (const dir of ['public/css', 'resources/views']) scanDir(path.join(ROOT, dir), scan);
    for (let size = -1; size !== used.size;) {
        size = used.size;
        for (const [k, v] of defs) if (used.has(k)) scan(v);
    }
    visit(out, (n) => { n.body = splitDecls(n.body).filter((d) => !d.startsWith('--') || used.has(splitProp(d)[0])).join(';'); });
    const ser = (nodes) => nodes.map((n) => {
        if (n.type === 'group') { const inner = ser(n.children); return inner && `${n.prelude}{${inner}}`; }
        return n.body && `${n.selectors.join(',')}{${n.body}}`;
    }).join('');
    return ser(out);
}

function splitDecls(body) {
    const out = [];
    let depth = 0, start = 0, quote = null;
    for (let i = 0; i < body.length; i++) {
        const c = body[i];
        if (quote) { if (c === '\\') i++; else if (c === quote) quote = null; continue; }
        if (c === '"' || c === "'") quote = c;
        else if (c === '(') depth++;
        else if (c === ')') depth--;
        else if (c === ';' && depth === 0) { if (body.slice(start, i).trim()) out.push(body.slice(start, i).trimStart()); start = i + 1; }
    }
    if (body.slice(start).trim()) out.push(body.slice(start).trimStart()); // keep "--x: " (empty value) intact
    return out;
}
const splitProp = (d) => { const i = d.indexOf(':'); return [d.slice(0, i).trim(), d.slice(i + 1).trim()]; };

function scanDir(dir, fn) {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        const p = path.join(dir, e.name);
        if (e.isDirectory()) { if (!['filament', 'guava', 'img'].includes(e.name)) scanDir(p, fn); }
        else if (/\.(css|php)$/.test(e.name) && p !== OUT) fn(fs.readFileSync(p, 'utf8'));
    }
}

function montserratFaces() {
    const css = fs.readFileSync(MONTSERRAT, 'utf8');
    const faces = parse(css).filter((n) => n.type === 'at' && /^@font-face$/i.test(n.prelude)).map((n) => {
        const props = Object.fromEntries(splitDecls(n.body).map(splitProp));
        const file = props.src.match(/url\(([^)]+)\)/)[1].replace(/['"]/g, '');
        const hash = createHash('sha1').update(fs.readFileSync(path.join(ROOT, 'public', file))).digest('hex');
        return { props, file, hash };
    });
    const groups = new Map();
    for (const f of faces) {
        const key = `${f.props['font-family']}|${f.props['font-style']}|${f.props['unicode-range']}`;
        groups.set(key, [...(groups.get(key) || []), f]);
    }
    const rule = (p) => `@font-face{${Object.entries(p).map(([k, v]) => `${k}:${v}`).join(';')}}`;
    return [...groups.values()].map((g) => {
        if (new Set(g.map((f) => f.hash)).size > 1) return g.map((f) => rule(f.props)).join(''); // static fonts: keep as is
        const weights = g.map((f) => +f.props['font-weight']).sort((a, b) => a - b);
        const main = g.find((f) => f.props['font-weight'] === '400') || g[0];
        return rule({ ...main.props, 'font-weight': `${weights[0]} ${weights.at(-1)}` });
    }).join('');
}

const tree = parse(fs.readFileSync(BOOTSTRAP, 'utf8'));
const allSelectors = [];
const visitRules = (nodes) => nodes.forEach((n) => (n.type === 'group' ? (/^@media\s+print\b/.test(n.prelude) || visitRules(n.children)) : n.type === 'rule' && allSelectors.push(...n.selectors)));
visitRules(tree);
const unique = [...new Set(allSelectors)];
const forms = unique.map(matchForm);

const cdp = await launchChrome();
const matched = new Set();
try {
    const pages = [...PAGES];
    for (const [listing, re] of DISCOVER) {
        const page = await openPage(cdp, BASE + listing, VIEWPORTS[0]);
        const href = await page.evaluate(`[...document.querySelectorAll('a[href]')].map((a) => a.pathname).find((p) => ${re}.test(p)) || null`);
        await page.close();
        if (href) pages.push(href); else console.warn(`! no link matching ${re} on ${listing}`);
    }
    for (const vp of VIEWPORTS) {
        for (const p of pages) {
            const page = await openPage(cdp, BASE + p, vp);
            const idx = await page.evaluate(`(${inPage})(${JSON.stringify(forms)})`);
            await page.close();
            idx.forEach((i) => matched.add(unique[i]));
            console.log(`${vp.width}x${vp.height} ${p}: ${idx.length} selectors`);
        }
    }
} finally {
    await cdp.close();
}

const keep = (sel) => matched.has(sel) || SAFELIST.some((re) => re.test(sel));
const version = (fs.readFileSync(BOOTSTRAP, 'utf8').match(/Bootstrap\s+v([\d.]+)/) || [, '?'])[1];
const css = `/*! Bootstrap v${version} subset | MIT License | github.com/twbs/bootstrap */` + montserratFaces() + bootstrapSubset(tree, keep);

fs.writeFileSync(OUT, `{{--
    GENERATED by scripts/critical-css.mjs - do not edit by hand, re-run the script instead.
    Above-the-fold subset of public/vendor/bootstrap/bootstrap.min.css + the Montserrat @font-face rules,
    inlined by layouts/app.blade.php so the full stylesheets can load without blocking the first paint.
--}}
<style id="critical-css">@verbatim${css}@endverbatim</style>
`);
console.log(`\n${matched.size} selectors kept, ${(Buffer.byteLength(css) / 1024).toFixed(1)} KB -> ${path.relative(ROOT, OUT)}`);
