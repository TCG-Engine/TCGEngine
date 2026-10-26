import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const sandbox={window:{}};vm.createContext(sandbox);vm.runInContext(readFileSync(new URL('./combat-sounds.js',import.meta.url),'utf8'),sandbox);
const events=(...args)=>JSON.parse(JSON.stringify(sandbox.window.DFGCombatSounds.events(...args)));
const p=()=>({life:40,shield:3,advantages:[],usedBreak:[],usedBurst:[],incomingConfirm:0});
const state=(extra={})=>({matchId:'a',revision:1,phase:'planning',round:1,players:[p(),p()],commitments:[null,{hidden:true}],reactions:[],...extra});
const ids=e=>e.map(x=>x.id);
test('hidden commitments cannot reveal burn, advantage, damage, or turn information',()=>{
 const a=state(),b=state({revision:2,commitments:[null,{hidden:true,bonuses:[1,2],advantages:['Red']}],stats:[{damage:99},{damage:99}]});
 assert.deepEqual(events(a,b),[]);
 assert.deepEqual(ids(events(a,b,{type:'commit'})),['commit']);
});
test('first load, repeated render, and different matches stay silent',()=>{
 const a=state();assert.deepEqual(events(null,a),[]);assert.deepEqual(events(a,a),[]);assert.deepEqual(events(a,state({revision:2,matchId:'b'})),[]);
});
test('simultaneous reveal sounds only when both plans become public',()=>{
 const a=state(),b=state({revision:2,phase:'reactions',reactionTurn:1,commitments:[{main:1,bonuses:[],advantages:[]},{main:2,bonuses:[3],advantages:['Red']}]});
 assert.deepEqual(ids(events(a,b)),['reveal','burn-bonus','advantage-spend']);
 assert.deepEqual(events(b,{...b,revision:3}),[]);
});
test('new public reactions sound once; reaction opportunity follows local priority',()=>{
 const a=state({phase:'reactions',reactionTurn:1,commitments:[{main:1},{main:2}]}),b={...a,revision:2,reactionTurn:0,reactions:[{seat:1,card:3}]};
 assert.deepEqual(ids(events(a,b)),['burn-reaction','reaction-ready']);
 assert.deepEqual(events(b,{...b,revision:3}),[]);
});
test('simultaneous impacts share timing and distinguish the two seats',()=>{
 const a=state({phase:'reactions'}),b=state({revision:2,phase:'switching',stats:[{damage:6},{damage:2}],players:[{...p(),life:38},{...p(),life:34}]});
 assert.deepEqual(events(a,b),[{id:'hit-light',seat:0,delay:0},{id:'hit-heavy',seat:1,delay:0}]);
});
test('armor and confirmed damage have distinct public resolution cues',()=>{
 const a=state({phase:'reactions'}),b=state({revision:2,phase:'switching',stats:[{damage:3,confirmed:2},{damage:0}],players:[{...p(),shield:0,usedBreak:[20]},{...p(),life:37,usedBurst:[22],incomingConfirm:2,advantages:['Green']}]});
 const heard=ids(events(a,b));for(const id of ['barrier-break','break-stop','burst-break','confirm-mark','confirm-cash','advantage-gain'])assert.ok(heard.includes(id),id);
});
test('knockout replaces ordinary impact and outcome follows later',()=>{
 const a=state({phase:'reactions'}),b=state({revision:2,phase:'finished',winner:0,stats:[{damage:40},{damage:0}],players:[p(),{...p(),life:0}]});
 const heard=events(a,b);assert.ok(heard.some(x=>x.id==='knockout'&&x.seat===1));assert.ok(!heard.some(x=>x.id.startsWith('hit-')&&x.seat===1));assert.equal(heard.find(x=>x.id==='victory').delay,.65);
});
test('concession has no fictional hit; new rounds have a distinct ready cue',()=>{
 const a=state();assert.deepEqual(ids(events(a,state({revision:2,phase:'finished',winner:1}),{type:'resign'})),['defeat']);
 assert.deepEqual(ids(events(state({phase:'switching'}),state({revision:2,round:2}))),['round-ready']);
});
