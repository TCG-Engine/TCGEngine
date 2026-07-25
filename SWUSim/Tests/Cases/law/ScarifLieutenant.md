# WhenDefeatedExpRebel
#// LAW_142 Scarif Lieutenant (2/1) — When Defeated: give an Experience token to a friendly Rebel unit.
#// Attacks SOR_046 and dies; SOR_095 (Rebel) gets the Experience.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_142:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NGORExperienceToNewControllerRebel
#// LAW_142 Scarif Lieutenant — the When Defeated "Experience to a friendly Rebel" resolves for whoever
#// controls the unit at defeat. P2 plays No Glory, Only Results (JTL_043) to take control of P1's Scarif
#// Lieutenant and defeat it, so the Experience lands on P2's own Rebel, Fleet Lieutenant (SOR_240).

## GIVEN
CommonSetup: bbw/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_142:1:0
WithP2GroundArena: SOR_240:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_240
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
