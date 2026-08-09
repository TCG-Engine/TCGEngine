# ModalCreateDroid
#// TS26_49 Separatist Council (Unit 2/6, cost 4) — When Played: choose one. "CreateDroid" mode creates a
#// Battle Droid token (TS26_T01).
## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_49}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:CreateDroid
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T01

---

# ModalGiveExpToDroid
#// TS26_49 Separatist Council — "GiveExp" mode gives 2 Experience tokens to a Battle Droid token. The
#// existing Battle Droid token (TS26_T01, 1/1) becomes 3/3 (auto-resolved as the only Battle Droid).
## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_49}
WithP1GroundArena: TS26_T01:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:GiveExp
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# TheONATTACKWindowOffersTheSameChoice
#// TS26_49 Separatist Council — the ability reads "When Played/ON ATTACK", two windows on one card. The
#// Council attacks P2's base for 2 and the modal choice fires again, creating a second Battle Droid.

## GIVEN
CommonSetup: ggk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_49:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:CreateDroid

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T01
P2BASEDMG:2

---

# ChoosingTheExperienceModeWithNoBattleDroidDoesNothing
#// TS26_49 Separatist Council — the second mode needs a Battle Droid token to put the Experience on. With
#// none in play, choosing it resolves to nothing: the Council enters alone and no token appears.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_49}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:GiveExp

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_49
