# AnotherGunganUnit_GainsShielded
#// HMW_084 Gunga City Guard (Vigilance, Gungan, 2-cost 2/1 Ground) —
#// "Restore 1 / While you control another Gungan unit or Naboo base, this unit gains Shielded."
#// COVERAGE: offer=N/A (static keyword grant, no target pick) · negative=NeitherEnabler_NoShielded +
#//           EnemyGunganDoesNotCount (the "you control" half) + DoesNotCountItself ·
#//           boundary pair=each enabler proven ALONE (Gungan unit with a non-Naboo base; Naboo base with
#//           no other Gungan) so neither can hide the other · control=StolenGuard_ReadsTheNEWControllers
#//           Board · reqboundary=PlayedWithEnabler_EntersWithAShieldToken (the token is written at entry
#//           and read after) · decline=N/A (no "you may")
#// ⚠ TWO DISTINCT THINGS ARE TESTED, and conflating them is the trap on this card:
#//   (a) HAVING the keyword — a continuous read, true whenever the condition holds;
#//   (b) GETTING a Shield TOKEN — which happens only as the unit ENTERS PLAY (GameLogic's entry hook).
#// A seeded unit never runs the entry hook, so it can legitimately have the keyword and NO token.
#// EnablerArrivesLater_NoRetroactiveShield pins exactly that distinction.
#// Here: another friendly Gungan (LOF_247) with a NON-Naboo base, so the unit half is proven alone.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029}
P1OnlyActions: true
WithP1GroundArena: HMW_084:1:0
WithP1GroundArena: LOF_247:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_084
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded

---

# NabooBase_GainsShielded
#// HMW_084 — the OTHER enabler, proven ALONE: a Naboo base (HMW_033 Otoh Gunga) with NO other Gungan
#// unit in play. The two halves of an "A or B" condition must each be shown independently, or a
#// single-branch implementation passes.

## GIVEN
CommonSetup: bbw/bgw/{myBase:HMW_033}
P1OnlyActions: true
WithP1GroundArena: HMW_084:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_084
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded

---

# NeitherEnabler_NoShielded_AndDoesNotCountItself
#// HMW_084 — the negative, and simultaneously the "ANOTHER Gungan" proof: the Guard is ITSELF a Gungan
#// unit, so a self-counting implementation would grant Shielded here. With a non-Naboo base and no other
#// Gungan, the condition is false.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029}
P1OnlyActions: true
WithP1GroundArena: HMW_084:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_084
P1GROUNDARENAUNIT:0:NOTKEYWORD:Shielded

---

# EnemyGunganDoesNotCount
#// HMW_084 — "while YOU control" — the only other Gungan unit belongs to P2, so the condition is false.
#// Without this section a scan of ALL units in play would satisfy every other test.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029}
P1OnlyActions: true
WithP1GroundArena: HMW_084:1:0
WithP2GroundArena: LOF_247:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Shielded

---

# PlayedWithEnabler_EntersWithAShieldToken
#// HMW_084 — the EFFECT, not just the keyword. Shielded gives a Shield token as the unit ENTERS PLAY,
#// so the condition has to be true at that moment. Played from hand with another Gungan already out.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029;myResources:3}
P1OnlyActions: true
WithP1GroundArena: LOF_247:1:0
WithP1Hand: HMW_084

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_084
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# PlayedWithoutEnabler_NoShieldToken
#// HMW_084 — the matching negative for the effect: played with no other Gungan and a non-Naboo base, it
#// enters with no Shield token.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029;myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_084

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_084
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# EnablerArrivesLater_NoRetroactiveShield
#// HMW_084 — the sharp case, and the reason (a) and (b) above must be tested separately.
#// The Guard is played with NO enabler (so it enters with no Shield). A Gungan Warrior is then played,
#// making the condition true: the Guard now HAS the keyword — but Shielded already missed its window,
#// so NO token is granted retroactively. Keyword true + token zero, in one section.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029;myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_084
WithP1Hand: LOF_247

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_084
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# StolenGuard_ReadsTheNEWControllersBoard
#// HMW_084 — "you control" follows whoever CONTROLS the Guard. P1 OWNS it, P2 CONTROLS it, and the other
#// Gungan unit is P2's; P1's base is non-Naboo. An owner-scoped read would find nothing and deny the
#// keyword. (The controlled unit seeds AFTER the plain one, so the Guard is at ground index 1.)

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029;theirBase:SOR_029}
P1OnlyActions: true
WithP2GroundArenaControlled: HMW_084:1
WithP2GroundArena: LOF_247:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:HMW_084
P2GROUNDARENAUNIT:1:HASKEYWORD:Shielded

---

# RestoreOneIsInnate_AndIndependentOfTheShieldedCondition
#// HMW_084 — Restore 1 is PRINTED, so it must work whether or not the Shielded condition holds. Board is
#// the no-enabler one (non-Naboo base, no other Gungan): attacking still heals 1 from P1's base, 3 -> 2.
#// Guards against an implementation that accidentally gates both keywords behind the one condition.

## GIVEN
CommonSetup: bbw/bgw/{myBase:SOR_029;myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_084:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2
