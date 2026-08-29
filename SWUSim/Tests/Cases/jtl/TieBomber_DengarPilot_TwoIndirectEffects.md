# TwoSeparateEffects_DamageSplitsAcrossTWOPlayers
#// Bug report #993 (game 3608): "TIE Bomber piloted by Dengar should have allowed damage to be split
#// between 2 players because they are 2 separate effects."
#//
#// VERDICT: already correct — this file is the guard, not a fix. Both abilities exist independently and
#// target by their OWN rule:
#//   • JTL_237 TIE Bomber — "On Attack: Deal 3 indirect damage to THE DEFENDING PLAYER" (determined by
#//     the attack; resolved through SWUCurrentDefendingSeat, never OtherPlayer()).
#//   • JTL_139 Dengar, granted to the attached unit — "On Attack: Deal 2 indirect damage to A PLAYER"
#//     (a free CHOICE, and "a player" includes yourself — see the pool section below).
#//
#// P1 attacks SEAT 3's base and sends Dengar's half at SEAT 4, so the two land on different seats.
#//
#// ⚠ P3's base takes FOUR, not three, and that is not the indirect being wrong: Dengar is a +1/+2 pilot,
#// so TIE Bomber's printed power 0 becomes 1 and the attack itself deals 1 combat damage on top of the
#// 3 indirect. Asserting 3 here would be asserting a bug (measured — it was my first expectation).
#// TIE Bomber is Imperial/Vehicle/Fighter, NOT Underworld, so Dengar's clause deals 2 rather than 3 —
#// which also keeps the two numbers distinct and so tells the two effects apart.
#//
#// Neither far seat controls a unit, so each indirect assignment auto-resolves onto that seat's base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6; myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1SpaceArenaUpgrade: 0:JTL_139
WithP3Base: SOR_019
WithP4Base: SOR_019

## WHEN
- P1>AttackSpaceArena:0:P3B
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:P4

## EXPECT
P3BASEDMG:4
P4BASEDMG:2
P2BASEDMG:0
P1NODECISION

---

# TwoSeparateEffects_TheyAreOrderable_NotMerged
#// The structural half of the report: "2 separate effects". Two On Attack abilities fire in the same
#// combat — the unit's own and the one its pilot grants — so the engine must offer the CR ordering
#// choice over both rather than fusing them into a single 5-point pool.
#// The decision is left pending so the prompt itself can be read; a merged implementation would show no
#// EffectStack choice at all.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6; myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1SpaceArenaUpgrade: 0:JTL_139
WithP3Base: SOR_019
WithP4Base: SOR_019

## WHEN
- P1>AttackSpaceArena:0:P3B

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# DengarsHalfOffersEVERYSeat_IncludingYourOwn
#// "A PLAYER", not "an opponent" — so the pool must contain all four live seats INCLUDING P1 itself.
#// That distinction is a documented trap: "an opponent" excludes you, "a player" does not, and the two
#// helpers are not interchangeable. Aiming indirect damage at your own base is a legal (if unusual)
#// play, so the menu offering it is correct rather than a leak.
#// This section cannot pass at two seats — it asserts P3 and P4 are on the menu.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6; myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1SpaceArenaUpgrade: 0:JTL_139
WithP3Base: SOR_019
WithP4Base: SOR_019

## WHEN
- P1>AttackSpaceArena:0:P3B
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1OPTIONHAS:P1
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1DECISIONTOOLTIP:Choose_a_player_to_deal_indirect_damage

---

# BothHalvesMayAlsoLandOnTHESAMESeat
#// The degenerate case, and the control for the headline section: the two effects are INDEPENDENT, not
#// forced apart. Pointing Dengar's half at the defending seat too stacks everything on P3 —
#// 1 combat + 3 (TIE Bomber) + 2 (Dengar) = 6 — while P4 is untouched.
#// Without this, "the damage split" could be satisfied by an implementation that spreads the two halves
#// automatically and never lets you concentrate them.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6; myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1SpaceArenaUpgrade: 0:JTL_139
WithP3Base: SOR_019
WithP4Base: SOR_019

## WHEN
- P1>AttackSpaceArena:0:P3B
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:P3

## EXPECT
P3BASEDMG:6
P4BASEDMG:0
P1NODECISION
