# DealIfVillainy
#// LAW_137 Ruthless Duo (Command,Villainy, cost 4) — When Played: if you control another Villainy unit,
#// you may deal 2 damage to a ground unit. P1 controls SEC_080 (Villainy) -> deal 2 to enemy SOR_046.

## GIVEN
CommonSetup: grk/bgw/{myResources:4}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_137

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoTriggerWithoutOtherVillainy
#// LAW_137 Ruthless Duo — the "deal 2 damage" clause is gated on controlling ANOTHER Villainy unit.
#// With no other friendly Villainy unit in play, the ability does not trigger and the enemy takes 0.

## GIVEN
CommonSetup: grk/bgw/{myResources:4}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_137

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_137
P2GROUNDARENAUNIT:0:DAMAGE:0
