import test from 'node:test';
import assert from 'node:assert/strict';
import { createDemoStore } from './store.mjs';
test('a stale or repeated command cannot pass twice', () => {
 const s=createDemoStore(), revision=s.getSnapshot().revision;
 assert.equal(s.command({type:'pass',revision}),true);
 assert.equal(s.command({type:'pass',revision}),false);
 assert.equal(s.command({type:'pass',revision:s.getSnapshot().revision}),false);
});
test('playing a melody pauses for Forese without activating her', () => {
 const s=createDemoStore();
 assert.equal(s.command({type:'play',cardId:'melody',revision:0}),true);
 assert.equal(s.getSnapshot().reminderMatched,true);
 assert.equal(s.getSnapshot().foreseReady,true);
 assert.equal(s.getSnapshot().priority,'you');
 assert.equal(s.getSnapshot().cards.some(c=>c.id==='melody'),false);
});
test('disabled preference does not match; explicit activation rests Forese', () => {
 const s=createDemoStore(); s.setReminder(false);
 s.command({type:'play',cardId:'harmony',revision:s.getSnapshot().revision});
 assert.equal(s.getSnapshot().reminderMatched,false);
 s.reset(); s.command({type:'activate',cardId:'forese',revision:s.getSnapshot().revision});
 assert.equal(s.getSnapshot().foreseReady,false);
});
test('unrelated updates preserve card identity for memoized rendering', () => {
 const s=createDemoStore(), cards=s.getSnapshot().cards;
 s.backgroundUpdate(); assert.equal(s.getSnapshot().cards,cards);
});
