# Deployed_Raid2_MoreCardsInHand
#// SEC_010 Dedra Meero (deployed) — While you have more cards in hand than an opponent, this unit gains
#// Raid 2 (+2/+0 while attacking). P1 has 2 cards, P2 has 0 → Raid 2 active. SEC_010 (2/5) attacks the
#// enemy base for 2 + 2 = 4.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# LeaderAction_OpponentDeals2
#// SEC_010 Dedra Meero (leader) — the opponent ACCEPTS (YES) → its controller deals 2 damage to its own
#// unit, and P1 does NOT draw.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Deck: [SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:0
P1LEADER:EXHAUSTED

---

# LeaderAction_OpponentDeclines_Draw
#// SEC_010 Dedra Meero (leader) — Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may
#// deal 2 damage to it. If they don't, draw a card. Here the opponent DECLINES (NO) → P1 draws a card and
#// the enemy unit is undamaged.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Deck: [SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Deployed_NoRaid_WhenHandsAreEqual
#// SEC_010 Dedra Meero (deployed) — the Raid 2 is gated on having MORE cards in hand than the opponent.
#// With both hands at 2 the condition is false (equal is not more), so she attacks at her printed 2.
#// The negative that proves the gate in Deployed_Raid2_MoreCardsInHand is load-bearing.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021;
  theirHandCardIds:SOR_095,SOR_095
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Deployed_NoRaid_WhenHoldingFewerCards
#// SEC_010 Dedra Meero (deployed) — and strictly fewer cards is likewise not "more": P1 holds 1 card to
#// P2's 3, so no Raid and the base takes her printed 2.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021;
  theirHandCardIds:SOR_095,SOR_095,SOR_095
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# TwinSuns_TheYesNoGoesToTheCHOSENUnitsController
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, DETERMINED). SEC_010 was filed under PROMPT (47) and
#// that was WRONG: its text says "ITS CONTROLLER", not "an opponent". The seat is fully DETERMINED by the
#// unit the caster already chose, so this card must not prompt for a player.
#// ⚠ Adding a picker here would be actively harmful, not merely redundant: it would let the caster route
#//   the YES/NO to a player who does not control the unit.
#// The bug: OtherPlayer() sent the decision to ONE fixed seat, so above two seats the WRONG PLAYER was
#// asked whether to damage a unit that is not theirs — and the unit's real controller was never asked.
#//
#// P1 uses the leader Action and picks SEAT 3's unit. SEAT 3 must own the YES/NO and, on YES, damage
#// their own unit. Seat 2 also controls a unit (the seat the old code would have asked) and must be left
#// entirely alone — no decision, no damage.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent "its controller" IS OtherPlayer(). The seat count
#//   IS the test, and the chosen unit must sit on a FAR seat.
#// Mutation check: revert to OtherPlayer() and this reds while all four 2-player sections stay green.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010;
  myBase:JTL_019
}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 2
WithP1Deck: [SOR_095]
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:p3GroundArena-0
- P3>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2NODECISION
P1HANDCOUNT:0

---

# TwinSuns_Deployed_Raid2_ComparesAgainstTheFEWESTOpponentHand
#// ⚠ THE SEAT-COUNT CELL for the DEPLOYED side — added 2026-08-24 by the card-specific-rulings retro
#// sweep, which caught a MISS: SEC_010's front was converted in the first pass while this gate — living in
#// KeywordEffects.php, not the card file — still asked ONE seat. §5 checklist item 4 exists for exactly
#// this: a leader is not done until BOTH sides clear independently.
#// OFFICIAL RULING (10/31/2025): "If there are multiple opponents, the controlling player chooses which
#// one will be 'an opponent.'" The gate is strictly beneficial and the chosen opponent is never referenced
#// again, so the controller's optimal answer is forced — auto-resolved rather than prompted (I2).
#//
#// ⚠⚠ NOTE THE DIRECTION, IT IS THE MIRROR OF LAW_083. "MORE cards than an opponent" is satisfied by the
#// opponent with the FEWEST cards (compare against the MINIMUM). LAW_083's "FEWER than an opponent"
#// compares against the MAXIMUM. Getting the quantifier backwards silently inverts the card.
#//
#// P1 holds 2 cards. SEAT 2 — the only seat the old code looked at — holds 3, so the old gate FAILED.
#// SEAT 4 holds 1, so P1 DOES have more than *an* opponent and Raid 2 is active: Dedra (2/5) attacks
#// SEAT 3's base for 2 + 2 = 4. Under the old code she would hit for 2.
#// ⚠ Raid is a WHILE-ATTACKING bonus, so it is invisible to a static POWER assertion — the attack itself
#//   is the only way to observe it (a first attempt asserted resting power and failed for that reason).
#// ⚠ A 2-player version CANNOT FAIL — one opponent is the only comparison there is.
#// Mutation check: revert to OtherPlayer() and this reds; change min to max and it reds too.

## GIVEN
CommonSetup: brk/bbk/{myLeader:SEC_010:1:1:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046]
WithP2Hand: [SOR_095 SOR_046 SEC_080]
WithP3Hand: [SOR_095 SOR_046 SEC_080]
WithP4Hand: [SOR_095]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATCOUNT:4
P3BASEDMG:4
