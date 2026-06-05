# SOR_246 You're My Only Hope — free-play upgrade: when base has 5 or less remaining HP the top
# card is played for FREE (ignoreCost=true). Top card is SOR_069 Resilient (cost 1, Upgrade).
# P1 has exactly 3 resources — just enough to pay for the event itself — leaving 0 after the event.
# A cost-1 upgrade is unaffordable on 0 resources (SWUPayCost would fail), so if ATTACH_UPGRADE
# incorrectly calls SWUPayCost the upgrade stays in deck. The only way the upgrade attaches is if
# ATTACH_UPGRADE skips payment entirely when ignoreCost=1 (Bug 2 fix). Sole friendly unit is
# SOR_095 Battlefield Marine (auto-selected as the target — no MZCHOOSE needed).

## GIVEN
CommonSetup: byw/byw/{myResources:3;myBaseDamage:25}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_246
WithP1Deck: SOR_069
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
