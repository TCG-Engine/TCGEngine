# WhenPlayed_ExhaustHost
#// TWI_070 Perilous Position (Upgrade -2/-2, cost 3, Vigilance, Condition) — "When Played: Exhaust attached
#// unit." Played on SOR_046 (3/7), it exhausts the host and reduces it to 1/5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TWI_070}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:1

---

# AttachPool_AnyUnitEitherSideEitherArena
#// TWI_070 Perilous Position — a Condition that prints NO attach restriction, so per CR 2.e every unit in
#// play is a legal host regardless of controller or arena. This matters because the card is a pure
#// DRAWBACK — -2/-2 plus "When Played: exhaust attached unit", with no upside at all — so the enemy half of
#// the pool is the only reason to play it. Discriminating board: a friendly ground unit, a friendly space
#// unit, an enemy ground unit and an enemy space unit are all legal hosts.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: TWI_070

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# OnAnENEMYHost_ItExhaustsAndShrinksTHEIRUnit
#// TWI_070 Perilous Position — the play the card is actually for, and the one that was unreachable while
#// the pool was friendly-only. Attached to an ENEMY ready SOR_046 (3/7), the When Played exhausts it and
#// the -2/-2 leaves it a 1/5. The existing WhenPlayed_ExhaustHost puts it on a friendly unit, which is the
#// direction nobody would ever choose.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: TWI_070

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
