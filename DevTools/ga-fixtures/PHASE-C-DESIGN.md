# Phase C design and review record

Investigation date: 2026-10-16. **Audit/design phase closed; deferred engine
implementations remain open in ROADMAP.md D0.** The authoritative disposition,
remaining contracts and test plans are in [PHASE-C-HANDOFF.md](PHASE-C-HANDOFF.md).
Earlier investigation checkpoints below are historical and their “remains open”
statements describe those earlier checkpoints, not the current phase status.
The closeout does not claim every audited mechanic is implemented.

## C1: Exalted element membership

The current generated dictionary contains 80 dual-element cards: 14 EXALTED,FIRE,
15 EXALTED,WATER, 17 EXALTED,WIND and 34 EXALTED,NORM. A read-only PHP probe
loaded GeneratedCardDictionaries.php and confirmed that exact comparison rejects
Scorched Conquest (HPDawzCDdr), Teardrop Diadem (K15jWbHAMY), Noble Dissolution
(iullthfLXc) and Mordred, Fated Luminary (KqBosnU7pU) for their secondary element,
while comma-delimited membership accepts them. This proves the representation
mismatch; it is not an end-to-end gameplay regression fixture.

### Approved first implementation batch: Kindle only

Affected paths, all in GrandArchiveSim/Custom/GameLogic.php:

- ActivationKindleReduction: counts eligible graveyard cards for affordability.
- DoActivateCard Kindle block: counts eligible cards and computes kindleMax.
- KindleChoose: rebuilds eligible choices after each banish.
- KindleProcess: existing payment/continuation path; verify it remains consistent.

Intended behavior: a card containing FIRE is a fire-element Kindle payment,
including EXALTED,FIRE. Continue to exclude removed cards and non-fire cards.
Use PropertyContains(CardElement($id), "FIRE") for these graveyard card-ID
queries; no new general helper is needed. Keep the printed/Stablemaster Kindle
amount, class conditions, reserve remainder, optional decline and banish timing.
Do not replace other element checks in this batch.

Regression plan before implementation:

1. A hand activation affordable only through an Exalted Fire graveyard payment
   must be offered, banish that card before responses and pay the correct remainder.
2. Ordinary Fire remains eligible; Exalted Water and removed Fire remain excluded.
3. Multiple payments rebuild choices after compaction without reusing a card.
4. Declining payment leaves the graveyard card and pays the full reserve cost.
5. Repeat the positive path from the other controller perspective; cover the
   Stablemaster-granted Kindle path as well as printed Kindle.
6. Mutate each of the three membership sites back to equality independently;
   assertions must catch affordability, initial limit and repeated-choice defects.
7. Run focused fixtures, suite, GABot and local guards; regenerate semantic backlog.
   Full scenario regeneration remains part of Phase G.

### Reviewed wider sites: separate future batches

| Path | Current behavior / proposed disposition |
| --- | --- |
| GameLogic Furnace Drone payment and its continuations | FIRE equality excludes dual Fire from fire-or-Automaton costs. Audit all eligibility/revalidation paths together before changing. |
| GameLogic ScavengeTempZoneChoices(element), GetOmenCountByElement | Equality treats an element list as one element. Membership candidates; review callers and printed clauses. |
| GameLogic AeneanGutteringFlamesBonus, TrackPRDChampionDamage | Fire-source equality excludes dual Fire. Test source identity after movement and existing class/suppression conditions. |
| GameLogic Jianye death trigger | Raw element query plus equality. Use effective field properties at the relevant death snapshot; verify snapshot lifetime before changing. |
| CombatLogic fire-source unpreventable branch | Local sourceElement equality also needs audit: searching only comparisons on CardElement calls misses it. Preserve source controller and effective-element fallback. |
| GameLogic Viridian Protective Trinket surcharge | WATER equality excludes dual Water. Verify declaration, affordability and actual payment together. |
| CombatLogic Shangxiang prevention; GameLogic Dawn of Ashes surcharge | Non-Norm equality includes EXALTED,NORM. Do not blanket-replace: settle the non-Norm predicate explicitly and add dual-Norm controls. |
| GeneratedMacroCode and existing overrides | Direct comparisons and element locals both exist. Correct through card editor regeneration or maintained overrides; never edit generated output. Check which generated bodies are superseded before counting live defects. |
| Advanced-element classification, distinct-element counts, element requirements | Not equivalent to basic-element membership. Exalted is itself advanced; existing lineage/requirement semantics must remain intact. Separate rules review required. |

The old estimate of 64 equality sites is historical, not a verified live count.
This review has not classified every comparison, local element variable, array
membership or generated body. ARCANE/ASTRA/etc. equality sites are not automatically
Exalted defects: the current dual-element dictionary has only the four combinations
listed above. Runtime effective overrides may still require their own review.

## Remaining Phase C investigations

Defender-relative combat reads; starting champion/opening setup; graveyard
Reactions; Multistrike; target-dependent discounts; Eternal Magistrate movement
restrictions; On Enter response timing; Forged Scalemail damage suspension.
Their design and implementation check-ins remain outstanding. Scalemail's existing
plan and required test matrix remain in ENGINE-FINDINGS.md.

### Kindle implementation evidence

Owner approval: 2026-10-16, following the concrete Kindle-only proposal. Changed
only the three FIRE membership checks described above; KindleProcess and other
element comparisons remain unchanged. Six scenarios in kindle-elements.php cover
limited-hand mixed affordability, two successive dual-Fire payments, dual-Water
exclusion, decline/full reserve, Stablemaster-granted Kindle and player two.
The mixed scenario asserts both banishes before reserve payment and responses.
Independently reverting affordability, initial-limit and rebuilt-choice checks
makes that fixture fail. A loaded-state probe confirms removed Fire is excluded
from affordability and rebuilt choices. PHP lint and diff formatting pass. Full verification: 4,021/4,021 integration
fixtures, GABot 10/10, strict audit 3,837 complete / zero incomplete / 184 legacy,
action/inline guard and three-card baseline pass. The semantic backlog was rebuilt.
Five further mutations make the dual-payment, Stablemaster, player-two, Water
exclusion and decline fixtures fail, giving eight expected failures across all
six new fixtures. Every mutation was restored. Full scenario regeneration was
not run; changes remain uncommitted and nothing was pushed.

### Approved second batch: Furnace Drone only (implemented)

The follow-up audit found three live equality sites, not only the two payment
sites: the generated activateCardPrereqs["cbNF64gCsS:0"], DoActivateCard's
Furnace Drone block, and FurnaceDroneCostBanish's rebuilt choices. No maintained
override for this prerequisite was found. The printed dictionary text requires
three fire element and/or Automaton cards. All three eligibility predicates must
accept FIRE membership or AUTOMATON subtype membership and exclude removed slots.
Change the prerequisite through a maintained override (or card-editor regeneration
if available), never by editing generated output. Preserve reserve payment, the
three mandatory banishes, graveyard cleanup and the existing On Death ability.

Regression matrix: exactly three dual-Fire non-Automaton cards; mixed ordinary
Fire / dual Fire / non-Fire Automaton; refusal with only two eligible cards;
reject dual Water and ordinary non-Automaton Norm; reject removed cards; repeated
choices after compaction; payment before response priority; other-controller
perspective. Independently restore equality at each of the three sites and require
a failure. Retain the existing reindexed-graveyard regression. Run full verification
and rebuild the semantic backlog after implementation. This proposal is scoped to
Furnace Drone; it does not authorize a global element replacement.

Further caller classification: ScavengeForElement currently has only ARCANE and
EXIA generated callers, so its exact comparison is not a confirmed defect for the
current four Exalted pairings. GetOmenCountByElement has FIRE and WIND callers
(discount, On Enter threshold and per-omen LIFE) plus UMBRA; the basic-element
callers remain candidates for a separate batch after checking live overrides,
printed text and omen legality. These are source inspections, not new gameplay
reproductions. No runtime code or fixtures changed during this audit.

#### Furnace Drone implementation evidence

Owner approved this bounded batch on 2026-10-16. The two payment checks now use
FIRE membership, and a maintained activateCardPrereqs override applies the same
predicate before activation. Generated output and the On Death ability were not
edited. Four scenarios in furnace-elements.php cover three dual-Fire payments,
mixed ordinary Fire / dual Fire / non-Fire Automaton, the other controller,
insufficient eligible cards, Water/Norm exclusion, compaction and payment timing.
The insufficient-cost input is accepted by the transport as a no-op; assertions
verify the card stays in hand and no graveyard or reserve payment occurs.
Restoring equality separately at the prerequisite, initial-choice and repeated-
choice sites produces three expected regression failures. A loaded-state probe
checks that removed Fire does not satisfy the prerequisite or rebuilt choices.
All mutations were restored. PHP lint and diff formatting pass. Full verification:
4,025/4,025 integration fixtures, GABot 10/10, strict audit 3,841 complete / zero
incomplete / 184 legacy, 3,806 scenarios checked with zero truncations, and the
three-card baseline pass. Semantic backlog rebuilt. Full scenario regeneration
remains Phase G work. Changes remain uncommitted and nothing was pushed.

### Third batch complete: Viridian Protective Trinket

Read-only investigation (2026-10-16): the printed dictionary clause is “During
your turn, water element cards your opponents activate cost 2 more to activate.”
The live block in CalculateActivationReserveCost checks WATER equality and does
not check HasNoAbilities. No other maintained implementation was found. The same
cost function supplies PreviewActivateReserveCost / CanAffordActivationReserve
and DoActivateCard's actual payment cost, so these paths must stay consistent.

A temporary loaded-state probe based on the existing ordinary-Water surcharge
fixture measured printed/base cost, active-trinket cost and suppressed-trinket
cost: Dissuading Halt 2/4/4; dual-Water Eminent Lethargy 2/2/2. Removing the
trinket supplied each base control. This reproduces both the missing dual-Water
surcharge and the retained surcharge under NO_ABILITIES. The probe cleaned up its
isolated game in finally; no runtime or existing fixture was changed.

Proposed implementation scope: change this trinket block to WATER membership;
ignore suppressed trinkets while continuing the scan for another active copy.
Preserve its turn/controller gate and existing once-only +2 treatment. Do not
change Dawn of Ashes, general element requirements or other cost modifiers.
Stacking of multiple trinkets is outside this bounded element/suppression batch;
the existing break behavior is preserved rather than declaring a rules ruling.

Regression matrix: dual Water pays printed +2 before responses; limited-hand
affordability includes the surcharge; ordinary Water retains +2; non-Water,
removed and suppressed trinkets do not add it; own activation and the opponent's
turn do not add it; repeat from the other viewer/controller perspective; a
suppressed first copy must not hide a later active copy. Retain the existing
three surcharge/payment fixtures. Independently mutate membership and suppression
filtering and require observable failures. Run focused fixtures, full suite,
GABot and local guards, then rebuild the semantic backlog. The user approved
implementation with Continue after reviewing this proposal.

Implemented and verified (2026-10-16): the maintained cost block uses WATER
membership and skips suppressed copies, continuing to find a later active copy.
Nine new fixtures cover dual and ordinary Water payment, suppression, removal,
suppressed-first scanning, own activation, the other controller, a Norm activation
control and insufficient reserve (three available reserve cards versus cost four).
The existing three Viridian fixtures remain unchanged. Separate loaded-state
assertions verify both Water forms cost two on the opponent's own turn and a
non-Water card pays its printed three even during the trinket controller's turn.
Those two boundaries are cost probes, not end-to-end turn-transition fixtures.
Independent membership and suppression mutations each fail; byte backups restored
both mutations. All 4,034 integration fixtures and GABot 10/10 pass. Strict audit:
3,850 complete / zero incomplete / 184 legacy, 8,813 assertions. Scenario audit:
3,815 checked / zero truncated. Three-card baseline, PHP lint and diff formatting
pass; semantic backlog rebuilt. Full regeneration remains Phase G. No commit or
push. The wider Exalted audit and multiple-copy stacking review remain open.

### Phase-wide continuation authorization and fourth batch (2026-10-16)

The owner explicitly requested “Continue through phase c”. This authorizes the
remaining roadmap work; another check-in is not needed for routine bounded fixes
within that scope. Deployment, pushing and PR creation remain held.

Fire damage batch affected paths: CombatLogic's Fire-source prevention bypass;
GameLogic's TrackPRDChampionDamage, CheckRubyFatestoneQuestCounter and Jianye
branch of DoAllyDestroyed. Basic-element predicates now use membership. Live
sources and the cloned dying ally use EffectiveCardElement; departed spell
sources retain the existing printed-card fallback. DoAllyDestroyed clones the
object before moving it, so its effective overrides survive for the death test.
Existing source-controller, non-combat, Guo Jia, class, influence and suppression
conditions are retained. Varuck's field-presence branch additionally ignores
suppressed copies, a separate defect reproduced by the new suppression fixture.

Six scenarios in fire-damage-elements.php cover dual Fire refinement, Varuck
prevention bypass, no-Varuck and suppressed-Varuck controls, a dual Fire ally's
Jianye draw, and Guo Jia's Ruby quest counter. Independent reversions of the four
membership predicates and Varuck suppression each fail. Full verification:
4040/4040 integration, GABot 10/10, 3856 complete / zero incomplete / 184 legacy,
8822 assertions, 3821 scenarios / zero truncated, baseline pass. All mutations
restored; generated files untouched. AeneanGutteringFlamesBonus is unchanged:
current dual Fire cards do not qualify as Aenean Spells, so that comparison is
not a demonstrated current dictionary defect. Its future/effective-source scope
remains part of the wider audit.

### Graveyard Reaction availability: already implemented, regression added

OpportunityLogic's GetPlayableFastCards already scans the graveyard and calls
CanUseFastOpportunityGraveyardCard. That gate checks Reaction speed, selection
legality, graveyard suppression and payable Ephemerate conditions. The old
hand/field-only finding is stale. graveyard-reactions.php proves Twingale Parry
can be selected during the opponent's real attack, pays two reserve, prevents
two combat damage and resolves to banishment. Disabling the availability gate
makes the selection fail. No runtime change was needed for this item.

### On Enter timing and pay-or-negate: reproduced and fixed

QueueEnterTriggeredAbility already creates ENTER stack entries with source
UniqueID, and PostResolutionCheck offers ENTER_RESPONSE. The default shortcut
auto-passes this window, explaining previous failed fixture attempts; the new
scenarios explicitly disable that preference through the normal 10015 action.
Stifling Aethercharge successfully negates a real Blackmarket Broker trigger.

Frostbitten Etui exposed a different defect: its cross-controller YESNO was
pending while AbilityOpportunity continued resolving the ENTER entry, so the
preparation counter appeared before payment or decline. AbilityOpportunity and
PostResolutionCheck now wait for NegateByCardCurrentTarget to clear. Completion
resumes stack processing; pay/decline continuations execute on the answering
player's queue so they cannot strand the remaining work on the other queue.

The two card continuations now filter negation to ENTER entries with the chosen
ally's UniqueID and controller. The shared helper's optional filter preserves
other callers. A loaded-state four-entry probe excludes another copy, a same-card
ordinary activation and the other controller. Three gameplay fixtures assert
Stifling negation, Etui decline and Etui payment (four activation reserve plus
three to preserve the trigger), surviving ally, banished Etui, empty DQ and stack.
Mutations detect early resolution, missing identity filtering and skipped
Stifling negation. Frostbitten Etui's semantic exception and the obsolete
Stifling clause exception were removed; the baseline now has two cards.

### Defender perspective: computed-stat fixes and remaining audit

ObjectCurrentPower contained 19 raw attacker reads and 26 raw target reads. These
functions run for both viewers; their object IDs are viewer-relative. All reads
in this function now use GetCombatAttackerMZ/GetCombatTargetMZ. No combat-state
storage format changed. Three mirrored-index fixtures prove an opposing Horned
Knight's ally bonus, Golden Rook's even-life bonus and Dissuading Halt's attack
debuff; same-index defending copies do not receive attacker bonuses. Reverting
the normalized reads makes all three fail. Full verification: 4047/4047,
GABot 10/10, 3863 complete / zero incomplete / 184 legacy, 8842 assertions and
all guards pass. The remaining raw readers are not blanket-replaced: index-based
GetRetaliatorOptions uses explicit attacker/defender fields; attack/on-hit/kill
bodies execute in the attacking controller's frame; presence-only checks do not
resolve a zone. Arrow Trap, Lost Promises, Scorching Trap and Visceral Inversion
already have maintained perspective overrides. Enervating Decay explicitly
converts the attacker to theirField. Further individual reader classification
is still required before closing the whole audit.

### Starting champions: already fixed; real opening fixtures added

PlacePregameStartingChampion suppresses the immediate field-add trigger on
purpose. PREGAME_CHOOSE_STARTING_CHAMPION then calls Enter on the first player's
selected champion, and PREGAME_RESOLVE_STARTING_CHAMPION_ENTER resolves the second
player before finishing setup. There is no unconditional draw-seven fallback.
The old finding inspected only the placement half of that sequence.

pregame-champions.php selects each of Fragmented Spirit of Fire, Spirit of
Fortuitous Fire and Spirit of Serene Fire as the actual starting champion, with
both players answering the printed Glimpse. It verifies six hand cards plus a
Spirit Shard, seven memory cards and zero hand cards, or six hand cards,
respectively, for both controllers. No champion is seeded. All three pass on
the existing runtime; omitting the second player's real Enter makes each fail.
Existing Flawless Spirit of Mordred startup sequencing coverage is retained.

### Latest checkpoint and remaining implementation boundaries

Full verification after the real-startup scenarios: 4050/4050 integration,
GABot 10/10, 3866 complete / zero incomplete / 184 legacy, 8856 assertions,
3831 scenario fixtures / zero truncated, two-card baseline pass. Semantic backlog
rebuilt. PHP lint and diff formatting pass. Nothing committed or pushed; full
scenario regeneration remains Phase G.

The following are still open; this checkpoint does not declare Phase C complete:

- Exalted: omen element membership, the remaining generated/live comparisons,
  non-Norm semantics in Shangxiang/Dawn of Ashes and effective-source boundaries.
  GetOmens already excludes removed slots and returns banished cards carrying
  omen counters; GetOmenCountByElement still compares full strings. Its FIRE/WIND
  callers need card-specific eligibility/threshold fixtures before replacement.
- Combat readers outside computed power: preserve explicit player-indexed and
  presence-only reads, check inherited trigger contexts and defender consumers.
  SamaritanReachResolve already normalizes its attacker but still reads a raw
  CombatTarget; it needs a focused defending-control regression.
- Target discounts: Slipstream Vault's entry in GetClassBonusActivationReduction
  is unconditional on target; Spectral Haunting's generated modifier tests for
  *any* Specter in the graveyard, and Astral Seal's tests for *any* matching stack
  entry. The chosen target is selected only at resolution. These are actual
  declaration/payment architecture gaps. Use the existing Break Apart/Coronation
  declaration paths as precedents, but bind chosen category/target identity to
  the stack entry, constrain resolution to paid eligibility and keep dry-run
  affordability free of mutations. Mixed-target fixtures must catch selecting a
  non-discounted target while paying the discounted cost; removed/changed target,
  suppression/class, other controller and insufficient reserve are required.
- Eternal Magistrate: generated Enter only records imbue. Material has no
  removal/replacement hook in the schema; generated MZMove removes the source
  before adding the destination. A universal veto must happen before removal,
  through a maintained schema/generator hook, rather than only in DoMaterialize.
  Audit MZMove and direct Remove/array mutations, plus material-to-hand/graveyard/
  field and materialize-ignored-cost/level-up paths. Lock scope is each opponent's
  materialize phase, not the Magistrate controller's phase; inactive, suppressed
  and non-imbued controls are required. No generated output may be hand-edited.
- Multistrike: the dictionary has Crosswind Cuts (Multistrike 1, S0YCKJcU5e) and
  the pay-three temporary Multistrike 2 grant (WFNM4etSFB). The current grant stores
  MULTISTRIKE_2 without a combat consumer. N means up to N *additional* targets.
  Reuse Cleave's multi-defender tracking where appropriate, but retain optional
  bounded selection, distinct legal targets and independent per-target damage,
  retaliation, redirection, removal and On Hit/On Kill bookkeeping. A marker-only
  implementation is insufficient. Required rules review and combat fixtures are
  still outstanding.
- Forged Scalemail: remains the documented suspend/resume damage-event design.
  The synchronous linked-prevention loop cannot simply enqueue a choice and
  continue. The existing detailed stable-identity/prevention-order matrix in
  ENGINE-FINDINGS.md remains the implementation contract.

### Defender response follow-up

Samaritan's Reach now reads GetCombatTargetMZ: the former raw target lookup
incorrectly granted Crowd's Favor for defending your own champion. A real attack
fixture reproduced the unwanted status before the fix and passes afterward. The
older damage-only fixture's snapshot contained that unwanted status; regenerated
that one snapshot after correcting the runtime.

Hymn of Gaia's Grace had three connected redirect defects: a raw target lookup
missed the defending champion, the callback stored its ally in the defender's
perspective, and it did not update combat target markers. It now normalizes the
lookup and stored target and calls MarkCombatTarget, matching existing shield
redirects. Real-attack fixtures cover accepting (ally takes one damage, champion
zero) and declining (champion takes one, ally zero). The accepting fixture failed
before these corrections. Wider combat reader classification remains open.

Defender follow-up verification: 4053/4053 integration; GABot 10/10;
3869 complete / zero incomplete / 184 legacy; 8862 semantic assertions;
3834 scenarios / zero truncated; two-card baseline and strict guards pass.
PHP lint and diff formatting pass. Semantic backlog rebuilt. Uncommitted/unpushed.

### Omen element membership follow-up

Changed GetOmenCountByElement to printed element membership. Its callers count
Fire omens for Inverted Pyroslash, Wind omens for Chrysalis's suppression threshold
and Favorable Omens's life bonus, and advanced-element omens in other effects.
Membership preserves single-element advanced cards; no advanced-element
classification or distinct-element calculation was changed.
Eight scenarios in omen-elements.php cover Exalted Wind life, ordinary Wind,
a dual Wind card without an omen counter, a mixed ordinary/Exalted Wind threshold,
a one-Wind negative threshold, Exalted Fire discount with three omens (cap at two),
ordinary Fire and non-Fire controls. The Fire cases run from player two.
The Wind life regression failed before the fix. Reverting the helper independently
makes the three dual-element positive scenarios fail; the mutation was restored.
No generated handlers were edited.

Verification: 4,061/4,061 integration fixtures; GABot 10/10; strict semantic
audit 3,877 complete, zero incomplete, 184 legacy and 8,870 assertions; action
guard 3,842 checked, zero truncated; two-card baseline passes. PHP lint and diff
formatting pass (the existing CRLF warnings remain). Semantic backlog rebuilt.
Full scenario regeneration remains deferred to Phase G. Nothing was pushed.

### Slipstream Vault target/payment binding

Slipstream now declares its friendly ally before announcement and reserve payment.
The card carries the ally's UniqueID on the effect stack; OnCardActivated transfers
that scalar into the synchronous resolution context before the Action moves to the
graveyard. Resolution finds that same live friendly ally, so field compaction cannot
substitute a different object. The class discount checks the declared ally's effective
UNIQUE type. An undeclared affordability preview searches for a qualifying ally without
changing state; after declaration it evaluates only the selected identity.

Four new fixtures cover mixed ordinary/unique allies, no class bonus, and insufficient
reserve for the ordinary choice. The existing unique-ally fixture now declares before
payment. Restoring the flat discount makes the ordinary-target regression fail
(expected two reserve cards, got one). The insufficient-reserve fixture also passes
under that mutation and is a control, not independent mutation evidence.

Astral Seal and Spectral Haunting still require target/payment binding. Wider combat
and element audits, Eternal Magistrate, Multistrike, and Forged Scalemail remain open.

Slipstream verification: 4,065/4,065 integration; GABot 10/10; 3,881 complete,
zero incomplete, 184 legacy; 8,881 semantic assertions. All local guards pass.


### Spectral Haunting target-category payment checkpoint

Spectral Haunting now asks whether to commit to a Specter before payment when
Alice Bonus and a Specter ally in the graveyard are available. The declaration
is serialized on the activation's stack entry. Declining pays the full reserve
cost; accepting pays two and restricts the resolution chooser to live Specter
allies. Resolution revalidates the chosen category before moving the card.
This follows the existing category-declaration cost flows and avoids pretending
that graveyard entries have stable field UniqueIDs. Dry-run cost preview reads
eligibility without creating a declaration or consuming anything.

Two mixed-graveyard fixtures cover both payment branches and assert the actual
resolution candidate lists as well as the returned ally's ephemeral effect.
Restoring the unconditional Alice discount fails the ordinary-target fixture;
removing the Specter-only chooser fails its candidate-list assertion. The older
non-Alice resurrection fixture remains a full-cost control. Further interruption,
insufficient-reserve and other-controller cases remain useful follow-up depth;
Astral Seal is still open. No Phase C closeout is claimed.

Validation: 4,067/4,067 integration fixtures, GABot 10/10, strict semantic audit
(3,883 complete, zero incomplete, 184 legacy), action/inline guard (3,848
scenarios, zero truncated), two-card coverage baseline, PHP lint and diff check
pass. No full scenario regeneration, commit or push was performed.


### Astral Seal target-category payment checkpoint

Astral Seal uses a pre-payment qualifying-name declaration when Cleric Bonus and
a legal matching activation are present. Accepting receives the three-reserve
reduction; declining pays full cost. Only live banishment entries grant the
reduction. The qualifying card IDs are captured before payment and retained on
the activation and its PLAY_CARD trigger context. This avoids relying on a
shared decision variable across response windows. The trigger restores context,
then queues a chooser and a short non-interactive resolver that revalidates the
chosen live, negatable activation against those captured names. Later removal
of a matching banished card does not invalidate an already paid declaration.
EffectStack entries have no field UniqueID, so this follows the category-binding
approach rather than retaining a mutable stack index through responses.

Four retained fixtures cover YES/free, NO/full cost, an opponent's matching
banishment and missing Class Bonus. The unrestricted branch includes a
nonmatching activation in its chooser; the discounted branch excludes it.
Restoring the blanket discount or unrestricted chooser fails the corresponding
fixture. An isolated runtime probe additionally checks an opposing activation,
removed banishment entries, preservation of declared names and removed or
unnegatable targets. Full retained interruption, affordability and dry-run
coverage remains follow-up work, along with Spectral Haunting's analogous depth.

Validation: 4,071/4,071 integration, GABot 10/10; strict audit 3,887 complete,
zero incomplete, 184 legacy, 8,901 assertions; 3,852 scenarios, zero truncated;
two-card baseline, PHP lint and diff check pass. Phase C is not complete.
