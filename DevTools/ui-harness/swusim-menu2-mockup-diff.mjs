// Computed-style diff of every setup modal against the APPROVED MOCKUP, element by element.
//
// This is what catches a port that looks right and is not: it found the page rendering in the
// wrong TYPEFACE entirely (the stylesheet asked for Archivo, the <link> was never emitted, and
// getComputedStyle reports the declared stack either way), buttons 25% too narrow, fields with
// 30px of legacy margin, and a pool chip whose caret had wrapped under its label.
//
// Differences that are EXPECTED and must be read as noise:
//   * .dblock — the mockup carries a "Mockup only" demo-link block (~102px) that is deliberately
//     not ported, so .pane/.body/.scroll are each ~102px shorter live.
//   * pre-con and saved-deck counts — the mockup has fixtures, the live page has real data.
//
// Run: node swusim-menu2-mockup-diff.mjs            (DLG=setup-twin-suns, W=390 to vary)
import { chromium } from 'playwright';
import { fileURLToPath } from 'url';
import path from 'path';
import fs from 'fs';
const HERE = path.dirname(fileURLToPath(import.meta.url));
// ⚠ docs/superpowers/ is GITIGNORED, so the mockup is not in the repo — this tool has no
// reference on a fresh clone. Say so instead of reporting a clean run against nothing.
const MOCK_PATH = process.env.MOCK_PATH ||
  path.resolve(HERE, '../../docs/superpowers/mockups/2026-09-24-swusim-mode-setups.html');
if (!fs.existsSync(MOCK_PATH)) {
  console.error('NO MOCKUP TO DIFF AGAINST: ' + MOCK_PATH +
    '\n  docs/superpowers/ is gitignored. Restore the approved preview there (or set MOCK_PATH)' +
    '\n  before trusting this tool — with no reference it cannot find anything.');
  process.exit(2);
}
const MOCK = 'file://' + MOCK_PATH;
const LIVE='http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const DLG = process.env.DLG || 'setup-pvp';
const W = +(process.env.W || 1440);

// mockup class -> live class (the port prefixed .btn to avoid colliding with legacy .btn)
const MAP = s => s.replace(/\.btn\b/g,'.swu2-btn');

const SEL = [
  ['.setup__pane','pane'], ['.setup__head','head'], ['.setup__tile','tile'],
  ['.setup__kind','kind'], ['.setup__title','title'], ['.setup__desc','desc'],
  ['.setup__body','body'], ['.setup__scroll','scroll'],
  ['.flabel','flabel'], ['.inwrap','inwrap'], ['.input','input'],
  ['.selwrap','selwrap'], ['.select','select'],
  ['.btn','btn-1'], ['.btn--primary','btn-primary'],
  ['.arow','arow'], ['.prow','prow'], ['.drow','drow'], ['.dblock','dblock'],
  ['.deckpick','deckpick'], ['.lb--deck .lb__btn','lb-btn-deck'], ['.lb__val','lb-val'], ['.deckprev','deckprev'], ['.deck__name','deck-name'],
  ['.deck__sub','deck-sub'], ['.deck__n','deck-n'],
  ['.fs','fieldset'], ['.lg','legend'], ['.note','note'],
  ['.pool','pool'], ['.pcs','pcs'], ['.pc__row','pc-row'], ['.pc__name','pc-name'],
  ['.pc__meta','pc-meta'], ['.tag','tag'], ['.seg','seg'], ['.seg__opt','seg-opt'],
];
const PROPS = ['fontFamily','fontSize','fontWeight','letterSpacing','lineHeight','textTransform',
  'color','paddingTop','paddingBottom','paddingLeft','paddingRight',
  'marginTop','marginBottom','rowGap','columnGap','gridTemplateColumns',
  'borderRadius','minBlockSize','alignItems','justifyContent','display'];

const b=await chromium.launch();
async function grab(url,isMock){
  const p=await b.newPage({viewport:{width:W,height:1100}});
  await p.goto(url,{waitUntil:'networkidle'});
  if(!isMock){   // give the guest a saved deck, so the picker compares like-for-like
    await p.evaluate(()=>localStorage.setItem('tcgengine:savedDecks:SWUSim',JSON.stringify([
      {key:'gA',name:'Krennic Blue',leaders:['SOR_001'],base:'SOR_024',
       subtitle:'Director Krennic · Echo Base',count:50,
       input:'https://swudb.com/deck/eeFFtweXI',format:'premier'}])));
    await p.goto(url,{waitUntil:'networkidle'});
  }
  const r=await p.evaluate(async ({DLG,SEL,PROPS,isMock})=>{
    document.querySelectorAll('img[loading="lazy"]').forEach(i=>i.loading='eager');
    const d=document.getElementById(DLG);
    if(d&&d.showModal&&!d.open) d.showModal();
    await Promise.all([...document.images].map(i=>i.decode().catch(()=>{})));
    await new Promise(r=>setTimeout(r,450));
    const out={};
    for(const [sel,key] of SEL){
      const q = isMock ? sel : sel.replace(/\.btn\b/g,'.swu2-btn');
      const e=document.querySelector('#'+DLG+' '+q);
      if(!e){ out[key]='(absent)'; continue; }
      const c=getComputedStyle(e), b=e.getBoundingClientRect(), o={};
      PROPS.forEach(pr=>o[pr]=c[pr]);
      o._box = Math.round(b.width)+'x'+Math.round(b.height);
      out[key]=o;
    }
    return out;
  },{DLG,SEL,PROPS,isMock});
  await p.close(); return r;
}
const m=await grab(MOCK,true), l=await grab(LIVE,false);
let n=0;
for(const [,key] of SEL){
  const a=m[key], c=l[key];
  if(a==='(absent)'&&c==='(absent)') continue;
  if(a==='(absent)'){ console.log(`~ ${key}: live-only`); continue; }
  if(c==='(absent)'){ console.log(`!! ${key}: MISSING IN LIVE`); n++; continue; }
  const bad=[];
  for(const pr of PROPS) if(a[pr]!==c[pr]) bad.push(`${pr}: mock=${a[pr]} live=${c[pr]}`);
  if(a._box!==c._box) bad.push(`BOX: mock=${a._box} live=${c._box}`);
  if(bad.length){ n++; console.log(`\n### ${key}`); bad.forEach(x=>console.log('   '+x)); }
}
console.log(`\n${DLG} @${W}: ${n} element(s) differ`);
await b.close();
