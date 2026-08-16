# VISUAL CHECK — the attack lunge plays AFTER On Attack resolves, not at declaration
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Load it by hand in
# the Test Schema Editor and step the WHEN.
#
# ⚠ NOTHING AUTOMATED VERIFIES THE ANIMATIONS. The schema suite bypasses ProcessInput and
# TestSchemaStep stubs the animation functions to no-ops, so these schemas are the ONLY proof that
# card motion works — and specifically the ONLY proof of animation ORDER. Treat a failure here as a
# real bug, not a flaky demo.
#
# THE BUG THIS GUARDS (fixed 2026-08-15)
# The lunge used to be queued in ExecuteSWUAttack at attack DECLARATION, which is the same request
# that raises the On Attack / On Defense prompts. So the clash animation played out while the player
# was still being asked about an ability the CR resolves BEFORE damage — the attack looked resolved
# before you had answered it. It is now queued in the SWUCombatDamage handler.
#
# CR ORDER being asserted:
#   declare attacker/defender → begin-attack window (Restore, Saboteur) → ON ATTACK abilities →
#   combat damage → attack ends.
# The lunge depicts the STRIKE, so it belongs to the combat-damage step.
#
# What to look at, in this order:
#   1. Attack with Sabine Wren. She EXHAUSTS immediately (tilts 9° + shades) — that is the attack's
#      cost and correctly happens at declaration.
#   2. The "You may deal 1 damage to the defender or to a base" prompt appears. AT THIS MOMENT THE
#      BOARD MUST BE STILL — no lean, no clash, no damage numbers. A lunge here is the bug.
#   3. Answer the prompt (either target, or decline).
#   4. NOW Sabine leans toward the Consular Security Force and the damage numbers land.
#
# Also still true (the pre-existing Lunge_* invariants — re-check them here):
#   • She leans ALREADY EXHAUSTED, with no step in darkness when the motion ends.
#   • The lunge happens BEFORE the damage numbers land.
#   • Neither card is left duplicated or displaced once the motion ends.
#
# ⚠ DECLINING must also lunge. The strike still happens whether or not the optional ability fires;
# only the attacker DYING to its own On Attack should suppress it (there is no strike then).

## GIVEN
CommonSetup: rrw/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
# Left PENDING on purpose: the On Attack prompt is exactly the moment the board must be still, so the
# schema stops here rather than answering it. Step past it by hand in the editor.
P1HASDECISION
