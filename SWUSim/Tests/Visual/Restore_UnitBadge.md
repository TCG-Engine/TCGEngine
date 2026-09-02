# VISUAL CHECK — Restore keyword icon on a unit (both arenas, two different values)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the restore.webp unit icon.
#
# The restore.webp icon shows (bottom, like the other keyword icons) on any arena unit that has the
# Restore keyword (printed or granted). Restore is a VALUED keyword (Restore 3 = heal 3 from your
# base on attack), but the badge is PRESENCE-ONLY like Exploit's — no number is drawn on it.
#
#   ground  SOR_051 Luke Skywalker     (Restore 3) -> icon SHOWS, bottom
#   ground  SOR_095 Battlefield Marine (none)      -> NO icon
#   space   SOR_044 Restored ARC-170   (Restore 1) -> icon SHOWS, bottom
#   space   SOR_237 Alliance X-Wing    (none)      -> NO icon  (renders as its LAW_253 reprint art)
#
# What to look at:
#   • SOR_051 and SOR_044 each show the restore icon at the bottom of the card — proving the badge
#     is wired in BOTH the GroundArena and SpaceArena schema blocks, not just one.
#   • The two positives are Restore 3 and Restore 1 and their badges are IDENTICAL — that is the
#     intended presence-only design, not a bug.
#   • SOR_095 and SOR_237 show no restore icon (the negative control).
#   • No WHEN steps — the initial GIVEN state is the whole check.
#
# ⚠ DO NOT swap a positive for SOR_102 Home One, SEC_047 Defiant or TS26_40 Obi-Wan: each grants
#   Restore to every OTHER friendly unit, so the negative controls light up too and the test stops
#   discriminating. (That is correct engine behaviour — GetConditionalKeyword_Restore_Value's
#   friendly-unit grant loop — it just makes them useless here.)

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: SOR_051:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_044:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
P1SPACEARENAUNIT:1:NOTKEYWORD:Restore
