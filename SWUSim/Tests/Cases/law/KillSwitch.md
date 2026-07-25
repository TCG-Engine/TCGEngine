# WhenPlayedExhaustsHost
#// LAW_127 Kill Switch (Upgrade, -1/-1, cost 2, Vigilance) — "When Played: Exhaust attached unit."
#// Played onto the ready SEC_080 → it becomes EXHAUSTED and is 2/2 (3/3 with -1/-1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_127

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# WhenPlayedDefeatsHostAtZeroHP
#// LAW_127 Kill Switch (-1/-1) — attaching it reduces the host's HP; if that drops the host to 0 remaining
#// HP, the host is defeated by the no-remaining-HP state-based check (no damage needed). Played onto the
#// friendly SOR_128 Death Star Stormtrooper (3/1 → 2/0): the unit is defeated, sending BOTH it and the
#// Kill Switch upgrade to P1's discard.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1Hand: LAW_127

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
