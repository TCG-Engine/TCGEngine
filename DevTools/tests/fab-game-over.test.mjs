import {test} from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../FaBSim/Custom/GameOver.php', import.meta.url), 'utf8').split('<script>')[1].split('</script>')[0];
function setup(viewer, seats) {
  const calls = [];
  const nodes = {'playerID': {value: viewer}, 'fab-game-result': {focus() {}}};
  const context = vm.createContext({window: {SeatOrderData: seats, WinnerData: 0}, document: {getElementById: id => nodes[id]}, ShowGameOver(...args) {
    calls.push(args);
    nodes['game-over-title'] = {textContent: args[0] ? 'You Won!' : 'You Lost'};
    nodes['game-over-overlay'] = {classList: {add() {}, contains() {return true;}}, setAttribute() {}, querySelectorAll() {return [];}, remove() {delete nodes['game-over-overlay'];}};
  }});
  vm.runInContext(source, context);
  return {context, nodes, calls, refresh: context.window.FaBRefreshGameOver};
}
test('duel result opens once, supports board review and reopening, and resets after undo', () => {
  const {context, nodes, calls, refresh} = setup('1', '12');
  refresh();assert.equal(calls.length, 0);
  context.window.WinnerData = '1';refresh();refresh();
  assert.equal(calls.length, 1);assert.equal(calls[0][0], true);
  assert.match(calls[0][4], /Player 1 wins · 1v1/);
  calls[0][3][1].onClick();refresh();
  assert.equal(nodes['game-over-overlay'], undefined);
  assert.equal(calls.length, 1);
  nodes['fab-game-result'].onclick();assert.equal(calls.length, 2);
  context.window.WinnerData = '0';refresh();
  assert.equal(nodes['game-over-overlay'], undefined);
  assert.equal(nodes['fab-game-result'].hidden, true);
  context.window.WinnerData = '2';refresh();assert.equal(calls[2][0], false);
});
test('UPF waits for a winner and identifies the winning seat', () => {
  const {context, calls, refresh} = setup('2', '1234');
  context.window.LiveSeatsData = '134';refresh();assert.equal(calls.length, 0);
  context.window.WinnerData = '4';refresh();
  assert.equal(calls[0][0], false);assert.match(calls[0][4], /Player 4 wins · Ultimate Pit Fight/);
});
test('spectators see a neutral result', () => {
  const {context, nodes, refresh} = setup('0', '12');
  context.window.WinnerData = '2';refresh();
  assert.equal(nodes['game-over-title'].textContent, 'Game over');
});
