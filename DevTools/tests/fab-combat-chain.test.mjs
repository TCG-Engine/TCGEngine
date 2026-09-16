import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const context = vm.createContext({ window: {} });
vm.runInContext(fs.readFileSync(new URL('../../FaBSim/Custom/CombatChainUI.js', import.meta.url), 'utf8'), context);
const record = (Role, ChainLink, order = 0) => 'card 0 ' + JSON.stringify({ Role, ChainLink, Counters: { FAB_PLAY_ORDER: order } });

test('links separate history and interleave opposing reactions by play order, preserving sparse zone indices', () => {
  const links = context.window.FaBChainLinks({
    myCombatChain: [record('ATTACK', 1), '- 0 -', record('ATTACK', 2), record('ATTACK_REACTION', 2, 8), record('ATTACK_REACTION', 2, 6)].join('<|>'),
    theirCombatChain: [record('DEFENSE_REACTION', 2, 7), record('DEFENSE', 2), record('DEFENSE', 1)].join('<|>')
  }, { chainLink: 2 });
  assert.equal(links.length, 2);
  assert.equal(links[0].current, false);
  assert.equal(links[1].current, true);
  assert.deepEqual(Array.from(links[1].cards, c => c.zone + '-' + c.index),
    ['myCombatChain-2', 'theirCombatChain-1', 'myCombatChain-4', 'theirCombatChain-0', 'myCombatChain-3']);
});

test('multiplayer references, removed cards and empty or older records remain safe', () => {
  const links = context.window.FaBChainLinks({ p3CombatChain: record('ATTACK', 4), p4CombatChain: record('DEFENSE', 4) + '<|>card 0 {"removed":true}<|>bad', p1CombatChain: '' }, { chainLink: 4 });
  assert.deepEqual(Array.from(links[0].cards, c => c.zone), ['p3CombatChain', 'p4CombatChain']);
  assert.equal(context.window.FaBChainLinks({}, {}).length, 0);
});
