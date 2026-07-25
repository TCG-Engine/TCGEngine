# HealUnitOrBase
#// ASH_081 Nebulon-C Frigate (Space, 3/6, cost 5) — When Played: you may heal 3 damage from a unit or
#// base. P1's base starts at 3 damage; playing the Frigate heals 3 from it (3 → 0).
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:ASH_081;myBaseDamage:3}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:0

---

# HealDamagedUnit
#// ASH_081 Nebulon-C Frigate — the heal may target a unit instead of a base. P1 heals 3 from the damaged
#// SOR_046 (3 damage → 0).
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:ASH_081}
WithP1GroundArena: SOR_046:1:3
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# HealClampsToFull
#// ASH_081 Nebulon-C Frigate — healing 3 from a target with only 2 damage clamps to 0 (no overheal). P1's
#// base starts at 2 damage; the Frigate heals it fully to 0.
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:ASH_081;myBaseDamage:2}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:0

---

# DeclineHeal
#// ASH_081 Nebulon-C Frigate — the heal is optional; declining leaves damage untouched. P1's base has 2
#// damage; the player passes on the heal and the base stays at 2.
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:ASH_081;myBaseDamage:2}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P1BASEDMG:2

---

# NoDamage_NoPrompt
#// ASH_081 Nebulon-C — the optional heal shows NO prompt when nothing is damaged (no unit and neither base
#// carries damage). Playing it with a full board and full bases resolves with no decision.
## GIVEN
CommonSetup: bbw/bbk/{myResources:5;handCardIds:ASH_081}
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1SPACEARENACOUNT:2
