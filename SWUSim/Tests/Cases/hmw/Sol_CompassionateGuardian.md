# OnAttack_GainsSentinelForThisPhase
#// HMW_210 Sol - Compassionate Guardian (Cunning/Heroism, Force+Jedi, cost 2, 2/2 Ground, unique) —
#// "Shielded / On Attack: This unit gains Sentinel for this phase."
#// COVERAGE: offer=Sentinel_ForcesEnemyAttackOntoSol (the attack-target POOL, proven behaviourally: with
#//           Sentinel up the pool narrows to {Sol} so an explicit ':BASE' request is overridden) ·
#//           negative=NoAttack_NoSentinel + OnlySolGainsIt_NotAnotherFriendlyUnit ·
#//           boundary=SentinelExpiresAtEndOfPhase (the DURATION - the positive alone passes a permanent
#//           grant, and the twin ASH_099's file has no expiry section at all) ·
#//           control=StolenSol_GrantsToItselfUnderTheNewController ·
#//           reqboundary=RequestBoundary_SentinelSurvivesBetweenActions ·
#//           decline=N/A (mandatory On Attack, no cost, no "you may")
#// ⚠ The second clause is ASH_099 Gozanti Assault Carrier's sentence WORD FOR WORD, so it reuses its
#//   one-liner: AddTurnEffect($mzID, SWUMakeTurnEffect('SENTINEL', [], SWU_DUR_PHASE, 'HMW_210')). The
#//   source tag is what puts the card's own art on the Active Effects badge.
#// ⚠ Shielded needs no wiring - the keyword generator derives it from the text (registry-confirmed), and
#//   a SEEDED unit never runs the entry hook, so Sol only carries a Shield token where a section plays
#//   her from hand. That keeps every combat number here clean.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_210:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:HMW_210
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoAttack_NoSentinel
#// HMW_210 - the negative that makes the trigger load-bearing. Sol sits on the board and does nothing;
#// she must NOT have Sentinel. A grant wired as a constant ability (or to the wrong window) passes the
#// positive above and fails only here.
#// (Green before implementation - an absence guard.)

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_210:1:0

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_210
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# OnlySolGainsIt_NotAnotherFriendlyUnit
#// HMW_210 - "THIS unit". Sol attacks and a friendly Battlefield Marine stands beside her; the Marine
#// must not pick up Sentinel. An implementation that grants to the controller's board rather than to the
#// attacking unit passes the positive unchanged.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_210:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_210
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# AttackingAUnitAlsoGrantsIt
#// HMW_210 - "On Attack" is not "attacks a base". Five LAW cards shipped with a trigger wrongly gated on
#// the attack's TARGET because every section only ever swung at the base. Sol attacks a UNIT here; the
#// seeded Shield token absorbs the whole 3-power counter (one token eats the entire damage instance), so
#// she survives at 2 HP to be asserted.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_210:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:HMW_210
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Sentinel_ForcesEnemyAttackOntoSol
#// HMW_210 - the granted keyword actually WORKING, not merely readable, and the offer cell with it. Sol
#// attacks the base and gains Sentinel; the Dark Trooper then asks to attack the BASE, but Sentinel
#// narrows its legal-target pool to {Sol} alone, so the attack auto-resolves onto her and the ':BASE'
#// request is never honoured. Sol (2 HP, no Shield - she was seeded) dies to the 3 and deals 2 back.
#// Initiative is left UNCLAIMED so the turn genuinely alternates; P1OnlyActions would auto-pass P2.

## GIVEN
CommonSetup: yyw/rrk/{}
WithActivePlayer: 1
WithP1GroundArena: HMW_210:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# SentinelExpiresAtEndOfPhase
#// HMW_210 - "for THIS PHASE" must END, and this is the section the twin ASH_099's file never had. Sol
#// attacks and gains Sentinel, then both players pass to reach the regroup phase and resolve their
#// resource steps; at the start of the next action phase the grant is gone. Without this, a permanent
#// grant passes every other section in the file.
#// Decks are seeded for both players because the regroup DRAW would otherwise deal deck-out damage.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_210:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_095]
WithP2Deck: [SEC_080 SOR_128 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_210
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2BASEDMG:2

---

# ShieldedOnEntry_PlayedFromHand
#// HMW_210 - the OTHER clause. Shielded is registry-derived, so this is a guard rather than new work: it
#// fails loudly if a regen ever stops picking it up. It has to be PLAYED, not seeded - the entry hook is
#// what grants the token, and every other section in this file deliberately seeds Sol to avoid it.
#// Shielded is her only entry trigger (no Ambush), so there is no trigger-ordering prompt to answer.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_210
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# RequestBoundary_SentinelSurvivesBetweenActions
#// HMW_210 - the request-boundary cell. The card raises no interactive decision, but production starts a
#// FRESH PROCESS between the two player actions below, and the grant is phase-scoped state written by the
#// first and read by the second. Held anywhere but the serialized TurnEffects it would be gone, and the
#// enemy attack would land on the base instead of being redirected.

## GIVEN
CommonSetup: yyw/rrk/{}
WithActivePlayer: 1
WithP1GroundArena: HMW_210:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# StolenSol_GrantsToItselfUnderTheNewController
#// HMW_210 - the control cell. "This unit" is self-referential, so a take-control effect changes WHO
#// benefits rather than what happens: P1 owns Sol, P2 controls her, and when P2 swings her she gains
#// Sentinel for P2. An implementation that resolved the grant against the OWNER's board would miss her
#// entirely here. (A Controlled unit is the only one in P2's arena, so it sits at index 0.)

## GIVEN
CommonSetup: yyw/rrk/{}
WithActivePlayer: 2
WithP2GroundArenaControlled: HMW_210:1

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2GROUNDARENAUNIT:0:CARDID:HMW_210
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
