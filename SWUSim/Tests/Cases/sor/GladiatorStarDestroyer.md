# Sentinel_ExpiresNextPhase
#// SOR_086 Gladiator Star Destroyer — "Give a unit Sentinel for this phase." The grant is now a
#// CardID turn-effect token ("SOR_086") resolved by the turn-effect registry to Sentinel/phase, and
#// expired by the centralized duration-driven SWUExpireTurnEffects at RegroupPhaseStart. After both
#// players pass (ending the action phase → regroup), the Battlefield Marine no longer has Sentinel.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
WithP1Hand: SOR_086
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine — idx 0, the Sentinel recipient

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# WhenPlayed_GrantsSentinel
#// SOR_086 Gladiator Star Destroyer (5/6, Space) — When Played: Give a unit Sentinel for this
#// phase. P1 plays it and chooses the friendly Battlefield Marine, which then has the Sentinel
#// keyword (a phase-scoped TurnEffect grant). Uses the new HASKEYWORD/NOTKEYWORD assertions.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_086
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine — idx 0, the Sentinel recipient

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
