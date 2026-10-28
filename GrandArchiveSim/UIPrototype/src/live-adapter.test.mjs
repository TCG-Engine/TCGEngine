import test from 'node:test';
import assert from 'node:assert/strict';
import { createLiveAdapter } from './live-adapter.mjs';
const state = revision => ({ revision, commands: [{ id: 'pass', label: 'Pass once', mode: 'DECISION', params: '&decisionIndex=0&cardID=PASS' }] });
test('only current server-projected actions submit, with one in-flight request', async () => {
 let resolve, calls=0;
 const adapter=createLiveAdapter({submit:(mode,params,options)=>{assert.equal(mode,'DECISION');assert.match(params,/&expectedRevision=1$/);assert.equal(options.responseFormat,'json');calls++;return new Promise(r=>resolve=r);},refresh:()=>{}});
 adapter.receive(state(1));
 assert.equal(await adapter.command('unknown',1),false);
 assert.equal(await adapter.command('pass',0),false);
 const request=adapter.command('pass',1);
 assert.equal(await adapter.command('pass',1),false);
 resolve({success:true});
 assert.equal(await request,true);
 assert.equal(await adapter.command('pass',1),false);
 assert.equal(adapter.receive(state(1)),false);
 adapter.receive(state(2));
 assert.equal(adapter.getSnapshot().pending,false);
 assert.equal(calls,1);
});
test('uncertain transport failure refreshes without retry or unlocking stale actions', async () => {
 let refreshes=0;
 const adapter=createLiveAdapter({submit:async()=>{throw Error('offline');},refresh:()=>{refreshes++;}});
 adapter.receive(state(2));
 assert.equal(await adapter.command('pass',2),false);
 assert.equal(refreshes,1);
 assert.equal(adapter.getSnapshot().pending,true);
 assert.match(adapter.getSnapshot().error,/Connection interrupted/);
 assert.equal(adapter.receive(state(1)),false);
 adapter.receive(state(3));
 assert.equal(adapter.getSnapshot().pending,false);
});
test('a poll arriving during submission cannot enable a second request', async () => {
 let resolve;
 const adapter=createLiveAdapter({submit:()=>new Promise(r=>resolve=r),refresh:()=>{}});
 adapter.receive(state(1));
 const request=adapter.command('pass',1);
 adapter.receive(state(2));
 assert.equal(await adapter.command('pass',2),false);
 resolve({success:false,message:'Invalid selection.'});
 await request;
 assert.equal(adapter.getSnapshot().error,'Invalid selection.');
 assert.equal(adapter.getSnapshot().pending,true);
 adapter.receive(state(3));
 assert.equal(adapter.getSnapshot().pending,false);
});
test('explicit refusal unlocks after its own unchanged refresh and keeps the reason visible', async () => {
 let finishRefresh, calls = 0;
 const adapter = createLiveAdapter({
  submit: async () => { calls++; return {success:false, message:'That choice is no longer legal.'}; },
  refresh: () => new Promise(resolve => { finishRefresh = resolve; })
 });
 adapter.receive(state(4));
 const request = adapter.command('pass',4);
 await Promise.resolve();
 assert.equal(adapter.receive(state(4)),false);
 assert.equal(await adapter.command('pass',4),false);
 finishRefresh(state(4));
 await request;
 assert.equal(adapter.getSnapshot().pending,false);
 assert.equal(adapter.getSnapshot().error,'That choice is no longer legal.');
 assert.equal(calls,1);
});
test('unchanged refresh cannot unlock uncertain or successful submissions', async () => {
 for (const outcome of ['network','malformed','success']) {
  const adapter = createLiveAdapter({
   submit: async () => {
    if (outcome === 'network') throw Error('connection lost');
    return outcome === 'success' ? {success:true} : {};
   },
   refresh: async () => state(4)
  });
  adapter.receive(state(4));
  await adapter.command('pass',4);
  assert.equal(adapter.getSnapshot().pending,true, outcome);
  assert.equal(await adapter.command('pass',4),false, outcome);
 }
});
test('late refusal refresh cannot replace newer state', async () => {
 let finishRefresh;
 const adapter = createLiveAdapter({submit:async()=>({success:false}),refresh:()=>new Promise(resolve=>{finishRefresh=resolve;})});
 adapter.receive(state(4));
 const request=adapter.command('pass',4);
 await Promise.resolve();
 adapter.receive(state(5));
 finishRefresh(state(4));
 await request;
 assert.equal(adapter.getSnapshot().revision,5);
 assert.equal(adapter.getSnapshot().pending,false);
});
test('failed refusal refresh stays locked', async () => {
 const adapter=createLiveAdapter({submit:async()=>({success:false}),refresh:async()=>{throw Error('offline');}});
 adapter.receive(state(4));
 await adapter.command('pass',4);
 assert.equal(adapter.getSnapshot().pending,true);
 assert.match(adapter.getSnapshot().error,/refresh failed/);
});
test('an older refresh cannot unlock or overwrite a later pending action', async () => {
 for (const fail of [false,true]) {
  const refreshes=[];
  const adapter=createLiveAdapter({
   submit:async()=>({success:false,message:'Current refusal'}),
   refresh:()=>new Promise((resolve,reject)=>refreshes.push({resolve,reject}))
  });
  adapter.receive(state(4));
  const first=adapter.command('pass',4);
  await Promise.resolve();
  adapter.receive(state(5));
  const second=adapter.command('pass',5);
  await Promise.resolve();
  if (fail) refreshes[0].reject(Error('Old read failed'));
  else refreshes[0].resolve(state(6));
  await first;
  assert.equal(adapter.getSnapshot().revision,5);
  assert.equal(adapter.getSnapshot().pending,true);
  assert.equal(adapter.getSnapshot().error,'Current refusal');
  refreshes[1].resolve(state(5));
  await second;
  assert.equal(adapter.getSnapshot().pending,false);
 }
});
test('decision values are JSON encoded separately from the opaque command', async () => {
 let body;
 const adapter=createLiveAdapter({submit:async (_mode,params)=>{body=new URLSearchParams(params);return {success:true,message:'Choose a target.'};},refresh:async()=>({revision:2,commands:[]})});
 adapter.receive({revision:1,commands:[{id:'answer-decision',label:'Confirm',mode:'100',params:'command=answer-decision'}]});
 assert.equal(await adapter.command('answer-decision',1,['0','1']),true);
 assert.deepEqual(JSON.parse(body.get('answer')),['0','1']);
 assert.equal(body.get('command'),'answer-decision');
 assert.equal(body.get('expectedRevision'),'1');
 assert.equal(adapter.getSnapshot().notice,'Choose a target.');
});
