# VISUAL CHECK — Bounty keyword icon on a unit (three grant paths) + Jabba's leader ability
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the bounty.webp unit icon, then drive the one
# WHEN step to watch Jabba's leader ability grant a Bounty live.
#
# P1's leader is Jabba the Hutt (SHD_006). P2 (the opponent) holds three ground units, each a different
# way a unit can have the Bounty keyword:
#   idx 0  SHD_027 Hylobon Enforcer      — INNATE Bounty (in $Bounty_Cards)        -> icon SHOWS immediately
#   idx 1  SEC_080 Imperial Dark Trooper — UPGRADE-granted Bounty (SHD_123 attached) -> icon SHOWS immediately
#   idx 2  SOR_095 Battlefield Marine    — no Bounty yet                            -> NO icon, until Jabba grants it
#
# What to look at:
#   • Before the WHEN: SHD_027 (idx 0) and SEC_080 (idx 1, wearing Bounty Hunter's Quarry) show the
#     animated bounty icon at the bottom of the card; the Battlefield Marine (idx 2) shows none.
#   • The WHEN uses Jabba's "Action [Exhaust]: Choose a unit. For this phase it gains a Bounty"
#     leader ability, targeting the Battlefield Marine — its bounty icon then appears too, and Jabba
#     exhausts. (All three opponent units now show the badge.)
#   • Hover a bounty badge → the Active Effects popup shows Jabba's card art + a "Phase" chip for the
#     granted one; the innate/upgrade ones are intrinsic to the unit.

## GIVEN
P1LeaderBase: SHD_006/SOR_028
P2LeaderBase: SOR_010/SOR_029
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SHD_027:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 1:SHD_123
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P2GROUNDARENAUNIT:1:HASKEYWORD:Bounty
P2GROUNDARENAUNIT:2:HASKEYWORD:Bounty
