import fs from 'node:fs/promises';
import { FileBlob, PresentationFile } from '@oai/artifact-tool';

const deck = await PresentationFile.importPptx(await FileBlob.load('C:/xampp/htdocs/DATN/NextGen_BaoVe_DoAn_2026.pptx'));
const out = 'C:/xampp/htdocs/DATN/.codex-slide-work/final-layout';
await fs.mkdir(out, { recursive: true });
for (let i = 0; i < deck.slides.items.length; i++) {
  const blob = await deck.export({ slide: deck.slides.items[i], format: 'layout' });
  await fs.writeFile(`${out}/slide-${String(i + 1).padStart(2, '0')}.layout.json`, await blob.text());
}
