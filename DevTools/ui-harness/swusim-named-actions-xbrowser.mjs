// SWUSim named Action abilities — the unit click menu, Chromium + Firefox + WebKit, desktop AND phone (2026-11-02).
//
// Board: SWUSim/Tests/Visual/NamedActions_UnitMenu.md, built through the Test Schema Editor's own endpoints
// (TestSchemaSetup.php — mod login, claudebot1) and opened as seat 1. Clicking a unit that has any Action opens
// #swuUnitActionMenu: "Attack" when the unit can attack, then ONE BUTTON PER ACTION, labelled after the card that gives
// it. An Action the unit can't use now is a disabled button with its reason, and clicking it sends nothing.
//   myGroundArena-0  exhausted Crosshair → Crosshair [2 resources] · Crosshair [Exhaust] (greyed) · Satine Kryze (greyed)
//   myGroundArena-1  Battlefield Marine  → Attack · Satine Kryze
//   myGroundArena-2  Chewbacca + Improvised Identity → Attack · Chewbacca · Improvised Identity · Satine Kryze
// Screenshots: DevTools/ui-harness/_shots/swusim-named-actions-<engine>-<layout>-<unit>.png — LOOK at them.
//
// Usage: node swusim-named-actions-xbrowser.mjs [BASE]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const here   = dirname(fileURLToPath(import.meta.url));
const SHOTS  = resolve(here, '_shots');
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/NamedActions_UnitMenu.md'), 'utf8');
const BASE_SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/NamedActions_BaseMenu.md'), 'utf8');
const DISCARD_SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/NamedActions_DiscardMenu.md'), 'utf8');
const LEADER_SCHEMAS = {
  ready:  readFileSync(resolve(here, '../../SWUSim/Tests/Visual/NamedActions_LeaderMenu.md'), 'utf8'),
  greyed: readFileSync(resolve(here, '../../SWUSim/Tests/Visual/NamedActions_LeaderMenuGreyed.md'), 'utf8'),
};
// The leader menu (#swuLeaderMenu, a centred panel): its buttons — Action entries carry .swu-uam-label/.swu-uam-reason.
const readLeaderMenu = (page) => page.evaluate(() => {
  const menu = document.getElementById('swuLeaderMenu');
  if (!menu) return null;
  const r = menu.getBoundingClientRect();
  return { inView: r.x >= 0 && r.y >= 0 && r.right <= innerWidth && r.bottom <= innerHeight,
    buttons: [...menu.querySelectorAll('button')].map((b) => { const br = b.getBoundingClientRect(); return {
      label: (b.querySelector('.swu-uam-label')?.textContent ?? b.textContent).trim(),
      reason: (b.querySelector('.swu-uam-reason')?.textContent ?? '').trim(), disabled: b.disabled,
      rect: [br.x, br.y, br.width, br.height] }; }) };
});
const ALL    = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const LAYOUTS = { desktop: { width: 1600, height: 1000 }, mobile: { width: 390, height: 844 } };

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}
async function buildGame(page, schema = SCHEMA) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps || []) {   // a board reached by play (the discard board): run its WHEN steps
    const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
      { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
    if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
  }
  return setup.gameName;
}

// The open menu: each button's label, reason and disabled state, whether it is the topmost element at its centre,
// and whether the menu fits the viewport.
const readMenu = (page) => page.evaluate(() => {
  const menu = document.getElementById('swuUnitActionMenu');
  if (!menu) return null;
  const r = menu.getBoundingClientRect();
  const buttons = [...menu.querySelectorAll('button')].map((b) => {
    const br = b.getBoundingClientRect();
    const top = document.elementFromPoint(br.x + br.width / 2, br.y + br.height / 2);
    return {
      label: (b.querySelector('.swu-uam-label')?.textContent ?? b.textContent).trim(),
      reason: (b.querySelector('.swu-uam-reason')?.textContent ?? '').trim(),
      disabled: b.disabled, opacity: parseFloat(getComputedStyle(b).opacity),
      rect: [br.x, br.y, br.width, br.height], topmost: !!top && (top === b || b.contains(top)),
    };
  });
  return { inView: r.x >= 0 && r.y >= 0 && r.right <= innerWidth && r.bottom <= innerHeight,
           hScroll: document.documentElement.scrollWidth > innerWidth, buttons };
});

async function openMenu(page, mz, layout, selector = `[data-mzid="${mz}"]`) {
  await page.evaluate(() => { const m = document.getElementById('swuUnitActionMenu'); if (m) m.remove(); });
  const box = await page.locator(selector).first().boundingBox();
  if (!box) return null;
  await page.mouse.click(box.x + box.width / 2, box.y + box.height / 2);
  // A phone has no hover, but a mouse click leaves the pointer resting on the unit and the board's hover card preview
  // then draws over the menu. Park the pointer off the board, as a lifted finger would be.
  if (layout === 'mobile') await page.mouse.move(2, 2);
  await page.waitForTimeout(600);
  return readMenu(page);
}

const WANT = {
  'myGroundArena-0': [['Crosshair [2 resources]', false], ['Crosshair [Exhaust]', true], ['Satine Kryze', true]],
  'myGroundArena-1': [['Attack', false], ['Satine Kryze', false]],
  'myGroundArena-2': [['Attack', false], ['Chewbacca', false], ['Improvised Identity', false], ['Satine Kryze', false]],
};

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    for (const [layout, viewport] of Object.entries(LAYOUTS)) {
      const tag = `${engineName}/${layout}`;
      const ctx = await browser.newContext({ viewport });
      const page = await ctx.newPage();
      const sent = [];
      page.on('request', (r) => { if (r.url().includes('ProcessInput.php')) sent.push(decodeURIComponent(r.url())); });
      try {
        await login(page);
        const gameName = await buildGame(page);
        await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema`
          + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
        await page.waitForFunction(() => window.myActionsData && window.myActionsData.unitActionEntries, null, { timeout: 45000 });
        await page.waitForTimeout(2500);   // the click handlers wire after the first glow pass

        for (const [mz, want] of Object.entries(WANT)) {
          const m = await openMenu(page, mz, layout);
          ok(`${tag} ${mz}: clicking opens the menu`, m !== null);
          if (!m) continue;
          const got = m.buttons.map((b) => [b.label, b.disabled]);
          ok(`${tag} ${mz}: buttons`, JSON.stringify(got) === JSON.stringify(want), JSON.stringify(got));
          for (const b of m.buttons.filter((x) => x.disabled)) {
            ok(`${tag} ${mz}: "${b.label}" greyed shows its reason`, b.reason === 'Exhausted', b.reason);
            ok(`${tag} ${mz}: "${b.label}" greyed looks dimmed`, b.opacity < 0.8, String(b.opacity));
          }
          for (const b of m.buttons) ok(`${tag} ${mz}: "${b.label}" not covered`, b.topmost, JSON.stringify(b.rect));
          ok(`${tag} ${mz}: menu inside the viewport`, m.inView);
          ok(`${tag} ${mz}: no horizontal page scroll`, !m.hScroll);
          await page.screenshot({ path: `${SHOTS}/swusim-named-actions-${engineName}-${layout}-${mz}.png` });
        }

        // A unit click never runs an Action outright — not even a lone usable one on an ENEMY unit (one tap on P2's
        // Mercenary Gunship would spend 4 resources and take control). The menu opens and nothing is sent.
        {
          const before = sent.length;
          const gm = await openMenu(page, 'theirSpaceArena-0', layout);
          ok(`${tag}: an enemy unit's lone Action opens a menu`, JSON.stringify(gm?.buttons.map((b) => [b.label, b.disabled]) ?? null)
            === JSON.stringify([['Mercenary Gunship', false]]), JSON.stringify(gm?.buttons.map((b) => b.label) ?? null));
          ok(`${tag}: …and the click alone sends nothing`, sent.length === before, sent.slice(before).join(' | '));
        }

        // A greyed button sends nothing and leaves the menu open.
        let m = await openMenu(page, 'myGroundArena-0', layout);
        const grey = m?.buttons.find((b) => b.label === 'Crosshair [Exhaust]');
        if (grey) {
          const before = sent.length;
          await page.mouse.click(grey.rect[0] + grey.rect[2] / 2, grey.rect[1] + grey.rect[3] / 2);
          await page.waitForTimeout(1000);
          ok(`${tag}: a greyed button sends no request`, sent.length === before, sent.slice(before).join(' | '));
          ok(`${tag}: …and the menu stays open`, await page.evaluate(() => !!document.getElementById('swuUnitActionMenu')));
        } else ok(`${tag}: greyed Crosshair [Exhaust] found`, false);

        // An enabled one submits "<mz>!CustomInput!Activate:<id>".
        m = await openMenu(page, 'myGroundArena-1', layout);
        const satine = m?.buttons.find((b) => b.label === 'Satine Kryze');
        if (satine) {
          const before = sent.length;
          await page.mouse.click(satine.rect[0] + satine.rect[2] / 2, satine.rect[1] + satine.rect[3] / 2);
          await page.waitForTimeout(1500);
          ok(`${tag}: an enabled button submits Activate:<id>`,
            sent.slice(before).some((u) => u.includes('mode=10001') && u.includes('myGroundArena-1!CustomInput!Activate:TWI_047.0')),
            sent.slice(before).join(' | '));
        } else ok(`${tag}: Satine Kryze button found on the Marine`, false);
      } catch (e) {
        ok(`${tag}: ran`, false, String(e).slice(0, 240));
      }
      await ctx.close();

      // ── The base menu (NamedActions_BaseMenu.md): same menu, one button per base Action, the spent Epic greyed.
      if (!process.env.SKIP_BASE) {
        const bctx = await browser.newContext({ viewport });
        const bpage = await bctx.newPage();
        const bsent = [];
        bpage.on('request', (r) => { if (r.url().includes('ProcessInput.php')) bsent.push(decodeURIComponent(r.url())); });
        try {
          await login(bpage);
          const g2 = await buildGame(bpage, BASE_SCHEMA);
          await bpage.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${g2}&playerID=1&authKey=testschema`
            + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
          await bpage.waitForFunction(() => window.myActionsData && window.myActionsData.baseActionEntries, null, { timeout: 45000 });
          await bpage.waitForTimeout(2500);
          const m = await openMenu(bpage, 'myBase-0', layout, '#myBaseSlot');
          ok(`${tag} base: clicking opens the menu`, m !== null);
          if (m) {
            const got = m.buttons.map((b) => [b.label, b.disabled]);
            ok(`${tag} base: buttons`, JSON.stringify(got) === JSON.stringify([['Security Complex', true], ['Carbonite Chamber', false]]), JSON.stringify(got));
            const grey = m.buttons.find((b) => b.disabled);
            ok(`${tag} base: greyed shows "Used this game"`, grey && grey.reason === 'Used this game', grey && grey.reason);
            ok(`${tag} base: menu inside the viewport`, m.inView);
            await bpage.screenshot({ path: `${SHOTS}/swusim-named-actions-${engineName}-${layout}-base.png` });
            if (grey) {
              const before = bsent.length;
              await bpage.mouse.click(grey.rect[0] + grey.rect[2] / 2, grey.rect[1] + grey.rect[3] / 2);
              await bpage.waitForTimeout(1000);
              ok(`${tag} base: a greyed button sends no request`, bsent.length === before, bsent.slice(before).join(' | '));
            }
            const mm = await openMenu(bpage, 'myBase-0', layout, '#myBaseSlot');
            const ch = mm?.buttons.find((b) => b.label === 'Carbonite Chamber');
            if (ch) {
              const before = bsent.length;
              await bpage.mouse.click(ch.rect[0] + ch.rect[2] / 2, ch.rect[1] + ch.rect[3] / 2);
              await bpage.waitForTimeout(1500);
              ok(`${tag} base: an enabled button submits EpicAction:<id>`,
                bsent.slice(before).some((u) => u.includes('mode=10001') && u.includes('myBase-0!CustomInput!EpicAction:HMW_095.0')), bsent.slice(before).join(' | '));
            } else ok(`${tag} base: Carbonite Chamber button found`, false);
          }
        } catch (e) {
          ok(`${tag} base: ran`, false, String(e).slice(0, 240));
        }
        await bctx.close();
      }

      // ── The leader menu (NamedActions_LeaderMenu*.md): the leader's Action named after it, greyed when exhausted;
      //    the deploy buttons unchanged.
      if (!process.env.SKIP_LEADER) for (const [kind, schema] of Object.entries(LEADER_SCHEMAS)) {
        const lctx = await browser.newContext({ viewport });
        const lpage = await lctx.newPage();
        const lsent = [];
        lpage.on('request', (r) => { if (r.url().includes('ProcessInput.php')) lsent.push(decodeURIComponent(r.url())); });
        try {
          await login(lpage);
          const g3 = await buildGame(lpage, schema);
          await lpage.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${g3}&playerID=1&authKey=testschema`
            + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
          await lpage.waitForFunction(() => window.myActionsData && window.myActionsData.leaderActionEntriesByIndex, null, { timeout: 45000 });
          await lpage.waitForTimeout(2500);
          await lpage.evaluate(() => { const m = document.getElementById('swuLeaderMenu'); if (m) m.remove(); });
          const lb = await lpage.locator('#myLeaderSlot').first().boundingBox();
          await lpage.mouse.click(lb.x + lb.width / 2, lb.y + lb.height / 2);
          if (layout === 'mobile') await lpage.mouse.move(2, 2);
          await lpage.waitForTimeout(800);
          const m = await readLeaderMenu(lpage);
          ok(`${tag} leader/${kind}: clicking opens the leader menu`, m !== null);
          if (m) {
            const got = m.buttons.map((b) => [b.label, b.disabled]);
            const want = kind === 'ready' ? [['Leader Ability', false], ['Deploy Leader', false], ['Cancel', false]]
                                          : [['Leader Ability', true], ['Cancel', false]];
            ok(`${tag} leader/${kind}: buttons`, JSON.stringify(got) === JSON.stringify(want), JSON.stringify(got));
            ok(`${tag} leader/${kind}: menu inside the viewport`, m.inView);
            await lpage.screenshot({ path: `${SHOTS}/swusim-named-actions-${engineName}-${layout}-leader-${kind}.png` });
            if (kind === 'greyed') {
              const grey = m.buttons.find((b) => b.disabled);
              ok(`${tag} leader/greyed: reason "Exhausted"`, grey && grey.reason === 'Exhausted', grey && grey.reason);
              if (grey) {
                const before = lsent.length;
                await lpage.mouse.click(grey.rect[0] + grey.rect[2] / 2, grey.rect[1] + grey.rect[3] / 2);
                await lpage.waitForTimeout(1000);
                ok(`${tag} leader/greyed: a greyed button sends no request`, lsent.length === before, lsent.slice(before).join(' | '));
              }
            } else {
              const ab = m.buttons.find((b) => b.label === 'Leader Ability');
              if (ab) {
                const before = lsent.length;
                await lpage.mouse.click(ab.rect[0] + ab.rect[2] / 2, ab.rect[1] + ab.rect[3] / 2);
                await lpage.waitForTimeout(1500);
                ok(`${tag} leader/ready: the Action submits LeaderAbility:<id>`,
                  lsent.slice(before).some((u) => u.includes('myLeader-0!CustomInput!LeaderAbility:JTL_014.0')), lsent.slice(before).join(' | '));
              }
            }
          }
        } catch (e) {
          ok(`${tag} leader/${kind}: ran`, false, String(e).slice(0, 240));
        }
        await lctx.close();
      }

      // ── The discard pile (NamedActions_DiscardMenu.md, spec §9): Battlefield Marine playable two ways — Cobb Vanth's
      //    free play and Boga's 1 less. Clicking it opens the Action menu with each price; picking Boga submits its id.
      if (!process.env.SKIP_DISCARD) {
        const dctx = await browser.newContext({ viewport });
        const dpage = await dctx.newPage();
        const dsent = [];
        dpage.on('request', (r) => { if (r.url().includes('ProcessInput.php')) dsent.push(decodeURIComponent(r.url())); });
        try {
          await login(dpage);
          const g4 = await buildGame(dpage, DISCARD_SCHEMA);
          await dpage.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${g4}&playerID=1&authKey=testschema`
            + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
          await dpage.waitForFunction(() => (window.myActionsData?.playableDiscards || []).some((x) => (x.entries || []).length > 1), null, { timeout: 45000 });
          await dpage.waitForTimeout(2500);
          const before = dsent.length;
          const m = await openMenu(dpage, 'myDiscard-1', layout, '#myDiscardSlot .discard-playable');
          ok(`${tag} discard: clicking the card opens the menu (no play yet)`, m !== null && dsent.length === before, dsent.slice(before).join(' | '));
          if (m) {
            const got = m.buttons.map((b) => [b.label, b.reason, b.disabled]);
            ok(`${tag} discard: buttons with their prices`, JSON.stringify(got) === JSON.stringify([['Cobb Vanth', 'Free', false], ['Boga', '1 resource', false]]), JSON.stringify(got));
            ok(`${tag} discard: menu inside the viewport`, m.inView);
            await dpage.screenshot({ path: `${SHOTS}/swusim-named-actions-${engineName}-${layout}-discard.png` });
            const boga = m.buttons.find((b) => b.label === 'Boga');
            if (boga) {
              const b2 = dsent.length;
              await dpage.mouse.click(boga.rect[0] + boga.rect[2] / 2, boga.rect[1] + boga.rect[3] / 2);
              await dpage.waitForTimeout(1500);
              ok(`${tag} discard: picking Boga submits Play:HMW_122.d0`,
                dsent.slice(b2).some((u) => u.includes('PlayFromDiscard-1!CustomInput!Play:HMW_122.d0')), dsent.slice(b2).join(' | '));
            }
          }
        } catch (e) {
          ok(`${tag} discard: ran`, false, String(e).slice(0, 240));
        }
        await dctx.close();
      }
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
