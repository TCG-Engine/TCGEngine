# EachFriendlyCapturesSameArena
#// SEC_131 Let's Talk (Event, Command, cost 9) — each friendly unit captures an enemy non-leader unit in
#//   the same arena. SOR_095 (ground) captures the lone enemy SOR_046 (ground).

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# TwoFriendliesEachCaptureGround
#// SEC_131 Let's Talk — "Each friendly unit captures an enemy non-leader unit in the same arena." Two
#//   friendly ground units each capture one of the two enemy ground units. The first captor is offered
#//   both enemies (choice); once one is taken the second captor auto-resolves onto the remaining enemy.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_037:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# FewerUnitsThanOpponent
#// SEC_131 Let's Talk — with fewer friendly units than the opponent, only as many captures happen as
#//   there are friendly units. P1's lone SOR_095 captures one of two enemy ground units (its choice);
#//   the other enemy remains.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_037:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# CrossArena_CapturesInOwnArena
#// SEC_131 Let's Talk — "in the same arena": a friendly ground unit captures an enemy ground unit and a
#//   friendly space unit captures an enemy space unit. Each captor has a single legal target in its own
#//   arena, so both auto-resolve with no prompt.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_066:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# SkipsFriendlyUnitsWithNoValidTargetInTheirArena
#// SEC_131 Let's Talk — "EACH friendly unit captures an enemy non-leader unit IN THE SAME ARENA." A
#// friendly unit whose arena holds no legal enemy target is simply skipped; it does not block or fizzle
#// the whole effect. P1 has a GROUND unit (with a legal ground target) and a SPACE unit (with no enemy in
#// space at all): the ground capture happens, the space unit captures nothing, and no prompt is left.
## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_131
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# EnemyLeaderUnitIsNotACaptureTarget
#// SEC_131 Let's Talk — the target must be an enemy NON-LEADER unit, so a deployed enemy leader in the
#// same arena is not capturable. P2's only ground presence is their deployed leader (TWI_002 Nute
#// Gunray); P1's ground unit therefore captures nothing and the leader stays on P2's board.
## GIVEN
CommonSetup: ggk/rrk/{myResources:9;theirLeader:TWI_002;theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_131
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# CostsThreeLessWhenAFriendlyUnitLeftPlay
#// SEC_131 Let's Talk — first clause: "If a friendly unit left play this phase, this event costs 3
#// resources less." P1's SOR_095 trades into P2's AT-AT Suppressor (SOR_039, 8/8) and dies, so the
#// discount is armed. P1 then plays Let's Talk with exactly 6 ready resources — it resolves (printed cost
#// 9 would be unaffordable), and P1's surviving space unit captures nothing since P2 has no space unit.
#// P1RESAVAILABLE:0 proves exactly 6 was paid.
## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Hand: SEC_131
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0

---

# ActivePlayerOwnsAUnitCapturedByTheOpponent_NoError
#// SEC_131 Let's Talk — resolving the mass capture must not choke when the ACTIVE player already OWNS a
#// unit that the OPPONENT is holding captive. P2's Discerning Veteran guards a P1-OWNED Battlefield Marine
#// (a captive subcard, not a unit in play); P1 then plays Let's Talk. The captive is NOT a capture target
#// (it is not a unit in the arena), so P1's ground unit captures the Veteran itself — and the P1-owned
#// captive rides along on it. Nothing errors and no prompt is left pending.
## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_120:1:0
WithP2GroundArenaCaptive: 0:SOR_095
WithP1Hand: SEC_131
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1NODECISION

---

# ExplicitPairing_EachCaptorTakesTheEnemyItWasOfferedFirst
#// SEC_131 Let's Talk — the captures aren't assigned for you: each friendly unit is offered every enemy
#// non-leader in its arena, so the player chooses the PAIRING, not just the set. Two friendly ground
#// units, two enemy ground units: the first captor (SOR_164 Wampa, arena index 0) takes SOR_232 AT-ST,
#// leaving SOR_239 Rebel Pathfinder to the second captor (LOF_064 Tauntaun). Asserted on the captives
#// themselves, not just on the counts — a capture loop that assigned enemies positionally would still
#// pass a count-only check.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: [SOR_164:1:0 LOF_064:1:0]
WithP2GroundArena: [SOR_239:1:0 SOR_232:1:0]
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_232
P1GROUNDARENAUNIT:1:CARDID:LOF_064
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_239
P1NODECISION

---

# ReversedPairing_SameBoardOppositeAssignment
#// SEC_131 Let's Talk — the mirror of the section above on an identical board: the first captor takes
#// the OTHER enemy instead, and the pairing flips. This is the control proving the pairing above came
#// from the player's choice rather than from board order.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: [SOR_164:1:0 LOF_064:1:0]
WithP2GroundArena: [SOR_239:1:0 SOR_232:1:0]
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_239
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_232
P1NODECISION

---

# TwoBountyCaptives_BothBountiesCollectedByTheCapturingPlayer
#// SEC_131 Let's Talk — capturing a Bounty unit triggers its Bounty, so a double capture hands the
#// capturing player BOTH bounties. P1's two ground units capture SHD_167 Wanted Insurgents ("Bounty —
#// Deal 2 damage to a unit") and SHD_027 Hylobon Enforcer ("Bounty — Draw a card"). P1 collects the
#// damage bounty (2 onto the still-uncaptured Hylobon) and then the draw bounty, ending with one card
#// in hand and both enemies captured.
#// Note SWUSim resolves Let's Talk one captor at a time, so the two bounties are offered in capture
#// order rather than as one simultaneous pair; the collected effects are the same either way.

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: [SOR_164:1:0 LOF_064:1:0]
WithP2GroundArena: [SHD_027:1:0 SHD_167:1:0]
WithP1Hand: SEC_131
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_167
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_027
P1NODECISION
