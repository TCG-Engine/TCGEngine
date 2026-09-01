# WhenPlayed_ImperialGetsBuff
#// SOR_227 Snowtrooper Lieutenant (2/2, Ground) — When Played: You may attack with a unit. If
#// it's an Imperial unit, it gets +2/+0 for this attack. The chosen attacker (SOR_229, an
#// Imperial 3/3) attacks the undefended enemy base for 3 + 2 = 5. The +2 is for THIS attack
#// only, so the attacker's power is back to 3 afterward.
#// COVERAGE: offer=AttackOffer_ReadyFriendlyUnitsOnly (two ready friendlies plus an exhausted one, the
#//           just-played Lieutenant and an enemy unit all seated; decision left pending and
#//           P1SELECTABLEEXACT asserts the exact pool) · reqboundary=SimulateRequestBoundary_Imperial
#//           BuffSurvives · control=ControlTakenImperial_TheCONTROLLERAttacksAndTheBuffStillApplies
#//           (owner differs from controller: the unit is in the CONTROLLER's pool and attacks the
#//           OWNER's base) · boundary=this section (Imperial -> 3+2 = 5) vs WhenPlayed_NonImperial_
#//           NoBuff (Rebel -> 3), the trait class discrimination that is this card's only quantity
#//           gate; the DURATION edge ("for this attack") is the POWER:3 assertion here, taken after
#//           the attack has ended · decline=Decline_NoAttack.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArena: SOR_229:1:0    # Imperial attacker (3/3, ready) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3

---

# WhenPlayed_NonImperial_NoBuff
#// SOR_227 Snowtrooper Lieutenant — the +2/+0 applies ONLY if the attacker is an Imperial
#// unit. Here the attacker (Battlefield Marine, Rebel) is not Imperial, so it attacks the base
#// for its base 3 with no buff. Guards the trait condition.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArena: SOR_095:1:0    # Rebel (NOT Imperial) attacker, 3/3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:3

---

# SimulateRequestBoundary_ImperialBuffSurvives
#// SOR_227 Snowtrooper Lieutenant — the "you may attack with a unit" choose ends the request in
#// production, so the chosen-attacker answer arrives in a FRESH process where every non-serialized
#// global is empty. Mirrors WhenPlayed_ImperialGetsBuff with the boundary inserted before the answer:
#// the pending attack-with-a-unit context AND the Imperial +2/+0 rider must both be serialized.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArena: SOR_229:1:0    # Imperial attacker (3/3, ready) — idx 0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3

---

# Decline_NoAttack
#// SOR_227 Snowtrooper Lieutenant — the decline branch of "You MAY attack with a unit". P1 answers the
#// choose with '-'. No attack happens at all: the Imperial Cell Block Guard is still READY (an attack
#// would have exhausted it), P2's base is untouched, and no decision is left pending. The Lieutenant
#// itself is on the board exhausted, so the play still resolved — declining the rider is not declining
#// the play.
#// Load-bearing because the offer is an MZMAYCHOOSE whose continuation carries the whole payoff: a
#// sticky PASS that skipped the continuation and a continuation that attacked anyway are both caught
#// here, and by nothing else in this file.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArena: SOR_229:1:0    # Imperial attacker (3/3, ready) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:1:CARDID:SOR_227
P1NODECISION

---

# AttackOffer_ReadyFriendlyUnitsOnly
#// SOR_227 Snowtrooper Lieutenant — "You may attack with A UNIT", but only a unit that could legally
#// attack: the pool is READY FRIENDLY units. Four units are seated so every exclusion is exercised at
#// once, and the choose is left PENDING because an answer proves only the branch, never the pool:
#//   * myGroundArena-0 Cell Block Guard (ready, Imperial)  — offered
#//   * myGroundArena-1 Battlefield Marine (ready, Rebel)   — offered (the trait gates the +2/+0, NOT
#//     the pool: a non-Imperial unit may still be chosen, it just attacks unbuffed)
#//   * myGroundArena-2 Consular Security Force (EXHAUSTED) — excluded
#//   * myGroundArena-3 the Lieutenant itself                — excluded: units enter play exhausted, so
#//     it can never be the attacker it enables
#//   * theirGroundArena-0 Imperial Dark Trooper             — excluded, "a unit" here is friendly-only
#// Intended: exactly myGroundArena-0 and myGroundArena-1.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArena: [SOR_229:1:0 SOR_095:1:0 SOR_046:0:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# ControlTakenImperial_TheCONTROLLERAttacksAndTheBuffStillApplies
#// SOR_227 Snowtrooper Lieutenant × a control change. The only ready friendly unit is a Cell Block
#// Guard (Imperial 3/3) that P1 CONTROLS but P2 OWNS. Two things have to resolve from the CONTROLLER's
#// frame, not the owner's: the unit has to be in P1's "attack with a unit" pool at all, and the attack
#// it makes has to be P1's — so it swings at P2's base, its owner's, for 3 + 2 = 5.
#// The +2/+0 is a TRAIT test on the chosen unit (Imperial), which a control change does not alter, so
#// the buff still applies; the attacker ends EXHAUSTED. A seat-bound pool or a seat-bound attack
#// direction would either offer nothing here or point the attack the wrong way.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_227
WithP1GroundArenaControlled: SOR_229:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1BASEDMG:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:3
