const assert = require('node:assert/strict');
const {targets,click} = require('../../PokeSim/interaction.js');
const source='p1Hand-6',active='p1Active-0',bench='p1Bench-3';
const attach={type:'attach',player:1,source,target:active};
const state={actions:[attach,{...attach,target:bench}]};
assert.deepEqual(click(state,source,active),{action:attach});
assert.deepEqual(click(state,source,bench),{action:{...attach,target:bench}});
assert.deepEqual(click(state,source,'p2Active-0'),{selected:source});
assert.deepEqual(click(state,source,source),{selected:null});
assert.deepEqual(click(state,source,'p1Hand-2'),{selected:'p1Hand-2'});
assert.deepEqual(targets({actions:[]},source),[]);
assert.deepEqual(click({actions:[]},source,active),{selected:active});
assert.equal(targets({actions:[{type:'attack',source,target:active}]},source).length,0);
assert.equal(click({actions:[{type:'evolve',source,target:bench}]},source,bench).action.type,'evolve');
for (const type of ['setup-active','bench','trainer','attach','evolve']) {
    const action={type,player:1,source,...(['attach','evolve'].includes(type)?{target:active}:{})};
    assert.deepEqual(click({actions:[action,{type:'end',player:1},{type:'ready',player:1}]},null,source),{action});
}
assert.deepEqual(click(state,null,source),{selected:source}); // Two Energy targets.
const attack={type:'attack',player:1,source:active,index:0};
assert.deepEqual(click({actions:[attack]},null,active),{selected:active});
assert.deepEqual(click({actions:[attack,{type:'end',player:1}]},null,active),{selected:active});
assert.deepEqual(click({actions:[attack,{...attack,index:1}]},null,active),{selected:active});
assert.deepEqual(click({actions:[attack,{type:'retreat',player:1,target:bench}]},null,active),{selected:active});
assert.deepEqual(click(state,null,active),{selected:active}); // Never attach another card just by inspecting a target.
assert.deepEqual(click({actions:[]},null,source),{selected:source});
console.log('21 card interaction regression checks passed.');
