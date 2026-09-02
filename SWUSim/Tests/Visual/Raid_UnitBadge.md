# VISUAL CHECK — Raid keyword icon on a unit (both arenas)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the raid.webp unit icon.
#
# The raid.webp icon shows (bottom, like the other keyword icons) on any arena unit that has the
# Raid keyword (printed or granted). Raid is a VALUED keyword (Raid 2 = +2 power while attacking),
# but the badge is PRESENCE-ONLY like Exploit's — no number is drawn on it, because the live bonus
# is already visible in the unit's CurrentPower counter while it attacks.
#
#   ground  SOR_157 Cantina Braggart      (Raid 2) -> icon SHOWS, bottom
#   ground  SOR_095 Battlefield Marine    (none)   -> NO icon
#   space   SOR_141 Green Squadron A-Wing (Raid 2) -> icon SHOWS, bottom
#   space   SOR_237 Alliance X-Wing       (none)   -> NO icon
#
# What to look at:
#   • SOR_157 and SOR_141 each show the raid icon at the bottom of the card — proving the badge is
#     wired in BOTH the GroundArena and SpaceArena schema blocks, not just one.
#   • Neither badge carries a number, even though both units are Raid 2.
#   • SOR_095 and SOR_237 show no raid icon (the negative control — without these, a badge that
#     rendered unconditionally would look correct).
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: SOR_157:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
P1SPACEARENAUNIT:1:NOTKEYWORD:Raid
