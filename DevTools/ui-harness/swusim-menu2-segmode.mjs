// A SEGMENTED MODE CONTROL MUST CHANGE THE GAME THAT STARTS.
//
// Owner, 2026-09-26: "the hotseat mode is not starting accurately. i think it's starting a
// Goldfish session."
//
// Root cause: SYNC_ACTIVE_SETUP() derived the format from the dialog id plus the pool <select>
// and NEVER read a radio, so every segmented control in the setup modals was decorative:
//     1P Mode  -> always 'goldfish', whichever of Goldfish / Hotseat was picked
//     Twin Suns-> always 'twinsuns',  whichever of Free-For-All / Team Suns was picked
// The stylesheet already keyed off the radio (`.setup__body:has(.hotpick:checked) .hot` reveals
// the second deck box), so the UI RESPONDED while the submission did not — which is exactly what
// makes this hard to spot by eye.
//
// This asserts the hidden field the queue actually reads, for every arm of every segment. It does
// not start games: SYNC_ACTIVE_SETUP is called directly, so the gate leaves no lobbies behind.
import { chromium, firefox, webkit } from 'playwright';
const MENU = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

// modal, the radio to click, the format the queue must be told to start
const CASES = [
  { modal: 'setup-solo',      radio: 'sp-mode-1', want: 'goldfish', label: '1P / Goldfish'   },
  { modal: 'setup-solo',      radio: 'sp-mode-2', want: 'hotseat',  label: '1P / Hotseat'    },
  { modal: 'setup-twin-suns', radio: 'ts-arr-1',  want: 'twinsuns', label: 'TS / Free-For-All' },
  { modal: 'setup-twin-suns', radio: 'ts-arr-2',  want: 'teamsuns', label: 'TS / Team Suns'  },
];

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 1000 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(MENU, { waitUntil: 'networkidle' });

  for (const c of CASES) {
    const got = await p.evaluate(([modal, radio]) => {
      document.querySelectorAll('dialog[open]').forEach(d => d.close());
      const d = document.getElementById(modal);
      d.showModal();
      const r = document.getElementById(radio);
      if (!r) return { err: 'no radio ' + radio };
      r.checked = true;
      r.dispatchEvent(new Event('change', { bubbles: true }));
      const synced = window.SYNC_ACTIVE_SETUP();
      const fmt = (document.getElementById('swu-format-select') || {}).value;
      d.close();
      return { synced, fmt };
    }, [c.modal, c.radio]);

    if (got.err) { bad(name, `${c.label}: ${got.err}`); continue; }
    if (!got.synced) { bad(name, `${c.label}: SYNC_ACTIVE_SETUP did not run`); continue; }
    if (got.fmt !== c.want) bad(name, `${c.label}: queue told to start "${got.fmt}", expected "${c.want}"`);
    else ok();
  }

  // The two arms must not agree — a mapping that returns one constant would satisfy half the
  // checks above and this catches that directly.
  const pair = await p.evaluate(() => {
    const read = (modal, radio) => {
      document.querySelectorAll('dialog[open]').forEach(d => d.close());
      const d = document.getElementById(modal); d.showModal();
      const r = document.getElementById(radio); r.checked = true;
      r.dispatchEvent(new Event('change', { bubbles: true }));
      window.SYNC_ACTIVE_SETUP();
      const v = (document.getElementById('swu-format-select') || {}).value;
      d.close(); return v;
    };
    return {
      solo: [read('setup-solo', 'sp-mode-1'), read('setup-solo', 'sp-mode-2')],
      twin: [read('setup-twin-suns', 'ts-arr-1'), read('setup-twin-suns', 'ts-arr-2')],
    };
  });
  if (pair.solo[0] === pair.solo[1]) bad(name, `both 1P arms start "${pair.solo[0]}" — the segment does nothing`);
  else ok();
  if (pair.twin[0] === pair.twin[1]) bad(name, `both Twin Suns arms start "${pair.twin[0]}" — the segment does nothing`);
  else ok();

  if (errs.length) bad(name, `pageerror ${errs[0]}`);
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} segmented-mode checks failed` : `\nSEGMENTED MODES REACH THE QUEUE — ${checks} checks`);
process.exit(fails ? 1 : 0);
