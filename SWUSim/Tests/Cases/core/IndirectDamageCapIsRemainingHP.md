# IndirectCap_UpgradedUnit_UsesCURRENTHPNotPrinted
#// Bug report #994 (game 3608): "indirect damage is not being selected correctly on an upgraded TIE
#// Bomber." The reported symptom was never pinned down, so these sections pin the RULE instead — every
#// reading of "upgraded" that could plausibly produce a wrong assignment cap.
#//
#// CR 8.35.3: "A player cannot assign more indirect damage to a unit than it has REMAINING HP."
#// Remaining HP is current HP (printed + upgrades + buffs) MINUS damage already on the unit — so a
#// printed-HP cap is wrong in both directions: too small on an upgraded unit, too large on a damaged one.
#//
#// JTL_237 TIE Bomber is printed 0/4. With SOR_120 Academy Training (+2/+2) it is 2/6 and undamaged, so
#// the offer must cap it at 6, not the printed 4. The decision is left PENDING so the offered cap can be
#// read directly — answering it would prove the branch and say nothing about the pool.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP2SpaceArena: [JTL_237:1:0]
WithP2SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Assign_3_indirect_damage

---

# IndirectCap_UpgradedANDDamaged_IsWhatREMAINS
#// The other direction, and the one a "use current HP" fix could still get wrong: current HP 6 with 4
#// damage already on the unit leaves REMAINING HP 2, so the cap must be 2 — not 6, and not the printed 4.
#// Pairing this with the section above is what pins "current minus damage" rather than either half alone.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP2SpaceArena: [JTL_237:1:4]
WithP2SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0:2,myBase-0:1

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:1

---

# IndirectCap_PILOTUpgradeCountsToo_NotJustPlainUpgrades
#// A Pilot contributes its upgradePower/upgradeHp to the host, and it is a different subcard kind from
#// a plain upgrade — so "upgraded" in the report could mean piloted, which is exactly what game 3608
#// had (Dengar aboard a TIE Bomber). JTL_139 Dengar is +1/+2, giving 1/6 and therefore a cap of 6.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP2SpaceArena: [JTL_237:1:0]
WithP2SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0:3

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENACOUNT:1
P2BASEDMG:0

---

# IndirectCap_ShieldTokenAddsNOHP_AndIsNOTDefeated
#// CR 8.35.2.a: indirect damage assigned to a unit carrying Shield tokens "is placed as though the unit
#// did not have any Shield tokens on it. The Shield tokens are not defeated or affected by the damage."
#//
#// So a Shield is the one "upgrade" that must NOT raise the cap — the TIE Bomber stays at 4 — and the
#// token must still be there afterwards. Both halves matter: a naive fix that routes indirect through
#// the ordinary damage funnel would consume the Shield and absorb the damage, and this is the only
#// section here that would catch it.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP2SpaceArena: [JTL_237:1:0]
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0:3

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
P2BASEDMG:0

---

# IndirectCap_TheBaseIsAnUNLIMITEDSink
#// The deliberate exception to the cap: units are limited to remaining HP, the BASE is not. The whole
#// pool may be dumped on the base even when that takes it past its remaining HP, so the base's spec is
#// capped only at the pool size.
#// Without this, "cap everything at remaining HP" reads as the safe general rule and quietly breaks the
#// commonest indirect play there is.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP2SpaceArena: [JTL_237:1:0]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myBase-0:3

## EXPECT
P2BASEDMG:3
P2SPACEARENAUNIT:0:DAMAGE:0
