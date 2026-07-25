# GiveControlDeal4
#// LAW_085 You Hold This (Aggression,Cunning event, cost 1) — "Choose a friendly non-leader unit. An
#// opponent takes control of it. If they do, deal 4 damage to another unit in the same arena." P1 gives
#// away SEC_080; the only other ground unit (P2's SOR_046, 3/7) takes 4 and survives.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P1DISCARDCOUNT:1

---

# DefeatEnemyGround
#// LAW_085 You Hold This — the 4 damage can defeat the "another unit". P1 gives away SEC_080 (its only
#// friendly ground unit); the only remaining ground unit is P2's SHD_029 Pyke Sentinel (2/3), which takes
#// 4 and is defeated.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_029:1:0
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# TransferSpaceDefeat
#// LAW_085 You Hold This — the arena is set by the transferred unit. P1 gives away its space unit SOR_237;
#// the damage step targets another SPACE unit only. The lone other space unit is P2's SOR_178 Cartel
#// Spacer (2/3), which takes 4 and is defeated.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# DamageFriendlyUnit
#// LAW_085 You Hold This — "another unit in the same arena" may be a FRIENDLY unit. P1 has two ground
#// units; it gives away SEC_080 (index 0). With no enemy ground unit present, the only remaining ground
#// unit is its own SOR_046 (3/7), which takes 4 and survives.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1

---

# SkipDamageNoOtherUnits
#// LAW_085 You Hold This — if there is no other unit in the transferred unit's arena, the damage step is
#// skipped. P1 gives away its only ground unit SEC_080; the only enemy unit is in SPACE, so no ground
#// target exists and the event simply resolves.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2SPACEARENACOUNT:1
P1DISCARDCOUNT:1

---

# CannotTakeControl_NoEffect
#// LAW_085 You Hold This — the damage only fires "if they do" take control. P1's only friendly unit is
#// LAW_149 Rey, Skywalker ("Opponents can't take control of this unit"). The transfer is blocked, so no
#// control changes and no damage is dealt.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP1GroundArena: LAW_149:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_149
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1

---

# NoFriendlyUnit_NoEffect
#// LAW_085 You Hold This — with no friendly non-leader unit to give away, the event has no effect and is
#// simply discarded.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1
P1DISCARDCOUNT:1
