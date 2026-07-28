# RevealCostMatchDamage
#// ASH_132 Queen Soruna (Ground, 5/7, cost 6) — When Played: you may reveal a unit from your hand; if you
#// do, deal 3 damage to a unit with the same cost as the revealed unit. P1 plays Soruna, reveals SOR_237
#// (cost 2), then deals 3 to the only cost-2 unit, SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_132,SOR_237}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P2GROUNDARENACOUNT:0

---

# DeclineReveal_NoDamage
#// ASH_132 Queen Soruna — the reveal is optional. Declining deals no damage.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_132,SOR_237}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# RevealNoSameCostUnit_NoDamage
#// ASH_132 Queen Soruna — revealing SOR_237 (cost 2) deals 3 to a cost-2 unit; with only SOR_046 (cost 4)
#// in play there is no valid target, so no damage is dealt.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_132,SOR_237}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_FriendlyTarget
#// ASH_132 Queen Soruna — the 3 damage may hit a FRIENDLY same-cost unit. P1 plays Soruna, reveals SOR_046
#// (cost 4); the only cost-4 unit is P1's own SOR_046, so it takes 3.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_132,SOR_046}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayed_NoUnitInHand_NoDamage
#// ASH_132 Queen Soruna — the reveal requires a UNIT in hand. With only an event (ASH_258) left in hand there
#// is nothing to reveal, so no damage is dealt.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_132,ASH_258}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_EmptyHand_NoDamage
#// ASH_132 Queen Soruna — with no other cards in hand there is nothing to reveal; no damage is dealt.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_132}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttack_EnemyTarget
#// ASH_132 Queen Soruna — she has the same ability On Attack. In-play Soruna attacks the base; On Attack she
#// reveals SOR_237 (cost 2) and deals 3 to the only cost-2 unit, SEC_080 (3/3), defeating it. Base takes 5.
## GIVEN
CommonSetup: ggk/ggk/{handCardIds:SOR_237}
WithP1GroundArena: ASH_132:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5

---

# OnAttack_FriendlyTarget
#// ASH_132 Queen Soruna — On Attack may hit a friendly same-cost unit. Soruna attacks the base, reveals
#// SOR_046 (cost 4); the only cost-4 unit is P1's own SOR_046 in space, which takes 3. Base takes 5.
## GIVEN
CommonSetup: ggk/ggk/{handCardIds:SOR_046}
WithP1GroundArena: ASH_132:1:0
WithP1SpaceArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:5

---

# OnAttack_Decline_NoDamage
#// ASH_132 Queen Soruna — On Attack reveal is optional. Declining deals no unit damage; base still takes 5.
## GIVEN
CommonSetup: ggk/ggk/{handCardIds:SOR_237}
WithP1GroundArena: ASH_132:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:5

---

# OnAttack_NoValidTarget_NoDamage
#// ASH_132 Queen Soruna — On Attack revealing SOR_237 (cost 2) deals 3 to a cost-2 unit; with only SOR_046
#// (cost 4) in play there is no valid target, so no damage is dealt. Base still takes 5.
## GIVEN
CommonSetup: ggk/ggk/{handCardIds:SOR_237}
WithP1GroundArena: ASH_132:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:5

---

# OnAttack_NoUnitInHand_NoDamage
#// ASH_132 Queen Soruna — On Attack with only an event (ASH_258) in hand, there is nothing to reveal; no
#// unit damage. Base still takes 5.
## GIVEN
CommonSetup: ggk/ggk/{handCardIds:ASH_258}
WithP1GroundArena: ASH_132:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:5

---

# OnAttack_EmptyHand_NoDamage
#// ASH_132 Queen Soruna — On Attack with an empty hand there is nothing to reveal; no unit damage. Base
#// still takes 5.
## GIVEN
CommonSetup: ggk/ggk/{}
WithP1GroundArena: ASH_132:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:5
