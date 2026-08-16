# NoDamage_Fizzle
#// SHD_054 Midnight Repairs — with no damaged units in play, the heal has no valid targets and fizzles
#// cleanly (no decision). The event still lands in the discard.
#// COVERAGE: offer=HealsAcrossBothSidesAndBothArenas (the pool is a distribution, not an mzID target
#//           choice, so SELECTABLE* cannot address it — the offer is asserted by its EFFECT instead: all
#//           four damaged units, friendly AND enemy, ground AND space, take an assignment in one answer)
#//           + this section as the negative (an UNDAMAGED unit is not a target at all — a board of nothing
#//           but undamaged units raises no decision) · reqboundary=RequestBoundary_DistributionStillApplies
#//           (pool size, per-target caps and the target list are re-read from serialized state) ·
#//           control=N/A (an event has no persistent object and the pool is built from the board at
#//           resolution, taking both seats' units regardless of control — there is no seat-bound
#//           attribution whose change could matter) · boundary pair=OverAssignToOneUnit_ClampsAtItsCurrent
#//           Damage (per-target cap: 8 assigned to a 2-damage unit heals 2 and the overflow is lost) vs
#//           HealFewerThanEight_UnassignedUnitsUntouched (total-pool underspend: 3 of 8 used, nothing
#//           topped up), plus this section at the empty-pool end · decline=HealZero_SoftPassLeavesEvery
#//           UnitDamaged (four legal targets, zero assigned) + LoneDamagedTarget_DoesNotAutoResolve /
#//           LoneDamagedTarget_MayStillBeDeclined (the sharp case — exactly ONE legal target must still
#//           prompt, because the AMOUNT remains a real choice and the player may decline it)

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
P1NODECISION

---

# HealsAcrossBothSidesAndBothArenas
#// SHD_054 — "from any number of units" spans BOTH sides of the table and BOTH arenas: the offered pool is
#// every DAMAGED unit anywhere, friendly or enemy. Four damaged units are seeded, 10 damage in total, and
#// P1 spends the whole 8-point pool across all four of them — 2 to friendly ground SEC_080, 2 to friendly
#// space SOR_237, 3 to ENEMY ground SOR_164 (Wampa, 4/5) and 1 to ENEMY space SOR_237. The two friendly
#// units end clean while the two enemies keep 1 damage each: 8 is a cap on the TOTAL, not a per-unit
#// amount, and the enemy units being healed at all is the proof that the pool is not seat-filtered.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:2
WithP1SpaceArena: SOR_237:1:2
WithP2GroundArena: SOR_164:1:4
WithP2SpaceArena: SOR_237:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:2,mySpaceArena-0:2,theirGroundArena-0:3,theirSpaceArena-0:1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1
P1NODECISION

---

# HealFewerThanEight_UnassignedUnitsUntouched
#// SHD_054 — "UP TO 8" means the pool may be left partly unspent. Same four damaged units as
#// HealsAcrossBothSidesAndBothArenas (10 damage on the board, all four in the offer), but P1 assigns only
#// 3 points and only to the enemy Wampa: it drops from 4 to 1 and the other three units keep every point
#// of damage they started with. 5 of the 8 points simply evaporate — an unassigned target is not silently
#// topped up, and the effect does not force the player to spend the rest.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:2
WithP1SpaceArena: SOR_237:1:2
WithP2GroundArena: SOR_164:1:4
WithP2SpaceArena: SOR_237:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1
P1NODECISION

---

# HealZero_SoftPassLeavesEveryUnitDamaged
#// SHD_054 — the soft-pass branch. "Up to 8 total from ANY NUMBER of units" includes ZERO units, so with
#// four legal targets on the table P1 may still assign nothing. Every unit keeps its damage exactly as it
#// was and no decision is left pending — healing 0 is a legal resolution of the ability, not a skipped or
#// stuck one. The event is still spent and lands in the discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:2
WithP1SpaceArena: SOR_237:1:2
WithP2GroundArena: SOR_164:1:4
WithP2SpaceArena: SOR_237:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:4
P2SPACEARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# LoneDamagedTarget_DoesNotAutoResolve
#// SHD_054 — an optional distribution must NOT auto-resolve down to its single legal target. Exactly ONE
#// damaged unit is on the board (SEC_080 with 2 damage) and an undamaged SOR_046 sits beside it, so the
#// pool has one member. The distribution is still raised and left PENDING with its full 8-point prompt:
#// the AMOUNT is a real choice (0, 1 or 2), so collapsing to "heal the only target for the maximum" would
#// take a decision away from the player. Nothing has been healed at the point the section ends.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:2
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Heal_up_to_8_damage_among_units
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# LoneDamagedTarget_MayStillBeDeclined
#// SHD_054 — resolution half of LoneDamagedTarget_DoesNotAutoResolve, and the reason the lone target must
#// stay interactive: with only one legal target P1 declines anyway and SEC_080 keeps all 2 of its damage.
#// An auto-resolved single target would have healed it without asking. The undamaged SOR_046 is untouched
#// throughout — it was never in the pool to begin with (the negative of that filter is NoDamage_Fizzle,
#// where every unit is undamaged and no decision is raised at all).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:2
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
P1DISCARDCOUNT:1
P1NODECISION

---

# OverAssignToOneUnit_ClampsAtItsCurrentDamage
#// SHD_054 — a unit can never be healed past 0 damage, and the overflow is NOT redistributed. P1 dumps the
#// entire 8-point pool onto SEC_080, which has only 2 damage: it is healed for 2 and the other 6 points are
#// simply lost. SOR_046 keeps all 3 of its damage even though it was in the offer and there was
#// nominally enough pool left to clear it — a heal is applied per assignment, never swept onward.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:2
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:8

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:3
P1DISCARDCOUNT:1
P1NODECISION

---

# RequestBoundary_DistributionStillApplies
#// SHD_054 — the distribution is answered in a LATER request than the one that built it, so the pool size,
#// the per-target caps and the whole target list have to live in serialized state rather than in a
#// transient global. Same four-unit fixture as HealsAcrossBothSidesAndBothArenas with a fresh-process
#// boundary inserted between the play and the assignment: the identical 2/2/3/1 split still lands on all
#// four units across both sides and both arenas.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SEC_080:1:2
WithP1SpaceArena: SOR_237:1:2
WithP2GroundArena: SOR_164:1:4
WithP2SpaceArena: SOR_237:1:2

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0:2,mySpaceArena-0:2,theirGroundArena-0:3,theirSpaceArena-0:1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1
P1NODECISION

---

# SplitHealAcrossUnits
#// SHD_054 Midnight Repairs (2-cost event, Vigilance/Vigilance) — "Heal up to 8 total damage from any
#// number of units." MZSPLITASSIGN up-to mode: heal 5 to SOR_046 (7 HP, 5 damage) and 2 to SEC_080 (3 damage
#// capped at its 2) — 7 total (≤ 8) → both end at 0 damage. Event lands in discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_054
WithP1GroundArena: SOR_046:1:5
WithP1GroundArena: SEC_080:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:5,myGroundArena-1:2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:DAMAGE:0
P1DISCARDCOUNT:1
