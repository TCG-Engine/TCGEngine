# NoUnderworldInTop5_NothingPlayed
#// LAW_074 Maz Kanata — when the top 5 contain NO Underworld unit, nothing is played (the player picks
#// none). Maz's deck is all SOR_095 (Rebel/Trooper — not Underworld), so the search finds no valid target;
#// declining leaves the board with just Maz, resources untouched, and the 5 looked-at cards returned to
#// the deck (count unchanged at 6).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: LAW_074:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:3
P1DECKCOUNT:6

---

# OnAttackEnd_PlaysUnderworldReady
#// LAW_074 Maz Kanata (4/4) — When Attack Ends (she survived): search the top 5 for an Underworld unit
#// and play it; it costs 4 less and enters play ready. Maz attacks the base (survives — no counter), then
#// searches the top 5 (only SOR_247 is an Underworld unit) and plays it. SOR_247 (cost 2) costs 0 after
#// the -4 discount, so resources are untouched, and it enters READY.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: LAW_074:1:0
WithP1Deck: SOR_247
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_247

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:READY
P1RESAVAILABLE:3

---

# PlayedUnit_ReturnsToDeckAtRegroup
#// LAW_074 Maz Kanata — "At the start of the regroup phase, put that unit on the bottom of your deck (if
#// still in play)." Maz plays SOR_247 via her attack-end ability; at the regroup phase it returns to the
#// bottom of the deck (NOT the discard). After regroup only Maz remains in the arena; SOR_247 is back in
#// the deck (deck = 6 bottomed − 2 drawn at regroup = 4), and the discard is empty.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: LAW_074:1:0
WithP1Deck: SOR_247
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_247
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_074
P1DISCARDCOUNT:0
P1DECKCOUNT:4

---

# SearchPool_ExcludesUnaffordable
#// LAW_074 Maz Kanata — "When Attack Ends: if this unit survived, search the top 5 for an Underworld unit
#// and play it. It costs 4 resources less…" Same class of bug as Kelleran Beq (LOF_100): the offered pool
#// included Underworld units the player couldn't afford at the −4 price, so picking one just fizzled (the
#// resolve returns it to the deck bottom). The playable set must exclude unaffordable units.
#//
#// Maz (pre-placed, ready) attacks the enemy base and survives → her ability searches the top 5. P1 has 0
#// resources, so only a net-0 unit is playable:
#//   - LAW_257 Hidden Hand Supplier — cost 1 (neutral) → max(0, 1−4) = 0 net → affordable, MUST be offered.
#//   - LAW_262 Bank Job Fugitives — cost 6 (neutral) → max(0, 6−4) = 2 net → UNaffordable, must NOT be offered.
#// Both neutral → no aspect penalty, isolating the cost check. Decision left pending to read the offer.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_074:1:0
WithP1Deck: LAW_257
WithP1Deck: LAW_262

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:LAW_257
P1SEARCHPLAYABLENOT:LAW_262
