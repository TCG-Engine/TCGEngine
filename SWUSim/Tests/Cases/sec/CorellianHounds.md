# EntersExhausted_OppHasGround
#// SEC_170 Corellian Hounds — when the opponent DOES control a ground unit, SEC_170 enters play
#//   exhausted (the default).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_NoOppGround
#// SEC_170 Corellian Hounds (Ground, 5/5, Creature, cost 5) — "If an opponent controls no ground
#//   units, this unit enters play ready." P2 has no ground units → SEC_170 enters ready.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SEC_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# EntersReady_OppGroundIsOnlyADeployedLeader
#// SEC_170 Corellian Hounds — "if an opponent controls no GROUND UNITS". A deployed leader IS a ground
#// unit, so an opponent whose only ground presence is their deployed leader still blocks the ready-entry.
#// P2 has TWI_002 Nute Gunray deployed and nothing else; SEC_170 enters EXHAUSTED.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;theirLeader:TWI_002;theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_170
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_170
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_OppHasOnlySpaceUnits
#// SEC_170 Corellian Hounds — the check is GROUND-arena scoped. An opponent with units only in SPACE
#// controls no ground units, so the Hounds still enter READY. Complements EntersExhausted_OppHasGround.
## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_170
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_170
P1GROUNDARENAUNIT:0:READY
