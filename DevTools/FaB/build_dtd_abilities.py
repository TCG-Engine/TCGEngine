"""Author DTD from the cached catalog; never silently accept an unhandled card."""
import ast,json,hashlib
from pathlib import Path
HERE=Path(__file__).parent
for filename,names in [('build_mon_abilities.py',['clean','choice']),('build_dyn_abilities.py',['pick','multi']),('build_out_abilities.py',['pay']),('build_upr_abilities.py',['deal'])]:
 for node in ast.parse((HERE/filename).read_text(encoding='utf-8')).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment((HERE/filename).read_text(encoding='utf-8'),node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
VICTIM="$victim = intval(FaBGetState()['defender']);"
def multi(expr,minimum,maximum,tip):
 return f'$refs = {expr}; $maximum = min({maximum}, count(array_filter(explode("&", $refs)))); $chosen = "-"; if ($maximum >= {minimum} && $maximum > 0) {{ $chosen = await $player.MZMultiChoose($refs, {minimum}, $maximum, "{tip}"); }}'
def hit(code):return 'if (FaBFaiHeroHit()) { '+VICTIM+code+' }'
def token(id,n=1):return f"FaBOUTToken($player, '{id}', {n});"
def select(expr,tip='Choose_card',may=True,var='$chosen',p='$player'):return pick(expr,tip,may,var=var).replace('await $player.',f'await {p}.')
def tag(expr,tip,tag):return select(expr,tip,False)+f"FaBDYNTag($chosen, '{tag}');"
def reserve_cost(reserve=1):
 return "$cost = intval(FaBGetState()['pendingPayment']['cost'] ?? 0); while (intval(GetResources($player)) < $cost) { "+select(f'FaBDTDCostPitchChoices($player, {reserve})','Pitch_before_banish_cost',False)+"if ($chosen === 'PASS') { break; } FaBARCPitchForEffect($player, $chosen); }"
def soul():return select('FaBMONSoul($player)','Banish_from_soul')+"if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Soul', 'Banish'); "
def anydamage(n,opposing=False):return select(('FaBDTDOpposingTargets($player)' if opposing else 'FaBUPRAnyTargets($player)'),'Choose_damage_target',False)+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);"+deal(n)
prevention=Path(HERE/'../../FaBSim/Custom/CodeGeneration.php').read_text().split("<<<'CODE'\n",1)[1].split('\nCODE;',1)[0]
existing={c['cardId']:c for p in sorted(HERE.glob('*_abilities.json')) if p.name!='dtd_abilities.json' for c in json.loads(p.read_text())}
old={c['cardId']:c for c in json.loads((HERE/'dtd_abilities.json').read_text())} if (HERE/'dtd_abilities.json').exists() else {}
out=[];pending=[]
for c in json.loads((HERE/'dtd_catalog.json').read_text()):
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);a=[];handled=False
 def add(m,code):
  global handled
  handled=True;a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id=='runechant':
  add('ResolveCard',UID+"FaBDTDArmRune($player, $uid); $targetUID = FaBUPRHeroUID(intval(FaBARCCard($uid, 'target'))); "+deal(1));out.append(dict(cardId=id,abilities=a));continue
 if id in existing:out.append(existing[id]);continue
 if b in ['beaming_bravado','light_the_way']:
  out.append(dict(cardId=id,abilities=existing[b+'_red']['abilities']));continue
 if b in ['prism_advent_of_thrones','prism_awakener_of_sol']:
  add('PrepareCard',UID+select('FaBMONSoul($player)','Banish_from_soul',False)+"FaBMoveChoice($player, $chosen, 'Soul', 'Banish');"+select("FaBDTDRefs($player, 'Arena', 'figment')",'Awaken_figment',False)+"FaBARCSetCard($uid, 'dtdTarget', intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0)); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"if (DecisionQueueController::GetVariable('dtdSearch')) { $mode = await $player.Modal(1, 1, \"Decline&Search_deck\", \"Search_for_figment\"); if ($mode === '1') { "+select("FaBDTDRefs($player, 'Deck', 'figment')",'Choose_figment')+"if ($chosen !== 'PASS') { FaBDTDFindFigment($player, $chosen); } FaBShuffleDeck($player); } } else { $uid = intval(DecisionQueueController::GetVariable('fabAbilityStackUID')); $targetUID = intval(FaBARCCard($uid, 'dtdTarget')); $target = FaBDTDSource($targetUID); FaBDTDAwaken($player, $target); }")
 if b=='luminaris_celestial_fury':add('ResolveAbility',tag("FaBDTDAttacks($player, 'angelHerald')",'Choose_angel_or_Herald_attack','GO_AGAIN'))
 if b=='empyrean_rapture':add('ResolveAbility',"FaBDYNTag($mzID, 'DTD_WARD:1');")
 if b.startswith('figment_of_'):
  kind=b[11:];body={'erudition':token('ponder'),'protection':token('spectral_shield'),'war':token('courage'),'tenacity':"FaBWTRAddEffect($player, 'NEXT_ATTACK', 0, ['dominate'=>true]);",'triumph':"FaBDTDAdd($player, 'TRIUMPH');"}.get(kind)
  if kind=='judgment':body=select("FaBDTDRefs($player, 'Banish', '', true)",'Turn_banished_card_face_down')+"FaBDTDFaceDown($chosen);"
  if kind=='rebirth':body=select("FaBDTDRefs($player, 'Graveyard', 'yellowAction')",'Top_yellow_action')+"FaBDTDTopChoice($player, $chosen);"
  if kind=='ravages':body=UID+anydamage(1)
  if body is not None:add('ResolveCard',body)
 if 'Angel' in c['types']:
  name=b.split('_')[0];body={'suraya':'DoDrawCard($player, 2);','aegis':token('spectral_shield',2),'bellona':'FaBDTDBellona($player);','metis':"FaBDTDAdd($player, 'DOMINATE');",'victoria':"FaBDTDAdd($player, 'TRIUMPH', 1, ['expiresAtStartOf'=>$player]);"}.get(name)
  if name=='themis':body=select("FaBDTDRefs($player, 'Banish', '', true)",'Turn_banished_card_face_down')+"FaBDTDFaceDown($chosen);"
  if name=='avalon':body=select("FaBDTDRefs($player, 'Graveyard', 'yellow')",'Top_yellow_card',False)+"FaBDTDTopChoice($player, $chosen);"
  if name=='sekem':body=anydamage(2)
  if body is not None:add('AttackDeclared',UID+soul()+body+' }')
 if b=='angelic_descent':add('ResolveCard',tag("FaBDTDAttacks($player, 'herald')",'Choose_Herald','GO_AGAIN')+f"FaBDTDAdd($player, 'NEXT_ANGEL', {v});")
 if b=='angelic_wrath':add('ResolveCard',tag("FaBDTDAttacks($player, 'herald')",'Choose_Herald',f'WTR_POWER:{v+1}'))
 if b=='celestial_reprimand':add('ResolveCard',tag("FaBDefendingChoices(intval(FaBGetState()['defender']), true)",'Weaken_defending_card',f'WTR_POWER:-{v}'))
 if b=='celestial_resolve':add('ResolveCard',tag('FaBDTDHeraldCards()','Choose_Herald',f'WTR_DEFENSE:{v+2}'))
 if b=='light_of_sol':add('CardPitched',"$uids = FaBDYNPeekTop($player, $player); if (count($uids) > 0) { $revealed = FaBEVRUIDRefs($uids); FaBRevealChoices($player, $revealed); "+select("FaBDTDRefs($player, 'Temp', 'yellow')",'Put_yellow_card_into_soul')+"FaBDTDPeekSoul($player, $uids, $chosen); }")
 if b in ['beckoning_light','glaring_impact','spirit_of_war']:
  add('PrepareCard',UID+select("FaBMONAffordableHand($player, '', true)",'Charge_your_soul')+"$yellow = FaBBoltynYellowChoice($chosen); if ($chosen !== 'PASS') { FaBARCSetCard($uid, 'dtdCharged', true); } FaBMONCharge($player, $chosen); if ($yellow) { FaBTagUID($uid, 'DTD_YELLOW'); } FaBFinishPreparedCard($uid);")
  if b=='glaring_impact':add('AttackDeclared',UID+"if (in_array('DTD_YELLOW', (array)FaBIdentityFromMZ($mzID)['object']->TurnEffects, true)) { FaBTagUID($uid, 'OVERPOWER'); }")
  else:add('AttackDeclared',"if (in_array('DTD_YELLOW', (array)FaBIdentityFromMZ($mzID)['object']->TurnEffects, true)) { FaBDTDAdd($player, '"+('BECKON' if b=='beckoning_light' else 'SPIRIT')+"'); }")
  if b=='beckoning_light':add('ResolveAbility',select("FaBDTDRefs($player, 'Graveyard', 'aa')",'Top_attack_action')+"FaBDTDTopChoice($player, $chosen);")
 if b=='charge_of_the_light_brigade':add('ResolveCard',f"FaBDTDAdd($player, 'NEXT_CHARGE', {v});")
 if b=='prayer_of_bellona':add('ResolveCard',"FaBWTRAddEffect($player, 'NEXT_ATTACK', 2); $yellow = FaBDTDPrayer($player); if ($yellow) { "+select("implode('&', FaBChoiceRefs($player, 'Hand'))",'Charge_your_soul',False)+"FaBMONCharge($player, $chosen); }")
 if b=='resounding_courage':add('ResolveCard',tag("FaBDTDAttacks($player, 'lightWarrior')",'Choose_Light_Warrior_attack',f'WTR_POWER:{v}')+"if (FaBMONCount($player, 'CHARGED')) { "+token('courage')+' }')
 if b in ['radiant_view','radiant_raiment','radiant_flow']:
  add('PrepareCard',UID+select('FaBMONSoul($player)','Banish_from_soul',False)+"FaBMoveChoice($player, $chosen, 'Soul', 'Banish'); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 2);")
 if b=='v_for_valor':
  add('PrepareCard',UID+select("FaBMONAffordableHand($player, '', false)",'Charge_your_soul',False)+"FaBMONCharge($player, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',tag("FaBDTDAttacks($player, '')",'Choose_attack',f'WTR_POWER:{v}'))
 if b=='soulbond_resolve':add('Defended',select("implode('&', FaBChoiceRefs($player, 'Hand'))",'Charge_your_soul')+"FaBMONCharge($player, $chosen);")
 if b=='lumina_lance':
  add('PrepareCard',UID+multi('FaBMONSoul($player)',0,3,'Banish_up_to_three_soul_cards')+"$count = FaBDTDBanishSoul($player, $chosen); FaBARCSetCard($uid, 'dtdModes', $count); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"$count = intval(FaBARCCard($uid, 'dtdModes')); if ($count > 0) { $modes = await $player.Modal($count, $count, \"Power&Draw_on_hit&Go_again_on_hit\", \"Choose_Lumina_Lance_modes\"); "+select("FaBDTDAttacks($player, 'light')",'Choose_Light_attack',False)+"FaBDTDLance($chosen, $modes); }")
 if b in ['blessing_of_salvation','grim_feast']:add('ResolveCard',f'FaBCRUGainLife($player, {v});')
 if b=='cleansing_light':add('ResolveCard',select("FaBDTDRefs($player, 'Arena', 'redAura', true)",'Destroy_red_aura',False)+"FaBDYNDestroyChoice($chosen);")
 if b=='break_of_dawn':add('ResolveCard',f"FaBDTDAdd($player, 'BREAK', {v+1});")
 if b=='lay_to_rest':add('Hit',hit(select("FaBDTDRefs($victim, 'Banish')",'Turn_banished_card_face_down')+"FaBDTDFaceDown($chosen);"))
 if b=='vynnset' or b=='vynnset_iron_maiden':
  add('StartTurn',select("implode('&', FaBChoiceRefs($player, 'Hand'))",'Banish_from_hand',False)+"if ($chosen !== 'PASS') { $moved = FaBMoveChoice($player, $chosen, 'Hand', 'Banish'); FaBARCCreateRunes($player, 1); }")
  add('ResolveAbility',"$mode = await $player.Modal(1, 1, \"Decline&Pay_one_life\", \"Make_next_Runechant_unpreventable\"); if ($mode === '1') { FaBARCLoseLife($player, 1, $player); FaBDTDAdd($player, 'UNPREVENTABLE_RUNE'); }")
 if b=='flail_of_agony':add('Hit','FaBARCCreateRunes($player, 1);')
 if b in ['envelop_in_darkness','putrid_stirrings']:
  add('ResolveCard',('FaBARCCreateRunes($player, 1);' if b=='envelop_in_darkness' else '')+f"FaBDTDAdd($player, 'NEXT_RUNE', {v+(2 if b=='putrid_stirrings' else 0)});")
 if b in ['funeral_moon','requiem_for_the_damned']:add('ResolveCard','FaBARCCreateRunes($player, 1);' if b=='funeral_moon' else token('eloquence'))
 if b=='oblivion':add('ResolveCard',token('nasreth_the_soul_harrower'))
 if b=='nasreth_the_soul_harrower':add('Hit',hit(select('FaBMONSoul($victim)','Banish_from_defending_soul',False)+"FaBDTDNasreth($player, $chosen);"))
 if b in ['deathly_delight','deathly_wail']:add('CombatChainClosed',('FaBCRUGainLife' if b=='deathly_delight' else 'FaBARCCreateRunes')+'($player, count(FaBDTDLostSeats()));')
 if b.startswith('widespread_'):
  zone={'widespread_annihilation':'Hand','widespread_destruction':'Arsenal','widespread_ruin':'Deck'}[b]
  body="FaBDTDTop($victim);" if zone=='Deck' else select(f"implode('&', FaBChoiceRefs($victim, '{zone}'))",f'Banish_from_{zone.lower()}',False,p='$victim')+f"FaBMoveChoice($victim, $chosen, '{zone}', 'Banish');"
  add('CombatChainClosed',"$seats = FaBDTDLostSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $victim = intval($seats[$i]); "+body+' }')
 if b=='dimenxxional_vortex':add('ResolveCard',"$seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $victim = intval($seats[$i]); "+select("implode('&', FaBChoiceRefs($victim, 'Arsenal'))",'Banish_from_arsenal',False,p='$victim')+"FaBMoveChoice($victim, $chosen, 'Arsenal', 'Banish'); }")
 if b=='vile_inquisition':add('ResolveCard',select('FaBDYNHeroTargets($player)','Choose_hero',False)+"FaBDTDVile($player, intval(FaBIdentityFromMZ($chosen)['player']));")
 if b=='beseech_the_demigon':add('ResolveCard',tag("FaBDTDRefs($player, 'Banish', 'aa')",'Empower_banished_attack',f'WTR_POWER:{v}'))
 if b=='tear_through_the_portal':add('ResolveCard',tag(f"FaBDTDPortal($player, {int(c['pitch'])})",'Give_banished_action_go_again','GO_AGAIN'))
 if b=='hungering_demigon':add('Hit',hit(select('FaBMONSoul($victim)','Banish_from_defending_soul',False)+"FaBMoveChoice($victim, $chosen, 'Soul', 'Banish');"))
 if b=='dabble_in_darkness':add('AttackDeclared',UID+"$card = FaBDTDTop($player); if ($card !== null) { $pitch = intval(CardPitch($card->CardID)); FaBTagUID($uid, 'WTR_POWER:-'.$pitch); }")
 if b in ['ram_raider','shaden_scream','shaden_swing','tribute_to_demolition','tribute_to_the_legions_of_doom','expendable_limbs']:
  body=UID+reserve_cost()+'FaBDTDHandBanish($player, $uid);'
  if b=='ram_raider':body+="if (FaBARCCard($uid, 'dtdSix')) { FaBTagUID($uid, 'GO_AGAIN'); }"
  if b.startswith('tribute_'):body+="if (FaBARCCard($uid, 'dtdSix')) { FaBCRUSelfTagUID($uid, 'WTR_POWER:2'); }"
  if b=='expendable_limbs':body+="if (FaBARCCard($uid, 'dtdSix')) { FaBDTDGrant($player, intval(FaBARCCard($uid, 'dtdBanished')), true); }"
  add('PrepareCard',body+'FaBFinishPreparedCard($uid);')
  if b=='shaden_scream':add('ResolveCard',f"FaBMONAdd($player, 'NEXT_SHADOW_BRUTE', {v+2});")
 if b=='blood_dripping_frenzy':
  add('PrepareCard',UID+reserve_cost(0)+'FaBDTDFrenzy($player, $uid); FaBFinishPreparedCard($uid);')
  add('ResolveCard',UID+"DoDrawCard($player, intval(FaBARCCard($uid, 'dtdDraw'))); FaBDTDAdd($player, 'FRENZY', intval(FaBARCCard($uid, 'dtdPower')));")
 if b=='shaden_death_hydra':add('AttackDeclared',UID+deal('max(0, 13 - FaBMONBloodDebt($player))',targetUID='FaBUPRHeroUID($player)',physical=True))
 if b=='dig_up_dinner':add('ResolveCard',UID+'FaBDTDDinner($player, $uid);')
 if b in ['levia_redeemed','blasmophet_levia_consumed']:
  add('PrepareCard',UID+"FaBDTDHideBanish($player); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"FaBDTDTransform($player, 'levia_redeemed');")
 if b=='blasmophet_levia_consumed':add('EndTurn',"$debt = FaBMONBloodDebt($player); while ($debt > 0 && FaBSeatIsLive($player)) { if (FaBMONHero($player, 'blasmophet_levia_consumed')) { FaBDTDTop($player); } else { FaBARCLoseLife($player, 1, $player); if (intval(GetHealth($player)) === 13) { $mode = await $player.Modal(1, 1, \"Decline&Transform\", \"Become_Blasmophet\"); if ($mode === '1') { FaBDTDTransform($player, 'blasmophet_levia_consumed'); } } } $debt = $debt - 1; }")
 if b=='spoiled_skull':
  add('PrepareCard',UID+multi("FaBDTDRefs($player, 'Banish', 'action')",3,3,'Choose_three_different_names')+"FaBDTDSkull($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('fabAbilityStackUID')); FaBDTDGrant($player, intval(FaBARCCard($uid, 'dtdSkull')));")
 if b=='grimoire_of_the_haunt':add('ResolveAbility',token('eloquence'))
 if b=='runic_reckoning':add('ResolveCard',"FaBDTDAdd($player, 'NEXT_RUNEBLADE', 3);")
 if b=='bequest_the_vast_beyond':add('ResolveCard',"FaBDTDAdd($player, 'BEQUEST');")
 if b=='scepter_of_pain':add('ResolveAbility',UID+anydamage(1,True)+"FaBARCCreateRunes($player, $dealt);")
 if b=='ironsong_versus':add('ResolveAbility',"FaBDTDAdd($player, 'NEXT_SWORD', 0);")
 if b=='decimator_great_axe':add('ResolveAbility',select("FaBDefendingChoices(intval(FaBGetState()['defender']), true)",'Halve_defending_base_defense',False)+"FaBDTDHalve($chosen);")
 if b=='scowling_flesh_bag':add('Defended',"FaBIntimidate($player, intval(FaBGetState()['attacker']));")
 if b=='diadem_of_dreamstate':add('ResolveAbility',pay(1,'Pay_for_Ponder')+"if ($paid) { "+token('ponder')+' }')
 if b=='censor':add('Hit',hit('$name = await $player.NameCard("CARD_NAMES", "Name_prohibited_card"); FaBDTDNameLock($victim, $name);'))
 if b=='hold_the_line':add('ResolveCard',"if (FaBDTDCount(intval(FaBGetState()['attacker']), 'DRAWN') >= 2) { FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 3); }")
 if b=='poison_the_well':add('ResolveCard',"FaBDTDAdd($player, 'POISON');")
 if b=='warmongers_diplomacy':add('ResolveCard',"$seats = FaBDTDUnitedOrder($player); for ($i = 0; $i < count($seats); $i = $i + 1) { $victim = intval($seats[$i]); $mode = await $victim.Modal(1, 1, \"War&Peace\", \"Choose_war_or_peace\"); FaBDTDDiplomacy($victim, $mode); }")
 if b=='hack_to_reality':
  add('ResolveCard',"FaBWTRAddEffect($player, 'NEXT_ATTACK', 2); FaBDTDAdd($player, 'HACK');")
  add('ResolveAbility',"$victim = intval(DecisionQueueController::GetVariable('dtdVictim')); $damage = intval(DecisionQueueController::GetVariable('amount')); "+select('FaBDTDHackTargets($victim, $damage)','Destroy_aura',False)+"FaBDYNDestroyChoice($chosen);")
 if b=='mischievous_meeps':add('Hit',hit(select("FaBDTDRefs($victim, 'Arena', 'item2')",'Take_control_of_item',False)+"if ($chosen === 'PASS') { DoDrawCard($player, 1); } else { FaBDTDSteal($player, $chosen); }"))
 if b in ['lost_in_thought','alluring_inducement']:
  prefix=select('FaBDYNHeroTargets($player)','Look_at_hero_hand',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']);" if b=='lost_in_thought' else VICTIM
  add('ResolveCard' if b=='lost_in_thought' else 'AttackDeclared',UID+prefix+"$uids = FaBDTDInspect($player, $victim, "+('true' if b=='alluring_inducement' else 'false')+"); "+select("FaBDTDRefs($player, 'Temp', 'aa')",'Choose_revealed_attack',b=='alluring_inducement')+"FaBDTDFinishInspect($player, $victim, $uids, $chosen, $uid, "+('true' if b=='alluring_inducement' else 'false')+");")
 if b=='anthem_of_spring':add('ResolveCard',"FaBWTRAddEffect($player, 'NEXT_AA', 1);")
 if b=='call_down_the_lightning':add('ResolveCard',"FaBDTDAdd($player, 'LIGHTNING');")
 if b=='chorus_of_ironsong':add('ResolveCard',select("FaBDTDDawnblades($player)",'Choose_Dawnblade',False)+"FaBDYNTag($chosen, 'WTR_POWER:1'); FaBDYNTag($chosen, 'UPR_UNPREVENTABLE');")
 if b=='star_struck':add('Hit',hit("if (intval($amount) >= 4) { FaBWTRAddEffect($victim, 'DTD_STAR', intval($amount), [], true); }"))
 if b=='northern_winds':add('ResolveCard',"$seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $victim = intval($seats[$i]); $kinds = ['Equipment', 'Item', 'Ally']; for ($j = 0; $j < 3; $j = $j + 1) { $kind = $kinds[$j]; "+select('FaBDTDFreezeTargets($victim, $kind)','Freeze_card')+"FaBUPRFreeze($player, $chosen); } }")
 unity={'alluring_inducement':'eloquence','anthem_of_spring':'embodiment_of_earth','call_down_the_lightning':'embodiment_of_lightning','chorus_of_ironsong':'courage','northern_winds':'spellbane_aegis','star_struck':'seismic_surge'}
 if b in unity:
  unitycode="$max = count(FaBLiveSeats()); "+multi('FaBDYNHeroTargets($player)',0,'$max','Choose_heroes_for_Unity')+f"FaBDTDTokenHeroes($chosen, '{unity[b]}');"
  if b=='call_down_the_lightning':unitycode="$lightningDamage = intval(DecisionQueueController::GetVariable('dtdLightningDamage')); if ($lightningDamage > 0) { "+UID+"$targetUID = FaBUPRHeroUID(intval(DecisionQueueController::GetVariable('dtdLightningTarget'))); "+deal(1,physical=True)+" } else { "+unitycode+" }"
  add('ResolveAbility',unitycode)
 if b in ['banneret_of_courage','banneret_of_gallantry','banneret_of_protection','banneret_of_resilience','banneret_of_vigor','bastion_of_unity','battlefield_breaker','beaming_blade','blistering_assault','chains_of_mephetis','cloak_of_darkness','dance_of_darkness','defender_of_daybreak','diabolic_offering','dyadic_carapace','eloquence','flicker_trick','frontline_gauntlets','frontline_helm','frontline_legs','frontline_plating','grasp_of_darkness','hell_hammer','numbskull','radiant_forcefield','reality_refractor','rift_skitter','rugged_roller','shroud_of_darkness','slithering_shadowpede','soul_butcher','soul_cleaver','searing_ray','united_we_stand','vantom_banshee','vantom_wraith','wall_breaker']:handled=True
 if b=='radiant_forcefield':add('ResolveAbility',"$dtdSource = intval(DecisionQueueController::GetVariable('dtdSourceUID')); $dtdVictim = $player; $dtdActor = intval(FaBGetState()['attacker']); $dtdType = 'PHYSICAL'; $dtdPacket = intval(DecisionQueueController::GetVariable('dtdDamage')); "+prevention+" FaBBeginDamageStep();")
 if b=='morlock_hill':add('ResolveCard',"FaBDTDAdd($player, 'MORLOCK');")
 if not handled:pending.append(id)
 out.append(dict(cardId=id,abilities=a))
if pending:print('Unhandled',pending);raise SystemExit(1)
for c in out:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'dtd_abilities.json').write_text(json.dumps(out,indent=2)+'\n');print('DTD:',len(out),'identities;',sum(len(c['abilities']) for c in out),'macros')
