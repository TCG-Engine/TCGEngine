import {createRequire} from 'node:module';
import test from 'node:test';
import assert from 'node:assert/strict';
const {aggregate}=createRequire(import.meta.url)('../../PokeSim/damage-stats.js');
test('Damage averages count zeros, separate decks and starting orders, and exclude unreached turns',()=>{
    const row=(turn,damage,order='first',deck='sinistcha',complete=true)=>({turn,damage,order,deck,complete});
    const games=[{status:'complete',damageTurns:[row(1,0),row(2,170),row(1,30,'second')]},
        {status:'complete',damageTurns:[row(1,30),row(1,230,'second','brisbane-lopunny')]},
        {status:'capped',damageTurns:[row(1,999)]},{status:'complete'},
        {status:'complete',damageTurns:[row(2,999,'first','sinistcha',false)]}];
    const bins=aggregate(games,'sinistcha');
    assert.equal(bins[0].sum/bins[0].count,15);
    assert.deepEqual(bins[0].counts,{'0':1,'30':1});
    assert.equal(bins.find(b=>b.turn===2).count,1);
    assert.equal(bins.find(b=>b.order==='second').sum,30);
    assert.equal(aggregate(games).find(b=>b.order==='second').count,2);
    assert.deepEqual(aggregate([]),[]);
});
