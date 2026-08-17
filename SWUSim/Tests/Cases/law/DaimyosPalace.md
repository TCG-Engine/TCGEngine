# IgnoreOneAspectPenalty
#// LAW_020 Daimyo's Palace (Vigilance common base) — Epic Action: Play a card from your hand, ignoring 1
#//   of its Vigilance/Command/Aggression/Cunning aspect penalties. P1 (Vigilance base + Vigilance/Heroism
#//   leader) plays an off-aspect Aggression unit (SEC_161, cost 2) — normally cost 2 + 2 penalty = 4, but
#//   the base waives the Aggression pip → pays the printed 2. Epic is consumed.

## GIVEN
CommonSetup: bbw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_161

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_161
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# OnlyOnePipWaived
#// LAW_020 — only ONE battlefield-aspect pip is waived, and NOT an alignment pip. SOR_128 (Aggression,
#//   Villainy, cost 1) has penalty 4 (Aggression +2, Villainy +2). The base waives the Aggression pip
#//   only → effective cost 1 + 2 (Villainy) = 3. With exactly 3 resources it plays, leaving 0 ready
#//   (a "waive all" bug would cost 1, leaving 2 ready).

## GIVEN
CommonSetup: bbw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_128

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:0

---

# MultipleSamePenaltyAspect_OnlyOneIgnored
#// LAW_020 — a card with the SAME penalty aspect twice: SHD_107 Enterprising Lackeys (Command, Command,
#//   cost 4). P1 is Vigilance/Heroism, so NEITHER Command pip is covered → +4 penalty. The base waives
#//   exactly ONE Command pip → +2 remaining → effective 4 + 2 = 6. With exactly 6 resources it plays,
#//   leaving 0 ready (a "waive both" bug would cost 4, leaving 2; a "waive none" bug needs 8 and no-ops).

## GIVEN
CommonSetup: bbw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_107

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_107
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# MultipleSameAspect_OnlyOnePenalized_Ignored
#// LAW_020 — a card with the SAME aspect twice where the player already covers ONE of them: TWI_054
#//   Duchess's Champion (Vigilance, Vigilance, cost 4). P1 aspects are Vigilance (base) + Cunning/Heroism
#//   (leader) → exactly ONE Vigilance source, so one Vigilance pip is covered and the SECOND is penalized
#//   (+2). The base waives that one → effective cost 4. With exactly 4 resources it plays, leaving 0 (no
#//   waive would need 6 and no-op).

## GIVEN
CommonSetup: byw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: TWI_054

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_054
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# DoesNotIgnoreVillainyPenalty
#// LAW_020 — the base only waives Vigilance/Command/Aggression/Cunning pips, NEVER Villainy. LOF_034
#//   Supremacy TIE/sf (Vigilance, Villainy, cost 3). P1 (Vigilance/Heroism) covers the Vigilance pip, so
#//   the only penalty is the Villainy pip (+2), which is NOT ignorable → effective 3 + 2 = 5. With exactly
#//   5 resources it plays, leaving 0 (a bug that waived Villainy would cost 3, leaving 2).

## GIVEN
CommonSetup: bbw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: LOF_034

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LOF_034
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# DoesNotIgnoreHeroismPenalty
#// LAW_020 — likewise the base never waives Heroism. SHD_043 Village Protectors (Heroism, Vigilance,
#//   cost 3). P1 is Vigilance/Villainy, so the Vigilance pip is covered and the Heroism pip (+2) is NOT
#//   ignorable → effective 3 + 2 = 5. With exactly 5 resources it plays, leaving 0 (a bug that waived
#//   Heroism would cost 3, leaving 2).

## GIVEN
CommonSetup: bbk/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SHD_043

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_043
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# NoDiscountWhenNoPenalizedAspects
#// LAW_020 — if the played card has no penalized aspects, the base gives NO discount. JTL_198 Fireball
#//   (Cunning, Heroism, cost 2). P1 is Vigilance/Cunning/Heroism, covering BOTH of Fireball's aspects, so
#//   there is nothing to waive → cost stays at the printed 2. With exactly 2 resources it plays, leaving 0
#//   (a bug that discounted anyway would leave ready resources).

## GIVEN
CommonSetup: byw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: JTL_198

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_198
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# NoDiscountWhenCardHasNoAspects
#// LAW_020 — a card with NO aspect icons can never be penalized, so the base gives no discount. JTL_260
#//   Death Star Plans (upgrade, no aspects, cost 2) is played via the Epic Action onto the lone friendly
#//   unit (SEC_080). Cost stays at the printed 2 → with exactly 2 resources it plays, leaving 0, and the
#//   upgrade attaches.

## GIVEN
CommonSetup: bbw/brk/{
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: JTL_260
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# NoExtraDiscountWhenAnotherEffectIgnoresAll
#// LAW_020 — when another effect already ignores ALL of a card's aspect penalties, the base's waive gives
#//   no EXTRA discount. Leader Hera Syndulla (SOR_008) ignores every aspect penalty on SPECTRE cards. She
#//   plays LAW_078 Sabine Wren (Aggression/Cunning/Heroism Spectre, cost 3) via the base: Hera already
#//   waives all of Sabine's penalties → cost is the printed 3. With exactly 3 resources it plays, leaving 0.
#//   (If Hera's waive were missing, the base only ignores 1 of Sabine's 2 battlefield pips → cost 5 > 3 →
#//   no play.)

## GIVEN
CommonSetup: bgw/brk/{
  myLeader:SOR_008;
  myBase:LAW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: LAW_078

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_078
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# MultipleSpecificAspectIgnoringEffects
#// LAW_020-family (Aggression common base LAW_025) — two DIFFERENT specific aspect-ignoring effects stack.
#//   SHD_046 Rey (Vigilance/Heroism, cost 5) ignores her OWN Heroism penalty while you control Kylo Ren.
#//   P1's leader is Kylo Ren (SHD_011), so Rey waives Heroism, and the Aggression common base waives the
#//   Vigilance pip → both penalties gone → cost stays the printed 5. With exactly 5 resources it plays,
#//   leaving 0. (Missing either waive would push cost to 7 > 5 → no play.)

## GIVEN
CommonSetup: rrk/brk/{
  myLeader:SHD_011;
  myBase:LAW_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SHD_046

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_046
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# SoftPassWithAnEmptyHand
#// LAW_020 — the Epic Action costs nothing but itself, so it is always usable even with no card to play.
#// With an empty hand it resolves to nothing: the Epic is spent, no resources move, and no decision is
#// left pending. (Same soft-pass shape as an exhaust-only leader Action — a no-target ability must still
#// be usable rather than being blocked by an affordability/target gate.)

## GIVEN
CommonSetup: bbw/brk/{myBase:LAW_020}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5

## WHEN
- P1>UseBaseAbility

## EXPECT
P1BASE:EPICUSED
P1RESAVAILABLE:5
P1NODECISION

---

# SoftPassWhenTheHandIsUNAFFORDABLEEvenAfterTheWaiver
#// LAW_020 — the sharper version of the soft pass: the hand is not empty, it is just out of reach. With 1
#// resource and SHD_107 Enterprising Lackeys (cost 4, +4 for two uncovered Command pips), waiving one pip
#// still leaves 6 — so nothing is playable. The Epic is spent, the card stays in hand, and the board is
#// unchanged.
#// This is the case a naive "offer only affordable cards, else do nothing" gate gets wrong by silently
#// refusing to spend the Epic at all.

## GIVEN
CommonSetup: bbw/brk/{myBase:LAW_020}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SHD_107

## WHEN
- P1>UseBaseAbility

## EXPECT
P1BASE:EPICUSED
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0

---

# P2Seat_EpicActionPlaysFromTHEIROwnHand
#// COVERAGE: offer=not asserted as a pool (every section leaves exactly one playable card, so the hand
#//           choice auto-resolves) · reqboundary=N/A (with a single playable card no decision is opened,
#//           so no answer crosses a request boundary) ·
#//           control=P2Seat_EpicActionPlaysFromTHEIROwnHand — a BASE can never change control, so seat
#//           resolution is the only observable form of the owner-vs-controller question here ·
#//           boundary=IgnoreOneAspectPenalty vs OnlyOnePipWaived / MultipleSamePenaltyAspect_OnlyOneIgnored
#//           / DoesNotIgnoreVillainyPenalty / DoesNotIgnoreHeroismPenalty / NoDiscountWhenNoPenalizedAspects
#//           · decline=SoftPassWithAnEmptyHand + SoftPassWhenTheHandIsUNAFFORDABLEEvenAfterTheWaiver.
#// LAW_020 — "Play a card from YOUR hand" on a Palace belonging to seat 2. The whole file drives the Epic
#// Action from seat 1, so a hand lookup pinned to P1 would never show up. P2 (Vigilance base +
#// Cunning/Villainy leader) holds the off-aspect SEC_161 Contraband Starhopper (Aggression, cost 2, +2
#// penalty) and P1 holds SOR_128. With exactly 2 resources P2 can afford SEC_161 ONLY if the waiver
#// applies from P2's seat, so the play itself proves the discount ran on the right side: the Starhopper
#// lands in P2's SPACE arena, P2 is left on 0 resources and P2's Epic is spent, while P1's hand still
#// holds its card and both of P1's arenas stay empty.

## GIVEN
CommonSetup: bbw/byk/{theirBase:LAW_020}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 2
WithP2Hand: SEC_161
WithP1Hand: SOR_128

## WHEN
- P2>UseBaseAbility

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SEC_161
P2RESAVAILABLE:0
P2BASE:EPICUSED
P1HANDCOUNT:1
P2HANDCOUNT:0
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
