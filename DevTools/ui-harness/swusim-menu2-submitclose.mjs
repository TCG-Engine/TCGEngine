// A submit DISMISSES the setup sheet — and that is what makes its errors visible.
//
// Owner, 2026-09-25: "clicking join queue should close this modal", then "when there's an error,
// the modal hides the error".
//
// ⚠ THE MECHANISM IS THE TOP LAYER, NOT A Z-INDEX. StyledAlert (Core/StyledDialog.js) mounts
// `.sd-overlay { position: fixed; z-index: 10000 }` into <body>. A <dialog> opened with
// showModal() renders in the browser's TOP LAYER, which paints above ALL z-indexed content no
// matter how large the value — so a failed join drew its error underneath the open modal and the
// player saw nothing happen. Raising the z-index would not have fixed it; closing the dialog does.
//
// So this gate does not merely assert "the dialog closed". It asserts the error is HITTABLE —
// elementFromPoint at the alert's own centre must land inside the alert. A geometry check alone
// would have passed while the modal covered it.
import { chromium, firefox, webkit } from 'playwright';
const SITE = 'http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/';
const MENU = 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
// Deliberately unresolvable: the join must FAIL, so no lobby is created and none is left behind
// on the 600s TTL for the next run to match against.
const BAD  = 'https://swudb.com/deck/ZZnotarealdeck';
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

const CASES = [
  { modal: 'setup-pvp',        act: 'join',    label: 'PvP / Join Queue' },
  { modal: 'setup-twin-suns',  act: 'join',    label: 'Twin Suns / Join Queue' },
  { modal: 'setup-pvp',        act: 'private', label: 'PvP / Create Private Room' },
];

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 1000 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));

  await p.goto(SITE + 'LoginPage.php', { waitUntil: 'networkidle' });
  await p.fill('input[name="userID"]', 'claudebot1');
  await p.fill('input[name="password"]', 'pass');
  await p.click('button[type="submit"], input[type="submit"]');
  await p.waitForTimeout(2200);

  for (const c of CASES) {
    await p.goto(MENU, { waitUntil: 'networkidle' });
    await p.evaluate(id => document.getElementById(id).showModal(), c.modal);
    await p.waitForTimeout(400);
    await p.fill(`#${c.modal} input[data-detect]`, BAD);
    await p.waitForTimeout(300);

    // the sync has to run BEFORE the close, or the submit reads stale hidden fields
    await p.click(`#${c.modal} [data-act="${c.act}"]`);
    await p.waitForTimeout(600);

    const synced = await p.evaluate(() => (document.getElementById('deck-link') || {}).value);
    if (synced !== BAD) bad(name, `${c.label}: SYNC did not run before the close (hidden field = "${synced}")`);
    else ok();

    const closed = await p.evaluate(id => !document.getElementById(id).open, c.modal);
    if (!closed) bad(name, `${c.label}: the modal stayed open`);
    else ok();

    // now let the failure come back and prove the player can actually SEE it
    await p.waitForTimeout(6000);
    const seen = await p.evaluate(() => {
      const o = document.querySelector('.sd-overlay');
      const w = document.getElementById('waiting-popup') ||
                [...document.querySelectorAll('body > div')].find(d => /Waiting for/i.test(d.textContent || ''));
      const box = o || w;
      if (!box) return { found: false };
      const r = box.getBoundingClientRect();
      if (r.width < 2 || r.height < 2) return { found: true, hittable: false, why: 'zero size' };
      const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
      return {
        found: true,
        hittable: !!hit && box.contains(hit),
        covered: hit ? (hit.closest('dialog') ? 'a dialog' : (hit.tagName + '.' + hit.className)) : 'nothing',
        kind: o ? 'error alert' : 'waiting popup',
      };
    });
    if (!seen.found) bad(name, `${c.label}: nothing was shown after the submit`);
    else if (!seen.hittable) bad(name, `${c.label}: the ${seen.kind} is COVERED BY ${seen.covered}`);
    else ok();

    // leave nothing running for the next case
    await p.keyboard.press('Escape');
    await p.waitForTimeout(800);
  }

  if (errs.length) bad(name, `pageerror ${errs[0]}`);
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} submit-close checks failed` : `\nSUBMIT CLOSES THE SHEET — ${checks} checks`);
process.exit(fails ? 1 : 0);
