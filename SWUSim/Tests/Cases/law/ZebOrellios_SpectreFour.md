# WhenPlayedDealFive
#// LAW_045 Zeb Orellios (4/4, Sentinel) — When Played: deal 3 to a ground unit (5 instead if you control
#// a Command or Cunning unit). P1 controls SOR_095 (Command) -> deal 5 to the enemy SOR_046 (3/7).

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WhenPlayedDealThreeNoCommandCunning
#// LAW_045 Zeb Orellios — When Played: deal only 3 (not 5) when you control NO Command/Cunning unit
#// (Zeb himself is Vigilance/Aggression). Only ground units targetable: enemy space SOR_178 is untouched.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:0

---

# WhenPlayedDealFiveCunning
#// LAW_045 Zeb Orellios — When Played: deal 5 when you control a friendly Cunning unit (SEC_213 A-Wing).
#// Target enemy AT-ST (SOR_232 6/7) -> 5 damage.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP1SpaceArena: SEC_213:1:0
WithP2GroundArena: SOR_232:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WhenPlayedDeclineNoDamage
#// LAW_045 Zeb Orellios — When Played ability is optional ("you may"): decline -> no damage dealt.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0
