# Sentinel_WhileControllingAnotherWookieeUnit
#// HMW_142 Wookie Rangers (5/6, Command, cost 5, Wookiee) — "While you control another Wookiee unit or a
#// Kashyyyk base, this unit gains Sentinel." Two copies each see the OTHER Wookiee, so both gain Sentinel —
#// which also proves the "another" self-exclusion (a lone copy does not count itself; see the negative).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_142:1:0 HMW_142:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_142
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# NoSentinel_LoneCopy_NoOtherWookieeNoKashyyykBase
#// A single Wookie Rangers with no other Wookiee unit and no Kashyyyk base does NOT gain Sentinel — the
#// clause is "ANOTHER Wookiee unit", so it never satisfies itself. (No Kashyyyk base is previewed in any
#// set, so that branch is currently unexercisable; it shares _SWUControlsBaseWithTrait with HMW_234/HMW_177.)

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_142:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_142
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
