# OnAttack_EvenCost_NoExp
#// JTL_200 Shuttle Tydirium — if the milled card has an even cost, no Experience is offered. Deck top
#// SOR_095 (cost 2, even) is milled; no decision follows.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_200:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P2BASEDMG:2
P1NODECISION

---

# OnAttack_OddCost_Exp
#// JTL_200 Shuttle Tydirium — On Attack: Discard a card from your deck. If it has an odd cost, you may
#// give an Experience token to another unit. Deck top SOR_225 (cost 1, odd) is milled, so P1 gives an
#// Experience (+1/+1) to SOR_095 (3/3 → 4/4). JTL_200 attacks the base for 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_200:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_225

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P2BASEDMG:2

---

# Offer_AnotherUnit_ExcludesSelf
#// JTL_200 Shuttle Tydirium — "you may give an Experience token to ANOTHER unit." "Another" is the only
#// restriction: the pool must exclude Shuttle Tydirium ITSELF while still containing every other unit,
#// friendly or enemy, in either arena. Deck top SOR_225 (cost 1, odd) makes the offer happen; the board holds
#// a second friendly space unit (SOR_237, mySpaceArena-1), a friendly ground unit (SOR_095) and an enemy
#// ground unit (SOR_046) — all three offered — while mySpaceArena-0 (the Shuttle) must not appear.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_200:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_225

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HASDECISION
P1SPACEARENAUNIT:0:CARDID:JTL_200
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-1&theirGroundArena-0

---

# ControlChange_MillsTheNewControllersDeck
#// JTL_200 Shuttle Tydirium — "On Attack: Discard a card from YOUR deck." Under SOR_224 Change of Heart the
#// NEW controller resolves the ability, so "your deck" is the THIEF's deck, not the original owner's. P1
#// steals P2's Shuttle (P2 also controls SOR_046 so the take-control choose stays interactive) and attacks
#// P2's base with it. One card must leave P1's deck for P1's discard (SOR_224 + the milled Battlefield
#// Marine = 2) while P2's deck and discard are untouched. Every seeded card costs 2 (even), so no
#// Experience offer follows on either reading.

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_224
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP2SpaceArena: JTL_200:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_200
P2BASEDMG:2
P1DECKCOUNT:1
P1DISCARDCOUNT:2
P2DECKCOUNT:3
P2DISCARDCOUNT:0
P1NODECISION
