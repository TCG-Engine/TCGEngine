# SentinelWhileExhaustedAlly
#// ASH_120 Warrior of Clan Kryze (Ground, 2/3) — While you control another exhausted unit, this unit
#// gains Sentinel. With an exhausted friendly SOR_095 present, Kryze has Sentinel.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_120:1:0
WithP1GroundArena: SOR_095:0:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_120
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# GainsSentinelWithExhaustedUnit
#// ASH_120 Warrior of Clan Kryze — "While you control another exhausted unit, this unit gains Sentinel."
#// With an exhausted SOR_095, the Warrior has Sentinel.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_120:1:0
WithP1GroundArena: SOR_095:0:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoSentinelWithoutOtherExhausted
#// ASH_120 Warrior of Clan Kryze — Sentinel requires ANOTHER exhausted friendly unit. Kryze itself is
#// exhausted, but its only ally SOR_095 is READY, so there is no OTHER exhausted friendly → no Sentinel.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_120:0:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_120
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SentinelWhileExhaustedSpaceAlly
#// ASH_120 Warrior of Clan Kryze — the other exhausted friendly unit may be in the SPACE arena. With an
#// exhausted friendly SOR_237 in space, the (ground) Warrior still gains Sentinel.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_120:1:0
WithP1SpaceArena: SOR_237:0:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_120
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
