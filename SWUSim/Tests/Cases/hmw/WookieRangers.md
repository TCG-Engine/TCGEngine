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
#// clause is "ANOTHER Wookiee unit", so it never satisfies itself. (The Kashyyyk-base branch is exercised
#// below now that HMW_021/024/030/031 are previewed; it shares _SWUControlsBaseWithTrait with HMW_234/177.)

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_142:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_142
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Sentinel_WhileControllingAKashyyykBase_NoOtherWookiee
#// The SECOND gate branch, unexercisable until HMW previewed a Kashyyyk base: a LONE Wookie Rangers (no
#// other Wookiee unit anywhere) still gains Sentinel while you control a Kashyyyk base — HMW_021 Kashirho,
#// a vanilla 30-HP Vigilance/Kashyyyk base. Contrast the section directly above: identical board, ordinary
#// base, no Sentinel — so the base is the only thing carrying this.

## GIVEN
CommonSetup: ggw/rrk/{myBase:HMW_021}
P1OnlyActions: true
WithP1GroundArena: HMW_142:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_142
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoSentinel_KashyyykBaseIsTheOPPONENTS
#// "While YOU control … a Kashyyyk base" — the opponent holding Kashirho grants nothing. This is the
#// load-bearing negative for the controller scoping: an implementation that scanned "a base with the
#// Kashyyyk trait" instead of the controller's own base passes the positive above and fails only here.

## GIVEN
CommonSetup: ggw/rrk/{theirBase:HMW_021}
P1OnlyActions: true
WithP1GroundArena: HMW_142:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_142
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# NoSentinel_NonKashyyykHMWBase
#// The trait is load-bearing, not merely "controls one of the new HMW bases": HMW_020 Great Grass Plains is
#// the same vanilla 30-HP shell with the NABOO trait, and grants nothing. Guards against a check that
#// matched the base by set/CardID prefix rather than by trait.

## GIVEN
CommonSetup: ggw/rrk/{myBase:HMW_020}
P1OnlyActions: true
WithP1GroundArena: HMW_142:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_142
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
