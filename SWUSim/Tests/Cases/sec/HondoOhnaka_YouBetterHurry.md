# GrantsRaidToOthers
#// SEC_140 Hondo Ohnaka (Ground, 6/5) — "Each other friendly unit gains Raid 1." The friendly SEC_041
#//   gains Raid; SEC_140 itself (no innate Raid) does not.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_140:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# GrantIsFriendlyOnly_EnemiesGetNothing
#// SEC_140 Hondo Ohnaka — "each other FRIENDLY unit gains Raid 1", so the opponent's board is untouched.
#// P2's SEC_041 has no Raid while P1's copy does. The scope negative the friendly-side section can't
#// prove on its own.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_140:1:0
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
