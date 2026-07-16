# Copy_BasicStatsAndTrait
#// TWI_116 Clone (Unit, 0/0, cost 7, Command) — "You may have this unit enter play as a copy of a
#// non-leader, non-Vehicle unit in play, except it gains the Clone trait and is not unique. (Only the
#// card's printed attributes are copied.)" Clone copies an enemy SOR_095 (3/3, Rebel/Trooper): it enters
#// play AS SOR_095 — 3/3, with SOR_095's printed traits — plus the gained Clone trait, and is not a leader.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:HASTRAIT:Clone
P1GROUNDARENAUNIT:0:HASTRAIT:Rebel
P1GROUNDARENAUNIT:0:NOTLEADERUNIT
P2GROUNDARENACOUNT:1

---

# Copy_NonUnique_NoUniquenessDefeat
#// TWI_116 Clone — the copy "is not unique." P1 already controls a real SOR_035 (unique). P1 plays Clone
#// copying that SOR_035 → P1 now controls TWO SOR_035 units, but because the Clone copy is non-unique the
#// uniqueness rule does NOT force a defeat: both survive. (Without the non-unique treatment, one would be
#// defeated and the count would be 1.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP1GroundArena: SOR_035:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:1:CARDID:SOR_035
P1GROUNDARENAUNIT:1:HASTRAIT:Clone

---

# Copy_PersistsAcrossUndoCycle
#// TWI_116 Clone — the copy identity + Clone flag are DURABLE (persist across the per-action gamestate
#// serialization). Clone copies an enemy SOR_095, then an UndoCycle (SaveVersion→LoadVersion round-trip)
#// reconstructs every zone object from its serialized form. The reloaded unit is still SOR_095 (3/3) and
#// still has the Clone trait — proving the IsClone field serializes. (Also ruling: the copy persists as a
#// modified copy regardless of the original; the reload does not revert it.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>UndoCycle
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HASTRAIT:Clone

---

# Copy_SpaceUnit_EntersSpaceArena
#// TWI_116 Clone — arena type is a printed attribute, so copying a SPACE unit makes Clone enter the SPACE
#// arena. Clone copies an enemy LOF_069 (2/7 space Creature with Sentinel): it enters P1's space arena as
#// LOF_069 — gaining Sentinel (a copied keyword) and the Clone trait — and P1's ground arena stays empty.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2SpaceArena: LOF_069:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LOF_069
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:HASTRAIT:Clone
P1GROUNDARENACOUNT:0

---

# Copy_WhenPlayedFires
#// TWI_116 Clone — the copied card's abilities are part of its printed attributes (CR 9.2), so when Clone
#// enters play AS a copy, the copied card's "When Played" fires on that entry. Clone copies SHD_160 (2/1,
#// "When Played: Deal 1 damage to each base") → as Clone enters as SHD_160, its When Played deals 1 to
#// EACH base. Clone is now a 2/1 SHD_160.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SHD_160:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:1
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:SHD_160
P1GROUNDARENAUNIT:0:HASTRAIT:Clone

---

# Decline_EntersZeroZeroAndDies
#// TWI_116 Clone — the copy is optional ("You MAY"). If P1 declines the copy, Clone enters play as its
#// printed self: a 0/0 unit, which is immediately defeated by the 0-HP state check. It goes to P1's
#// discard as TWI_116. The enemy SOR_095 that could have been copied is untouched.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDUNIT:0:CARDID:TWI_116
P2GROUNDARENACOUNT:1

---

# DefeatRevertsToClone
#// TWI_116 Clone — a Clone copy leaves play as the REAL card (TWI_116), not the card it copied (the
#// printed copy only exists while in play). Clone copies an enemy SOR_095 (3/3); then P1's Open Fire
#// (SOR_172, "Deal 4 damage to a unit") targets the Clone → 4 ≥ 3 HP → defeated. It goes to P1's discard
#// as TWI_116 (Clone), NOT as SOR_095. The enemy's original SOR_095 is untouched.
## GIVEN
CommonSetup: rrk/bbw/{myResources:16;handCardIds:TWI_116,SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_172
P1DISCARDUNIT:1:CARDID:TWI_116
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# NoEligibleTarget_VehicleOnly
#// TWI_116 Clone — copy targets are non-leader, NON-VEHICLE units in play. When the only other unit in
#// play is a Vehicle (SOR_099, a space Vehicle), there is NO eligible copy target: no copy prompt is
#// offered, Clone enters as a plain 0/0, and is defeated (→ P1 discard as TWI_116). Proves Vehicles are
#// excluded (an eligible Vehicle would have produced a copy prompt).
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2SpaceArena: SOR_099:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDUNIT:0:CARDID:TWI_116
P1NODECISION
