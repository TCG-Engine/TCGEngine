# RestoreGrantAndBounce
#// TS26_37 Abandoned the Order (Upgrade +1/+1, Cunning/Vigilance) — Attached unit loses the Jedi trait
#// and gains Restore 1. When Played: you may return a non-leader unit to its owner's hand. Attaching to
#// LAW_124 makes it 5/8 with Restore; the When-Played bounce returns the enemy SEC_080 to hand.
## GIVEN
CommonSetup: byk/rrk/{myResources:4;handCardIds:TS26_37}
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore

---

# AttachesToAJediUnitAndGrantsRestore
#// TS26_37 Abandoned the Order — the constant half on its natural target. LOF_093 Gungi is a Jedi; the
#// upgrade attaches (one upgrade on him) and he gains Restore 1. The bounce half is declined here so the
#// grant is the only thing under test.
#// NOTE: the "loses the Jedi trait" clause has no direct assertion in this harness — only the Restore
#// grant and the attachment are observable, so that half is covered by the trait-consumer cards' tests.

## GIVEN
CommonSetup: byk/rrk/{myResources:4;handCardIds:TS26_37}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_093:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# DecliningTheBounce
#// TS26_37 Abandoned the Order — "You MAY return a non-leader unit to its owner's hand". Declining leaves
#// P2's SEC_080 on the board; the upgrade still attaches and still grants Restore.

## GIVEN
CommonSetup: byk/rrk/{myResources:4;handCardIds:TS26_37}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
