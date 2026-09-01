# Falcon_Combo
#// SOR_017 Han Solo "Audacious Smuggler" (Leader, cost 6, [Cunning][Heroism], Underworld, UNIQUE,
#// 4/6). FRONT: "Action [exhaust]: Put a card from your hand into play as a resource and ready it. At
#// the start of the next action phase, defeat a resource you control. / Epic Action: If you control 6
#// or more resources, deploy this leader."  DEPLOYED: "On Attack: Put the top card of your deck into
#// play as a resource and ready it. At the start of the next action phase, defeat a resource you
#// control."
#// COVERAGE (per SIDE — the two sides queue the same delayed downside through different entry points,
#// so each needs its own sections):
#//   FRONT · offer=LeaderAction_Offer_EveryHandCardIsALegalChoice (the hand-card MZCHOOSE asserted on
#//     a PENDING decision; the pool is the WHOLE hand, which is what the printed text says) ·
#//     boundary pair=Boundary_EpicAction_FiveResources_CannotDeploy (5, no deploy, Epic NOT spent) vs
#//     Boundary_EpicAction_SixResources_Deploys (6, deploys) + LeaderAction_EmptyHand_UsableNoResource
#//     (zero cards to ramp; the [Exhaust] cost still changes state so the Action stays usable) ·
#//     decline=N/A — the Action has no "you may": once paid, the ramp is mandatory, and the delayed
#//     "defeat a resource you control" is mandatory too (a mandatory MZCHOOSE, not an MZMAYCHOOSE) ·
#//     control change=ControlChange_ADefeatedForeignOwnedResourceGoesToItsOWNERSDiscard (a resource in
#//     P1's zone OWNED by P2: "a resource YOU CONTROL" accepts it, and the defeat lands in the
#//     OWNER's discard) · request boundary=LeaderAction_PendingDefeatNextActionPhase and
#//     ControlChange_ADefeatedForeignOwnedResourceGoesToItsOWNERSDiscard — the delayed trigger is
#//     armed in one action phase and resolved in the NEXT, across a full regroup, so it is stored on
#//     the player's GlobalEffects and rebuilt from serialized state.
#//   DEPLOYED · offer=N/A — neither deployed clause offers a target list: the ramp takes THE TOP CARD
#//     (no choice at all, asserted by identity in Deployed_OnAttack_RampsTheTOPCardSpecifically), and
#//     the delayed defeat is queued as a whole-zone MZCHOOSE over 'myResources' rather than an mzID
#//     list, so there is no candidate set for P1SELECTABLEEXACT to read · boundary
#//     pair=OnAttack_RampFromDeck (a card is there → ramp) vs Deployed_OnAttack_EmptyDeck_
#//     NothingIsRamped (zero cards → clean fizzle, nothing substituted) · decline=N/A — no "may" on
#//     either deployed clause · control change=N/A on this side: the deployed unit IS the leader and a
#//     leader unit cannot change control, and the resource-zone owner≠controller reading is the same
#//     code path, already covered by the FRONT control section above · request
#//     boundary=Deployed_PendingDefeatFiresAtTheStartOfTheNextActionPhase (armed by the attack,
#//     resolved after the regroup) · dispatch paths=Deployed_OnAttack_AlsoFiresWhenAttackingAUNIT
#//     (On Attack is not gated on a base target).
#// This section: SOR_017 Han Solo + SOR_193 Millennium Falcon — the combo.
#//
#// Han's leader action ramps a resource and leaves a pending "defeat a resource you control at
#// the start of the next action phase." The Falcon's regroup trigger lets you pay 1 resource to
#// keep her — exhausting a resource. The synergy: the resource you exhaust to keep the Falcon
#// becomes the one you feed to Han's mandatory defeat, so you keep the Falcon "for free" and
#// never have to defeat a ready resource.
#//
#// Sequence:
#//   1. Han leader action: hand card → ready resource (2 → 3), pending defeat armed.
#//   2. Both pass → regroup. During the Ready step the Falcon asks pay-or-bounce; pay 1 resource
#//      (exhaust resource 0) to keep her.
#//   3. Next action phase starts → Han's pending trigger: defeat resource 0 (the exhausted one).
#//   Net: Falcon stays, resources 3/1 → 3/0 (all 3 ready), one resource in discard.
#//
#// NOTE (phase-crossing): both players answer the Resource-step MZMAYCHOOSE by resourcing their first hand card
#// the Ready step (Falcon trigger) and the next Action phase (Han's pending defeat) are reached.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_193
WithP1Resources:2
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE
- P1>Pass
- P1>ResourceHand:0
- P2>ResourceHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myResources-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:1

---

# LeaderAction_EmptyHand_UsableNoResource
#// SOR_017 Han Solo — CR 6.4.587.c: the [Exhaust] cost changes game state, so the Action is usable even with
#// an empty hand. It exhausts Han and puts no resource into play (nothing to resource); the resource count
#// is unchanged this phase.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESCOUNT:2

---

# LeaderAction_PendingDefeatNextActionPhase
#// SOR_017 Han Solo — Leader Action delayed downside:
#// "...At the start of the next action phase, defeat a resource you control."
#// Han ramps (hand card → ready resource, 3 → 4). Both players pass → regroup phase runs
#// (draw, resource, ready). At the start of the NEXT action phase Han's pending trigger fires:
#// the player must defeat one resource they control (mandatory, player chooses which).
#// Resources 4 → 3, defeated resource goes to discard.
#//
#// NOTE (phase-crossing): ending the action phase pauses auto-advance at the Resource step
#// (each player has a "resource up to 1 card" MZMAYCHOOSE that does not auto-resolve), so both
#// players must answer with ResourcePass before the cycle reaches Ready → next Action phase.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 3
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:1

---

# LeaderAction_RampFromHand
#// SOR_017 Han Solo "Audacious Smuggler" — Leader Action [exhaust]:
#// "Put a card from your hand into play as a resource and ready it."
#// One hand card (SOR_095) auto-resolves → becomes a READY resource. Han exhausts.
#// Resources go 3 → 4, all 4 ready (the new one entered READY, not exhausted).

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESCOUNT:4
P1RESAVAILABLE:4
P1HANDCOUNT:0

---

# OnAttack_RampFromDeck
#// SOR_017 Han Solo (deployed leader unit) — On Attack:
#// "Put the top card of your deck into play as a resource and ready it."
#// Han is deployed (free, 6 resources), then attacks P2's base. OnAttack puts the top deck
#// card into play as a READY resource (mandatory — no "may"). Resources 6 → 7, deck 3 → 2,
#// P2 base takes 4 (Han's power). Han is exhausted from attacking.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
P1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_017
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:4
P1RESCOUNT:7
P1RESAVAILABLE:7
P1DECKCOUNT:2

---

# LeaderAction_Offer_EveryHandCardIsALegalChoice
#// THE OFFER CELL for the front side, asserted as a MENU on a PENDING decision. "Put A CARD FROM YOUR
#// HAND into play as a resource" puts NO restriction on the card — any card in hand qualifies, of any
#// aspect, cost or type — so the pool is the whole hand and nothing else. Two cards are seeded
#// (SOR_095 Battlefield Marine and SOR_046 Consular Security Force) because with one the choice
#// auto-resolves and there is no menu at all, which is exactly what LeaderAction_RampFromHand relies
#// on. The decision is left unanswered; its resolution lives in that section.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# Boundary_EpicAction_FiveResources_CannotDeploy
#// THE N-1 SIDE of the Epic Action gate — "Epic Action: If you control 6 OR MORE resources, deploy
#// this leader." With exactly FIVE the deploy is not available: the attempt is a no-op, Han stays on
#// his leader card, no leader unit appears in the ground arena, and the once-per-game Epic Action is
#// NOT spent (a gate that consumed the Epic Action on a failed attempt would silently cost the player
#// the whole ability).
#// Paired with Boundary_EpicAction_SixResources_Deploys below — one resource apart, opposite answers.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
P1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1GROUNDARENACOUNT:0
P1RESCOUNT:5

---

# Boundary_EpicAction_SixResources_Deploys
#// THE N SIDE — exactly SIX resources satisfies "6 or more" and Han deploys as a ground unit at his
#// printed 4/6, with the Epic Action now spent. No attack is made, so the deployed side's On Attack
#// ramp must stay silent: the deck and the resource count are both untouched, which separates the
#// DEPLOY from the ability the deployed unit carries.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
P1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_017
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P1RESCOUNT:6
P1DECKCOUNT:3
P1NODECISION

---

# ControlChange_ADefeatedForeignOwnedResourceGoesToItsOWNERSDiscard
#// OWNER ≠ CONTROLLER on the RESOURCE zone. "Defeat A RESOURCE YOU CONTROL" is a control test, not an
#// ownership test, so a card sitting in P1's resource zone but OWNED by P2 (the end state of an effect
#// that resources an enemy card) is a legal thing to feed to Han's delayed downside — and when it is
#// defeated it leaves to its OWNER's discard pile, not the controller's.
#// The foreign resource is seated at resource index 0. Han's leader action ramps the single hand card
#// (4 resources → 5); both players then pass through the regroup and, at the start of the next action
#// phase, the pending "defeat a resource you control" resolves onto myResources-0.
#// Net: P1 ends on 4 resources with an EMPTY discard, and P2 — who never acted — gains the card.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1ResourceControlled: SOR_063:2
WithP1Resources: 3
WithP1Hand: SOR_095
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:4
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_063

---

# Deployed_OnAttack_RampsTheTOPCardSpecifically
#// DEPLOYED SIDE — "On Attack: Put THE TOP CARD OF YOUR DECK into play as a resource and ready it."
#// The card taken is the top one specifically, not an arbitrary or bottom card: the deck is seeded
#// SOR_172 / SOR_095 / SOR_046 and after the attack the top card is SOR_095, i.e. exactly SOR_172 left.
#// OnAttack_RampFromDeck already asserts the COUNTS; this section asserts the IDENTITY, which a
#// count-only assertion cannot distinguish from a bottom-of-deck implementation.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: [SOR_172 SOR_095 SOR_046]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
P1RESCOUNT:7
P1RESAVAILABLE:7
P2BASEDMG:4

---

# Deployed_OnAttack_EmptyDeck_NothingIsRamped
#// THE ZERO BOUNDARY of the deployed side. With an EMPTY deck there is no top card to put into play,
#// so the ramp fizzles cleanly: the resource count is untouched at 6, nothing is drawn or discarded to
#// substitute for it, and no decision is raised. The attack itself is unaffected — P2's base still
#// takes Han's 4 — which proves the ramp is a rider on the attack rather than a cost of it.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1RESCOUNT:6
P1RESAVAILABLE:6
P2BASEDMG:4
P1NODECISION

---

# Deployed_OnAttack_AlsoFiresWhenAttackingAUNIT
#// DISPATCH PATH — "On Attack" fires on ANY attack, not only on a base attack. Deployed Han attacks
#// P2's SOR_046 Consular Security Force (3/7): the ramp still happens (deck 3 → 2, resources 6 → 7,
#// the new one READY), the defender takes his 4 and he takes 3 back.
#// Every other deployed-side section in this file attacks the base, so without this one an
#// implementation gated on a base target would pass them all.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
P1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P1RESCOUNT:7
P1RESAVAILABLE:7
P1DECKCOUNT:2
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Deployed_PendingDefeatFiresAtTheStartOfTheNextActionPhase
#// DEPLOYED SIDE, clause 2 — "At the start of the next action phase, defeat a resource you control."
#// The deployed On Attack arms the same delayed downside the front side does, so it has to be proved
#// on this side too: the flag lives on the PLAYER, not on the leader card, and the two sides queue it
#// through different entry points.
#// Han deploys at 6, attacks the base (ramp → 7), both players pass through the regroup, and at the
#// start of the next action phase the mandatory choice resolves onto one resource: 7 → 6, one card in
#// the discard. P2 is never asked for anything — "a resource YOU control" is the acting player's own.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:6
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0
P2BASEDMG:4
