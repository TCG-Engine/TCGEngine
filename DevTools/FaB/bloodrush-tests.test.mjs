import {test} from 'node:test';
import assert from 'node:assert/strict';
import {analyze,draws,payments,stats,validate,defaults,createSolver,summarize} from './bloodrush-model.mjs';
const close=(a,b)=>assert.ok(Math.abs(a-b)<1e-9,`${a} != ${b}`);
test('hypergeometric draws preserve mass and counts',()=>{
    const outcomes=draws([2,3,4,1],4);close(outcomes.reduce((s,o)=>s+o.p,0),1);
    for(const o of outcomes){assert.equal(o.hand.reduce((s,v)=>s+v,0),4);assert.deepEqual(o.deck.map((v,i)=>v+o.hand[i]),[2,3,4,1]);}
    close(draws([2,2],2).find(o=>o.hand[0]===1).p,2/3);
});
test('pitching cannot overpitch or spend the card being played',()=>{
    assert.deepEqual(payments([0,0,2,0],0,1),[{hand:[0,0,1,0],resources:2}]);
    assert.deepEqual(payments([1,0,0,0],1,1),[{hand:[1,0,0,0],resources:0}]);
    assert.equal(payments([0,0,0,0],0,1).length,0);
});
test('four-card deterministic successful Bellow: pitch blue, discard six, two claws for ten',()=>{
    const row=analyze(0,{size:4,bellows:1,blue6Block:3,blue6Nonblock:0});
    close(row.all.mean,10);assert.equal(row.all.min,10);assert.equal(row.all.max,10);close(row.all.failedRate,0);close(row.groups[1].probability,1);
});
test('non-six discard does not draw or retain an action point',()=>{
    const row=analyze(0,{size:4,bellows:1,blue6Block:0,blue6Nonblock:0});
    close(row.all.damage.mean,0);close(row.all.block.mean,3);close(row.all.mean,3);close(row.all.failedRate,1);close(row.groups[1].probability,1);
});
test('deterministic red hand pays one, discards seven, but cannot fund an attack',()=>{
    const row=analyze(3,{size:4,bellows:1,blue6Block:0,blue6Nonblock:0});close(row.all.damage.mean,0);close(row.all.block.mean,3);close(row.all.mean,3);close(row.all.failedRate,0);
});
test('optimizer may block with a spare Bellow instead of gambling for a double',()=>{
    // 2 Bellows + 4 cost-six blue sixes: openings with two Bellows prefer block 3 + 10
    // over 50% double for 14 / 50% failed first Bellow for 0.
    // Openings with one Bellow always draw the second and produce 14.
    const row=analyze(0,{size:6,bellows:2,blue6Block:4,blue6Nonblock:0,blue6Cost:6});
    close(row.groups[1].mean,13);close(row.groups[1].damage.mean,10);close(row.groups[1].block.mean,3);close(row.groups[2].mean,14);
    close(row.groups[1].probability,6/14);close(row.groups[2].probability,8/14);
    close(row.all.mean,190/14);close(row.all.failedRate,0);
});
test('distribution statistics are conditional and population weighted',()=>{
    const d=new Map([['1,0,1',.25],['1,10,0',.25],['2,20,0',.5]]),s=stats(d,1);
    close(s.probability,.5);close(s.mean,5);close(s.sd,5);close(s.failedRate,.5);assert.equal(stats(d,3).mean,null);
});
test('mixed deck conserves probability and supports genuine double/triple Bellows',()=>{
    const row=analyze(18);close(row.groups.reduce((s,g)=>s+g.probability,0),1);
    close(row.all.mean,row.groups.reduce((s,g)=>s+g.probability*(g.mean??0),0));
    close(row.openingProbability,1-(57*56*55*54)/(60*59*58*57));
    assert.ok(row.groups[2].probability>0);assert.ok(row.groups[3].probability>0);
    assert.ok(row.all.min>=0);assert.ok(row.all.max<=39); // two buffed claws + a buffed red, at most three buffs
    assert.deepEqual(analyze(18),row);
});
test('arsenal line can block three, then deal 19 for cycle value 22',()=>{
    const {dist,blocked}=createSolver({arsenal:1}).opening([0,1,2,1,0],[0,1,1,0,0]),s=summarize(dist).all;
    close(s.mean,22);close(s.damage.mean,19);close(s.block.mean,3);assert.deepEqual(blocked,[0,0,0,1,0]);
});
test('pitching non-six blue first protects the random discard',()=>{
    const {dist,blocked}=createSolver().opening([1,1,1,1,0],[0,1,1,0,0]),s=summarize(dist).all;
    // Pitch blue five, discard either seven or six, draw red + blue six.
    // Both branches reach 5 + 5 + 9. Pitching a six exposes a miss instead.
    close(s.damage.mean,19);close(s.failedRate,0);close(s.block.mean,0);assert.deepEqual(blocked,[0,0,0,0,0]);
});
test('no retroactive credit for new draws or leftover cards',()=>{
    const {dist}=createSolver().opening([1,1,1,1,0],[0,1,1,0,0]),s=summarize(dist).all;
    close(s.mean,19);close(s.block.max,0);assert.equal(s.histogram[22],undefined);
});
test('nonblocking blue sixes cannot defend; block split can change cycle value',()=>{
    const solver=createSolver({arsenal:1});
    const withBlocks=summarize(solver.opening([0,0,4,0,0],[0,0,2,0,0]).dist).all;
    const nonblocks=summarize(solver.opening([0,0,0,0,4],[0,0,0,0,2]).dist).all;
    close(withBlocks.block.mean,3);close(withBlocks.mean,21);close(nonblocks.block.mean,0);close(nonblocks.mean,18);
});
test('cycle SD comes from joint outcomes and includes damage/block correlation',()=>{
    const d=new Map([['1,19,0,3',.5],['1,10,0,6',.5]]);
    close(stats(d).mean,19);close(stats(d).sd,3);close(stats(d,null,'damage').mean,14.5);close(stats(d,null,'block').mean,4.5);
});
test('arsenal Bellow is removed from the draw deck without conditioning the hand',()=>{
    const row=analyze(0,{size:5,bellows:1,blue6Block:4,blue6Nonblock:0,arsenal:1});
    close(row.openingProbability,1);assert.deepEqual(row.openingBellows,[1,0,0,0]);close(row.groups[1].probability,1);
    close(row.all.mean,row.all.damage.mean+row.all.block.mean);
});
test('invalid compositions are rejected',()=>{
    assert.throws(()=>analyze(37));assert.throws(()=>analyze(1,{blue5Cost:-1}));assert.throws(()=>validate({...defaults,bellows:4},0));
});
