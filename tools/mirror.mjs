// Mirror a WordPress site into static files. Usage: node mirror.mjs <outDir>
import fs from 'node:fs/promises';
import path from 'node:path';

const ORIGIN = 'https://www.strandhaus-gonska-dahme.de';
const HOSTS = ['https://www.strandhaus-gonska-dahme.de', 'https://strandhaus-gonska-dahme.de', 'http://www.strandhaus-gonska-dahme.de', 'http://strandhaus-gonska-dahme.de', '//www.strandhaus-gonska-dahme.de'];
const OUT = path.resolve(process.argv[2]);
const PAGES = ['/', '/die-region-1/', '/faq-haeufig-gestellte-fragen/', '/fruehstueck/', '/gemeinschaftsraum/',
  '/impressum-and-datenschutzerklaerung/', '/kontakt-1/', '/terasse/', '/zimmer-reservieren-am-meer/'];

const done = new Set();
const failed = [];

function localPath(urlPath) {
  let p = decodeURIComponent(urlPath.split('#')[0].split('?')[0]);
  if (p.endsWith('/')) p += 'index.html';
  return path.join(OUT, p);
}

function normalize(text) {
  // make all own-domain URLs root-relative, incl. JSON-escaped variants
  for (const h of HOSTS) {
    text = text.split(h + '/').join('/');
    text = text.split(h.replace(/\//g, '\\/') + '\\/').join('\\/');
  }
  return text;
}

function collectAssets(text, baseDir) {
  const found = new Set();
  const add = (u) => {
    if (!u) return;
    u = u.trim().replace(/&amp;/g, '&').replace(/\\\//g, '/');
    if (u.startsWith('data:') || u.startsWith('#') || u.startsWith('mailto:') || u.startsWith('tel:')) return;
    if (/^(https?:)?\/\//.test(u)) return; // external
    if (!u.startsWith('/')) u = path.posix.normalize(path.posix.join(baseDir, u));
    found.add(u.split('#')[0]);
  };
  for (const m of text.matchAll(/(?:src|href|data-src|poster|content)=["']([^"']+)["']/g)) {
    const u = m[1];
    if (/\.(css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|otf|eot|mp4|webm|pdf|json)(\?|$)/i.test(u)) add(u);
  }
  for (const m of text.matchAll(/srcset=["']([^"']+)["']/g)) {
    // URLs may contain commas (go-x crop paths), so split on descriptors instead
    const items = [...m[1].matchAll(/(?:^|,)\s*(\S+)\s+\d+(?:\.\d+)?[wx]/g)].map(x => x[1]);
    (items.length ? items : [m[1].trim()]).forEach(add);
  }
  for (const m of text.matchAll(/url\(\s*(?:&apos;|&quot;|["'])?([^"')&]+)/g)) if (!m[1].includes('data:')) add(m[1]);
  for (const m of text.matchAll(/@import\s+["']([^"']+)["']/g)) add(m[1]);
  return found;
}

async function fetchTo(urlPath, isPage) {
  const key = urlPath.split('?')[0].split('#')[0];
  if (done.has(key)) return;
  done.add(key);
  const res = await fetch(ORIGIN + urlPath, { headers: { 'User-Agent': 'Mozilla/5.0 (site-mirror)' } });
  if (!res.ok) { failed.push(`${res.status} ${urlPath}`); return; }
  const file = localPath(key);
  await fs.mkdir(path.dirname(file), { recursive: true });
  const type = res.headers.get('content-type') || '';
  if (isPage || /text\/css|javascript|json|svg/.test(type)) {
    let text = normalize(await res.text());
    const baseDir = path.posix.dirname(key.endsWith('/') ? key + 'x' : key);
    const assets = collectAssets(text, baseDir);
    await fs.writeFile(file, text);
    for (const a of assets) await fetchTo(a, false);
  } else {
    await fs.writeFile(file, Buffer.from(await res.arrayBuffer()));
  }
}

for (const p of PAGES) await fetchTo(p, true);
console.log(`Saved ${done.size} URLs`);
if (failed.length) console.log('Failed:\n' + failed.join('\n'));
