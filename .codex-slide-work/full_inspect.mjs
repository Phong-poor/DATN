import fs from 'node:fs/promises';
import { FileBlob, PresentationFile } from '@oai/artifact-tool';

const input = 'C:/xampp/htdocs/DATN/.codex-slide-work/source.pptx';
const output = 'C:/xampp/htdocs/DATN/.codex-slide-work/full-inspect.ndjson';
const deck = await PresentationFile.importPptx(await FileBlob.load(input));
const result = await deck.inspect({
  kind: 'slide,textbox,shape,image,table,chart,notes,layout',
  include: 'id,slide,name,title,text,textPreview,textChars,textLines,bbox,isPlaceholder,placeholders',
  maxChars: 1000000,
});
await fs.writeFile(output, result.ndjson || '', 'utf8');
console.log(JSON.stringify({ truncated: result.truncated, output }));
