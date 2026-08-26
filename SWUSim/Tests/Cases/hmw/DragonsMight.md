# DefeatsAnEnemyFourPowerUnit
#// HMW_102 Dragon's Might — Event, cost 4, [Vigilance], trait Innate, non-unique.
#// Text: "Defeat a non-leader unit with 4 or less power."
#// COVERAGE: offer=FivePowerUnitIsNotSelectable / DeployedLeaderUnitIsNotSelectable /
#//           DarksaberHostIsNotSelectable — three pending-pool assertions, one per exclusion the card
#//           prints (the power threshold, and the two ways a unit can BE a leader unit) ·
#//           decline=N/A — the text has no "you may" and no "up to", so this is a mandatory MZCHOOSE
#//           and there is nothing to decline. The no-target case is NoLegalTarget_CleanFizzle ·
#//           ⚠ every offer section seeds ONE excluded target plus TWO legal ones: a mandatory
#//           choose AUTO-RESOLVES at a single target, so "one excluded + one legal" leaves no
#//           pending decision to inspect and the assertion fails for the wrong reason ·
#//           boundary=DefeatsAnEnemyFourPowerUnit (exactly 4 dies) + FivePowerUnitIsNotSelectable
#//           (5 is out of reach), doubled on the DYNAMIC side by BuffedToFivePower_NoLongerSelectable
#//           and DebuffedToFourPower_BecomesSelectable ·
#//           control=StolenUnitGoesToItsOwnersDiscard — a defeated unit leaves to its OWNER's discard,
#//           not the defeating player's, and not the controller's ·
#//           reqboundary=RequestBoundary_TargetSurvivesTheBoundary ·
#//           modes=2P only (no player reference and no friendly/enemy wording — "a non-leader unit"
#//           names neither side, which is exactly why FriendlyUnitIsALegalTarget exists).
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md. Read straight from the two released
#//   halves this card combines: SOR_077 Takedown ("Defeat a unit with 5 or less remaining HP" — the
#//   threshold-filtered defeat) and SOR_078 Vanquish ("Defeat a non-leader unit" — the leader
#//   exclusion). The only new decision is that the metric is POWER, which is CURRENT power, not
#//   printed — see the buffed/debuffed pair.
#//
#// LAW_124 (printed 4 power) is exactly on the threshold and is defeated. It is a non-token card, so
#// it goes to its owner's discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:LAW_124
P1DISCARDCOUNT:1
P1NODECISION

---

# FivePowerUnitIsNotSelectable
#// HMW_102 — the boundary partner. LOF_063 (5/5) is one power over the line and must be absent from
#// the pool; LAW_124 (4/7) is on it and must be present. The decision is left PENDING so the POOL is
#// asserted rather than an outcome — answering a target proves only that the branch works.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: LOF_063:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-2

---

# BuffedToFivePower_NoLongerSelectable
#// HMW_102 — "4 or less power" reads CURRENT power, not printed. SOR_095 Battlefield Marine is a
#// printed 3/3, but SOR_120 Academy Training (+2/+2) puts it at 5 and out of reach. A printed-power
#// implementation would happily defeat it.
#// A second, genuinely legal target (LAW_124) is seeded so the pool has something to contain — with
#// only the excluded unit on the board the event would fizzle and this would pass vacuously.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-1&theirGroundArena-2

---

# DebuffedToFourPower_BecomesSelectable
#// HMW_102 — the mirror of the buffed case, and the half that a printed-power implementation gets
#// wrong in the OTHER direction. LOF_063 is a printed 5/5 — excluded in FivePowerUnitIsNotSelectable —
#// but carrying a -1/-0 for the phase it is at 4 and becomes a legal target, and is defeated.
#// The two sections share a fixture and differ only by the debuff token, so between them they pin the
#// reading to ObjectCurrentPower.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: LOF_063:1:0:SWUDEBUFF-1-0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:LOF_063

---

# FriendlyUnitIsALegalTarget
#// HMW_102 — "a non-leader unit" carries NO friendly/enemy qualifier, so it reaches the caster's own
#// board. P1 defeats its OWN LAW_124; the enemy's is left alone, which proves the pick really went to
#// the friendly one.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1DISCARDCOUNT:2

---

# SpaceUnitIsALegalTarget
#// HMW_102 — no arena restriction either. JTL_069 Munificent Frigate (4/7) sits in the SPACE arena and
#// is a legal target; an implementation that collected only the ground arenas would fizzle here.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:JTL_069

---

# TokenUnitIsALegalTargetAndCeases
#// HMW_102 — a Token Unit is a unit. TWI_T01 Battle Droid (1/1) is well under the threshold and is a
#// legal target; a target pool spelled ['Unit'] rather than the full Unit / Token Unit / Leader Unit
#// triplet would silently miss it.
#// A defeated TOKEN ceases to exist rather than going to a discard pile, so the opponent's discard
#// stays EMPTY — that assertion is what distinguishes "the token was defeated" from "nothing happened".

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# DeployedLeaderUnitIsNotSelectable
#// HMW_102 — "NON-LEADER unit". A deployed ASH_011 Cad Bane is a 4-power unit, comfortably inside the
#// threshold, so the power filter cannot be what excludes it — only the leader-unit check can.
#// LAW_124 is seeded as the legal alternative so there is a pool to inspect.
#// ⚠ A deployed leader appends AFTER the plain WithP2GroundArena units, so Cad Bane is at index 1.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;theirLeader:ASH_011:1:1}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# DarksaberHostIsNotSelectable
#// HMW_102 — the SHARP form of the leader exclusion: a unit whose printed CardType is "Unit" but which
#// IS a leader unit right now. ASH_135 The Darksaber reads "Attached unit IS A LEADER UNIT", so the
#// check has to read the live object (IsLeaderUnit), never the printed type.
#// ⚠ THE FIXTURE IS LOAD-BEARING AND WAS CHOSEN FOR ITS ARITHMETIC. The Darksaber is +4/+2, so almost
#//   any host ends up ABOVE 4 power and would be excluded by the threshold instead — the section would
#//   then pass without the leader check existing at all. SHD_028 Doctor Pershing is a static 0/5
#//   unique non-Vehicle unit, so with the Darksaber attached he is at EXACTLY 4 power: inside the
#//   threshold, and excluded only for being a leader unit.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: SHD_028:1:0
WithP2GroundArenaUpgrade: 0:ASH_135
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_028
P2GROUNDARENAUNIT:0:POWER:4
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-1&theirGroundArena-2

---

# NoLegalTarget_CleanFizzle
#// HMW_102 — every unit on the board is over the threshold, so the event resolves to nothing: no
#// prompt, no defeat, and the event itself still goes to the discard (the cost was paid; an ability
#// that cannot find a target still resolves as far as it can).

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: LOF_063:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# StolenUnitGoesToItsOwnersDiscard
#// HMW_102 — ownership versus control. P1 CONTROLS a LAW_124 that P2 OWNS. Defeating it must put the
#// card in the OWNER's discard (P2's), not in the discarding player's or the controller's — P1's
#// discard holds only the event. Asserting BOTH piles is what separates a correct implementation from
#// one that happens to pick the right seat.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP1GroundArenaControlled: LAW_124:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:LAW_124

---

# RequestBoundary_TargetSurvivesTheBoundary
#// HMW_102 — the request-boundary cell. The target choose is a real interactive decision, so in
#// production the answer arrives in a fresh process. Two legal targets are seeded so a decision really
#// is pending; the pick must still resolve to the unit it named after the boundary.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_102
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P1NODECISION
