import fs from 'node:fs/promises';

const input = await fs.readFile('C:/xampp/htdocs/DATN/.codex-slide-work/full-inspect.ndjson', 'utf8');
const rows = input.split(/\r?\n/).filter(Boolean).map((line) => JSON.parse(line));
const roles = [
  'opening thesis', 'team', 'problem and product vision', 'three user groups',
  'real interface evidence', 'commerce journey', 'growth and retention',
  'technology architecture', 'end-to-end data flow', 'security and governance',
  'differentiators', 'verified quality evidence', 'project scale', 'live demo route',
  'closing synthesis'
];

const outputSlides = Array.from({ length: 15 }, (_, index) => {
  const slide = index + 1;
  const editTargets = rows
    .filter((row) => row.slide === slide && ['textbox', 'table', 'chart'].includes(row.kind))
    .map((row) => ({ sourceElementId: row.id, action: row.kind === 'textbox' ? 'rewrite' : 'rewrite' }));
  if (slide === 5 || slide === 14) {
    for (const row of rows.filter((item) => item.slide === slide && item.kind === 'image' && !/Picture (6|17)$/.test(item.name || ''))) {
      editTargets.push({ sourceElementId: row.id, action: 'replace' });
    }
  }
  return { outputSlide: slide, sourceSlide: slide, narrativeRole: roles[index], reuseMode: 'duplicate-slide', editTargets };
});

await fs.writeFile('C:/xampp/htdocs/DATN/.codex-slide-work/template-frame-map.json', JSON.stringify({ outputSlides, omittedSourceSlides: [] }, null, 2));
