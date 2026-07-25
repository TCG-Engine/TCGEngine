# ReturnUpgradeAndShield
#// ASH_232 Full of Surprises (Event, cost 2) — Return an upgrade that costs 2 or less to its owner's hand,
#// then give a Shield token to a unit. SOR_120 (cost 2) on SOR_095 is the only ≤2 upgrade (auto-returned);
#// the Shield then goes to SOR_095 (the only unit, auto-targeted). SOR_095 reverts to 3 power and gains a Shield.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_232}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# NoUpgradesToReturn_StillGivesShield
#// ASH_232 Full of Surprises — the two clauses are independent. With no upgrades in play there is nothing
#// to return, but the Shield is still given to a unit (auto-targeted to the lone SOR_095).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_232}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# UpgradeCostingMoreThanTwo_NotReturnable
#// ASH_232 Full of Surprises — only upgrades costing 2 or less can be returned. LOF_091 (Craving Power,
#// cost 5) is not a legal return target, so nothing is bounced (hand stays empty); the Shield is still given.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_232}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_091
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# ChooseWhichUnitGetsShield
#// ASH_232 Full of Surprises — after returning the only 2-or-less upgrade (SOR_120 on SOR_095, back to
#// power 3 and into P1's hand), the Shield may be given to any unit; here it is put on the second unit
#// SOR_164 (Wampa) instead of SOR_095.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_232}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
