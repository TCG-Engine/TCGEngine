# PreventsDamageToOtherFriendly
#// SEC_050 Vigil (Space, 5/9) — "If damage would be dealt to another friendly unit, prevent 1 of that
#//   damage." With SEC_050 in play, SOR_046 (3 power) attacks the friendly SEC_041 → it takes 3-1 = 2.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_050:1:0
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# SelfTakesPlusOne
#// SEC_050 Vigil — "If damage would be dealt to this unit by another card, deal that much damage plus 1
#//   instead." SOR_237 (2 power) attacks SEC_050 → SEC_050 takes 2+1 = 3.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_050:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:3

---

# IndirectToVigilPlusOne_OthersUnreduced
#// SEC_050 Vigil — indirect damage is UNPREVENTABLE, so the "prevent 1 to another friendly" half never
#// applies to it; but Vigil's own "+1 if damage would be dealt to this unit" DOES apply to the portion
#// assigned to Vigil (added at the damage step, since indirect is dealt all together). P2's Torpedo Barrage
#// (JTL_234, 5 indirect) is assigned by P1: 2 to Vigil → it takes 3; 2 to the friendly SOR_237 → it takes the
#// full 2 (NOT reduced to 1); 1 to base.
## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2Hand: JTL_234
WithP1SpaceArena: SEC_050:1:0
WithP1SpaceArena: SOR_237:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:mySpaceArena-0:2,mySpaceArena-1:2,myBase-0:1
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:1:DAMAGE:2
P1BASEDMG:1
