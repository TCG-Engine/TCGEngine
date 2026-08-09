# AttackEnd_GiveThreeAdvantage_ToAnotherUnit
#// ASH_036 Rukh (Ground, 1/5, Support) — When Attack Ends: if the defending unit was defeated, you may give
#// 3 Advantage tokens to a unit. The tokens may land on ANY friendly unit, not just Rukh. Rukh kills
#// SOR_128 (3/1, deals 1), takes 3 counter (survives), then gives 3 Advantage to a friendly space unit.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_036:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# AttackEnd_GiveThreeAdvantage_MayDecline
#// ASH_036 Rukh — the give-Advantage rider is a "you may" even when the defender IS defeated. Rukh kills
#// SOR_128 but P1 declines; no Advantage tokens are placed anywhere.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_036:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# Support_LendsAttackEndGiveThreeAdvantage
#// ASH_036 Rukh (Support) — the lent "When Attack Ends: you may give 3 Advantage" now fires on the BORROWING
#// attacker's kill (the SUPPORT_GRANT marker is captured before it expires). Rukh is played; SOR_046 supports,
#// kills SOR_108, and the lent rider gives 3 Advantage to SOR_046.
## GIVEN
CommonSetup: grk/grk/{myResources:8;handCardIds:ASH_036}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_108:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# AttackEndFiresEvenWhenRUKHDiesInTheAttack
#// ASH_036 Rukh — CR 16.c: "When Attack Ends" fires even when the attacker is defeated by combat damage.
#// Rukh (1/5, pre-damaged to 4) attacks the 3/1 SOR_128: his 1 power defeats it, and its 3 power finishes
#// him. His attack-end condition ("if the defender was defeated") is met, so the 3 Advantage tokens are
#// still handed out — here to the surviving SOR_046, which ends holding all three.
#// Discriminating: under the old blanket survival gate a Rukh who traded with his target gave nothing.
#// Rukh's handler ignores its mzID entirely, so it needs no live self-reference to resolve.

## GIVEN
CommonSetup: ggk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [ASH_036:1:4 SOR_046:1:0]
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
