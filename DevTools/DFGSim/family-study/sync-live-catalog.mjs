import {readFile,writeFile} from 'node:fs/promises';
import {createHash} from 'node:crypto';
const source=new URL('../../../DFGSim/data/cards.json',import.meta.url);
const raw=await readFile(source,'utf8');
const runtime=await readFile(new URL('../../../DFGSim/Custom/GameLogic.php',import.meta.url),'utf8');
const version=Number(runtime.match(/const DFG_RULES_VERSION = (\d+);/)[1]);
const snapshot={rulesVersion:version,sha256:createHash('sha256').update(raw).digest('hex'),cards:JSON.parse(raw).cards};
await writeFile(new URL('live-catalog.js',import.meta.url),`'use strict';\n// Read-only study snapshot. Refresh with sync-live-catalog.mjs; never imported by the game.\nwindow.DFGStudyLiveCatalog = ${JSON.stringify(snapshot,null,2)};\n`);
console.log(`Study snapshot refreshed: ${snapshot.cards.length} cards, rules v${version}. No game files written.`);
