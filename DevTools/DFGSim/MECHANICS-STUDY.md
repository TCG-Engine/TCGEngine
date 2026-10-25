# DFGSim mechanics study

Study date: 2026-10-23. Baseline: local rules v44 and the current 36-card catalog.

This is a design inventory and proposal study, not a balance experiment. Existing behavior was checked against the catalog compiler and runtime. Proposed rules and numbers below are starting hypotheses; none have been implemented or playtested for this study.

Related exploration: [Small differences, different fights — family gallery](family-study/index.html). The original pool was refined from 240 entries to 143, then merged with all current-card mechanics. The revision-3 gallery has 65 families and 257 entries: 221 proposals/reference placements and 36 exact current-card baselines. A [coverage index](family-study/COVERAGE.md) traces every original main clause, objective, spend, burn mode, and printed-route set. The game is unchanged.

## Design direction

DFG's strongest foundation is predicting a hidden commitment while managing public, permanently shrinking options. A main card chooses both an exchange and a future route. New mechanics should strengthen that connection: **what do I think you will do, what am I willing to risk, and what does this leave us able to do next?**

The game already contains three different resource pressures:

- Life: immediate survival.
- Cards: permanent expenditure of future options, including the danger of an empty hand.
- Advantage: renewable, objective-earned, colored resources with specific spending opportunities.

It also already has several forms of tempo: carried attack, missed-attack recovery, next-round burn denial, replacement cancels, and control of future hands. These are useful foundations for expansion.

My first prototypes would be **move tags**, **short combo conditions**, and **telegraphed commitments**. Stamina is the strongest candidate for a larger resource experiment. Break bars and burst bars deserve separate variants because they can reshape the whole damage economy.

## 1. What is already implemented

Runtime catalog audit: 36 cards, six per printed color; 18 objective cards; 11 advantage spenders; 33 cards with bonus text; 26 with reaction text; six cards with their own printed color among their destinations. Counts describe available card modes, not their frequency of use.

### Core decisions and information

| Mechanic | Current behavior | Decision it creates |
|---|---|---|
| Six active hands | Only cards currently located in the active color hand can be played or burned | Plan access to future tools |
| Public inventory | Remaining/burned cards and hand locations are observable | Read a shrinking set of possibilities |
| Hidden simultaneous plans | Main, optional bonus, and advantage spending are committed before joint reveal | Predict commitment rather than respond with perfect information |
| Reusable main | Normally returns after resolution; reserved against your own burns | Preserve an important option |
| Permanent bonus burn | At most one same-hand bonus, committed with the main | Buy strength before seeing the opponent's plan |
| Permanent reaction burns | Alternate one same-hand burn or pass; burns reset the consecutive-pass count | Pay for a known result, then allow a response |
| Two-pass closure | Two consecutive passes resolve damage | Decide whether further escalation is worthwhile |
| Alternating reaction priority | First reaction player alternates by round | A predictable timing asymmetry, not attack speed |
| Hidden next-hand choices | Choose from legal routes; reveal after both choices | Predict the next matchup |
| Multiple defeat paths | Zero life, no remaining cards, or entering an empty active hand; simultaneous losses draw | Protect options as well as health |

### Exchange math and reads

| Mechanic | Existing example | Important distinction |
|---|---|---|
| Simultaneous attack versus block | Both sides deal `max(0, attack - effective opposing block)` | No first strike or hit interruption |
| General block penetration | Clinch Throw, Crushing Blow; Feint on its read | Changes only the attacker's effective opposing block |
| Bonus-source penetration | Heavy Feint ignores block granted by opposing bonus burns | Printed, main, advantage, and reaction block remain |
| Reaction-source penetration | Quick Jab without an own bonus ignores opposing reaction block | Late defense can still improve the opponent's own outcome elsewhere |
| High/low printed attack read | Counter Throw, Off-Balance Sweep, Read the Strike | Tests printed stats, not boosted attack |
| High/low printed block read | Feint, Outside Step, Driving Kick | Counters a property of the opposing main |
| Exact-value reads | Mirror Step independently reads attack 3 and block 3 | Narrower conditions can overlap |
| Opposing bonus-commitment read | Firm Counter, Watchful Guard, Bait and Slip | Predicted before reveal; later reactions do not satisfy it |
| Defensive-bonus read | Grip Break | Checks the resolved bonus branch actually granting its owner block |
| Opposing advantage-spend read | Heavy Feint | Counts spending, not holding or earning; multiple tokens trigger once |
| Carried-pressure read | Grip Break | Tests attack carried into this round, not buffs earned during it |
| Conservation reward | Quick Jab, Charged Fist, Measured Advance, Iron Guard | No bonus and no burns at all are different conditions |
| Gifts as costs | Rushdown, Driving Tackle, Haymaker, Trade Blows bonuses | Your boost also helps the opponent |
| Direct retaliation | Guarded Strike | Zero incoming combat damage triggers 2 direct damage; no recursion |
| Recovery | Driving Palm | On-hit heal 1, capped at 40, after knockout determination |

### Objectives, advantage, and future state

| Mechanic | Existing example / rule | Tactical role |
|---|---|---|
| Final-outcome objectives | Deal damage, clean counter, guard high attack, efficient hit | Reward execution of a read |
| Exact damage taken | Glancing Step: take exactly 1 | Sometimes taking a little damage is desirable |
| Damage threshold | Haymaker: 4+ without a bonus | Incentivizes a large unassisted hit |
| Delayed objective | Rooted Guard: deal 4+ next round | Public setup and a visible future threat |
| Colored advantage | Public, persistent across hands, one held token per color | Route toward ways to earn and spend |
| Hidden token spend | Chosen with main, consumed on reveal; new rewards arrive later | Risk a resource before knowing success |
| Specialist spending | Rushdown, Driving Tackle, Counterpunch, etc. | Different currencies have different tactical roles |
| Two-token cashout | Combination Strike accepts up to two different colors | Gives a larger payoff for resource assembly |
| One-round attack carry | Rushdown +2 on spend-and-hit; Outside Step +1 on hit; Glancing Step +2 on taking 1; Iron Guard +1 on taking none | Tempo across hand changes; largest amount applies rather than sums |
| Pressure suppression/reset | Brace's Green spend | Stops opposing incoming carry and both sides' next-round attack gains |
| Recovery penalty | Haymaker dealing no combat damage gives -2 final block next round | Failure creates a punish window |
| Burn lockout | Clinch Throw's Orange spend on hit | Opponent still plays a main and can spend advantage next round |
| Deny new advantage | Counterpunch's Blue spend on hit | Prevents current-round awards without removing held tokens |
| Remove held advantage | Mirror Step's Blue spend | Separate hit and no-damage removals; before new awards |
| Permanently destroy opposing main | Charged Fist's Purple spend on hit | Denies a reusable tool after it completes the exchange |

### Routing, relocation, and adaptation

| Mechanic | Existing example | What it changes |
|---|---|---|
| Printed routes | Two or three destinations per card; exactly one printed self-route per color | A main also selects future hand access |
| Extra stay permission | Forearm Check's Green spend | Adds Green even though it is not a printed destination |
| Chosen route cut | Driving Tackle's Orange spend on hit | Removes one legal opposing destination, never the last |
| Forced legal destination | Driving Kick's Yellow spend on hit | Chooses the opposing next hand after cuts; can force an empty printed destination |
| Persistent card relocation | Bait and Slip against a bonus commitment | Moves a surviving opposing main into another hand, independent of hitting |
| Post-reveal main cancel | Darting Strike's Yellow spend | Permanently burn Darting to replace it with another available Yellow main before reactions |

**Catalog distinction:** the compiler still recognizes route-expansion, flexible color-specific spending, and other vocabulary from earlier drafts. The current v44 catalog has no card using the `safe_switch`, `hit_switch`, `free_switch`, or `clean_force_move` special effects, and Combination Strike currently uses its one-/two-token cashout rather than color-specific benefits. Those supported code paths are extension points, not additional live card mechanics. The historical README contains superseded card text and names.

Darting's replacement retains the committed bonus and carried modifiers, receives its normal main abilities/objective/routes, and cannot spend advantage. Opposing printed-stat reads recalculate. Eligible replacement decisions remain hidden until both finish. This is already a limited, expensive way to adapt after reveal.

### What is absent as a general system

There is no stamina pool, segmented HP gate, burst-damage barrier, printed speed stat, persistent fighter stance, move-type taxonomy, range track, positional arena, cooldown system, or general stagger gauge. Color style names already exist (Pressure, Grappling, Footwork, Defense, Counterplay, Power), but they are not extra mechanical keywords on individual moves.

## 2. Proposed directions

### A. Stamina: a renewable tactical budget

**What it adds:** a short-term cost between free main reuse and permanent card loss. Expensive techniques become periodically available without deleting a card.

| Model | Example prototype | Main tradeoff |
|---|---|---|
| Passive regeneration | Start/max 4; regain 1 at the start of each round after the first; selected enhanced abilities cost 2 | Predictable availability; risks automatic spend/save cycles |
| Card-generated only | Selected recovery mains generate 2 after damage; no passive gain | Preparation is an actionable read, but bad routes can starve a player |
| Hybrid | Max 4, regain 1; designated recovery cards gain 1 extra | Provides a floor and active acceleration, at the cost of more interactions |
| Exertion alternative | A move adds fatigue; fatigue decays 1 per round | Similar pacing, but tracks debt rather than stored spending power |

First test: passive regeneration with **optional enhancements on a small subset of mains**. Every player must retain a legal zero-cost main choice. Keep ordinary reactions paid by permanent burns initially, so stamina does not immediately replace the attrition game.

Commit stamina spending face down with the plan, validate against the opening balance, debit on joint reveal, and award card-generated stamina after resolution for future rounds. Regenerate by game round, never elapsed wall-clock time. A failed hit still pays its committed cost. The prototype must separately define whether an enhancement survives a Darting replacement; the conservative starting rule is that it does not transfer and is not refunded.

**Read:** spend now because the opponent is recovering, or conserve because they are holding an anti-spend counter. **Risk:** stamina and advantage become two currencies buying the same +stats. Keep stamina about exertion/recovery and advantage about successful reads/specialist payoffs. Compare identical boards with and without the meter to see whether choices actually change.

### B. Break bars: checkpoints on the life track

Interpretation used here: **a hit/exchange can reach a life threshold but cannot damage past it until a later exchange**. This is a proposed clarification of the idea, not an existing rule.

Example: a fighter has 23 life and an intact checkpoint at 20. Incoming damage is 8. They lose 3 life, stop at 20, and consume that checkpoint. The next exchange can go below 20. A fighter exactly at a consumed checkpoint is not protected again. Prototype thresholds could be 30/20/10 on the existing 40 life, but start with one at 20 to isolate the effect.

Use total incoming damage for the exchange, including Guarded Strike retaliation, when enforcing the checkpoint. Otherwise direct damage could accidentally consume the gate and then bypass it in the same exchange. Both fighters' results must be calculated before either is applied.

**Read:** the defender can anticipate a protected exchange; the attacker may save a burn and seek routing, denial, or setup instead of overkill. **Risk:** much of the tension disappears if conserving is always correct near a gate. Large attacks and expensive cashouts lose value precisely at those thresholds; healing could prolong the game unless gates are permanently consumed.

Important interaction: distinguish *damage attempted* from *life actually lost*. Starting proposal: damage objectives and on-hit effects use actual post-gate damage, with combat/direct subamounts recorded consistently. Add explicit fixtures for a fighter already at the gate, retaliation, knockout, recovery, and simultaneous gate breaks.

Best use: a segmented duel or boss variant that deliberately changes match pacing. Do not assume it improves the core duel without testing.

### C. Burst bars: require a concentrated damage commitment

Interpretation used here: **a barrier must be met in one exchange; insufficient damage does not accumulate**. This differs from an ordinary shield that can be chipped down.

Example prototype: a one-time burst barrier of 4. Calculate damage after normal block and penetration, aggregating that exchange's combat and direct damage. Less than 4 deals no life damage and leaves the barrier intact. At least 4 consumes the barrier and deals only the overflow to life. Exactly 4 breaks it with zero life damage. It does not refresh automatically.

Alternative worth testing separately: once the threshold is met, the entire damage amount reaches life. That is substantially more explosive than overflow-only. Also distinct: a persistent armor rule that subtracts 4 every exchange, and a stagger gauge that accumulates chip across rounds.

**Read:** will the attacker commit enough resources, or bait the defender into overreacting to an apparent burst? **Risk:** unconditional full refresh can produce unwinnable late boards when inventories no longer support sufficient damage. A first prototype should be one-use, modest, and acquired at a meaningful opportunity cost.

Define `barrier broken` as its own trigger. Under the overflow proposal, breaking at exactly 4 is not a normal life-damage hit and does not award ordinary damage objectives. If that makes successful breaks too unrewarding, add an explicit break reward rather than silently redefining every hit trigger.

Break bars cap the value of oversized damage; burst bars reward reaching a minimum damage package. They pull incentives in opposite directions. Test them separately before considering a shared match mode.

### D. Speed and tempo: decide which advantage is intended

| Interpretation | Candidate rule | Fit with current DFG |
|---|---|---|
| Reaction initiative | Faster revealed main chooses who reacts first; equal speed follows alternating priority | Preserves simultaneous damage; initiative value needs measurement |
| Timing read | A Quick move gains block against a Heavy move; Heavy punishes Recovering | Creates new reads without changing resolution order |
| Tempo ownership | A qualifying clean hit grants one public tempo token for next round | Overlaps carried attack and advantage; give it a distinctive spend |
| First strike | Faster damage resolves first and can prevent a slower attack on KO | Fundamental combat redesign; high risk of dominant fast offense |
| Recovery windows | Strong moves expose a public next-round vulnerability | Already partially present on Haymaker; natural extension |

First prototype: **Quick / Committed / Recovering tags and narrow conditional effects**, keeping simultaneous damage. Avoid assigning speed values to all 36 cards before demonstrating that a small timing vocabulary improves decisions.

Reaction priority is not automatically beneficial: being required to act first may reveal a burn before the opponent needs to decide. Measure the value before making speed a priced stat. If speed changes which attacks execute, revisit every simultaneous objective, retaliation, trade, and knockout rule.

### E. Stance/style keywords: more useful things to read

There are three different designs here:

1. **Move tags:** each card has intrinsic descriptors such as Strike, Throw, Guard, Evade. Opposing cards can read those tags.
2. **Persistent stance:** the fighter publicly enters Offensive, Defensive, or Mobile stance; selected cards gain or lose properties there.
3. **Sequence style:** using different kinds of moves across rounds unlocks a payoff, even across color changes.

Start with move tags. Tags should cross color boundaries so they describe a new axis rather than simply restating the current hand. Keep Strike/Throw as contact-type tags and Guard/Evade as behavior tags; a card can have one from each axis. An attack-plus-defense move need not fit one exclusive bucket.

Example read: "If the opposing main has Throw, gain +2 block." A strong Throw also needs meaningful upside or a way to punish the anti-Throw read. Avoid making all Throws automatically defeat all Guards; DFG already has nuanced block penetration, and a global immunity chart could erase it.

Persistent stances are a larger second experiment. Use one public stance per fighter; enter or change it through a main's resolved effect, effective next round. Keep the entry cost and its counterplay visible. Giving every color a mandatory stance would couple two systems that currently do different jobs.

**Risk:** a handful of obvious tag counters turns hidden planning into a solved matchup. Each reachable hand should retain meaningful alternatives, and relocation must change hand access without changing a card's intrinsic tags.

### F. Status effects, damage over time, and cleansing

Statuses add a different kind of pressure: a successful exchange can leave a problem the opponent must manage on later rounds. They can support attrition, setup, recovery, and deckbuilding around answers. Unlike immediate damage, their value depends on how much time and freedom the victim has to respond.

DFG already has narrow status-like effects: Haymaker's next-round block penalty, Clinch Throw's next-round burn lock, and carried attack bonuses. It does not have a general status inventory, damage-over-time timing, or cleansing system. Existing effects should not automatically become cleanseable just because they resemble a status; that would change those cards' payoffs.

| Candidate | Example starting rule | Response / design concern |
|---|---|---|
| Bleed | Deal 1 status damage after each of the next two exchanges | Cleanse, race, or accept the damage; reliable delayed damage needs an application cost |
| Burn | Next round, take 1 status damage if you use any bonus or reaction burn | Conserve or cleanse; word this distinctly from permanently burning a card |
| Weakened | -1 attack next round | Defend, seek a utility payoff, or cleanse; avoid making all attacks pointless |
| Exposed | -1 block next round | Creates a public punish opportunity; overlaps Haymaker recovery |
| Marked | The next qualifying Strike against you gets +2 attack; expires after two rounds | Choose a defense for the telegraphed threat; requires move tags |
| Exhausted | Your next optional stamina enhancement costs 1 extra; expires after two rounds | Save stamina or use unenhanced moves; requires a stamina system |
| Lingering injury | A small penalty persists until cleansed or a demanding recovery condition is met | Strong build identity, but can become a mandatory-cleanse tax |

These are alternatives to prototype, not a proposal to add seven conditions at once. Start with Bleed and one short stat penalty. Leave indefinite injuries and hard action denial for later experiments.

**Starting timing contract:** statuses are public and follow the fighter across hands. A status applied by this exchange becomes active next round and cannot tick immediately. A two-round Bleed applied in round 3 ticks after the exchanges in rounds 4 and 5, then expires. At each exchange, snapshot existing statuses, resolve combat and direct retaliation together, record ordinary damage-based triggers/objectives, then resolve eligible status ticks simultaneously. Determine defeat before recovery; status damage can cause a simultaneous knockout. Finalize surviving recovery and post-exchange choices under an explicitly documented order.

Status damage bypasses block but is a separate damage source. For the first experiment it does not satisfy deal-damage objectives, negate no-exchange-damage objectives, trigger retaliation, or activate on-hit effects. The UI must make that distinction clear. Do not implement it as extra generic damage without auditing every existing trigger. If combined with break/burst bars later, specify whether status damage respects them; that interaction is not settled by the proposed block bypass.

**Starting stacking contract:** one instance of each named condition per fighter. Reapplication refreshes duration to the larger of the current remaining duration and the newly applied duration; it does not add potency or durations. Bleed therefore deals at most 1 per eligible round in this prototype. Different named penalties can coexist, but keep the available set small and floor attack/block at zero. Track source and remaining duration explicitly. A damage-based status does not tick after combat has already ended the match.

**Cleansing should create a decision, not merely occupy a required deck slot.** Candidate forms:

- A modest attack/defense main that removes one named condition on reveal, before its combat modifiers apply. If several are present, commit the chosen public condition with the main; two simultaneous cleanses resolve from the same opening state.
- An objective-based cleanse: remove Bleed after taking zero exchange damage, but only after that round's scheduled tick. This rewards execution rather than drawing or including a dedicated answer.
- A resource-based cleanse on selected mains: spend stamina or advantage for removal, competing with another payoff. It must be a supported card ability rather than an unrestricted new use of every token.
- A recovery route: a particular move removes one condition and opens defensive destinations, trading immediate pressure for future flexibility.

A reveal-time cleanse removes an existing condition and its future ticks; it does not prevent a new condition applied by the current exchange. Prevention or immunity would be a separate, stronger ability. A cleanse attached to a main cannot be gained retroactively through Darting replacement unless its timing rule explicitly supports that case. Delaying status activation preserves at least one planning opportunity before the first tick; putting all cleanse actions after the first tick would change that promise.

For deckbuilding, distinguish broad, weaker cleanses from efficient narrow answers. Put useful non-cleanse modes on answer cards, and provide natural expiry or an accessible recovery condition so a player without a dedicated cleanse still has a credible plan. Do not force every color to carry an identical mandatory cleanse. Status-centric builds also need playable plans against an opponent who can remove their primary condition.

**Experiment:** compare a small Bleed package against the same damage budget delivered immediately, then add a cleanse variant. Measure actual ticks before removal/expiry/defeat, damage prevented per cleanse, application and answer opportunity costs, match length, repeated applications, and win rates by access to answers. Update bot forecasts to value delayed ticks and optional removal; existing evaluation cannot be assumed to understand them.

## 3. Other mechanic ideas

These are original candidate designs for DFG, not claims about implemented rules. Priority is a qualitative judgment about fit and scope.

| Idea | Concrete prototype | Read or tradeoff | Priority / main risk |
|---|---|---|---|
| Short combo routes | A main gets +1 attack if your previous revealed main had a different specified tag; only one round of history | Predict a tempting continuation or deliberately break the sequence | High; avoid a single prescribed best combo |
| Telegraph / charge | A setup move publicly threatens a boosted Heavy next round; the payoff still requires choosing a qualifying main | Opponent prepares a counter, attacker can abandon the plan | High; must distinguish it from Rooted Guard's existing delayed objective |
| Temporary exhaustion | An enhanced main cannot be selected as main next round, then refreshes automatically | Strong reuse now versus narrower future options | High; requires explicit rules for bonus/reaction use and otherwise-empty hands |
| Prediction call | A main commits a hidden prediction of the opposing move tag; success earns advantage after damage | Wager on the read rather than purchase guaranteed stats | High after tags; extra chooser may slow commitment |
| Commitment punishment | Gain a payoff if the opposing plan both spends advantage and commits a bonus | Punish stacking resources rather than just high printed stats | High; new conjunction of existing reads, little new state |
| Counterhit | Gain +2 attack against a Committed-tag main while you committed no bonus | Choose a clean punish over matching an expensive investment | High after timing tags; avoid duplicating existing high-attack reads |
| Guard strain / stagger | Taking zero damage while blocking an attack adds 1 strain; at 3, suffer -2 block next round and reset; decay 1 in rounds without strain gain | Pressure a defense without needing immediate life damage | Medium; can punish legitimate defense too broadly |
| Brace versus evade | A Guard effect resists raw power; an Evade effect rewards avoiding a specific move category but loses to another | Defense becomes a read, not a single block total | Medium; prevent absolute damage immunity |
| Push/pull range | Public Close/Far state; selected moves change next-round range and have range-dependent effects | Fight for a favorable future menu of attacks | Medium/high scope; hand routes already express positional access |
| Ring pressure | Track one shared position toward either edge; selected hits push, retreat resets at a cost; edge pressure restricts one route | Choose life damage versus spatial control | Later; do not add instant ring-out until positional control is understood |
| Feint into recovery | A low-damage main gains a future benefit if the opponent spends a reaction | Induce waste after reveal rather than merely read a precommitted bonus | Medium; unlimited bonus for every reaction would snowball |
| Reaction commitment limits | A special main allows at most one opposing reaction burn this round | Predict and constrain a reaction-heavy defense | Medium; less absolute than Clinch's existing next-round lock, but reduces responses |
| Recoverable damage | A portion of damage becomes public recoverable life; a successful recovery main restores a capped amount, while an opposing hit removes the opportunity | Pursue the hurt opponent or predict a recovery attempt | Later; adds bookkeeping and risks long defensive loops |
| Paid recycle | Once per match, a recovery action restores one burned non-main card, replacing that round's offensive payoff | Restore access versus concede tempo | Later; permanent burns are central and empty-hand defeat timing is delicate |
| Wounds | A costly precise hit marks one specific vulnerability for two rounds, such as an extra cost for an enhanced Throw | Exploit a temporary weakness versus take immediate damage | Later; can create oppressive access denial |
| Once-per-match escape | Spend an escape marker to cancel a particular forced-route effect while accepting a limited penalty | Keep insurance or risk saving it too long | Medium; directly softens an existing control mechanic |
| Fighter identities | Choose a character with one narrow passive and one restricted specialist payoff, using the shared cards | Asymmetric priorities without separate full decks | Later; multiplies matchups and complicates balance attribution |
| Shared public objective | Reveal a fixed scenario goal such as "earn a reward for a clean hit without burning" for both players | Compete over a known short-term incentive | Medium; use deterministic schedules if no randomness is desired |

The most promising additional idea is **temporary exhaustion**: permanent burns govern long-term attrition, while exhaustion could govern short-term reuse. The most promising way to add reads with little extra state is **cross-color move tags plus short combo conditions**. The most promising pacing mechanic is **telegraphing**, because the opponent can see a future threat and decide how seriously to respect it.

## 4. How these systems interact

| Combination | Opportunity | Collision to avoid |
|---|---|---|
| Tags + short combos | More sequence reads using a small shared vocabulary | Guaranteed optimal rotations |
| Tags + prediction calls | Explicit reads beyond printed attack/block | Redundant card text and repeated chooser clicks |
| Stamina + exhaustion | Separate ability budget from move availability | Too many costs attached to every move |
| Stamina + advantage | Predictable exertion alongside earned specialist rewards | Two currencies buying interchangeable buffs |
| Stamina + recovery windows | Strong moves create a visible chance to recharge | Forced rest turns with no interesting alternative |
| Break bar + burst cashouts | Protected setup exchange could prepare a later burst | Oversized cashouts wasted against a checkpoint |
| Burst barrier + permanent burns | A deliberate all-in attack can become meaningful | An exhausted inventory unable to penetrate at all |
| Stance + range + hand routing | Rich positional identity | Three overlapping ways to express access and position |
| Stagger + burn lock + forced hand | Several forms of control | Repeated turns without credible agency |
| Temporary exhaustion + empty-hand defeat | New access pressure | Accidental losses caused solely by temporary availability rules |
| Statuses + cleansing | New attrition and recovery decisions | Mandatory answer slots or cleanses that erase an entire archetype |
| Damage over time + damage gates | A way to pressure a protected defender | Undefined bypass rules or extra ticks accidentally crossing a checkpoint |

Keep no more than one new persistent resource or positional system in an initial experiment. Extra depth is useful only if players can still understand why the exchange resolved as it did.

## 5. Suggested experiments

### Experiment 0: establish a current baseline

Freeze the actual v44 code and catalog. Existing checked-in pair and game result directories are v37; historical README studies also predate later revisions. Do not treat those results as current v44 balance measurements.

Record code/data hashes and run a fresh baseline before comparing a prototype. Cover all 36 ordered opening-color pairs, both seat assignments, both reaction priorities, and multiple bot profiles. Separate decisive wins, draws, and externally capped simulations. The runtime itself has no automatic round cap.

### Experiment 1: tags and sequence reads

Add move metadata to a balanced sample spanning all colors, with a few conditional reads. Test tags alone first; add a small number of one-round combo conditions in a separate variant. Preserve stats unless a change is essential to making a read work, and report all changes.

Question: do players have more plausible reasons to choose different mains, or does the new label simply identify the best counter?

### Experiment 2: a stamina economy

Compare passive regeneration with a card-generated variant in isolation. Use the same enhanced techniques and otherwise unchanged cards where possible. Track spending, turns at the cap, turns unable to afford an enhancement, recovery usage, and whether conserving becomes automatic. Keep card-based recovery an active combat choice.

Question: does stamina create useful timing decisions beyond advantage and permanent burns?

### Experiment 3: telegraphing or exhaustion

Prototype each independently before combining them. Telegraphs should create at least two credible continuations. Exhaustion should disable main reuse for one next round; retain a clear fallback main so temporary unavailability does not silently introduce a new defeat rule.

Question: do opponents adapt, and can the initiating player benefit by breaking the apparent pattern?

### Experiment 4: separate damage-mode variants

Compare one HP checkpoint at 20 against one one-use burst barrier of 4, each in a separate rules variant. Inspect low-resource endgames and closed defensive loops before broad self-play. Do not apply both to the baseline at once.

Question: do these make resource commitment more interesting, and can every reachable surviving player still threaten progress?

### What to measure

- Match length, draws, capped runs, and repeated no-progress states.
- Main/bonus/reaction usage divided by opportunities where each action was legal.
- Permanent burns per match and how often players lose to empty hands versus life.
- Objective success and advantage spend/earn rates, including denied and wasted rewards.
- How often the proposed mechanic actually changes the legal choices or outcome.
- Opening-color, seat, and reaction-priority sensitivity.
- Decision time and number of extra interactions in human playtests.
- Whether players can explain the opponent's threat and name at least one plausible alternative.

Use paired starting scenarios and held-out seeds; report uncertainty and sample counts. Selected-card win rates are policy-dependent associations, not isolated card strength. Existing bots need rules-aware candidate generation and evaluation before their results can judge a new mechanic. Deterministic resolution does not imply that hidden simultaneous choices have a single best fixed response; preserve mixed strategies where appropriate.

## 6. Implementation boundaries for a later prototype

DFGSim currently uses a standalone PHP runtime; there is no `Schemas/DFGSim/GameSchema.txt`. This study changes no runtime rules and requires no generation or remote card saves.

- `DFGSim/data/cards.json`: current normalized card data; new metadata would need local support and a plan for preserving it through future exports.
- `DFGSim/Custom/CardCatalog.php`: restricted effect compiler, objective/spend parsing, and metadata validation. Unsupported prose fails explicitly.
- `DFGSim/Custom/GameLogic.php`: authoritative state, commitment validation, timing, simultaneous math, objectives, routes, and public observations.
- `DFGSim/Bot/HeuristicBot.php` and `ProBot.php`: legal plans, evaluation, rollout/search handling. A new rule must reach both live execution and forecasts.
- `DFGSim/app.js`, card renderer, and CSS: visible state, planning controls, and clear explanations of costs/triggers.
- `DevTools/DFGSim/`: baseline and variant experiments, independent replay checks, and reports.

For any stateful rule, specify visibility, commitment/payment timing, refresh/expiry, replacement behavior, and simultaneous resolution before implementing it. Extend information-boundary checks so bots and observations cannot see pending spends, predictions, replacements, or destinations early. Future shared-engine integration should introduce a schema and use codegen.

## 7. Card-by-card index of current main mechanics

Burn modes are cataloged separately in the source; this index lists main effects, objectives, and spends so every current card is represented.

| Color | Card | Current distinguishing mechanics |
|---|---|---|
| Red | Quick Jab | No-bonus attack boost and reaction-block penetration |
| Red | Rushdown | Red spend: on-hit +2 attack next round |
| Red | Guarded Strike | Zero-combat-damage direct retaliation; clean-counter Green objective |
| Red | Feint | High-block attack boost and penetration; Yellow objective |
| Red | Combination Strike | Any one token for +1 attack; two different tokens instead for +4 attack, +2 block, ignore 2 block |
| Red | Covering Step | High-attack block read; guard-high Red objective |
| Orange | Clinch Throw | Ignore 2 block; Orange spend locks opposing burns next round on hit |
| Orange | Body Lock | High-block attack read; Orange objective |
| Orange | Driving Tackle | Orange spend: ignore 2 block and cut one route on hit |
| Orange | Grip Break | Independent defensive-bonus and carried-pressure reads; either objective path earns Blue |
| Orange | Off-Balance Sweep | Low-attack punish; Yellow objective; printed Orange stay |
| Orange | Counter Throw | High-attack attack/block read; Orange objective |
| Yellow | Sidestep | High-attack defensive read; Green objective |
| Yellow | Darting Strike | Yellow spend enables post-reveal burn-and-replace cancel |
| Yellow | Bait and Slip | Bonus-commitment defense and optional permanent main relocation; guard-bonus Blue objective |
| Yellow | Outside Step | High-block punish; +1 next-round attack on hit |
| Yellow | Glancing Step | Taking exactly 1 earns Yellow and +2 next-round attack |
| Yellow | Driving Kick | Low-block defensive read; Yellow spend forces next hand on hit |
| Green | Rooted Guard | High-attack read; independent next-round 4+ damage objective earns Green |
| Green | Forearm Check | Low-attack punish; Green spend adds block and Green stay permission |
| Green | Firm Counter | Opposing bonus read grants attack and block; printed Green stay |
| Green | Brace | Guard-high Blue objective; Green spend suppresses/reset attack carry |
| Green | Driving Palm | On-hit recovery 1, after knockout determination |
| Green | Measured Advance | No-bonus attack boost; no-own-burn hit earns Orange |
| Blue | Read the Strike | High-attack attack/block read; Purple objective |
| Blue | Catch the Opening | Low-block attack read |
| Blue | Watchful Guard | Bonus-commitment attack read; Red objective |
| Blue | Find the Gap | High-block attack read |
| Blue | Mirror Step | Independent exact-3 stat reads; Blue spend removes held tokens for hit and no-damage outcomes |
| Blue | Counterpunch | High-attack defense; Blue spend boosts both stats and denies new advantage on hit |
| Purple | Haymaker | High raw attack; miss recovery penalty; unassisted 4+ damage earns Purple |
| Purple | Charged Fist | No-bonus attack boost; Purple spend permanently burns opposing main on hit |
| Purple | Crushing Blow | Ignore 2 block; damage versus printed block 5+ earns Red |
| Purple | Iron Guard | No-bonus attack boost; no-damage +1 next-round attack; independent damage objective earns Green |
| Purple | Trade Blows | High-attack attack read |
| Purple | Heavy Feint | Bonus-block penetration and independent opposing advantage-spend attack read |

## Sources

- [Current card data](../../DFGSim/data/cards.json)
- [Catalog compiler and color styles](../../DFGSim/Custom/CardCatalog.php)
- [Rules v44 runtime](../../DFGSim/Custom/GameLogic.php)
- [Simulator README and historical revisions](../../DFGSim/README.md)
- [Heuristic bot](../../DFGSim/Bot/HeuristicBot.php) and [Pro bot](../../DFGSim/Bot/ProBot.php)
- [Existing balance methodology](BALANCE.md)
