# OnAttack_ExhaustIndirect
#// JTL_227 Superheavy Ion Cannon — granted "On Attack: You may exhaust a non-leader unit the defending
#// player controls. If you do, deal indirect damage equal to its power to that player." JTL_069 carries
#// the cannon and attacks the P2 base; on attack P1 exhausts SOR_225 (power 2) and deals 2 indirect to P2,
#// who splits it (1 onto SOR_225, which dies; 1 onto the base). Base = 4 (combat) + 1 (indirect) = 5.
#//
#// (This exercises an indirect MZSPLITASSIGN fired INSIDE a mid-combat On Attack — previously deferred
#// as a known engine bug; the session-50 indirect-funnel rework resolved it. This test guards that.)

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_227
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:mySpaceArena-0:1,myBase-0:1

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:5

---

# OnAttack_ExhaustIndirectSplit
#// JTL_227 Superheavy Ion Cannon (upgrade on a Capital Ship) — granted On Attack: may exhaust an enemy
#// non-leader unit; if you do, deal indirect to the defending player equal to that unit's power. Host
#// JTL_069 (Capital Ship, 4 power; JTL_227 grants +0 power) attacks P2's base. P1 exhausts P2's SEC_080
#// (power 3) → 3 indirect, which P2 ASSIGNS across a unit AND the base: 1 to their 1-HP SOR_128 (defeats
#// it) + 2 to their base. SEC_080 stays in play (exhausted, undamaged). P2 base = 4 combat + 2 indirect = 6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_227
WithP2SpaceArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1
P2BASEDMG:6
P1NODECISION

---

# AttachesToADeployedLeaderCapitalShip
#// "Attach to a Capital Ship or Transport unit." — the restriction must read the LIVE trait of the unit in
#// play. HMW_004's deployed side is The Death Star, an Imperial Vehicle Capital Ship, so it is a legal
#// host even though the leader row prints Imperial Official (same family as
#// PlanetaryBombardment::DeployedLeaderCapitalShipCounts).

## GIVEN
CommonSetup: grw/grw/{
  myLeader:HMW_004;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_227
WithP1Resources: 20

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_227
P1HANDCOUNT:0


---

# Offer_DefendingPlayersNonLeaderUnitsAnyArena
#// JTL_227 Superheavy Ion Cannon — the granted "You may exhaust a NON-LEADER unit the DEFENDING PLAYER
#// controls" offer is scoped by controller and leader-ness but NOT by arena. Attacking from space, P1
#// must be offered the defender's ground trooper AND their space fighter, but neither P1's own units
#// (the attacking frigate, the friendly marine) nor the defender's DEPLOYED LEADER unit. The decision is
#// a "may" and is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021;
  theirLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_227
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:NOTLEADERUNIT
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# TwinSuns_OfferIsONLYTheDefendingSeatsUnits
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 during the "an opponent" sweep (Pass 0 seam 1).
#// "a non-leader unit THE DEFENDING PLAYER controls" names ONE seat. Two independent bugs above two
#// seats, and they point in OPPOSITE directions:
#//   (a) the OFFER was too WIDE — ZoneSearch('their…') fans out across every live opponent in Twin Suns,
#//       so P1 was offered seat 2's and seat 4's units while attacking seat 3. The pool GREW, so nothing
#//       looked broken and every 2-player section stayed green.
#//   (b) the indirect damage went to OtherPlayer($player) = seat 2 — a player not in the combat at all
#//       (and seat 1 for any far-seat attacker).
#// Root cause of both: CollectCombatStep1Triggers hands On-Attack triggers the ATTACKER's mzID and never
#// the defender's, so the handler could not learn who was defending. Fixed by publishing
#// SWU_CURRENT_DEFENDING_SEAT at attack declaration (read via SWUCurrentDefendingSeat()).
#// Here P1's cannon-equipped frigate attacks SEAT 3's base. Seats 2 and 4 each hold a non-leader unit
#// that MUST NOT be offered; only seat 3's ground trooper and space fighter may be.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent, "their*" IS the defending seat and
#//   OtherPlayer() is the right answer. The seat count IS the test, and the defender must be a FAR seat.
#// Mutation check: revert the offer to the plain their* union and this reds on the extra p2/p4 entries,
#// while all four 2-player sections above stay green. That asymmetry is the proof.

## GIVEN
CommonSetup: bbk/bbk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_227
WithP2GroundArena: SOR_128:1:0
WithP3GroundArena: SOR_128:1:0
WithP3SpaceArena: SOR_225:1:0
WithP4GroundArena: SOR_128:1:0
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>AttackSpaceArena:0:P3B

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:p3GroundArena-0&p3SpaceArena-0
