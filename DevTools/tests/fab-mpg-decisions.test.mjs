import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const ui = fs.readFileSync(new URL('../../Core/UILibraries20260910.js', import.meta.url), 'utf8');
test('A local decision takes precedence over another hero retaining combat priority', () => {
  for (const viewer of [2, 4]) {
    const context = vm.createContext({window: {PriorityPlayerData: 1, myDecisionQueueData: 'choice'}, document: {getElementById: () => ({value: viewer})}, _firstPendingDecisionFromRaw: raw => raw === 'choice' ? {Type: 'MZCHOOSE'} : null});
    vm.runInContext(ui.slice(ui.indexOf('function _shouldShowOpponentWaitingMessage('), ui.indexOf('function _positionMessageNearAnchor(')), context);
    assert.equal(context._shouldShowOpponentWaitingMessage(false), false);
    context.window.myDecisionQueueData = '';
    assert.equal(context._shouldShowOpponentWaitingMessage(false), true);
    context.window.PriorityPlayerData = viewer;
    assert.equal(context._shouldShowOpponentWaitingMessage(false), false);
  }
});
