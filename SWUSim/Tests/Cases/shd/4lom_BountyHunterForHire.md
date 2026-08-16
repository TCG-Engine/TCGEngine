# BuffsZuckuss
#// SHD_188 4-LOM (4-cost, Cunning/Villainy) — Ambush + "Each friendly unit named Zuckuss gets +1/+1 and gains
#// Ambush." With 4-LOM in play, the friendly Zuckuss (SHD_190, base 6/6) becomes 7/7 and has Ambush.
#// COVERAGE: offer=N/A (a static aura has no target pick) · decline=N/A (no "you may") ·
#//           control=EnemyZuckuss_NotBuffedByFriendlyOnly4LOM (the aura is seat-scoped: the SAME
#//           Zuckuss under the opponent's control gets nothing) · boundary=BuffsZuckuss (aura on) vs
#//           EnemyZuckuss_NotBuffedByFriendlyOnly4LOM (aura off) · reqboundary=N/A (constant ability,
#//           recomputed from board state every read — no stored per-turn state to serialize)

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_188:1:0
WithP1GroundArena: SHD_190:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_190
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush

---

# EnemyZuckuss_NotBuffedByFriendlyOnly4LOM
#// Intended: the aura reads "Each FRIENDLY unit named Zuckuss", so an ENEMY Zuckuss facing 4-LOM keeps
#// its printed 6/6 and never gains Ambush. Same board as BuffsZuckuss with the Zuckuss moved across the
#// table — the seat is the only variable.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_188:1:0
WithP2GroundArena: SHD_190:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_190
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
