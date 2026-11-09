import fs from 'node:fs';
import path from 'node:path';
const root = path.resolve(process.argv[2]);
const walk = d => fs.readdirSync(d, { withFileTypes: true }).flatMap(e => e.isDirectory() ? walk(path.join(d, e.name)) : [path.join(d, e.name)]);
for (const f of walk(root).filter(f => f.endsWith('.html'))) {
  let s = fs.readFileSync(f, 'utf8'), before = s.length;
  s = s.replace(/<link[^>]+(wp-json|xmlrpc\.php|\/feed\/|api\.w\.org|rel=["']shortlink)[^>]*>\s*/g, '');
  fs.writeFileSync(f, s);
  console.log(path.relative(root, f), before - s.length, 'bytes removed');
}
