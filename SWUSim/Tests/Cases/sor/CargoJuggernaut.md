# WhenPlayed_HealsBaseIfVigilance
#// SOR_068 Cargo Juggernaut (4/6, Ground, Vigilance) — Shielded + When Played: If you control
#// another [Vigilance] unit, heal 4 damage from your base. P1 already controls 2-1B (SOR_059,
#// Vigilance), so the condition holds: the base (pre-damaged 4) heals to 0. The Juggernaut also
#// enters with a Shield (Shielded). Two entry triggers (Shielded + When Played) → order them
#// via the EffectStack choice; both then resolve automatically (When Played is not optional).
#// COVERAGE: offer=WhenPlayed_HealsYOURBaseOnly_NoTargetIsOffered — neither clause takes a target
#//           (Shielded goes to the played unit, the heal to "your base"), so the pool is fixed to one
#//           and the auto-resolution IS the assertion: P1NODECISION plus the opponent's base sitting
#//           untouched at 4 proves no picker was raised and no other base was reachable ·
#//           reqboundary=the two entry triggers are ordered through an EffectStack answer in EVERY
#//           section of this file, which is a serialized decision round-trip; neither clause carries
#//           any state past it (the gate is re-measured from the board and the heal amount is a
#//           constant), so there is no transient continuation to lose · control=ControlTakenVigilance
#//           Unit_CountsForTheCONTROLLER + ControlTakenVigilanceUnit_DoesNOTCountForItsOWNER (owner
#//           differs from controller; "if you CONTROL another Vigilance unit" must count for exactly
#//           one of the two seats) · boundary=WhenPlayed_HealClampsAtZero_LessDamageThanTheHeal (2
#//           damage -> 0, no overflow) vs WhenPlayed_HealIsExactlyFour_SixLeavesTwo (6 -> 2), the pair
#//           that pins the heal at exactly 4 rather than "all" · decline=N/A — neither clause is
#//           printed as "you may": Shielded is a keyword and the heal is an unconditional consequence
#//           of the If, so no branch is declinable. The If's false side is the negative, held by
#//           WhenPlayed_NoVigilance_NoHeal (wrong aspect), WhenPlayed_JuggernautAlone_ANOTHERExcludes
#//           ITSELF (self-exclusion) and WhenPlayed_EnemyVigilanceUnitDoesNotCount (wrong controller).

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0    # another Vigilance unit (2-1B) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# WhenPlayed_NoVigilance_NoHeal
#// SOR_068 Cargo Juggernaut — the heal is conditional on controlling ANOTHER Vigilance unit.
#// Here P1's only other unit is Battlefield Marine (Command, not Vigilance), so the condition
#// fails and the base stays damaged. The Shielded token is still granted (unconditional). The
#// Juggernaut is itself Vigilance, but "another" excludes itself. Absence guard for the filter.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (Command, NOT Vigilance) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# WhenPlayed_AnotherVigilanceUnitInSPACE_AlsoCounts
#// SOR_068 Cargo Juggernaut — "If you control another [Vigilance] UNIT" says nothing about an arena, so
#// the condition scans SPACE as well as ground. P1's only other unit is SOR_066 System Patrol Craft
#// (Vigilance, space); the Juggernaut itself lands on the ground. The gate holds and the base heals
#// 4 -> 0. The Shield still lands on the Juggernaut.
#// The existing positive seats its Vigilance unit on the GROUND, so an implementation that searched
#// only myGroundArena would pass it and fail nothing else.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP1SpaceArena: SOR_066:1:0    # System Patrol Craft (Vigilance, SPACE)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayed_JuggernautAlone_ANOTHERExcludesITSELF
#// SOR_068 Cargo Juggernaut — the Juggernaut IS a [Vigilance] unit, so the word "ANOTHER" is the only
#// thing stopping it from satisfying its own condition. It is played onto an empty board: the base
#// stays at 4 damage. The Shielded token still arrives, because that clause is unconditional.
#// WhenPlayed_NoVigilance_NoHeal proves the ASPECT filter bites; this proves the self-exclusion does.
#// They are different bugs — dropping "another" leaves the aspect check intact and vice versa — and
#// each section is green under the other's defect.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayed_EnemyVigilanceUnitDoesNotCount
#// SOR_068 Cargo Juggernaut — "if YOU CONTROL another [Vigilance] unit". The only other Vigilance unit
#// on the table is the OPPONENT's 2-1B Surgical Droid, which P1 does not control, so the gate fails and
#// P1's base stays at 4. The third and last way the condition can be got wrong (after the aspect filter
#// and the self-exclusion): a scan written over all units rather than over the controller's own.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP2GroundArena: SOR_059:1:0    # 2-1B (Vigilance) — the OPPONENT's

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:1

---

# WhenPlayed_HealClampsAtZero_LessDamageThanTheHeal
#// SOR_068 Cargo Juggernaut — "heal 4 damage from your base" with only 2 damage on it. The base ends at
#// 0, not at -2: a heal removes damage counters and cannot go below zero, and it certainly cannot bank
#// the surplus. Pairs with WhenPlayed_HealIsExactlyFour_SixLeavesTwo as the two sides of the quantity.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:2;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0    # another Vigilance unit — the gate holds

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:2

---

# WhenPlayed_HealIsExactlyFour_SixLeavesTwo
#// SOR_068 Cargo Juggernaut — the heal is a fixed 4, not "all damage". With 6 damage on the base, 2
#// remain. The existing positive starts from exactly 4 damage and lands on 0, which is equally
#// consistent with "heal the base fully"; this section is the one that pins the number.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:6;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:2

---

# WhenPlayed_HealsYOURBaseOnly_NoTargetIsOffered
#// SOR_068 Cargo Juggernaut — "heal 4 damage from YOUR BASE" names its target, so there is nothing to
#// choose: the destination is fixed to the controller's own base. Both bases start at 4 damage; only
#// P1's is healed, P2's is untouched, and once the entry triggers have resolved no decision is left
#// pending. The auto-resolution IS the offer assertion here — a P1SELECTABLEEXACT has nothing to read
#// because no target choice is ever raised.
#// Guards the two ways a fixed-target heal goes wrong: a picker appearing at all, and the heal landing
#// on "a base" (either one) instead of the controller's.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;theirBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:0
P2BASEDMG:4
P1NODECISION

---

# ControlTakenVigilanceUnit_CountsForTheCONTROLLER
#// SOR_068 Cargo Juggernaut × a control change. The 2-1B Surgical Droid (Vigilance) sits in P1's arena
#// under P1's CONTROL but is OWNED by P2 — the end state after a take-control effect. "If you CONTROL
#// another [Vigilance] unit" is a control test, not an ownership test, so the gate holds for P1 and
#// their base heals 4 -> 0.
#// A condition that counted by OWNER instead would find nothing here and silently skip the heal, while
#// staying green on every same-seat section in this file.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_059:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0

---

# ControlTakenVigilanceUnit_DoesNOTCountForItsOWNER
#// SOR_068 Cargo Juggernaut × a control change, the inverse. The same P2-owned 2-1B is under P1's
#// control, and now P2 plays their OWN Cargo Juggernaut with 4 damage on their base. They own a
#// Vigilance unit but do not CONTROL it, so the gate fails and their base stays at 4 — while the
#// Shielded clause, which asks nothing about the board, still gives their Juggernaut its token.
#// Without this, ControlTakenVigilanceUnit_CountsForTheCONTROLLER is satisfied by an implementation
#// that lets BOTH seats count the same unit.

## GIVEN
CommonSetup: ggw/ggw/{theirResources:10;theirBaseDamage:4}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArenaControlled: SOR_059:2
WithP2Hand: SOR_068

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:EffectStack-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENACOUNT:1
