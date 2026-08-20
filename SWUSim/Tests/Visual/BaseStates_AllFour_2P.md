# VISUAL CHECK — 2P: every base/leader state on ONE player at once
#   the Force · 3 fortifications · 2 arrests · Epic Action used · 16 base damage (DOUBLE DIGIT)
#
#   curl -s -X POST .../SWUSim/TestSchemaSetup.php --data-urlencode "schema@<this file>"
#   open http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=1
#
# WHY THIS EXISTS: each of these four is fine on its own; the risk is that they COLLIDE. They all
# crowd the same small centre column, and three of them stack around the base:
#   • THE FORCE  — token drawn INSIDE the base card, top-RIGHT corner (refreshForceToken).
#   • FORTIFIED 3 — grey tab directly UNDER the base.
#   • ARRESTED 2  — goldenrod tab under that.
#   • EPIC ACTION USED — on the LEADER *and* on the BASE (both bottom-right, Card()'s epicActionUsed).
#     ⚠ On the base that icon shares the card with the CENTRED damage token and the top-right Force
#     token, so the base alone is carrying three overlays here. That is the collision worth looking at.
#     ⚠ 2P Premier runs ONE leader, so there is one leader icon; the Twin Suns sibling has two.
#   • 16 DAMAGE — the damage token sits CENTRED on the base, and 16 is deliberately DOUBLE DIGIT: a
#     two-digit number is wider than the token art and is the case that overflows or clips.
#
# WHAT TO LOOK AT — P1's centre column, top to bottom:
#   [Base: 16 centred + Force top-right + epic-used bottom-right] / [FORTIFIED 3] / [ARRESTED 2] /
#   [Leader + epic-used icon]
#   • ALL of them visible at once, none overlapping or clipped.
#   • "16" must sit centred and fully inside its token — both digits, not clipped, and not colliding
#     with the Force token in the corner above it.
#   • The Force token must not be pushed off or covered by the tabs, and the tabs must not overlap
#     the leader's epic-used icon.
#   • The two tabs keep their own colours (grey / goldenrod) — they are different facts.
#   • CLICK FORTIFIED → "Attached Upgrades" with THREE images that RENDER (assert naturalWidth > 0,
#     not merely that the panel opened — see the underscore trap in BaseTabs_FortifiedAndArrested_2P).
#   • P2 has none of the four: their base carries no token and no tabs, and #theirBaseTabs is
#     zero-height. An empty tab strip must not push their leader and base apart.
#
# ⚠ Arrests are SEEDED here (WithP1BaseCaptive writes the same SWU_BASECAPTIVE GlobalEffects flag the
# card writes). This test is about the four states CO-EXISTING; the played path is covered by
# BaseTabs_FortifiedAndArrested_2P.md and Cases/sec/Arrest.md.
# ⚠ Arrests reset at RegroupPhaseStart, so do not advance the phase and expect ARRESTED to survive.
#
# VERIFIED 2026-08-19, Chromium + Firefox at 1700x1100 — see the sibling Twin Suns file for the
# 4-player half. WebKit NOT covered: it does not launch on this machine.

## GIVEN
#// myLeader spec is CARDID[:ready[:deployed[:epicUsed[:damage]]]] — the trailing 1 is EPIC USED.
#// ⚠ Base damage goes through myBaseDamage, NOT myBase:ID:damage — that form silently DROPS the
#// damage and the assertion below would pass against an undamaged base.
CommonSetup: yyk/rrk/{myLeader:SOR_016:1:0:1; myBaseEpicUsed:true; myBaseDamage:16}
P1OnlyActions: true
WithP1Force: true
WithP1BaseUpgrade: HMW_081
WithP1BaseUpgrade: HMW_171
WithP1BaseUpgrade: HMW_205
WithP1BaseCaptive: SOR_095
WithP1BaseCaptive: SOR_046

## WHEN

## EXPECT
P1BASEUPGRADECOUNT:3
P1BASECAPTIVECOUNT:2
P1BASEDMG:16
