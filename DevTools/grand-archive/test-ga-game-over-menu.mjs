import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

for (const layout of ['GameLayout.php', 'GameLayoutMobile.php']) {
  const source = readFileSync(new URL(`../../GrandArchiveSim/Custom/${layout}`, import.meta.url), 'utf8');
  const start = source.indexOf('(function()', source.indexOf('window.GAMatchId ='));
  const script = source.slice(start, source.indexOf('})();', start) + 5);
  for (const scenario of ['offline', 'pending', 'ready']) {
    for (const seat of [1, 2]) {
      test(`${layout}: ${scenario}, viewer ${seat}`, async () => {
        const shown = [];
        const context = {
          URL, location: {pathname: '/TCGEngine/NextTurn.php', href: `http://localhost/TCGEngine/NextTurn.php?playerID=${seat}&gameName=test&authKey=test`},
          GAMatchId: 'test', DecisionQueueVariablesData: JSON.stringify({GAMEOVER_WINNER: '2'}),
          document: {getElementById: () => null}, setInterval: () => 1,
          ShowGameOver: (...args) => shown.push(args),
          fetch: () => scenario === 'offline' ? Promise.reject(new Error('offline')) : Promise.resolve({json: () => Promise.resolve(scenario === 'pending' ? {gameWinner: 0} : {gameWinner: 2, didWin: seat === 2, seriesOver: true})}),
        };
        context.window = context;
        vm.runInNewContext(script, context);
        context.GAShowEndGameMenu('stats');
        assert.equal(shown.length, 1, 'result appears before the match-info request finishes');
        assert.equal(shown[0][0], seat === 2);
        assert.equal(shown[0][2], 'stats');
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(shown.length, scenario === 'ready' ? 2 : 1);
      });
    }
  }
}
