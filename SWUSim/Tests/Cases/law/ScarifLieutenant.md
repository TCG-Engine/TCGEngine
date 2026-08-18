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

---

# Offer_FriendlyRebelsOnly_NonRebelsAndEnemyRebelsExcluded
#// LAW_142 Scarif Lieutenant — "Give an Experience token to a FRIENDLY REBEL unit" carries both a
#// controller word and a trait word, so the pool must apply both filters. Discriminating board: two
#// friendly Rebels (SOR_095, SOR_240) are IN; the friendly SEC_080 Imperial Dark Trooper is OUT on the
#// trait; and the enemy SOR_046 Consular Security Force — which IS a Rebel — is OUT on the controller.
#// That enemy Rebel is the point of the board: it is the only card that can tell a trait-only filter from
#// a correct one. Two legal targets keep the choose genuinely pending (WhenDefeatedExpRebel has a single
#// Rebel and auto-resolves, so it proves nothing about the pool).

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_142:1:0 SOR_095:1:0 SOR_240:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENAUNIT:2:CARDID:SEC_080

---

# NoFriendlyRebelSurvives_TriggerFizzlesSilently
#// LAW_142 Scarif Lieutenant — the negative case. The Lieutenant is the only friendly Rebel on the board,
#// so once it dies there is nothing left to receive the token: no decision is raised, the friendly
#// non-Rebel SEC_080 gets nothing, and the enemy Rebel it died to gets nothing either. Without this
#// section a pool that had quietly fallen back to "any unit" would still pass every other section here.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_142:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
