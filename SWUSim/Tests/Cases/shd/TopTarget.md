# Bounty_Heal4_NonUniqueHost
#// SHD_071 Top Target — "Bounty — Heal 4 damage from a unit or base. If this unit is unique, heal 6
#// instead." Host is the NON-unique Battlefield Marine → heal 4. P1's base starts at 6 damage; after
#// collecting and choosing their own base, exactly 4 heals (6 → 2) — the value distinguishes the
#// 4-vs-6 formula.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_071

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:2

---

# Bounty_Heal6_UniqueHost
#// SHD_071 Top Target on a UNIQUE host → heal 6 instead of 4. Host is Synara San (SHD_033, unique
#// 3/6 Grit), kept READY so her own exhausted-only bounty does NOT trigger — only Top Target's is
#// offered. She starts at 2 damage; LAW_124's 4 damage defeats her (Grit counter 3+2=5 onto the
#// attacker). P1's base starts at 6 → heals 6 → 0.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_033:1:2
WithP2GroundArenaUpgrade: 0:SHD_071

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:5
