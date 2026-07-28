# ActionSetPrintedHpTo1
#// LAW_126 Adventurer Sniper Rifle (Upgrade) — grants "Action [Exhaust]: Choose an undamaged non-leader
#// ground unit. Its printed HP is considered to be 1 for this phase." SEC_080 wears the rifle and uses
#// the action targeting the enemy SOR_046 (3/7, undamaged); its HP becomes 1. The host SEC_080 exhausts.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ActionSetFriendlyHpTo1
#// LAW_126 Adventurer Sniper Rifle — the granted action may target a FRIENDLY undamaged non-leader ground
#// unit too. Host SEC_080 wears the rifle; the action targets the friendly SOR_095 (3/3, undamaged) whose
#// printed HP becomes 1 this phase. The host exhausts.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ActionSetOwnHpTo1
#// LAW_126 Adventurer Sniper Rifle — the host may target itself. SEC_080 (3/3, undamaged) wears the rifle
#// and uses the action on itself; its printed HP becomes 1 this phase and it exhausts from the cost.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AttachNonVehicleOnly
#// LAW_126 Adventurer Sniper Rifle — "Attach to a non-Vehicle unit." A friendly Vehicle (SOR_232 AT-ST)
#// is NOT a legal host; only the non-Vehicle SEC_080 is selectable.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_232:1:0
WithP1Hand: LAW_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
