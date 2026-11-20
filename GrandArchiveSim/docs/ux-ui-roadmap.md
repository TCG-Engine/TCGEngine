# GrandArchiveSim UX/UI roadmap

Status: React prototype paused, 2026-10-28. Branch: `codex/ux-ui-initiative`.

## Current direction: improve the existing match screen

Keep the React prototype available for reference. The local lobby defaults to
Classic again. Continue UX work in the existing PHP/JavaScript interface, using
`Custom/GameLayout.php` and `Custom/GameLayoutMobile.php` and the existing
inspection, decision, and rendering hooks. Do not edit generated files.

Proposed sequence:

1. Card readability: preserve useful card sizes on crowded fields and hands;
   evaluate scrolling and inspection against the current shrink-to-fit behavior.
2. Board space: reduce secondary panel prominence and keep hand, field, and
   current decision visible together on phones.
3. Card actions: make legal actions and selection states easier to identify,
   retaining existing engine validation and explicit confirmations.
4. Prompt clarity: improve priority ownership, pending effects, and response
   controls before introducing any automatic passing.
5. Performance: measure existing rendering and polling, then reduce redundant
   updates without changing game behavior.

Validate each small change in Chromium, Firefox, and Safari/WebKit, including
crowded boards and interactive decisions. React rollout is not current work.

## Preserved React checkpoint

The following describes the completed prototype before it was paused.
The opt-in React screen now uses the authenticated PHP engine for main actions,
fast responses, materialization, combat choices, multi-picks, modes, numeric
choices, splits, naming and rearrangement. It displays board/card information,
public effects and declared field targets, combat, victory and concession.
A short two-seat game reaches victory through its HTTP commands. Card actions
are selected through inspection; secondary zones collapse on the mobile board.
Validation: 77 HTTP/browser checks, 12 responsive browser/viewport combinations,
281 projection checks, 75 control checks, 17 JavaScript tests, and revision/codegen
checks pass. Final JavaScript: 221,408 bytes (68,300 gzip).

Production rollout remains gated on broader gameplay/real-device validation and
an equivalent-workload performance comparison. Priority automation and the
remaining production features below are not enabled by this prototype.
See [current coverage and run instructions](../UIPrototype/README.md).
Earlier checkpoints below are historical records.

### React trial checkpoint — 2026-10-26

- [x] Build an isolated React action/inspection prototype with simulated normal,
  attack, Forese-response, and opponent-priority states.
- [x] Keep state/commands separate from components, preserve unchanged card
  identity, and reject stale/repeated demo commands.
- [x] Verify inspection, explicit play/activation, reminder, waiting, and reset
  in Chromium, Firefox, and WebKit at 320px, 390px, and 1280px. Four store tests
  pass. Initial production JS: 202,689 bytes, 62,919 bytes gzipped.
- [x] Prepare and unit-test an unconnected command adapter: revision checks,
  duplicate submission protection, refusal feedback, and recovery locks.
- [x] Add an opt-in GrandArchiveSim server revision guard under the existing
  action write lock; verify malformed/stale revisions and unavailable locks.
- [x] Recover from unchanged-state refusals through a fresh post-response read,
  retaining the refusal reason and blocking uncertain resubmissions.
- [x] Verify the revision guard through authenticated HTTP requests (18 checks;
  isolated cache stand-in, real endpoint/authentication/engine). Production APCu
  and browser-to-server integration remain unverified.
- [ ] Adapt viewer-safe live state and existing server-validated commands to the
  React boundary; compare equivalent workloads before deciding on adoption.
- [x] Add a persistent mobile response dock with measured scroll clearance,
  safe-area padding, and scrollable preferences on short screens.
- [ ] Complete full zone/chooser parity and real-device usability checks.

Run instructions and limitations: [React UI trial](../UIPrototype/README.md).
The trial does not implement live Forese rules or automatic passing, and changes
no existing match renderer or RL execution path. Its speed is not evidence of a
performance improvement over the live simulator.

## Direction and scope

Build a mobile-first match interface informed by Material 3 Expressive: clear
action hierarchy, readable typography, comfortable touch targets, grouped
controls, and brief motion that explains game events. Preserve Grand Archive's
card art and ink/ivory/teal/gold identity. Adapt to available window space rather
than simply shrinking the desktop board or choosing a layout by device name.

The initial scope is in-game play. Lobby, deck selection, account features, and
cosmetics are later initiatives. Full Pantheon support remains on its separate
branch. Do not require a framework migration for this redesign.

Concept references from the design conversation:

- **Your action:** visible fields, compact hand, readable inspection, and a stable
  bottom action area with Pass and its options.
- **Attack response:** attacker/target summary and an explicit interruption of
  auto-pass before the response window is passed.
- **Forese response:** highlight the relevant ready card and explain the saved
  response rule; offer activation or a single pass.
- **Desktop adaptation:** use persistent side panels for inspection and response
  controls while leaving the battlefield visible.

The concepts use illustrative card art, values, and simplified zones. They are
interaction references, not a complete layout specification or verified rules
examples. Validate real card text, zone needs, and timing before implementation.

## Delivery phases

### Phase 0 — Audit and baseline

- [ ] Inventory every generated zone, action, chooser, overlay, and priority state
  used by GrandArchiveSim. Include materialization, memory selection, attacks,
  targeting, multi-selection, ordering, mandatory choices, and spectators.
- [ ] Capture representative real matches: sparse and crowded boards, long hands,
  stacked effects, multiple abilities, and long card names/rules.
- [ ] Record current prompt counts, interaction latency, and RL/headless speed
  using repeatable scenarios and fixed bot seeds/settings.
- [ ] Map maintained UI entry points and shared dependencies. Start with
  `GrandArchiveSim/Custom/GameLayout.php`, `GameLayoutMobile.php`, and
  `GameLayoutDevice.php`; change schemas/generator sources where needed, never
  generated output.

Exit: a state/interaction checklist and repeatable baseline exist; essential
information that must remain visible is identified before choosing breakpoints.

### Phase 1 — Design foundations and realistic prototype

- [ ] Define GA-scoped color, typography, spacing, shape, focus, and motion tokens.
  Avoid global style changes affecting other simulators.
- [ ] Prototype your action, opponent response, Forese reminder, card inspection,
  and target selection with real card data and representative board density.
- [ ] Keep both fields and current decision understandable on portrait phones;
  use expandable zones and hand inspection without hiding required targets.
- [ ] Establish compact, medium, and wide layouts based on content fit. Include
  phone landscape, browser resizing, safe areas, and mobile browser controls.
- [ ] Review visual hierarchy and touch behavior before implementing the full
  match UI. Validate contrast, text scaling, keyboard focus, and reduced motion.

Exit: the prototype handles the representative states at 320/390px phone widths,
phone landscape, tablet, and desktop without overlapping essential controls.

### Phase 2 — Adaptive match shell and card inspection

- [ ] Build an opt-in GA layout using existing game actions and zone bindings.
- [ ] Introduce stable player summaries, battlefield groups, accessible zone
  counts, and a hand that expands for inspection and selection.
- [ ] Make tapping a card inspect it; require an explicit legal action to play or
  activate. Preserve efficient keyboard/mouse interaction on desktop.
- [ ] Use a readable partial-height inspection sheet on phones and a side panel
  on wide layouts. Preserve selection and focus through game-state updates.
- [ ] Keep board position stable as cards enter, leave, rest, or change counters;
  show crowded-board overflow explicitly rather than silently clipping cards.

Exit: a complete manual match is playable in the new shell, including all Phase
0 interaction types. No priority automation is required for this phase.

### Phase 3 — Decisions, targeting, and combat clarity

- [ ] Add a persistent action area with clear states: your priority, opponent
  priority, choosing targets/costs, waiting, and resolving.
- [ ] Show attacker, defender, pending effects, and whose response is required.
  Offer an expanded ordered stack view when the compact summary is insufficient.
- [ ] Adapt single/multi-target, modal, number, split-assignment, and ordering
  interactions. Show legal candidates, selection progress, and confirmation;
  minimize inspection sheets during battlefield targeting.
- [ ] Distinguish cancelable selection from committed costs/actions. Revalidate
  actions against current server state and explain refusals without losing context.
- [ ] Use brief event motion without blocking inputs; support reduced motion and
  use labels/icons as well as color for legality and priority.

Exit: representative real card flows work end to end without covered targets,
accidental activation, ambiguous priority, or lost selections.

### Phase 4 — Conditional auto-pass and Forese stops

- [ ] Implement Stage 1 of the detailed priority plan below on top of the stable
  action area. Check all stops before each server-authorized automatic pass.
- [ ] Add the Forese preference through her card menu, with explicit interruption
  feedback and normal activation choices. Inspecting a card must not enable it.
- [ ] Keep active yield session-scoped; decide how saved preferences persist
  separately. Reconnect must not silently re-enable an active yield.
- [ ] Add timing and stale-request regression coverage, including multiple
  triggers and cancellation concurrent with a state update.

Exit: ordinary windows can advance automatically, every required stop retains
the legal response opportunity, and passing after an interruption stays manual.

### Phase 5 — Validation and staged rollout

- [ ] Verify Chromium, Firefox, and Safari; touch, mouse, and keyboard; narrow
  screens, landscape, enlarged text, reduced motion, and crowded boards.
- [ ] Test reconnects, slow responses, repeated taps, stale selections, spectator
  views, and state refreshes while sheets or menus are open.
- [ ] Compare prompts per turn, time spent making decisions, accidental/refused
  actions, and game duration against baseline. Do not infer better UX from faster
  games alone. Keep measurement free of hidden card information.
- [ ] Run relevant gameplay regressions and fixed-seed RL/headless benchmarks;
  investigate any repeatable slowdown before rollout.
- [ ] Ship opt-in first with a reliable return to the current layout, then expand
  only after gameplay parity and usability checks pass. If shared client assets
  change, follow the repository cache-busting workflow.

Exit: no unresolved gameplay-blocking UI regressions, no unexplained repeatable
RL slowdown, and a documented rollback path. Broader preferences below follow
this baseline rollout; lobby/deck-selection redesign is separately scoped.

## Implementation order

Phase 0 → Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5.
Keep layout, decision presentation, and priority automation in separate
reviewable changes so visual iteration does not obscure game-timing changes.
The first implementation milestone is a playable mobile shell with manual
priority and readable card inspection, not the full automation system.

Design references: [Material 3](https://m3.material.io/),
[adaptive layouts](https://developer.android.com/design/ui/mobile/guides/layout-and-content/adapt-layout).

## Conditional auto-pass and response stops

Status: planned, 2026-10-25. Implementation has not started.

Reduce repetitive priority prompts without removing meaningful opportunities to
respond. Preserve response windows through explicit stopping rules rather than
trying to predict lethal threats.

### Stage 1 — Priority controls and Forese response rule

- [ ] Add a menu beside **Pass**: pass once, auto-pass until the opponent acts,
  and auto-pass until my next turn. Define the first mode's expiration at the
  current turn boundary; the second may span turns until the player's next turn.
- [ ] Both auto-pass modes must stop on an opponent's card play, ability
  activation, or attack declaration, before passing its response window.
- [ ] Offer stops before combat, before the player's turn ends, and at each phase
  change. Stop at the last legal opportunity to act before the transition, using
  the game's actual phase names and timing rules.
- [ ] Add card-specific response preferences that override auto-pass. First rule:
  **Pause when I play a Harmony or Melody while Forese can activate.** Verify
  card text and engine timing during implementation; pause before resolution and
  highlight Forese with the normal activation controls.
- [ ] Never automatically activate a card, pay its costs, or choose its targets.
- [ ] Display the active auto-pass mode, its stopping conditions, and a persistent
  **Take control** button. Cancellation prevents future passes; it cannot undo
  an already processed pass.
- [ ] Explain each interruption and disable auto-pass afterward. Resuming requires
  an explicit player action; passing once must not silently resume it.
- [ ] Evaluate saved response rules first, then opponent-action and selected
  phase stops, then auto-pass. Evaluate before every automatic pass, including
  windows involving multiple effects or triggers. Mandatory choices remain
  interactive.

### Stage 2 — Broader preferences

- [ ] Extend card-specific rules to other useful response patterns. Consider a
  dismissible suggestion after a player manually makes a matching response.
- [ ] Offer a separate, optional **Pass when I have no legal responses** preference
  only after auditing legal-action detection for abilities, alternate costs, and
  all other response options. Preserve higher-priority stopping rules. Explain
  that immediate passing can reveal the absence of a legal response.
- [ ] Decide preference persistence and defaults explicitly; do not carry an
  active yield into another game or silently enable it on reconnect.

### Acceptance and measurement

- [ ] Empty windows advance when no stop matches; opponent actions interrupt
  before resolution, including potentially lethal actions.
- [ ] Selected phase stops preserve the opportunity to act before transition.
- [ ] Forese's rule pauses for an eligible own Harmony or Melody, including while
  yielding; ineligible or unrelated events do not trigger that rule.
- [ ] Multiple effects/triggers, state changes, cancellation, reconnects, and
  stale client requests cannot bypass a required stop or cause duplicate passes.
- [ ] Verify controls in Chromium, Firefox, and Safari, including mobile layout
  and keyboard access.
- [ ] Compare manual prompts, automatic passes, stop reasons, and game duration
  before and after rollout. Check RL/headless performance for regressions;
  interactive preferences must not add unnecessary work to bot decisions.

Out of scope: automatic card activation, lethal prediction as a safeguard, and
unconditional yielding through new opponent actions.

### Read-only projection checkpoint

- Added authenticated `UIState.php`, read under the game lock with no-store responses.
- Allowlisted card identities by viewer and zone; concealed banish and internal engine data omitted.
- 82 projection checks and 24 HTTP checks pass (HTTP uses the test cache stand-in).
- Still pending: React live rendering, legal commands, temporary reveals, fuller card presentation, and measured performance comparison. The preview remains simulated.


### Read-only React connection checkpoint

- Added an opt-in live viewer alongside the simulated demo, served from the same PHP host as the match.
- Displays server-filtered card names, zone counts, ready/rested status, damage and turn information. No game actions are enabled.
- Credentials use POST and remain in memory; disconnect/unmount cancels polling. Failed reads clear private cards and require reconnection. Unchanged revisions skip React store updates.
- Validation: 16 JS tests, 82 projection checks and 26 HTTP checks; browser connection coverage uses fixture responses. Existing CLI runtime warnings remain.
- Next: project legal commands and supported decisions, then benchmark both renderers with the same match updates. Card art and temporary-reveal policies remain pending.


### Browser-to-PHP verification checkpoint

- Real built React viewer verified against the PHP endpoint in Chromium, Firefox and WebKit using a disposable match, with no browser response mocks.
- Both seats, own hand rendering, hidden opponent hands, polling revision changes, disconnect, denied access, and absence of browser credential storage pass. The saved fixture is unchanged after the browser checks.
- Repeat with `php DevTools/test-ga-ui-revision-http.php --browser` after building the preview (28 HTTP/harness checks plus three browser scenarios). The 16 JavaScript tests also pass.
- Production APCu/deployment verification remains separate. The viewer is still read-only; legal actions and decision presentation are the next implementation milestone.

### Decision context checkpoint

- Live viewer now describes response opportunities, card choices, waiting and spectator status. It reads only the active viewer's first live decision, using fixed messages; parameters, tooltips and future continuations remain private.
- Unknown decision types direct the player to the existing match screen. This is informational, not a declaration that passing or any other action is legal.
- Projection coverage expanded to 111 checks, including removed decisions, unknown types and private decision data. Real-server browser checks now require the attention message to be visible on mobile.
- Next: connect server-authorized commands to the revision-protected action adapter. Passing priority, declining a choice and requesting the end of main phase need distinct legality checks and labels.

### First live action checkpoint

- Optional card choices now expose a decline command to the active seat. Fast response windows use “Pass priority”; other optional choices use “Decline optional choice”. Mandatory decisions and main-phase passing remain unavailable.
- The POST-only action endpoint fixes the engine command and checks availability and revision under the same write lock as execution. Credentials remain out of URLs.
- The React action adapter blocks repeated submissions and uncertain retries; controls wait for the visible board revision to catch up after submission.
- Validation includes command visibility, real endpoint refusals and duplicate protection, plus real pass-button clicks and refreshed boards in Chromium, Firefox and WebKit. Existing runtime override warnings remain.
- Next: expose safe choice options and response context before adding card play and activation controls.


### Visible choice preview checkpoint

The React viewer now displays complete ordinary single-card choice pools using the board's visibility policy, including player and zone labels. This is display-only; selection still happens in the existing match screen. Unsupported selectors or any concealed candidate suppress the entire pool. Fast-action context and selection submission remain next steps. Added privacy/perspective coverage and responsive browser checks for the panel.


### Single-card selection checkpoint

Ordinary visible card-choice pools now offer live Choose buttons. Command membership and revision are rechecked against the locked state before passing the selected card to the existing engine validator. Pending submissions reuse the single-action guard and refresh flow. Hidden or unsupported pools stay in the existing match screen. Next: safe effect/response context and compound ability selections.

### Public pending-effects checkpoint

- Added a pending-effects panel to the live viewer with public card names, controllers and activation/trigger categories in newest-first order.
- Kept captured state, copied identities, source identifiers and raw trigger strings out of the projection.
- Explicitly distinguish an empty stack from absence of combat or pending decisions.
- Next: safe target and ability descriptions, followed by compound fast-action choices. The existing match screen remains necessary for complete context.

### Printed effect reference checkpoint

Added expandable static printed card text to public pending stack entries, visible to players and spectators. Text is rendered without HTML execution and labeled as reference rather than the exact pending effect. Runtime targets, copied abilities and captured decision data remain excluded. Next: establish an explicit public runtime effect-description contract before presenting exact ability/target details or compound fast-action choices.

### Public trigger labels checkpoint

Pending effects now identify recognized trigger events (including On Enter, On Attack,
On Hit and On Kill) using a fixed server allowlist matched to the effect resolver.
Both players and spectators receive the same labels. Unknown tags and compound
DTR events retain the generic category; captured decision data and copied identities
remain excluded. A trigger label describes the event, not the exact ability or targets.

Validation: 278 projection checks and 16 JavaScript tests pass. Existing generated
ability override warnings remain. Production JavaScript is 212,367 bytes (65,769 gzip).
Exact ability/target context and compound fast-action choices remain unfinished.


### Classic improvements — October 28, 2026

Implemented the first pass of the four Classic priorities in authored custom layouts:

- Readability: mobile fields retain 88–112px card cells and scroll when crowded. The hand uses separated 96px-high art instead of overlapping cards. Scroll regions support keyboard focus.
- Board space: compact resource bands, more room for the hand, and scrolling for short landscape viewports so controls remain reachable.
- Action clarity: existing engine-provided legal choices receive visible outlines; attacking and targeted cards have distinct solid/dashed outlines. Keyboard focus and reduced-motion preferences are supported. No priority or legality rules changed.
- Rendering: removed the 400ms field layout refresh, skip unchanged phase/hand-summary writes, and coalesce desktop field updates into one animation frame.

Validation: crowded 18-card fields/hands in Chromium, Firefox, and WebKit at 320px, 390px, and 740px landscape. At 390px the original field cells were 54px and neither field nor hand scrolled; updated cells were 112px and both scrolled. Over a 1.25-second idle sample, field attribute mutations dropped from 6 to 0. This measures layout overhead, not end-to-end game speed. PHP syntax checks pass. Full live-game and physical-device evaluation remains useful.

Repeat layout checks with `PLAYWRIGHT_MODULE=<path-to-playwright/index.mjs> node DevTools/ga-classic-layout-check.mjs` from the repository root.
