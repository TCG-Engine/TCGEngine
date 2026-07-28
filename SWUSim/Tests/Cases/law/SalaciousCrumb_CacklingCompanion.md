# EntersExhausted_NoJabba
#// LAW_210 Salacious Crumb — fizzle/guard: with NO Jabba the Hutt controlled, Crumb enters play
#// EXHAUSTED (CR 8.22.f default). Played at index 0.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_WithJabba
#// LAW_210 Salacious Crumb (0/2 ground, Underworld/Creature, Raid 2) — "If you control Jabba the Hutt
#// (as a leader or unit), this unit enters play ready." P1 controls SOR_181 Jabba the Hutt (a unit) →
#// Crumb (played at index 1) enters READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_210
P1GROUNDARENAUNIT:1:READY

---

# EntersReady_WithSECJabbaLeaderFront
#// LAW_210 Salacious Crumb — "If you control Jabba the Hutt (as a leader or unit), this unit enters
#// play ready." P1's leader is SEC_002 Jabba the Hutt (undeployed front). Controlling the Jabba
#// LEADER counts, so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SEC_002}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:READY

---

# EntersReady_WithSECJabbaLeaderUnit
#// LAW_210 Salacious Crumb — controlling a DEPLOYED SEC_002 Jabba the Hutt leader unit also satisfies
#// "control Jabba the Hutt", so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SEC_002:1:1:1}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_210
P1GROUNDARENAUNIT:1:READY

---

# EntersReady_WithSHDJabbaLeaderFront
#// LAW_210 Salacious Crumb — P1's leader is SHD_006 Jabba the Hutt (undeployed front). Controlling the
#// Jabba leader counts, so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SHD_006}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:READY

---

# EntersReady_WithSHDJabbaLeaderUnit
#// LAW_210 Salacious Crumb — a DEPLOYED SHD_006 Jabba the Hutt leader unit satisfies "control Jabba the
#// Hutt", so Crumb enters play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SHD_006:1:1:1}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_210
P1GROUNDARENAUNIT:1:READY

---

# EntersExhausted_OpponentControlsJabbaLeader
#// LAW_210 Salacious Crumb — only YOUR control of Jabba the Hutt matters. P1's own leader is SHD_018
#// The Mandalorian and the OPPONENT controls SHD_006 Jabba the Hutt (front). P1 does not control Jabba,
#// so Crumb enters play EXHAUSTED (CR 8.22.f default).

## GIVEN
CommonSetup: yyk/rrk/{myResources:5; myLeader:SHD_018; theirLeader:SHD_006}
P1OnlyActions: true
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_210
P1GROUNDARENAUNIT:0:EXHAUSTED
