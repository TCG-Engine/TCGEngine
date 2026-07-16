# OnAttack_BountyDefenderDebuff
#// SHD_216 Chain Code Collector (4-cost 4/2 ground) — Ambush + "On Attack: If the defender has a Bounty, it
#// gets -4/-0 for this attack." Attacking the Bounty unit SHD_095 (2/3), the defender's counter-power drops
#// to 0, so the Collector takes no damage (and its 4 power defeats SHD_095).

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_216
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttack_NonBounty_NoDebuff
#// SHD_216 Chain Code Collector — against a NON-Bounty defender (SOR_046) there is no debuff, so its full
#// 3 counter-power kills the fragile Collector (2 HP), and SOR_046 survives with 4 damage.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
