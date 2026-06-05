# Millennium Falcon (JTL_249) +1/+0 per Pilot bonus — two pilots attached.
#
# JTL_249 text: "You may play or deploy 1 additional Pilot on this unit."
# Plus the "+1/+0 for each Pilot on this unit" self-buff in ObjectCurrentPower.
#
# Dictionary values:
#   JTL_249: power=3, hp=4
#   JTL_245 (R2-D2): upgradePower=1, upgradeHp=1, pilotingCost=0 (Heroism)
#   JTL_100 (Poe):   upgradePower=2, upgradeHp=3, pilotingCost=2 (Command+Heroism)
#
# MF capacity: 1 (base) + 1 (innate CardPilotAddsCapacity) = 2.
# After R2-D2 attaches: capacity = 2 + 1 (R2 grant) = 3; count = 1.
# After Poe attaches: count = 2 < capacity 3. ✓
#
# Expected POWER = 3 (base) + 1 (R2 upgradePower) + 2 (Poe upgradePower) + 2 (per-pilot × 2) = 8.
# Expected HP    = 4 (base) + 1 (R2 upgradeHp)   + 3 (Poe upgradeHp)   + 0 (no per-pilot HP) = 8.
#
# Resources = 5. SOR_005 (Vigilance+Heroism) + SOR_022 (Command) covers R2-D2 Heroism + Poe Command+Heroism.
#
# Step 1 — Play JTL_245 R2-D2 (index 0) as Pilot onto MF:
#   canUnit = true (5 >= 1); canPilot = true (5 >= 0, MF present, count 0 < capacity 2)
#   → Both options → AnswerDecision:Pilot. One vehicle → auto-resolve (no explicit vehicle pick).
#   Resources consumed: 0; remaining: 5.
#
# Step 2 — Play JTL_100 Poe (now index 0) as Pilot onto MF:
#   canUnit = true (5 >= 4); canPilot = true (5 >= 2, count 1 < capacity 3)
#   → Both options → AnswerDecision:Pilot. One vehicle → auto-resolve.
#   Resources consumed: 2; remaining: 3.

## GIVEN
P1LeaderBase: SOR_005/SOR_022
P2LeaderBase: SOR_005/SOR_022
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP1Hand: JTL_245
WithP1Hand: JTL_100
WithP1SpaceArena: JTL_249:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_249
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_245
P1SPACEARENAUNIT:0:UPGRADE:1:CARDID:JTL_100
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:8
P1HANDCOUNT:0
P1RESAVAILABLE:3
