# FriendlyHiddenCantBeAttacked
#// LOF_211 Dooku — Hidden + When Played: each friendly unit with Hidden can't be attacked for this phase.
#// P1 has a GIVEN-placed Hidden unit (LOF_228, normally attackable). Playing Dooku marks it can't-be-
#// attacked, so P2's attack finds no valid unit target and auto-redirects to P1's base.

## GIVEN
CommonSetup: yyk/yyw/{myResources:4;handCardIds:LOF_211}
WithActivePlayer: 1
WithP1GroundArena: LOF_228:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3

---

# LostAbilities_HiddenUnitNotProtected
#// LOF_211 Dooku — the protection extends only to units that CURRENTLY have Hidden. A friendly Grogu (LOF_246,
#// innate Hidden) wearing Imprisoned (SHD_072, "loses all abilities") has lost Hidden, so Dooku's When Played
#// does not shield it: an enemy Battlefield Marine can still attack Grogu (which survives on 6 HP, taking 3).
#// Dooku himself (just played, Hidden) stays protected, so the base is untouched. Intended: "should not protect
#// units with Hidden that have lost their abilities."

## GIVEN
CommonSetup: yyk/yyw/{myResources:4;handCardIds:LOF_211}
WithActivePlayer: 1
WithP1GroundArena: LOF_246:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0

---

# EnemyHiddenUnit_NotProtected
#// LOF_211 Dooku — the protection is "each FRIENDLY unit with Hidden". An ENEMY unit that has Hidden is
#// not covered, so P1 can still attack it after playing Dooku. P2's Grogu (LOF_246, innate Hidden, 1/6)
#// is attacked by P1's Battlefield Marine (3/3) and takes 3. (Grogu's own Hidden only blocks attacks on
#// the phase it was PLAYED; here it is GIVEN-placed, so it is attackable to begin with.)
## GIVEN
CommonSetup: yyk/yyw/{myResources:4;handCardIds:LOF_211}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_246:1:0
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LOF_246
P2GROUNDARENAUNIT:0:DAMAGE:3
