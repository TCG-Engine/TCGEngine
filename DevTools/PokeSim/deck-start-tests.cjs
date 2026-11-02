const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '../../PokeSim');
const source = fs.readFileSync(path.join(root, 'local.js'), 'utf8');
const html = fs.readFileSync(path.join(root, 'index.php'), 'utf8');
// Exercise the real URL startup and reset functions together, capturing their API request.
const reset = source.match(/^function reset\(mode\).*$/m)[0];
const start = source.match(/^async function start\(\).*$/m)[0].replace(/start\(\);\s*$/, '');
async function launch(search, savedState = null, extraDeck = null) {
    const elements = {};
    for (const seat of [1, 2]) {
        const options = [...html.match(new RegExp(`<select id="deckKey${seat}">([\\s\\S]*?)</select>`))[1]
            .matchAll(/<option value="([^"]+)"/g)].map(match => ({value: match[1]}));
        if (extraDeck) options.push({value: extraDeck});
        elements['deckKey' + seat] = {options, value: options[0].value};
    }
    for (const id of ['seed', 'deck1', 'deck2']) elements[id] = {value: '', reportValidity: () => true};
    for (const id of ['stats', 'settings']) elements[id] = {close() {}};
    const requests = [];
    const context = vm.createContext({
        URLSearchParams, state: savedState, selectedRef: null, statsShownFor: null,
        location: {search, pathname: '/PokeSim/'}, history: {replaceState() {}},
        $: id => elements[id], request: async body => { if (body) requests.push(body); },
    });
    await vm.runInContext(reset + '\n' + start + '\nstart();', context);
    return requests;
}
(async () => {
    for (const mode of ['bot', 'hotseat']) {
        for (const saved of [null, {mode, players: {1: {deckKey: 'sinistcha'}, 2: {deckKey: 'sinistcha'}}}]) {
            const requests = await launch(`?mode=${mode}&new=1&deck1=ceruledge-ex&deck2=fan-rotom-lopunny`, saved);
            assert.equal(requests.length, 1);
            assert.equal(requests[0].deckKey1, 'ceruledge-ex');
            assert.equal(requests[0].deckKey2, 'fan-rotom-lopunny');
            assert.equal(requests[0].mode, mode);
        }
    }
    const fork = await launch('?mode=bot&new=1&deck1=ceruledge-budew&deck2=ceruledge-budew', null);
    assert.equal(fork[0].deckKey1, 'ceruledge-budew', 'Budew fork starts as the player deck');
    assert.equal(fork[0].deckKey2, 'ceruledge-budew', 'Budew fork starts as the bot deck');
    const fighting = await launch('?mode=bot&new=1&deck1=ceruledge-fighting&deck2=ceruledge-fighting', null);
    assert.equal(fighting[0].deckKey1, 'ceruledge-fighting', 'Fighting deck starts as the player deck');
    assert.equal(fighting[0].deckKey2, 'ceruledge-fighting', 'Fighting deck starts as the bot deck');
    const future = await launch('?mode=bot&new=1&deck1=future-deck', null, 'future-deck');
    assert.equal(future[0].deckKey1, 'future-deck', 'New dropdown decks need no separate startup whitelist');
    const resumed = await launch('?table=1', {mode: 'bot', players: {1: {deckKey: 'ceruledge-ex'}, 2: {deckKey: 'sinistcha'}}});
    assert.equal(resumed.length, 0, 'Resuming does not reset an existing game');
    console.log('PASS deck selection startup: new and existing sessions, both modes, future decks, resume');
})().catch(error => { console.error(error); process.exitCode = 1; });
