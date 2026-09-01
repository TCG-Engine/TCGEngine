# CanTargetFriendly
#// COVERAGE: offer=CanTargetFriendly + DebuffsAndHeals (the unqualified "a unit" spans BOTH sides —
#//           each side proven by seating it as the ONLY body in play so the auto-target lands on it,
#//           which is the assertion when the pool is one) + SimulateRequestBoundary_ShrinkTargetPick
#//           (two bodies → a genuine interactive pick rather than an auto-resolve) ·
#//           boundary=ShrinkDefeatsShieldedUnit + DebuffsAndHeals (the −2 HP straddling lethal: a 2-HP
#//           unit reaches 0 and is defeated, a 9-HP unit lands at 7 and lives) ·
#//           reqboundary=SimulateRequestBoundary_ShrinkTargetPick ·
#//           decline=N/A: neither clause is optional — no printed "you may", the base heal is
#//           unconditional, and the target pick is a mandatory MZCHOOSE ·
#//           control=StolenUnit_ShrinkKillsIt_AndItGoesToTheOWNERsDiscard (both readings in one
#//           section: the pool is CONTROLLER-relative so a stolen unit is targetable by its controller,
#//           while the defeat it causes routes the card to the OWNER's discard, and "your base" heals
#//           the seat that PLAYED the event)
#// SOR_076 Make an Opening — "a unit" means ANY unit, friendly included (unlike Disarm).
#// Only a friendly unit in play (AT-AT, 9/9) → auto-target it: power 9−2=7, HP 9−2=7.
#// Base heal still applies (3 → 1).

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7

---

# DebuffsAndHeals
#// SOR_076 Make an Opening — Give a unit −2/−2 for this phase. Heal 2 damage from your base.
#// Single unit in play (enemy AT-AT, 9/9) → auto-target: power 9−2=7, HP 9−2=7.
#// P1 base starts at 3 damage → healed by 2 → 1.

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1BASEDMG:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:7

---

# ShrinkDefeatsShieldedUnit
#// SOR_076 Make an Opening — the shrink is NOT damage: it lowers HP directly.
#// A 2/2 unit dropped to 0 HP is defeated as a state-based effect — and a Shield
#// token does NOT save it, because shields only prevent damage, not HP reduction.
#// Target Leia (SOR_189, 2/2) carrying a Shield → −2/−2 → 0 HP → defeated.
#// The shield token is set aside (not discarded); only the unit hits the discard.
#// Base heal still applies (3 → 1).

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP2GroundArena: SOR_189:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1BASEDMG:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# SimulateRequestBoundary_ShrinkTargetPick
#// SOR_076 Make an Opening — with more than one unit in play the -2/-2 target pick is a real
#// interactive decision, which ends the request in production so the answer arrives in a fresh process.
#// Mirrors DebuffsAndHeals (same end state for the AT-AT and the base heal) with a second enemy seated
#// to keep the pick interactive and the boundary inserted before the answer.

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP2GroundArena: SOR_088:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DISCARDCOUNT:1
P1BASEDMG:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:3

---

# StolenUnit_ShrinkKillsIt_AndItGoesToTheOWNERsDiscard
#// SOR_076 Make an Opening — CONTROL axis, the owner≠controller reading. P1 controls SOR_189 Leia (2/2)
#// but P2 OWNS her (the board state after a take-control effect). Two things have to come apart here:
#//  · "Give A UNIT −2/−2" is controller-relative, so the stolen body is a legal target for its
#//    CONTROLLER (it sits in myGroundArena) and, being the only unit in play, auto-resolves onto her —
#//    a pool built from the OWNER's seat would have found nothing to shrink at all;
#//  · 2/2 − 2/2 = 0 HP, so she is defeated as a state-based effect — and a defeated card goes to its
#//    OWNER's discard, not its controller's. P2's discard must hold her while P1's holds only the event.
#// "Heal 2 damage from YOUR base" is the same seat split from the other side: "your" is the player who
#// PLAYED the event, so P1's base drops 3 → 1 and P2's base is never touched.

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;theirBaseDamage:4;myResources:3;handCardIds:SOR_076}
WithP1GroundArenaControlled: SOR_189:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:1
P2BASEDMG:4
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_076
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_189
