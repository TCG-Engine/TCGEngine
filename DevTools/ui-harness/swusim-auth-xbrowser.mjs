// SWUSim's redesigned Login / Signup pages (SiteDef auth.layout = 'arena').
//
// The point of this gate is NOT that the card looks right — the mockup diff and the eye cover
// that. It is that the form STILL WORKS. RenderLoginPage/RenderSignup were re-laid-out around
// the same inputs, and a login page that looks beautiful and posts the wrong field name is a
// site nobody can get into. So this drives a real sign-in with the real credentials, in all
// three engines, and checks the page's accessibility contract on the way past.
//
// It also asserts the four OTHER sites that share those renderers are untouched.
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.SWU_BASE || 'http://localhost:3400/TCGEngine';
const LOGIN = BASE + '/SharedUI/Sites/SWUSim/LoginPage.php';
const SIGNUP = BASE + '/SharedUI/Sites/SWUSim/Signup.php';
const ENGINES = [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]];

let fails = 0, checks = 0;
const ok = (name, cond, detail) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${detail ? ' :: ' + detail : ''}`); }
};

for (const [eng, launcher] of ENGINES) {
  const browser = await launcher.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const errs = [];
  page.on('pageerror', e => errs.push(e.message));
  page.on('response', r => { if (r.status() >= 400) errs.push(`HTTP ${r.status()} ${r.url().split('/').pop()}`); });

  // ── the page renders the new layout, with its contract intact ─────────────
  await page.goto(LOGIN, { waitUntil: 'networkidle' });
  let r = await page.evaluate(() => {
    const card = document.querySelector('.auth__card');
    const de = document.documentElement;
    const unlabelled = [...document.querySelectorAll('input:not([type=hidden])')]
      .filter(e => e.offsetParent !== null)
      .filter(e => !e.labels?.length && !e.getAttribute('aria-label'))
      .map(e => e.name || e.id);
    // the styled checkbox must still be a REAL checkbox underneath, or the browser will not
    // submit it and "keep me signed in" silently stops working
    const box = document.querySelector('.auth__box input');
    // nothing may sit on top of the submit button
    const btn = document.querySelector('.auth__submit');
    const b = btn.getBoundingClientRect();
    const hit = document.elementFromPoint(b.left + b.width / 2, b.top + b.height / 2);
    return {
      card: !!card,
      legacy: !!document.querySelector('.container.bg-black, .flex-padder'),
      hOverflow: de.scrollWidth > de.clientWidth + 1,
      unlabelled,
      boxType: box ? box.type : 'MISSING',
      boxName: box ? box.name : '',
      boxChecked: box ? box.checked : false,
      submitReachable: !!(hit && (hit === btn || btn.contains(hit))),
      submitType: btn.getAttribute('type'),
      h1: document.querySelectorAll('h1').length,
    };
  });
  ok(`${eng} login renders the arena card`, r.card);
  ok(`${eng} login drops the legacy shell`, !r.legacy);
  ok(`${eng} login has no horizontal overflow`, !r.hOverflow);
  ok(`${eng} every login field is labelled`, r.unlabelled.length === 0, r.unlabelled.join(','));
  ok(`${eng} the styled box is a real checkbox`, r.boxType === 'checkbox' && r.boxName === 'rememberMe',
     `${r.boxType}/${r.boxName}`);
  ok(`${eng} keep-me-signed-in defaults on`, r.boxChecked);
  ok(`${eng} the submit button is reachable`, r.submitReachable);
  ok(`${eng} the submit button still submits`, r.submitType === 'submit', r.submitType);
  ok(`${eng} login has exactly one h1`, r.h1 === 1, String(r.h1));

  // ── IT ACTUALLY LOGS IN ───────────────────────────────────────────────────
  // ⚠ Check the fields are THERE before driving them. page.fill() on a renamed input throws a
  // timeout and takes the whole gate down with it — which is detection, but it reports as a
  // crash instead of naming the field that moved.
  const present = await page.evaluate(() => ({
    user: !!document.querySelector('input[name="userID"]'),
    pass: !!document.querySelector('input[name="password"]'),
  }));
  ok(`${eng} the login form still has userID + password`, present.user && present.pass,
     `userID=${present.user} password=${present.pass} — the backend reads these names`);

  let signedIn = false;
  if (present.user && present.pass) {
    await page.fill('input[name="userID"]', 'claudebot1');
    await page.fill('input[name="password"]', 'pass');
    await page.click('.auth__submit');
    await page.waitForLoadState('networkidle');
    signedIn = await page.evaluate(() => /Log Out/i.test(document.body.innerText));
  }
  ok(`${eng} THE REDESIGNED FORM SIGNS YOU IN`, signedIn,
     `landed on ${page.url().split('/TCGEngine')[1]} without a Log Out link`);

  // a signed-in visitor is bounced off the login page, as before
  await page.goto(LOGIN, { waitUntil: 'networkidle' });
  ok(`${eng} a signed-in visitor is redirected away from login`,
     !/LoginPage\.php$/.test(page.url()), page.url().split('/TCGEngine')[1]);

  // ── signup renders and keeps every field the backend reads ────────────────
  await page.goto(BASE + '/AccountFiles/LogoutUser.php', { waitUntil: 'networkidle' }).catch(() => {});
  await page.goto(SIGNUP, { waitUntil: 'networkidle' });
  r = await page.evaluate(() => {
    const names = [...document.querySelectorAll('form input')].map(e => e.name).filter(Boolean);
    const form = document.querySelector('form[action*="signup.inc"]');
    const unlabelled = [...document.querySelectorAll('input:not([type=hidden])')]
      .filter(e => e.offsetParent !== null)
      .filter(e => !e.labels?.length && !e.getAttribute('aria-label'))
      .map(e => e.name || e.id);
    return { card: !!document.querySelector('.auth__card'), names, form: !!form, unlabelled,
             hOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1 };
  });
  ok(`${eng} signup renders the arena card`, r.card);
  ok(`${eng} signup posts to signup.inc`, r.form);
  for (const n of ['uid', 'email', 'pwd', 'pwdrepeat', 'redirect']) {
    ok(`${eng} signup keeps the ${n} field`, r.names.includes(n), r.names.join(','));
  }
  ok(`${eng} every signup field is labelled`, r.unlabelled.length === 0, r.unlabelled.join(','));
  ok(`${eng} signup has no horizontal overflow`, !r.hOverflow);

  ok(`${eng} no page errors`, errs.length === 0, errs[0]);
  await browser.close();
}

// ── the four sites that share these renderers must be untouched ─────────────
{
  const browser = await chromium.launch();
  const page = await browser.newPage();
  for (const site of ['SWUDeck', 'HellbreakSim', 'FaBSim', 'HellbreakDeck']) {
    const res = await page.goto(`${BASE}/SharedUI/Sites/${site}/LoginPage.php`,
                                { waitUntil: 'domcontentloaded' }).catch(() => null);
    const r = await page.evaluate(() => ({
      arena: !!document.querySelector('.auth__card'),
      legacy: !!document.querySelector('.container.bg-black'),
    })).catch(() => ({ arena: false, legacy: false }));
    ok(`${site} did NOT opt in and keeps its legacy login`,
       res && res.status() === 200 && !r.arena && r.legacy,
       `status=${res && res.status()} arena=${r.arena} legacy=${r.legacy}`);
  }
  await browser.close();
}

console.log(fails ? `\n${fails}/${checks} checks failed` : `\nAUTH OK — ${checks} checks`);
process.exit(fails ? 1 : 0);
