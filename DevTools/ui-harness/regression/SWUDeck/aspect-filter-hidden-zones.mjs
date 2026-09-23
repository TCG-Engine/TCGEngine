// Regression: InAspectFilter must survive a HIDDEN deck view.
//
// SWUDeck/GetNextTurn.php renders the literal sentinel "CardBack" for every zone it will not reveal
// (`$canSeePrivatePlayer1 === false`). A deck URL with NO playerID is exactly that case — NextTurn.php
// defaults playerID to "S" (spectator), and the shared deck-link shape SWUSim hands out
// (`NextTurn.php?gameName=<id>&folderPath=SWUDeck`) carries no playerID. So `window.myLeaderData`
// reads "CardBack 0 -", and the Cards zone's registered filter did
//     Cardaspect("CardBack").split(",")
// Cardaspect() returns NULL for any id outside the dictionary, so that threw
//     Uncaught TypeError: can't access property "split", Cardaspect(...) is null
// and the Cards pane rendered nothing.
//
// Both halves are load-bearing:
//   • the hidden view must not throw, and must not filter (there are no visible aspects to filter BY);
//   • the OWNER view must still filter off-aspect cards out — a null-guard that returned "show
//     everything" unconditionally would also stop the error, and silently delete the feature.
import { ENGINES, BASE, EnvError, login, openBoard, desktopContextOpts, harness } from '../lib.mjs';

// Premier deck: leader ASH_009 Ahsoka Tano (Command,Heroism) + base ASH_025 Emperor's Observatory
// (Cunning). Allowed aspects are therefore {Command, Heroism, Cunning}.
const DECK = process.env.DECK || '104';
const OFF_ASPECT = 'SOR_229';   // Cell Block Guard — Villainy, outside the deck's aspects -> HIDE
const ON_ASPECT  = 'SOR_240';   // Fleet Lieutenant — Heroism, inside the deck's aspects -> SHOW

const SUITE_ENGINES = (process.env.ENGINES || 'chromium,firefox')
  .split(',').map(s => s.trim()).filter(Boolean)
  .reduce((acc, n) => { if (ENGINES[n]) acc[n] = ENGINES[n]; return acc; }, {});

// Wait for the client filter to be installed rather than sleeping: the hidden view renders no card,
// so openBoard()'s card-based readiness signal cannot be used for it.
const waitForFilter = async (page) => {
  try {
    await page.waitForFunction(() => typeof window.InAspectFilter === 'function'
      && typeof window.myLeaderData === 'string', null, { timeout: 20000 });
  } catch {
    throw new EnvError(`deck ${DECK}: InAspectFilter/myLeaderData never appeared (stack up? login ok? Games/${DECK}/ exists?)`);
  }
};

// Call the filter the way UILibraries' ShouldSkipZoneCardRendering does, reporting a throw as data.
const callFilter = (page, cardID) => page.evaluate((id) => {
  try { return { threw: false, hidden: window.InAspectFilter(id) }; }
  catch (e) { return { threw: true, message: String(e.message) }; }
}, cardID);

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    console.log(`\n=== ${name} ===`);
    const browser = await engine.launch();
    const page = await (await browser.newContext(desktopContextOpts())).newPage();
    const pageErrors = [];
    page.on('pageerror', e => pageErrors.push(String(e.message)));
    await login(page);

    // ---- hidden view: the shared deck-link shape, no playerID -> spectator -> "CardBack" zones ----
    const resp = await page.goto(`${BASE}/NextTurn.php?gameName=${DECK}&folderPath=SWUDeck`,
      { waitUntil: 'domcontentloaded' });
    if (resp && resp.status() >= 400) throw new EnvError(`deck ${DECK}: HTTP ${resp.status()}`);
    await waitForFilter(page);

    const leaderData = await page.evaluate(() => String(window.myLeaderData));
    // Guards the premise: if the sentinel ever stops being "CardBack", this suite stops testing it.
    check('hidden view really serves the CardBack sentinel', leaderData.startsWith('CardBack'), leaderData.slice(0, 40));

    const hidden = await callFilter(page, ON_ASPECT);
    check('hidden view: filter does not throw', hidden.threw === false, hidden.message);
    check('hidden view: filter hides nothing', hidden.hidden === false, JSON.stringify(hidden));

    const hiddenOff = await callFilter(page, OFF_ASPECT);
    check('hidden view: filter hides nothing for an off-aspect card either',
      hiddenOff.threw === false && hiddenOff.hidden === false, JSON.stringify(hiddenOff));

    // Typing in the pane filter re-renders the Cards zone — one of the paths that routes real users
    // into the filter. pageErrors is NOT cleared first on purpose: the original report was an
    // *uncaught* TypeError, and the load's own render is where it surfaced. Asserting over the whole
    // hidden-view visit is what makes this check fail before the fix.
    const box = await page.$('#myCardPaneFilterText');
    if (!box) throw new EnvError('deck view has no #myCardPaneFilterText — layout changed?');
    await box.fill('vader');
    await page.waitForTimeout(1200);
    check('hidden view: no uncaught error anywhere in the visit',
      pageErrors.length === 0, [...new Set(pageErrors)].join(' | '));

    // ---- owner view: the filter must still actually filter ----
    await openBoard(page, { game: DECK });
    await waitForFilter(page);
    const ownerLeader = await page.evaluate(() => String(window.myLeaderData).split(' ')[0]);
    check('owner view sees the real leader', ownerLeader === 'ASH_009', ownerLeader);

    const off = await callFilter(page, OFF_ASPECT);
    check(`owner view: off-aspect ${OFF_ASPECT} is still hidden`, off.threw === false && off.hidden === true, JSON.stringify(off));
    const on = await callFilter(page, ON_ASPECT);
    check(`owner view: on-aspect ${ON_ASPECT} is still shown`, on.threw === false && on.hidden === false, JSON.stringify(on));

    await browser.close();
  }
});
