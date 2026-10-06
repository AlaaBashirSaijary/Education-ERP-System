#!/usr/bin/env node
/**
 * Builds docs/demo/index.html: a single static page that replays REAL pages of the app
 * (captured from a throw-away demo-mode copy) so it can be hosted on GitHub Pages,
 * which cannot run PHP. Nothing in it talks to a server and nothing is saved.
 *
 *   npm run build && node scripts/build-static-demo.cjs
 */
const { spawn, spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '..');

let chromium;
try { ({ chromium } = require('playwright')); } catch { ({ chromium } = require('/opt/node22/lib/node_modules/playwright')); }

const PORT = process.env.PORT || '8799';
const BASE = `http://127.0.0.1:${PORT}`;
const DB = path.join(root, 'database', 'static-demo.sqlite');
const env = {
  ...process.env, SCHOOL_DEMO_MODE: 'true', APP_ENV: 'local', APP_DEBUG: 'false', DB_CONNECTION: 'sqlite', DB_DATABASE: DB,
  SESSION_DRIVER: 'file', QUEUE_CONNECTION: 'sync', CACHE_STORE: 'file', MESSAGING_CHANNEL: 'log',
  SCHOOL_NAME: process.env.SCHOOL_NAME || 'مدرستنا', DEMO_WHATSAPP: '', DEMO_EMAIL: '',
};

const PAGES = {
  guest: ['/', '/login', '/register', '/forgot-password'],
  admin: ['/dashboard', '/students', '/students/1', '/attendance', '/gate', '/grades', '/fees', '/timetable', '/years', '/staff-attendance', '/reports/attendance', '/reports/finance', '/announcements', '/messages', '/users', '/setup', '/profile', '/students/1/report-card'],
  teacher: ['/dashboard', '/students', '/attendance', '/grades', '/timetable'],
  accountant: ['/dashboard', '/fees', '/reports/finance'],
  parent: ['/dashboard', '/students/1', '/fees', '/timetable', '/students/1/report-card'],
};

const artisan = (...a) => { const r = spawnSync('php', ['artisan', ...a], { cwd: root, env, encoding: 'utf8' }); if (r.status) throw new Error(r.stderr || r.stdout); };

async function snapshot(p) {
  return p.evaluate(() => {
    document.querySelectorAll('input,textarea').forEach(e => { if (e.type === 'checkbox' || e.type === 'radio') { e.checked ? e.setAttribute('checked', '') : e.removeAttribute('checked'); } else if (e.tagName === 'TEXTAREA') { e.textContent = e.value; } else e.setAttribute('value', e.value); });
    document.querySelectorAll('select').forEach(s => [...s.options].forEach(o => o.selected ? o.setAttribute('selected', '') : o.removeAttribute('selected')));
    const r = document.documentElement.cloneNode(true);
    r.querySelectorAll('script,link,noscript').forEach(n => n.remove());
    r.querySelectorAll('[wire\\:navigate]').forEach(a => a.removeAttribute('wire:navigate'));
    return { lang: document.documentElement.lang, dir: document.documentElement.dir, body: r.querySelector('body').innerHTML.split(location.origin).join('') };
  });
}

function inlineCss() {
  const dir = path.join(root, 'public/build/assets');
  const file = fs.readdirSync(dir).find(f => /^app-.*\.css$/.test(f));
  if (!file) throw new Error('Run `npm run build` first.');
  let css = fs.readFileSync(path.join(dir, file), 'utf8');
  css = css.replace(/url\(([^)]*?\.woff2)\)\s*format\("woff2"\)\s*,\s*url\([^)]*?\.woff\)\s*format\("woff"\)/g, 'url($1) format("woff2")');
  css = css.replace(/url\(([^)]*?\.woff2)\)/g, (m, u) => {
    const f = path.join(dir, path.basename(u.replace(/["']/g, '')));
    return fs.existsSync(f) ? `url(data:font/woff2;base64,${fs.readFileSync(f).toString('base64')})` : m;
  });
  return css;
}

(async () => {
  fs.rmSync(DB, { force: true }); fs.writeFileSync(DB, '');
  artisan('migrate', '--force'); artisan('demo:reset', '--if-empty');
  const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], { cwd: root, env, stdio: 'ignore' });
  try {
    for (let i = 0; i < 40; i++) { try { if ((await fetch(BASE + '/login')).ok) break; } catch {} await new Promise(r => setTimeout(r, 250)); }
    const b = await chromium.launch({ executablePath: fs.existsSync('/opt/pw-browsers/chromium') ? '/opt/pw-browsers/chromium' : undefined, args: ['--no-sandbox', '--no-proxy-server'] });
    const out = {};
    for (const [role, pages] of Object.entries(PAGES)) {
      const ctx = await b.newContext({ viewport: { width: 1280, height: 900 }, colorScheme: 'light' });
      const p = await ctx.newPage();
      for (const lang of ['ar', 'en']) {
        await p.goto(`${BASE}/lang/${lang}`);
        if (role !== 'guest') {
          await p.goto(BASE + '/');
          await p.click(`form[action$="/demo/login/${role}"] button`); await p.waitForURL('**/dashboard');
          await p.goto(`${BASE}/lang/${lang}`);
        }
        for (const pg of pages) {
          await p.goto(BASE + pg); await p.waitForLoadState('networkidle');
          if (role === 'admin' && pg === '/gate') for (const c of ['1003', '1007', '1003', '9999', '1007']) { await p.fill('input[x-ref=code]', c); await p.press('input[x-ref=code]', 'Enter'); await p.waitForTimeout(350); }
          if (role === 'admin' && pg === '/grades') { await p.selectOption('select >> nth=1', { index: 1 }); await p.waitForTimeout(500); }
          ((out[role] ??= {})[lang] ??= {})[pg] = await snapshot(p);
        }
        if (role !== 'guest') { await p.context().clearCookies(); }
      }
      await ctx.close();
    }
    await b.close();

    const tpl = fs.readFileSync(path.join(__dirname, 'static-demo.template.html'), 'utf8');
    const safe = s => s.replace(/<\//g, '<\\/');
    const html = tpl.replace('__DATA__', () => safe(JSON.stringify(out))).replace('__CSS__', () => safe(JSON.stringify(inlineCss())));
    fs.mkdirSync(path.join(root, 'docs/demo'), { recursive: true });
    fs.writeFileSync(path.join(root, 'docs/demo/index.html'), html);
    const cfg = path.join(root, 'docs/demo/config.json');
    if (!fs.existsSync(cfg)) fs.writeFileSync(cfg, JSON.stringify({ name: 'مدرستنا', whatsapp: '', email: '' }, null, 2) + '\n');
    fs.writeFileSync(path.join(root, 'docs/demo/.nojekyll'), '');
    console.log(`docs/demo/index.html  ${Math.round(html.length / 1024)} KB`);
  } finally { server.kill(); fs.rmSync(DB, { force: true }); }
})().catch(e => { console.error(e.message); process.exit(1); });
