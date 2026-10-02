import {createRequire} from 'node:module';
import test from 'node:test';
import assert from 'node:assert/strict';
const stats=createRequire(import.meta.url)('../../PokeSim/opening-stats.js');
const opening=(extras={})=>({player:1,deck:'custom',order:'second',goal:'Any attack',status:'complete',attackDeclared:false,attackResolved:false,goalAttack:false,fullyEnabledAttack:false,blockers:[],milestones:{},trace:[],...extras});
test('Opening denominators separate reached opportunities, early endings, incomplete and untracked games',()=>{
    const games=[{status:'complete',winner:1,openingStats:[opening({attackDeclared:true,attackResolved:true,goalAttack:true,fullyEnabledAttack:true})]},
        {status:'complete',winner:2,openingStats:[opening({blockers:['energy_shortfall','attacker_unavailable']})]},
        {status:'complete',winner:2,openingStats:[opening({status:'game_ended_before_opportunity'})]},
        {status:'capped',winner:0,openingStats:[opening()]},{status:'error',openingStats:[opening({status:'incomplete'})]},
        {status:'complete',winner:1}];
    const [g]=stats.summarize(games);
    assert.equal(g.games,3);assert.equal(g.reached,2);assert.equal(g.earlyEnded,1);assert.equal(g.incomplete,2);
    assert.equal(g.enabled,1);assert.deepEqual(g.blockers,{energy_shortfall:1,attacker_unavailable:1});
    assert.deepEqual(g.conversion,{enabled:{games:1,wins:1},missed:{games:1,wins:0}});
    assert.deepEqual(stats.summarize([]),[]);
});
test('Both seats group by deck and order, with seat-specific win attribution',()=>{
    const groups=stats.summarize([{status:'complete',winner:2,openingStats:[opening({order:'first'}),opening({player:2,deck:'another'})]}]);
    assert.equal(groups.length,2);assert.equal(groups[0].conversion.missed.wins,0);assert.equal(groups[1].conversion.missed.wins,1);
});
test('Rendering tolerates older results and exposes trace text without HTML injection',()=>{
    class Element{constructor(tag){this.tag=tag;this.children=[];this.textContent='';}append(...nodes){this.children.push(...nodes);}replaceChildren(...nodes){this.children=nodes;}}
    const prior=globalThis.document;globalThis.document={createElement:tag=>new Element(tag)};
    try{
        const root=new Element('section');stats.render(root,[{status:'complete'}]);
        assert.match(root.children.at(-1).textContent,/Older results/);
        stats.render(root,[{status:'complete',winner:1,openingStats:[opening()]}]);assert.ok(root.children.length>2);
        stats.match(root,{openingStats:[opening({trace:[{turn:1,type:'trainer',sourceName:'<img onerror=bad>'}]})]});
        const strings=node=>[node.textContent,...node.children.flatMap(strings)];
        assert.ok(strings(root).some(s=>s.includes('<img onerror=bad>')));
        stats.match(root,undefined);assert.equal(root.children.length,0);
    }finally{globalThis.document=prior;}
});
