# GrantsHidden
#// LOF_132 Grand Inquisitor (3/4) — Hidden + Raid 1 + "Other friendly Inquisitor units gain Hidden." The
#// friendly Inquisitor (Eighth Brother) gains Hidden from him.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_132:1:0
WithP1GroundArena: LOF_087:1:0

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# GrantedHiddenLostWhenGILeaves
#// LOF_132 Grand Inquisitor — the granted Hidden is an aura: a friendly Inquisitor that has no INNATE Hidden
#// (Eighth Brother, LOF_087) loses Hidden the moment Grand Inquisitor leaves play. P2 defeats Grand Inquisitor
#// with Takedown (SOR_077, defeat a unit with 5 or less remaining HP — GI is 3/4 → only legal target), and
#// Eighth Brother no longer has Hidden. Ref: units that don't have Hidden on their own lose it when GI is
#// removed from play.

## GIVEN
CommonSetup: rrk/bbk/{theirBase:SOR_021}
WithActivePlayer: 2
WithP1Resources: 3
WithP2Resources: 8
WithP2Hand: SOR_077
WithP1GroundArena: LOF_132:1:0
WithP1GroundArena: LOF_087:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_087
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden
