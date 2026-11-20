const assert=require('node:assert/strict');
const fs=require('node:fs');
const api=require('../../PokeSim/opening-stats.js');
const games=JSON.parse(fs.readFileSync(__dirname+'/fixtures/lunar-cycle-kpi-test-data.json','utf8'));
const summary=api.summarize(games)[0].lunarCycleTurnOne;
assert.equal(summary.tracked,2);assert.equal(summary.activated,1);assert.equal(summary.reached,2);
assert.deepEqual(summary.conversion,{activated:{games:1,wins:1},missed:{games:1,wins:0}});
class Element{constructor(tag){this.tag=tag;this.children=[];this.textContent='';}append(...items){this.children.push(...items);}replaceChildren(...items){this.children=items;}}
global.document={createElement:tag=>new Element(tag)};
const box=new Element('div');api.render(box,games);
const text=n=>n.textContent+' '+n.children.map(text).join(' ');
assert.match(text(box),/Lunar Cycle completed on own turn 1 \/ tracked games\s+50.0% \(1\/2\)/);
api.match(box,games[0]);assert.match(text(box),/Own turn 1 Lunar Cycle · Legal: yes · Completed: yes/);
api.render(box,[games[2]]);assert.match(text(box),/Not tracked in these results/);
console.log('PASS Lunar KPI summary, render, conversion and legacy handling');
