# VISUAL CHECK — Han Solo's token cost with one of EVERY token type on the board at once
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test
# Schema Editor, then drive P1's Han Solo.
#
# WHY THIS EXISTS
# LAW_017's "[defeat a friendly token]" cost used to be a hand-written whitelist of token kinds, which
# silently refused an Advantage token and switched the whole ability off. The pool is now derived from
# the card TYPE ("Token Upgrade" / "Token Unit" / "Credit Token" / "Force Token"), shared with LAW_019
# Alliance Outpost. This board is the check for that claim: one of every token type the game has, all
# friendly, all payable, all offered in one prompt.
#
# EVERY TOKEN TYPE (13) — one of each is on this board:
#   Token upgrades — Experience SOR_T01 · Shield SOR_T02 · Advantage ASH_T02 · Weakness HMW_T02
#   Token units    — Ground: Battle Droid TWI_T01 · Clone Trooper TWI_T02 · Spy SEC_T01 ·
#                            Mandalorian ASH_T01 · Beast HMW_T03
#                    Space:  TIE Fighter JTL_T01 · X-Wing JTL_T02
#   Credit LAW_T01 · The Force LOF_T03
# Experience and Shield are reprinted once per set (JTL_T03, LAW_T02, LOF_T01, SEC_T02, SHD_T01,
# TS26_T03 …) and the later printings are mechanically identical, so one printing of each stands in
# for all of them here. That the pool is type-driven rather than a CardID list is what makes that
# substitution safe — and it is asserted directly in Tests/Cases/law/HanSolo_AdvantageTokenPaysTheCost.md.
#
# WHAT TO DO
#   1. Attack with deployed Han (the non-token unit in P1's ground arena). His On Attack offers
#      "defeat any number of friendly tokens", repeatedly.
#   2. Defeat them ALL — the prompt re-offers after each one, with the defeated token gone from the
#      pool and the rest still highlighted.
#   3. The final prompt deals damage equal to the number defeated: all 13 tokens → 13 damage. Point it
#      at P2's ASH_083 Summa-verminoth (15/15, P2's SPACE arena) and it SURVIVES, showing a 13 damage
#      badge. Han is a GROUND unit, so this also shows that the damage target word is unqualified —
#      the pool spans both arenas and both sides.
#   4. Worth a second pass on the LEADER-FRONT side (reload, don't deploy): that Action defeats exactly
#      ONE token as its cost and then deals 1 — same pool, single pick, no repeat.
#
# WHAT TO LOOK FOR
#   • NO text-button popup with tilde-separated labels ("Exp~myGroundArena-0~0"). That was the old UI;
#     the cost is now picked on the board.
#   • The offer spans BOTH ARENAS and every token form at once: upgrade slivers under their hosts, the
#     Shield orb, the token units themselves, the Credit in the resource row, and the Force token on
#     P1's base. Nothing in that list may be un-clickable.
#   • Weakness (HMW_T02, a -1/-1 debuff on P1's own unit) is still a friendly token: it must be in the
#     pool, and defeating it is a benefit — its host goes back up to full size.
#   • P2's tokens (the Shield on its ground unit and P2's Credit) must NEVER highlight. "Friendly"
#     follows CONTROL, and an enemy token can never pay this cost.
#   • HOST UNITS must not glow — only their attachments.
#   • After each pick every ring clears and the re-offer repaints cleanly; no orphan glow is left on a
#     token that was already defeated.
#   • The pool shrinks by exactly one each pass. If a defeated token is still offered, the re-offer is
#     reading a stale pool.
#   • The offer is DECLINABLE — the pass/decline control must be present, because "any number" includes
#     zero. Declining ends the loop and deals no damage.
#
# VERIFIED SERVER-SIDE (2026-09-20): on this board the cost offers 13 candidates — 5 ground token
# units, 2 space token units, 4 token upgrades across two hosts in both arenas, 1 Credit and the
# Force. So anything MISSING from the highlight is a rendering gap, not a pool gap: the server offered
# it. ⚠ A failure message from the schema harness prints only the first 160 characters of a pending
# decision's candidate list, so a truncated list THERE is the message being cut off, not the offer.
#
# CROSS-BROWSER: check Chromium, Firefox AND Safari, plus ?swuLayout=mobile — the highlight rings use
# a keyframed box-shadow inside an overflow:visible span whose stacking differs between engines.
#
# BOARD SHAPE
#   P1 ground 0 — SOR_095 wearing Experience, Shield and Weakness (three upgrade types on one host)
#   P1 ground 1-5 — the five ground token units
#   P1 ground 6 — deployed Han
#   P1 space  0 — ASH_167 wearing the ADVANTAGE token (the kind the old whitelist refused, and in the
#                 SPACE arena, which is where it was reported from)
#   P1 space  1-2 — TIE Fighter and X-Wing
#   P1 also holds a Credit and the Force
#   P2 space  0 — ASH_083 Summa-verminoth (15/15), the ping target — no tokens on it
#   P2 ground 0 — SOR_046 wearing a Shield; P2 also holds a Credit. Enemy tokens, never selectable.
#
# No WHEN steps — interaction is manual.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
WithP1Resources: 20
WithP1Force: true
WithP1Credits: 1

WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:HMW_T02

WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: SEC_T01:1:0
WithP1GroundArena: ASH_T01:1:0
WithP1GroundArena: HMW_T03:1:0

WithP1SpaceArena: ASH_167:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArena: JTL_T02:1:0

WithP2SpaceArena: ASH_083:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2Credits: 1

## EXPECT
# Not run by the regression runner — kept so the fixture can be validated by hand, because a wrong
# board makes the visual check meaningless. P1 ground = 1 host + 5 token units + deployed Han.
P1GROUNDARENACOUNT:7
P1SPACEARENACOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1CREDITCOUNT:1
P1HASFORCE
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:ASH_083
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2CREDITCOUNT:1
