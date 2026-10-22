# Sabe_LeaderWatcher_FiresWhenTheOverwhelmAttackerDies
#// Base-damage WATCHERS must not need the attacker alive (owner 2026-10-20: "fix the other damage watchers for their Overwhelm
#// pieces even when the unit dies"). An Overwhelm attacker spills combat damage onto the base and can die to the defender in
#// the same combat. SOR_232 AT-ST (6/7, Overwhelm) with 6 damage kills P2's SOR_128 Death Star Stormtrooper (3/1): 5 spills
#// onto P2's base, and the 3 back defeats the AT-ST. These sat below SWUCollectCombatHitTriggers's "attacker defeated → its OWN
#// triggers don't fire" return, so every one of them was skipped on that trade.
#// SEC_017 Sabé (ready, undeployed) — "When a friendly unit deals combat damage to a base: You may exhaust this leader …".
## GIVEN
CommonSetup: yyk/rrk/{myLeader:SEC_017}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:6
WithP2GroundArena: SOR_128:1:0
WithP2Deck: [SOR_095 SOR_046 SOR_095]
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:5
P1DECISIONTOOLTIP:Exhaust_Sabé_to_look_at_the_top_2_cards?

---

# CassianLeaderUnit_FiresWhenTheOverwhelmAttackerDies
#// SOR_013 Cassian Andor, deployed — "When you deal damage to an enemy base: You may draw a card. Use this ability only once each
#// round." The AT-ST (index 0; Cassian is index 1) trades as above and Cassian is offered the draw.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_013:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:6
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:5
P1DECISIONTOOLTIP:Cassian_Andor:_draw_a_card?

---

# TempleOfDestruction_FiresWhenTheOverwhelmAttackerDies
#// LOF_025 Temple of Destruction — "When a friendly unit deals 3 or more combat damage to an enemy base: The Force is with you."
#// The trade spills 5: P1 gets the Force token though the AT-ST is gone.
## GIVEN
CommonSetup: yyk/rrk/{myBase:LOF_025}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:6
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:5
P1HASFORCE

---

# StayOnTarget_GrantedDrawFiresWhenTheOverwhelmAttackerDies
#// JTL_177 Stay on Target — "Attack with a Vehicle unit. For this attack, it gets +2/+0 and gains: 'When this unit deals damage
#// to a base: Draw a card.'" The AT-ST (a Vehicle) attacks at 8: 7 spills onto the base, and it dies to the 3 back. It DID deal
#// damage to a base, so the granted draw still happens.
## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: JTL_177
WithP1GroundArena: SOR_232:1:6
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:7
P1HANDCOUNT:1

---

# RashAction_GrantedDiscardFiresWhenTheOverwhelmAttackerDies
#// ASH_162 Rash Action — "Attack with a unit. For this attack, it gets +1/+0 and gains: 'When Attack Ends: If this unit dealt
#// combat damage to an opponent's base, that opponent discards a card.'" No "(and survives)" rider and not on the
#// _SWUAttackEndRequiresSurvival roster, so it fires on a dead attacker (CR 7.6.16.c). The AT-ST attacks at 7: 6 spills, it dies,
#// and P2 discards one of its two cards.
## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: ASH_162
WithP1GroundArena: SOR_232:1:6
WithP2GroundArena: SOR_128:1:0
WithP2Hand: [SOR_095 SOR_046]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:6
P2HANDCOUNT:1
