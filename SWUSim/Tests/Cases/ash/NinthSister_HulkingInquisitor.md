# DeclineSplit
#// ASH_148 Ninth Sister — the damage is OPTIONAL ("you MAY deal damage..."). The opponent still discards
#// (SOR_046, cost 4, their only card → auto), but P1 declines to assign any of the 4 damage (AnswerDecision:-).
#// Nothing takes damage; the enemy SEC_080 and the played ASH_148 are both undamaged.
## GIVEN
CommonSetup: rrk/rrk/{
  myResources:7;
  myhandCardIds:ASH_148;
  theirHandCardIds:SOR_046
}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2DISCARDCOUNT:1
P1NODECISION

---

# DiscardChoiceThenSplitDamage
#// PROBE C: after P1 plays ASH_148 (P2 holds 2 cards), is P2's discard decision present and whose turn?
## GIVEN
CommonSetup: rrk/rrk/{
  myResources:7;
  myhandCardIds:ASH_148;
  theirHandCardIds:SEC_142,SEC_144
}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
## EXPECT
P2HASDECISION
P1NODECISION
TURNPLAYER:2

---

# DiscardThenSplitDamage
#// ASH_148 Ninth Sister (Ground, 8/7, Overwhelm, cost 7) — When Played: an opponent discards a card; you
#// may deal damage equal to its cost divided among any number of units. P2 discards SOR_046 (cost 4, its
#// only card), and P1 assigns all 4 to SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: rrk/rrk/{myResources:7;handCardIds:ASH_148;theirHandCardIds:SOR_046}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:4
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2

---

# DivideAmongTwo
#// ASH_148 Ninth Sister — the cost damage is divided as you choose among any number of units. P2 discards
#// SOR_046 (cost 4); P1 splits it 2/2 across SEC_080 and SEC_135 (both survive with 2 damage each).
## GIVEN
CommonSetup: rrk/rrk/{myResources:7;handCardIds:ASH_148;theirHandCardIds:SOR_046}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:2
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# EmptyOpponentHand_NoOp
#// ASH_148 Ninth Sister — When Played with the opponent holding NO cards: there is nothing to discard,
#// so the whole "discard → deal damage" rider cleanly fizzles (no discard, no damage, no pending decision).
#// The unit itself still enters play.
## GIVEN
CommonSetup: rrk/rrk/{
  myResources:7;
  myhandCardIds:ASH_148
}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_148
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION
