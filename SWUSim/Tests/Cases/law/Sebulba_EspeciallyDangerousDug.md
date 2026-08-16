# FrontGrantRaid1
#// LAW_012 Sebulba (leader front) — "Action [Exhaust, discard a card from your deck]: A friendly unit
#// gains Raid 1 for this phase." Grant Raid 1 to SEC_080, then it attacks the base for 3+1 = 4 (Raid
#// gives +1/+0 while attacking).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# FrontNoFriendlyUnits_UsableAnyway
#// LAW_012 Sebulba (front) — "Action [Exhaust, discard a card from your deck]: A friendly unit gains
#// Raid 1." With NO friendly units in play the ability has no legal target, but the Action is still usable
#// (CR 6.4.587.c "Use it anyway"): the costs are still paid — Sebulba exhausts and the top of the deck is
#// discarded (deck 2 -> 1).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1DECKCOUNT:1

---

# FrontEmptyDeck_CannotUse
#// LAW_012 Sebulba (front) — the "discard a card from your deck" portion of the cost cannot be paid with
#// an empty deck, so the Action is unavailable. Sebulba stays ready and the friendly SEC_080 gains NO
#// Raid 1: it then attacks the base for its base power 3 (not 4).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:READY
P2BASEDMG:3

---

# DeployedOnAttackDiscardsFromDeck
#// LAW_012 Sebulba (deployed) — "On Attack: Discard a card from your deck." Non-optional, no prompt.
#// Deployed Sebulba attacks the base (3 damage: 2 power + Raid 1) and the top card of P1's deck is discarded (deck 2 -> 1);
#// the opponent's deck is untouched.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012:1:1:1;
  myBase:SOR_028;
  theirBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_128
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:1
P2BASEDMG:3

---

# DeployedOnAttackEmptyDeck_NoOp
#// LAW_012 Sebulba (deployed) — the On Attack discard does nothing if P1's deck is empty. Sebulba attacks
#// the base for 3 (2 power + Raid 1) and there is no card to discard (deck stays at 0).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012:1:1:1;
  myBase:SOR_028;
  theirBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:0
P2BASEDMG:3

---

# FrontGrantRaid1_SurvivesTheRequestBoundary
#// LAW_012 — request-boundary guard for FrontGrantRaid1. Production starts a FRESH process on every
#// answered decision, so the leader action's pending payload (which grant, Raid 1, and the "for this
#// phase" duration it will stamp) has to be reconstructed from serialized gamestate rather than an
#// in-memory continuation global — and the granted Raid then has to be read back by a LATER action, the
#// attack, from that same serialized state.
#// FrontGrantRaid1's own fixture has exactly one friendly unit, so its grant AUTO-RESOLVES and a boundary
#// there would be vacuous; a second friendly unit (SOR_095) is seeded purely to make the choose real
#// (MZCHOOSE [myGroundArena-0&myGroundArena-1]). SEC_080 is still the one granted, and still attacks the
#// base for 3+1 = 4.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
