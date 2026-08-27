# SentinelLoseSaboteur
#// JTL_077 In the Heat of Battle — Each unit gains Sentinel and loses Saboteur for this phase. The
#// Saboteur unit SHD_147 gains Sentinel and loses Saboteur.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_077
WithP1Resources: 6
WithP1GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# AffectsBothSides
#// JTL_077 In the Heat of Battle — "EACH unit" spans both players. P1's Saboteur unit (SHD_147) gains
#// Sentinel and loses Saboteur; the enemy SOR_095 also gains Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_077
WithP1Resources: 6
WithP1GroundArena: SHD_147:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_147
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# RegainSaboteurSuppressed
#// JTL_077 In the Heat of Battle — an affected unit cannot REGAIN Saboteur later this phase. P1's SOR_095 (no native Saboteur)
#// is hit by the event (gains Sentinel, and the "loses Saboteur for this phase" continuous effect). P1 then
#// attaches SOR_166 Infiltrator's Skill ("Attached unit gains Saboteur") onto SOR_095 — the lone friendly
#// unit auto-targets. The granted Saboteur is still SUPPRESSED for the phase, so SOR_095 keeps Sentinel and
#// does NOT have Saboteur. Aspects brw cover JTL_077 (Vigilance) + SOR_166 (Aggression).

## GIVEN
CommonSetup: brw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_077 SOR_166]
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_166
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# DoesNotAffectLaterUnits
#// JTL_077 In the Heat of Battle — does NOT affect units that enter play AFTER the event resolves. P1's pre-existing
#// SOR_095 gains Sentinel (control). P1 then plays SOR_164 Wampa; because Wampa entered play after the
#// event resolved, it does NOT gain Sentinel (it keeps only its printed Overwhelm). Aspects brw cover
#// JTL_077 (Vigilance) + SOR_164 (Aggression).

## GIVEN
CommonSetup: brw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_077 SOR_164]
WithP1Resources: 8
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# CanStillLoseSentinel
#// JTL_077 In the Heat of Battle — an affected unit can still LOSE Sentinel later in the phase. P1's SOR_095 gains Sentinel from the
#// event. P1 then plays SOR_140 SpecForce Soldier ("When Played: A unit loses Sentinel for this phase"),
#// which auto-targets the lone Sentinel unit SOR_095 — it loses the granted Sentinel. Aspects brw cover
#// JTL_077 (Vigilance) + SOR_140 (Aggression/Heroism).

## GIVEN
CommonSetup: brw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_077 SOR_140]
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_140

---

# SimulateRequestBoundary_SaboteurSuppressionSurvivesRoundTrip
#// JTL_077 In the Heat of Battle — the "loses Saboteur for this phase" continuous effect is written by one
#// action and must still suppress a Saboteur GRANTED by a later action. In production those are two
#// separate requests, so the phase-duration effect must live in the gamestate. Mirrors
#// RegainSaboteurSuppressed with the boundary between the event and the upgrade.

## GIVEN
CommonSetup: brw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_077 SOR_166]
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_166
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# SaboteurSuppressionExpiresNextPhase
#// JTL_077 In the Heat of Battle — DURATION. Both halves are "for this phase", so after passing into the
#// next action phase SHD_147 Ketsu Onyo must have her printed Saboteur BACK and must have lost the
#// granted Sentinel. The Sentinel line is the control: JTL_077_SENTINEL is registry-known and expires
#// correctly, so it proves the phase really advanced and isolates the Saboteur suppressor.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_077
WithP1Resources: 6
WithP1GroundArena: SHD_147:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_147
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# TwinSuns_EachUnitMeansEVERYSeat
#// ⚠ REGRESSION GUARD, live bug 2026-08-27 — Twin Suns two-seat hardcode family.
#// "EACH unit gains Sentinel and loses Saboteur" is the WHOLE TABLE. The handler looped seats 1..2, so at
#// four seats the units on seats 3 and 4 were simply never reached: no Sentinel, and a Saboteur unit there
#// kept its Saboteur. Every existing section in this file is a two-seat board, which is exactly why it
#// stayed invisible — the same reason the JTL_047 Yularen seat bug survived.
#//
#// P1 plays the event. SHD_147 Ketsu Onyo (native Saboteur) sits on seat 1 AND on seat 3: both must gain
#// Sentinel and lose Saboteur. The seat-1 copy is the control — it was always correct, so if IT ever goes
#// red the problem is the fixture, not the seat loop.

## GIVEN
CommonSetup: bbw/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: JTL_077
WithP1Resources: 6
WithP1GroundArena: SHD_147:1:0
WithP3GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P3GROUNDARENAUNIT:0:CARDID:SHD_147
P3GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P3GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
