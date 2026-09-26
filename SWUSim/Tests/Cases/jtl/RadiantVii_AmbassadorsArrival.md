# DamageDebuffAura
#// JTL_226 Radiant VII — Each enemy non-leader unit gets -1/-0 for each damage on it. P2's SOR_046
#// (power 3) with 2 damage is reduced to power 1.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_226:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1

---

# WhenPlayed_5Indirect
#// JTL_226 Radiant VII — When Played: Deal 5 indirect to a player. P1 deals 5 indirect to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_226
WithP1Resources: 15

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5

---

# Offer_IndirectDamage_EitherPlayer
#// JTL_226 Radiant VII — "When Played: Deal 5 indirect damage to A PLAYER." The offer here is not a unit pool
#// but a player choice, and the printed text lets it be EITHER player — so the option list must contain the
#// SELF option ("You") as well as "Opponent", not just the opponent. The prompt is left PENDING and both
#// options plus the exact tooltip are asserted (WhenPlayed_5Indirect already covers answering it).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_226
WithP1Resources: 15

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_player_to_deal_indirect_damage
P1OPTIONHAS:You
P1OPTIONHAS:Opponent

---

# ThreeSeat_RadiantOnAFarSeat_StillDebuffs
#// The aura is resolved FROM THE AFFECTED UNIT's side: for each unit, is there a Radiant VII among ITS
#// enemies? P1's damaged SOR_046 has the Radiant on SEAT 3 — `OtherPlayer(1)` is seat 2, so the search
#// looked at the wrong board and the debuff silently vanished.
#// ⚠ Note the mirror case (Radiant on seat 1, victim on seat 3) accidentally WORKS, because
#// OtherPlayer(3) == 1. The victim must be on seat 1 for the bug to show.

## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:2
WithP3SpaceArena: JTL_226:1:0

## WHEN

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:POWER:1

---

# FourSeat_RadiantOnTheFarthestSeat_StillDebuffs
#// 4P sibling: the Radiant sits on SEAT 4.

## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:2
WithP4SpaceArena: JTL_226:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:POWER:1
