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

---

# RescuedFromCapture_ChecksTheConditionOnReentry
#// SEC_170 Corellian Hounds — "enters play ready" is evaluated every time it ENTERS play, not only on a
#// play from hand. Here it comes back by being RESCUED from capture: P1's base captures it with SEC_195
#// Arrest, both players pass to the regroup phase, and the rescue puts it back with the opponent still
#// controlling no ground units — so it returns READY rather than with the usual exhausted-on-rescue.
#// (CR 8.34.3 rescues exhausted; the Hounds' own replacement overrides that when its condition holds.)

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SEC_170:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_170
P2GROUNDARENAUNIT:0:READY
