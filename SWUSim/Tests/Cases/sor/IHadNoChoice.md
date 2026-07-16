# NoUnits_Fizzle
#// SOR_187 I Had No Choice — with no non-leader units in play the event fizzles cleanly (no decision)
#// and goes to the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_187
WithP1Resources: 9

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION

---

# OneUnit_ReturnsToHand
#// SOR_187 I Had No Choice — when the caster chooses only ONE unit, the opponent's "choose 1 of those"
#// is forced and there is no "other," so that unit just returns to its owner's hand (no deck-bottom).
#// P1 picks SEC_080; it returns to P2's hand, SOR_128 stays in play.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2HANDCOUNT:1
P2DECKCOUNT:1
P1DISCARDCOUNT:1

---

# TwoUnits_OpponentChoosesOne
#// SOR_187 I Had No Choice (event, cost 7) — "Choose up to 2 non-leader units. An opponent chooses 1
#// of those units. Return that unit to its owner's hand and put the other on the bottom of its owner's
#// deck." P1 picks both of P2's units; P2 chooses which is saved to hand (myGroundArena-0 = SEC_080),
#// so the other (SOR_128) is buried on the bottom of P2's deck. SOR_002 covers Villainy only, so the
#// Cunning aspect adds +2 (cost 9) — WithP1Resources:9.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2DECKCOUNT:2
P1DISCARDCOUNT:1
