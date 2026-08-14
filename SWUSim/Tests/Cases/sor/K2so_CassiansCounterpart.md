# WhenDefeated_DealsThreeToBase
#// SOR_145 K-2SO (4/4, Overwhelm) — "When Defeated: For each opponent, choose one: either deal 3 damage
#// to that player's base, or that player discards a card from their hand." K-2SO attacks a 4/7 wall and
#// dies to the 4 counter-damage; its controller (P1) chooses Base → 3 damage to P2's base.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Base

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenDefeated_OpponentDiscards
#// SOR_145 K-2SO — the other branch of the When Defeated choice: P1 chooses Discard, so the opponent
#// discards a card from their hand (here their only card, auto-discarded). The base is untouched.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Discard

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:0

---

# NoGloryOnlyResults_NewControllerResolvesIt
#// SOR_145 K-2SO — a take-control-then-defeat (JTL_043) defeats the unit under the TAKER's control,
#// so the TAKER resolves the When Defeated and "for each opponent" is read from the TAKER's seat:
#// P1 takes and defeats P2's K-2SO, chooses Base, and the 3 damage lands on P2's base. K-2SO still
#// goes to its OWNER P2's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: SOR_145:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:Base

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:3
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_145
