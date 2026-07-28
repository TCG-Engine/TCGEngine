# DamageEqualToTotal
#// ASH_187 Reckoning (Event, cost 3) — Deal damage to a unit equal to the total amount of damage on all
#// units you control. P1 controls SOR_046 (2 damage) and SOR_095 (1 damage) = 3 total; the chosen enemy
#// SEC_080 (3/3) takes 3 and is defeated.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_187}
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0

---

# NoDamageOnUnits_DealsZero
#// ASH_187 Reckoning — the damage equals total damage on your units; with all your units undamaged, it
#// deals 0. The chosen enemy SEC_080 takes nothing.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_187}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# MultiSource_SpaceAndLeaderCount
#// ASH_187 Reckoning — the total counts damage on ALL your units across both arenas AND your deployed
#// leader unit. SOR_046 ground (3 dmg) + SOR_237 space (1 dmg) + deployed leader (2 dmg) = 6. The chosen
#// enemy SOR_046 (7 HP) takes 6 and survives.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_187;myLeader:ASH_009:1:1:0:2}
WithP1GroundArena: SOR_046:1:3
WithP1SpaceArena: SOR_237:1:1
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# TargetSpaceUnit
#// ASH_187 Reckoning — the target may be a space unit. With 2 total damage on friendly SOR_046, the
#// chosen enemy space unit SOR_237 takes 2.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_187}
WithP1GroundArena: SOR_046:1:2
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2

---

# TargetFriendlyUnit
#// ASH_187 Reckoning — the target may be one of your own units. With 2 total damage (on SOR_046 at
#// index 0), the chosen friendly SOR_095 at index 1 takes 2.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_187}
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
