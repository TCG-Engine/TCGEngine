# WhenPlayed_OneEachBase
#// SHD_160 Reckless Gunslinger (1-cost 2/1) — "When Played: Deal 1 damage to each base." Both bases
#// take 1 (including the controller's own).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_160

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:1
P2BASEDMG:1

---

# TwinSuns_EachBaseMeansALLFOUR
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — twin of SOR_014 Sabine's front Action, and found the same way.
#// "When Played: Deal 1 damage to EACH base" was two literal seat calls, SWUDealDamageToBase(1, 1) and
#// (1, 2) — a two-seat hardcode written as integers, invisible to any scan for the legacy seat helpers.
#// EACH base includes the caster's OWN and a teammate's: all four take exactly 1.
## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:SHD_160}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P1BASEDMG:1
P2BASEDMG:1
P3BASEDMG:1
P4BASEDMG:1
