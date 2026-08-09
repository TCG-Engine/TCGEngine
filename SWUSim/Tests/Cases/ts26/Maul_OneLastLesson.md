# WhenPlayedAttackWithAnother
#// TS26_30 Maul (Unit 5/4, cost 4) — Sentinel. When Played: you may attack with another unit. Playing
#// Maul lets SEC_080 attack the enemy base for 3.
## GIVEN
CommonSetup: ryk/rrk/{myResources:4;handCardIds:TS26_30}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3

---

# DecliningTheFollowUpAttack
#// TS26_30 Maul — "You MAY attack with another unit". Declining leaves P2's base untouched; Maul is in
#// play alongside the unit that did not swing.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;handCardIds:TS26_30}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:2

---

# MaulHimselfIsNotAnEligibleAttacker
#// TS26_30 Maul — "attack with ANOTHER unit" excludes the Maul who just arrived, which also keeps him
#// from swinging the turn he enters play. Only the two units already on the board are offered.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;handCardIds:TS26_30}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
