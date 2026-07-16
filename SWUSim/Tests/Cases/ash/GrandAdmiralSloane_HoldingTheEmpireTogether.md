# GroundSentinelOverwhelm
#// ASH_007 Grand Admiral Sloane — Leader Action [Exhaust]: choose one — give each ground unit (or each space
#// unit) Sentinel and Overwhelm for this phase. P1 chooses Ground, so SOR_095 (ground) gains both keywords
#// while SOR_237 (space) is unaffected; Sloane exhausts.
## GIVEN
CommonSetup: ggk/brk/{
  myLeader:ASH_007
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Ground
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P1LEADER:EXHAUSTED

---

# Deployed_Passive_GrantOverwhelmSentinel
#// ASH_007 Grand Admiral Sloane (deployed) — passive: each other friendly unit gains Overwhelm and
#// Sentinel. The Dark Trooper (SEC_080) gains both.

## GIVEN
CommonSetup: ggk/brk/{
  myLeader:ASH_007:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
