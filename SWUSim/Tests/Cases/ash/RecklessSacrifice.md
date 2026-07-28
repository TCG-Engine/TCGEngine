# DiscardThenDamageCostlier
#// ASH_163 Reckless Sacrifice (Event, cost 2) — Discard a unit from your hand, then deal 5 damage to a unit
#// that costs MORE than the discarded card. SOR_095 (cost 2) is the only hand unit (auto-discarded); SEC_135
#// (cost 3, 4/3) is the only unit costing more than 2 (auto-targeted) and is defeated by the 5 damage.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_163,SOR_095}
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# NoCostlierUnit_Fizzles
#// ASH_163 Reckless Sacrifice (Event, cost 2) — the target must cost STRICTLY MORE than the discarded card.
#// SOR_095 (cost 2) is discarded; the only enemy unit SEC_080 also costs 2 (equal, not more), so it is NOT
#// a legal target and the damage fizzles. The unit is still discarded (discard pile = 2) and SEC_080 lives.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_163,SOR_095}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1DISCARDCOUNT:2
P2GROUNDARENACOUNT:1

---

# ChooseDiscard_ThenDamageFriendly
#// ASH_163 Reckless Sacrifice — with several units in hand the player chooses WHICH unit to discard, and
#// the 5 damage may hit ANY unit (friendly or enemy) costing more than the chosen discard. P1 discards
#// Greedo (SOR_204, cost 1); both SEC_135 (friendly, cost 3) and SEC_080 (enemy, cost 2) then qualify.
#// P1 damages its own SEC_135 (4/3) for 5, defeating it (a can't-be-attacked unit is still damageable).
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_163,SOR_095,SOR_204}
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1DISCARDCOUNT:3
