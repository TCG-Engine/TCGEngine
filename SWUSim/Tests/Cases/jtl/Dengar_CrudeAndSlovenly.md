# OnAttack_Indirect
#// JTL_139 Dengar (pilot) — Attached gains "On Attack: deal 2 indirect to a player (3 if attached is an
#// Underworld unit)." On a non-Underworld host SOR_237 (2+1 power = 3), attacking the base: 3 combat + 2
#// indirect = 5 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5

---

# OnAttack_IndirectSplitUnitAndBase
#// JTL_139 Dengar (pilot) — On Attack: deal 2 indirect to a player (non-Underworld host). With an enemy
#// unit in play the damaged player (P2) ASSIGNS the 2 indirect, splitting it across a unit AND the base:
#// 1 to their 1-HP SOR_128 (defeats it) + 1 to their base. Host SOR_237 (2 power +1 from JTL_139 = 3)
#// attacks P2's base for 3 combat, so P2 base = 3 combat + 1 indirect = 4; SOR_128 is defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION

---

# OnAttack_Indirect3_UnderworldHost
#// JTL_139 Dengar (pilot) — the granted "On Attack: deal 2 indirect to a player" becomes 3 when the
#// attached unit is an UNDERWORLD unit. On SOR_178 Cartel Spacer (Underworld, 2 power → 3 with Dengar),
#// attacking the base with no enemy units: 3 combat + 3 indirect = 6 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_178:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:6

---

# TwinSuns_APlayer_OffersEVERYLiveSeat
#// ⚠ REPORTED BUG (2026-08-25), the other half of the TIE Bomber report. JTL_139's granted ability says
#// "Deal 2 indirect damage to A PLAYER" — an unqualified "a player", which per the Twin Suns rules is a
#// real CHOICE and includes YOURSELF. SWUDealIndirectToChosenPlayer hardcoded a two-option
#// OPTIONCHOOSE "You&Opponent", and its INDIRECT_CHOOSE_PLAYER handler mapped "Opponent" through
#// GetOpponent($controller) — which returns 2 for seat 1, 1 for seat 2, and NULL for seats 3 and 4.
#//
#// So at four seats the picker could not name seats 3 or 4 at all: the damage "defaulted to the same
#// player regardless of who was attacked". Note this is NOT the defending-player bug — this card really
#// does ask, it just could only ever offer one of the three opponents.
#//
#// This is the single widest seam in the batch: THIRTEEN JTL cards route "a player" through
#// SWUDealIndirectToChosenPlayer (JTL_139/142/145/150/172/196/208/210/218/222/226/230/236-family).
#//
#// Asserting the OFFER, not a branch: answering a target proves the branch and never the pool.
#// P1 must be IN it ("a player" includes you) and all three opponents must be too.
#// ⚠ Premier is deliberately NOT changed — at two seats the labels stay "You&Opponent" byte-identical,
#// which is why the three existing 2-player sections above still answer `Opponent`.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001; theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:P4B

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P1
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4

---

# TwinSuns_APlayer_DamageLandsOnTheFARSeatChosen
#// The pool being right is not the same as the answer being honoured: INDIRECT_CHOOSE_PLAYER has to
#// decode a P{n} label into a seat and hand it to SWUDealIndirectDamage. Answer P3 — a seat that is
#// NEITHER the caster nor the defender nor OtherPlayer(1) — so no legacy code path can produce it by
#// accident. Non-Underworld host, so the amount is 2.
#//
#// Seat 4 (the DEFENDER) is asserted at 3 because the host SOR_237 (2 power +1 from Dengar) attacked its
#// base for 3 combat and must take no indirect; seat 2 (OtherPlayer(1), the legacy answer) must be at 0.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001; theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:P4B
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3BASEDMG:2
P4BASEDMG:3
P2BASEDMG:0
P1BASEDMG:0
