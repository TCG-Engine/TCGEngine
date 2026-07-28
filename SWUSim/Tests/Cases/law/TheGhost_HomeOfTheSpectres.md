# WhenPlayedExpShieldTwoUnits
#// LAW_069 The Ghost (4/4) — When Played: give an Experience + Shield token to a unit (to each of up to
#// 2 units if you control a Vigilance or Aggression unit). P1 controls SOR_063 (Vigilance) -> up to 2;
#// give Exp+Shield to both SOR_063 (ground) and The Ghost (space).

## GIVEN
CommonSetup: gyw/bgw/{myResources:6}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_069
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayedSingleTargetEnemyUnit
#// LAW_069 The Ghost — When Played with NO friendly Vigilance or Aggression unit in play, it gives an
#// Experience + Shield token to a single unit, and that unit MAY be an enemy. P1 controls only The Ghost
#// (Command/Cunning/Heroism), so the "up to 2" clause is off; give the tokens to the enemy SEC_080.

## GIVEN
CommonSetup: gyw/bgw/{myResources:6}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayedAggressionChooseOne
#// LAW_069 The Ghost — controlling an Aggression unit (SOR_164 Wampa) enables the "up to 2 units" mode,
#// but the player may still choose just one. Give Exp+Shield only to the Wampa; The Ghost gets nothing.

## GIVEN
CommonSetup: gyw/bgw/{myResources:6}
WithP1GroundArena: SOR_164:1:0
WithP1Hand: LAW_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_069
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayedDeclineWithConditionMet
#// LAW_069 The Ghost — the When Played ability is optional. Even with the "up to 2" mode active (Aggression
#// Wampa in play), the player may choose nothing: no unit receives Experience or Shield tokens.

## GIVEN
CommonSetup: gyw/bgw/{myResources:6}
WithP1GroundArena: SOR_164:1:0
WithP1Hand: LAW_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
