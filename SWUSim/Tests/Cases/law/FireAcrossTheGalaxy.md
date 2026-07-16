# AnyNumber_TwoUnits
#// LAW_256 — "use ANY NUMBER": re-resolve multiple Spectre units' When-Played abilities, each fully
#// before the next. P1 controls LAW_055 (1/2) and SOR_050 The Ghost ("When Played: may give a Shield to
#// another Spectre unit"). Choosing both: LAW_055's When-Played gives it 2 Experience (it now controls a
#// Vigilance/Cunning unit — The Ghost — so the "2 instead" branch applies), and The Ghost's When-Played
#// gives a Shield to LAW_055. Result: LAW_055 = 1+2 = power 3, with 2 Experience + 1 Shield (3 subcards).

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: LAW_055:1:0
WithP1GroundArena: SOR_050:1:0
WithP1Hand: LAW_256

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_055
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3

---

# ChooseNone_NoOp
#// LAW_256 — "use ANY NUMBER" includes zero: choosing none re-resolves nothing. LAW_055 stays 1/2 with
#// no Experience token.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: LAW_055:1:0
WithP1Hand: LAW_256

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_055
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ReusesSpectreWhenPlayed
#// LAW_256 Fire Across the Galaxy (Event, Heroism) — "Use any number of 'When Played' abilities on
#// friendly Spectre units." P1 controls LAW_055 (1/2 Spectre, "When Played: give it an Experience token").
#// Playing Fire Across the Galaxy and choosing LAW_055 re-resolves its When-Played → it gains an Experience
#// token (no Cunning/Vigilance unit controlled, so 1 token) → 2/3.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: LAW_055:1:0
WithP1Hand: LAW_256

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_055
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
