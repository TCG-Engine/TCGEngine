# ExhaustedHostGainsSentinel
#// SEC_071 (upgrade, +1/+3) — "While attached unit is exhausted, it gains Sentinel." The host SEC_041 is
#//   exhausted → it has Sentinel.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:0:0
WithP1GroundArenaUpgrade: 0:SEC_071

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# ReadyHostNoSentinel
#// SEC_071 — the Sentinel grant is conditional on the host being exhausted. A READY host does NOT have
#//   Sentinel.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArenaUpgrade: 0:SEC_071

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# LoseSentinelForPhase_OverridesGrant
#// SEC_071 — a "loses Sentinel for this phase" effect beats the upgrade's ongoing grant. The exhausted host
#//   (SEC_041 with SEC_071) has Sentinel; P2 plays SpecForce Soldier (SOR_140, "When Played: a unit loses
#//   Sentinel for this phase") targeting it. Even though the host stays exhausted, it no longer has Sentinel
#//   for the phase.

## GIVEN
CommonSetup: bbk/rrw
WithActivePlayer: 2
WithP1GroundArena: SEC_041:0:0
WithP1GroundArenaUpgrade: 0:SEC_071
WithP2Resources: 1
WithP2Hand: SOR_140

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2NODECISION
