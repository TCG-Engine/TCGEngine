# FiveToDamaged
#// ASH_147 The Cyborg Mech (Ground, 3/7, Grit, cost 6) — When Played: deal 2 to an undamaged ground unit
#// OR 5 to a damaged ground unit. P1 targets the DAMAGED SOR_046 (1 damage) → 5 damage → 6 total.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147}
WithP2GroundArena: SOR_046:1:1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# TwoToUndamaged
#// ASH_147 The Cyborg Mech — the alternative mode: 2 damage to an UNDAMAGED ground unit. P1 targets the
#// undamaged SEC_080 (3/3) → 2 damage (survives).
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# GritPowerScalesWithDamage
#// ASH_147 The Cyborg Mech — Grit gives +1/+0 per damage on it. Seated with 2 damage, its power is 3 + 2 = 5.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_147:1:2
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5

---

# TwoToSelf
#// ASH_147 The Cyborg Mech — When Played it may deal 2 to an undamaged ground unit, and it is itself a
#// legal (undamaged) target. With no other ground units it deals 2 to itself.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_147
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# TwoToUndamagedFriendly
#// ASH_147 The Cyborg Mech — the 2-mode can hit an undamaged FRIENDLY ground unit. Friendly SEC_080 (3/3)
#// is targeted; the freshly-played Cyborg is undamaged.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# FiveToDamagedFriendly
#// ASH_147 The Cyborg Mech — the 5-mode can hit a damaged FRIENDLY ground unit. Friendly SOR_046 (3/7)
#// starts with 1 damage → 5 more → 6 total.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147}
WithP1GroundArena: SOR_046:1:1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:6

---

# TwoToUndamagedFriendlyLeader
#// ASH_147 The Cyborg Mech — an undamaged friendly deployed leader (a ground unit) is a legal 2-mode target.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147;myLeader:SOR_010:1:1}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_010
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# FiveToDamagedEnemyLeader
#// ASH_147 The Cyborg Mech — a damaged enemy deployed leader (ground unit) is a legal 5-mode target.
#// Enemy SOR_010 deployed with 1 damage → 5 more → 6 total.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147;theirLeader:SOR_010:1:1:0:1}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_010
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# TwoToUndamagedEnemyLeader
#// ASH_147 The Cyborg Mech — the 2-mode (undamaged) can target an enemy deployed leader in the ground arena.
#// Enemy SOR_010 deployed undamaged → 2 damage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147;theirLeader:SOR_010:1:1:0:0}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_010
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# FiveToDamagedFriendlyLeader
#// ASH_147 The Cyborg Mech — the 5-mode (damaged) can target a friendly deployed leader in the ground arena.
#// Friendly SOR_010 deployed with 1 damage → 5 more → 6 total.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_147;myLeader:SOR_010:1:1:0:1}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_010
P1GROUNDARENAUNIT:0:DAMAGE:6
