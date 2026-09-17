import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const ui = fs.readFileSync(new URL('../../Core/UILibraries20260917.js', import.meta.url), 'utf8');
const source = ui.slice(ui.indexOf('function CheckAndShowDecisionQueue('), ui.indexOf('// --- Selection Mode State ---'));
function setup(enabled, spectator = false) {
  const answers = [];
  const context = vm.createContext({
    window: {SelectionMode: {}, TCGSettings: {get: () => enabled}},
    document: {querySelectorAll: () => []}, setTimeout: fn => fn(),
    IsSpectatorClient: () => spectator,
    ResetDelayedDecisionUndoAffordance() {}, UpdateDelayedDecisionUndoAffordance() {},
    CategorizeMZChooseSpecs: specs => ({inlineSpecs: specs, popupCards: []}),
    ShowSelectionMessage() {}, SubmitInput: (...args) => answers.push(args)
  });
  vm.runInContext(source, context);
  return {context, answers};
}
test('single mandatory and optional targets auto-submit only after preparation, once', () => {
  for (const Type of ['MZCHOOSE', 'MZMAYCHOOSE']) {
    const {context, answers} = setup(true);
    const queue = [{Type, Param: 'p2Hero-0'}];
    context.CheckAndShowDecisionQueue(queue, 'prepare');
    assert.equal(answers.length, 0);
    context.CheckAndShowDecisionQueue(queue, 'finalize');
    context.CheckAndShowDecisionQueue(queue, 'finalize');
    assert.equal(answers.length, 1);
    assert.match(answers[0][1], /cardID=p2Hero-0$/);
  }
});
test('disabled preference, spectators, multiple targets, whole zones and filters stay manual', () => {
  for (const [enabled, spectator, Param] of [[false,false,'p2Hero-0'],[true,true,'p2Hero-0'],[true,false,'p2Hero-0&p3Hero-0'],[true,false,'p2Hand'],[true,false,'p2Hero-0:Status=2']]) {
    const {context, answers} = setup(enabled, spectator);
    context.CheckAndShowDecisionQueue([{Type:'MZMAYCHOOSE', Param}], 'finalize');
    assert.equal(answers.length, 0);
  }
});
