"""Build complete MON CardEditor snapshot; refuse unhandled card identities."""
import ast,json,re,hashlib
from pathlib import Path
HERE=Path(__file__).parent
src=(HERE/'build_arc_abilities.py').read_text(encoding='utf-8')
for node in ast.parse(src).body:
 if isinstance(node,ast.FunctionDef):exec(ast.get_source_segment(src,node))
original_damage_body=damage_body
spellvoid="""$voidRefs = FaBMONSpellvoidRefs($target);
while ($damage > $payment && $voidRefs !== '') {
 $void = await $target.MZMayChoose($voidRefs, "Destroy_Spellvoid_to_prevent_arcane_damage");
 if ($void === 'PASS') { break; }
 $prevented = FaBMONSpellvoid($target, $void);
 if ($prevented <= 0) { break; }
 $damage = max(0, $damage - $prevented);
 $voidRefs = FaBMONSpellvoidRefs($target);
}
"""
def damage_body():return original_damage_body().replace('$dealt = FaBARCDealArcane(',spellvoid+'$dealt = FaBARCDealArcane(')
previous={c['cardId']:c for c in json.loads((HERE/'mon_abilities.json').read_text(encoding='utf-8'))} if (HERE/'mon_abilities.json').exists() else {}
cards=json.loads((HERE/'mon_catalog.json').read_text(encoding='utf-8'))
existing={}
for name in ['wtr','arc','fai','professor','cru','ira']:
 existing.update({c['cardId']:c for c in json.loads((HERE/(name+'_abilities.json')).read_text(encoding='utf-8'))})
snapshot=[];pending=[]
def clean(code):
 # Emit explicit statement/block lines for the await compiler.
 out=[];quote=None;escape=False;depth=0
 for ch in code:
  if quote:
   out.append(ch)
   if escape:escape=False
   elif ch=='\\':escape=True
   elif ch==quote:quote=None
  elif ch in [chr(34),chr(39)]:quote=ch;out.append(ch)
  elif ch=='(':depth+=1;out.append(ch)
  elif ch==')':depth-=1;out.append(ch)
  elif ch==';' and depth==0:out.append(';\n')
  elif ch=='{':out.append('{\n')
  elif ch=='}':out.append('\n}\n')
  else:out.append(ch)
 return ''.join(out)
def choice(expr,tip='Choose_a_card',may=True,var='$chosen',chooser='$player'):
 return f'{var} = \'PASS\'; $targets = {expr};\nif ($targets !== "") {{ {var} = await {chooser}.{("MZMayChoose" if may else "MZChoose")}($targets, "{tip}"); }}'
def multi(expr,nmin,nmax,tip='Choose_cards',var='$chosen'):
 return f'$targets = {expr};\n{var} = await $player.MZMultiChoose($targets, {nmin}, {nmax}, "{tip}");'
def arcane(n,target=None):
 code='$caster = $player;\n'
 if target is None:
  code+=choice('FaBARCHeroTargets($player, false)','Choose_arcane_target',False)+'\n$target = FaBARCTargetSeat($caster, $chosen, false);\n'
 else:code+=f'$target = {target};\n'
 code+=f'$damage = {n};\n'+damage_body().replace('FaBARCDealArcane($player,','FaBARCDealArcane($caster,')+'\n$player = $caster;'
 return code
prep_start="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
prep_end='FaBFinishPreparedCard($uid);'
def finish_prepare(code):return prep_start+'\n'+code+'\n'+prep_end
charge=choice("FaBMONAffordableHand($player, '', true)",'Charge_your_soul')+"\nFaBMONCharge($player, $chosen ?? 'PASS');"
for c in cards:
 id=c['id'];base=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id
 v=4-int(c['pitch'] or 0);abilities=[];handled=False
 def add(m,code):
  global handled
  handled=True
  prior=next((a for a in abilities if a['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:abilities.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:snapshot.append(existing[id]);continue
 # Keyword and shared-rule cards are deliberately listed, not a fallback.
 if base in ['adrenaline_rush','arc_light_sentinel','battlefield_blitz','bolting_blade','bounding_demigon','carrion_husk','cracked_bauble','deep_rooted_evil','enigma_chimera','ghostly_visit','graveling_growl','impenetrable_belief','invigorating_light','iris_of_reality','levia','levia_shadowborn_abomination','luminaris','mark_of_the_beast','mutated_mass','ode_to_wrath','out_muscle','parable_of_humility','piercing_shadow_vise','pound_for_pound','raydn_duskbane','rift_bind','rip_through_reality','soul_shackle','soul_shield','spears_of_surreality','spectral_shield','stony_woottonhog','surging_militia','tremor_of_iarathael','ursur_the_soul_reaper','valiant_thrust','vestige_of_sol','void_wraith','yinti_yanti','zealous_belting','hatchet_of_body','hatchet_of_mind','galaxxi_black']:
  handled=True
 if base in ['valiant_thrust','raydn_duskbane','galaxxi_black','piercing_shadow_vise','tremor_of_iarathael','yinti_yanti','stony_woottonhog','surging_militia']:
  add('AttackPowerModifier','return FaBMONOwnPower($player, $subjectObj);')
 if base in ['impenetrable_belief','yinti_yanti','mutated_mass']:add('DefenseModifier','return FaBMONDefense($player, $subjectObj);')
 if base=='bolting_blade':add('CostModifier',"return -2 * FaBMONCount($player, 'CHARGED');")
 if base in ['bolt_of_courage','cross_the_line','engulfing_light','express_lightning','take_flight']:add('PrepareCard',finish_prepare(charge))
 if base=='v_of_the_vanguard':
  add('PrepareCard',finish_prepare("$maximum = count(FaBChoiceRefs($player, 'Hand'));\n"+multi("implode('&', FaBChoiceRefs($player, 'Hand'))",0,'$maximum','Charge_any_number')+"\n$n = FaBMONCharge($player, $chosen); FaBMONAdd($player, 'VANGUARD', $n);"))
 if base in ['boneyard_marauder','convulsions_from_the_bellows_of_hell','dread_screamer','endless_maw','hungering_slaughterbeast','unworldly_bellow','writhing_beast_hulk']:
  code="$six = FaBMONBanishRandom($player, 3); FaBARCSetCard($uid, 'monSix', $six);"
  if base=='dread_screamer':code+="if ($six) { FaBTagUID($uid, 'GO_AGAIN'); }"
  if base=='endless_maw':code+=f"if ($six) {{ FaBCRUSelfTagUID($uid, 'WTR_POWER:{v}'); }}"
  if base=='writhing_beast_hulk':code+="if ($six) { FaBTagUID($uid, 'DOMINATE'); }"
  add('PrepareCard',finish_prepare(code))
 if base=='convulsions_from_the_bellows_of_hell':add('ResolveCard',f"if (FaBARCCard(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'monSix')) {{ FaBWTRAddEffect($player, 'NEXT_ATTACK', {v}, ['dominate'=>true]); }}")
 if base=='unworldly_bellow':add('ResolveCard',f"FaBMONAdd($player, 'NEXT_SHADOW_BRUTE', {v+1});")
 if base=='howl_from_beyond':add('ResolveCard',effect('NEXT_ATTACK',v))
 if base in ['arcanic_crackle','vexing_malice']:add('ResolveCard',arcane(1 if base=='arcanic_crackle' else 2))
 if base=='rifted_torment':add('ResolveCard',"if (FaBARCCard(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'monFromBanish')) { "+arcane(1)+' }')
 if base in ['bolt_of_courage','engulfing_light']:
  add('Hit',"if (FaBFaiHeroHit() && FaBMONCount($player, 'CHARGED')) { "+('DoDrawCard($player, 1);' if base=='bolt_of_courage' else "FaBMONToSoul($player, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID));")+' }')
 if base.startswith('herald_of_') or base in ['wartune_herald','illuminate','rising_solartide']:
  add('Hit',"if (FaBFaiHeroHit()) { FaBMONToSoul($player, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID)); }")
  if base=='herald_of_protection':add('Hit',"if (FaBFaiHeroHit()) { FaBWTRCreateArena($player, 'spectral_shield'); }")
  if base=='herald_of_ravages':add('Hit',"if (FaBFaiHeroHit()) { "+arcane(1)+' }')
  if base=='herald_of_erudition':add('Hit',"if (FaBFaiHeroHit()) { DoDrawCard($player, 2); }")
  if base=='herald_of_judgment':add('Hit',"if (FaBFaiHeroHit()) { $target = intval(FaBGetState()['defender']); FaBWTRAddEffect($target, 'MON_NO_BANISH_PLAY', 1, ['expiresAfterTurnOf'=>$target], true); }")
  if base=='herald_of_rebirth':add('Hit',"if (FaBFaiHeroHit()) { "+choice("implode('&', FaBChoiceRefs($player, 'Graveyard', ['keyword'=>'Phantasm']))")+" if (isset($chosen) && $chosen !== 'PASS') { FaBARCToDeck($player, intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), true); } }")
 if base in ['seeds_of_agony','seeping_shadows','captains_call']:
  if base=='captains_call':
   add('ResolveCard',f'$mode = await $player.Modal(1, 1, "Power&Go_again", "Choose_Captains_Call_mode"); FaBMONAdd($player, "NEXT_SMALL", $mode === "0" ? 2 : 0, ["maxCost"=>{v-1}, "goAgain"=>$mode === "1"]);')
  else:add('ResolveCard',f"FaBMONAdd($player, 'NEXT_SMALL', {1 if base=='seeping_shadows' else 0}, ['maxCost'=>{v-1}, 'goAgain'=>{'true' if base=='seeping_shadows' else 'false'}, 'seeds'=>{'true' if base=='seeds_of_agony' else 'false'}]);")
  if id=='seeds_of_agony_red':add('ResolveAbility',arcane(1))
 if base in ['minnowism','phantasmify']:add('ResolveCard',f"FaBMONAdd($player, '{'MINNOW' if base=='minnowism' else 'PHANTASMIFY'}', {v if base=='minnowism' else v+2});")
 if base in ['dusk_path_pilgrimage','plow_through','seek_enlightenment','warmongers_recital','shadow_puppetry']:
  effect_name='NEXT_WEAPON' if base in ['dusk_path_pilgrimage','plow_through'] else 'NEXT_ATTACK'
  flag={'dusk_path_pilgrimage':'monDusk','plow_through':'monPlow','seek_enlightenment':'monSoul','warmongers_recital':'monBottom','shadow_puppetry':'monPuppet'}[base]
  add('ResolveCard',f"FaBWTRAddEffect($player, '{effect_name}', {1 if base=='shadow_puppetry' else v}, ['{flag}'=>true, 'goAgain'=>{'true' if base=='shadow_puppetry' else 'false'}]);")
 if base=='shadow_puppetry':add('Hit',"$top = FaBARCStageTop($player, 1); if (count($top)) { "+choice("implode('&', FaBChoiceRefs($player, 'Temp'))",'Banish_top_card')+" FaBMoveChoice($player, $chosen ?? 'PASS', 'Temp', 'Banish'); FaBARCFinishOrder($player, $top, ''); }")
 if base=='brandish':add('Hit',effect('NEXT_WEAPON',1))
 if base=='overload':add('Hit',"FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'GO_AGAIN');")
 if base=='second_swing':add('ResolveCard',f"if (FaBCRUCount($player, 'WEAPONS')) {{ FaBWTRAddEffect($player, 'NEXT_ATTACK', {v+1}); }}")
 if base=='spill_blood':add('ResolveCard',"FaBMONAdd($player, 'SPILL', 2);")
 if base=='prismatic_shield':add('ResolveCard',f"for ($i = 0; $i < {v}; ++$i) {{ FaBWTRCreateArena($player, 'spectral_shield'); }}")
 if base=='tome_of_torment':add('ResolveCard','DoDrawCard($player, 1);')
 if base=='tome_of_divinity':add('ResolveCard',"DoDrawCard($player, FaBMONCount($player, 'SOUL_ADDED') ? 3 : 2);")
 if base=='soul_food':add('ResolveCard',"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); FaBMoveChoices($player, implode('&', FaBChoiceRefs($player, 'Hand')), 'Hand', 'Soul'); FaBMoveUID($uid, 'Soul', $player);")
 if base in ['blood_tribute','dimenxxional_gateway']:
  add('ResolveCard',opt(v))
  if base=='blood_tribute':add('ResolveCard',"$top = FaBChoiceRefs($player, 'Deck')[0] ?? ''; $f = FaBIdentityFromMZ($top); if ($f !== null) { FaBMoveUID(intval($f['object']->UniqueID), 'Banish', $player); }")
  else:add('ResolveCard',"$caster = $player; $top = FaBChoiceRefs($player, 'Deck')[0] ?? ''; $uid = intval(FaBIdentityFromMZ($top)['object']->UniqueID ?? 0); FaBRevealChoices($player, $top); $runeblade = FaBHasType(FaBIdentityFromMZ($top)['object'] ?? '', 'Runeblade'); $shadow = FaBHasType(FaBIdentityFromMZ($top)['object'] ?? '', 'Shadow'); if ($runeblade) { $seats = FaBOpponents($caster); for ($i = 0; $i < count($seats); ++$i) { "+arcane(1,'$seats[$i]')+" } } if ($shadow) { $mode = await $caster.Modal(1, 1, \"Leave_on_top&Banish\", \"Banish_revealed_Shadow_card\"); if ($mode === '1') { FaBMoveUID($uid, 'Banish', $caster); } } $player = $caster;")
 if base=='memorial_ground':add('ResolveCard',choose_move('Graveyard','AA',maxcost=v-1,may=False,top=True))
 if base=='unhallowed_rites':add('ResolveCard',choice("FaBMONSelect($player, 'Graveyard', 'NAA_DEBT')")+" FaBMoveChoice($player, $chosen ?? 'PASS', 'Graveyard', 'Deck');")
 if base=='spew_shadow':add('ResolveCard',choice(f"implode('&', FaBChoiceRefs($player, 'Banish', ['attackAction'=>true, 'maxCost'=>{v-1}]))",'Choose_banished_attack',False)+" $f = FaBIdentityFromMZ($chosen ?? ''); if ($f !== null) { $f['object']->PlayableFromBanish = 1; FaBWTRTag($f['object'], 'MON_SPEW'); }")
 if base in ['consuming_aftermath','shadow_of_ursur','seek_horizon','rise_above']:
  expr="FaBMONAffordableHand($player, 'DEBT')" if base=='shadow_of_ursur' else "implode('&', FaBChoiceRefs($player, 'Hand'))" if base=='rise_above' else "FaBMONAffordableHand($player)"
  code=("$mayDecline = FaBAvailablePitch($player) >= intval(FaBGetState()['pendingPayment']['cost']); $chosen = 'PASS'; $targets = "+expr+"; if ($targets !== '') { if ($mayDecline) { $chosen = await $player.MZMayChoose($targets, \"Pay_optional_cost\"); } else { $chosen = await $player.MZChoose($targets, \"Pay_alternative_cost\"); } }")+" $f = FaBIdentityFromMZ($chosen ?? ''); if ($f !== null) { "
  if base=='consuming_aftermath':code+="if (FaBHasType($f['object'], 'Shadow')) { FaBTagUID($uid, 'DOMINATE'); } FaBMoveChoice($player, $chosen, 'Hand', 'Banish');"
  elif base=='shadow_of_ursur':code+="FaBMoveChoice($player, $chosen, 'Hand', 'Banish'); FaBTagUID($uid, 'GO_AGAIN');"
  else:
   code+="FaBARCToDeck($player, intval($f['object']->UniqueID), true); "
   code+="FaBTagUID($uid, 'GO_AGAIN');" if base=='seek_horizon' else "FaBMONSetCost($uid, 0);"
  add('PrepareCard',finish_prepare(code+' }'))
 if base in ['celestial_cataclysm','beacon_of_victory','soul_harvest']:
  zone='Graveyard' if base=='soul_harvest' else 'Soul';n=6 if base=='soul_harvest' else 3 if base=='celestial_cataclysm' else 1
  maximum=str(n) if base!='beacon_of_victory' else '$maximum'
  code=f"$maximum = count(FaBChoiceRefs($player, '{zone}')); "+multi(f"implode('&', FaBChoiceRefs($player, '{zone}'))",n,maximum,'Banish_to_pay')+f" $count = FaBMoveChoices($player, $chosen, '{zone}', 'Banish'); FaBARCSetCard($uid, 'monX', $count);"
  if base=='soul_harvest':code=code.replace('$count = FaBMoveChoices',"$debt = FaBMONSelectedDebt($chosen); FaBCRUSelfTagUID($uid, 'WTR_POWER:' . $debt); $count = FaBMoveChoices")
  add('PrepareCard',finish_prepare(code))
 if base=='beacon_of_victory':
  add('ResolveCard',"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $x = intval(FaBARCCard($uid, 'monX')); FaBTagUID(intval(FaBGetState()['attackUID']), 'WTR_POWER:' . $x); if (FaBMONCount($player, 'CHARGED')) { "+choose_move('Deck','',class_='Action',maxcost='$x',may=False)+' }')
 if base=='courageous_steelhand':add('ResolveCard',f"if (FaBMONCount($player, 'CHARGED')) {{ FaBTagUID(intval(FaBGetState()['attackUID']), 'WTR_POWER:{v}'); }}")
 if base=='galaxxi_black':add('Hit',"if (FaBFaiHeroHit()) { "+arcane(1,"intval(FaBGetState()['defender'])")+' }')
 if base=='dread_scythe':add('AttackDeclared',"if ((FaBGetState()['attackTarget']['type'] ?? '') === 'HERO') { "+arcane(1,"intval(FaBGetState()['defender'])")+" if (($dealt ?? 0) > 0) { FaBWTRAddEffect($target, 'MON_NO_HEAL', 1, ['expiresAfterTurnOf'=>$target], true); } }")
 if base in ['prism','prism_sculptor_of_arc_light','boltyn','ser_boltyn_breaker_of_dawn']:
  add('PrepareCard',finish_prepare(choice('FaBMONSoul($player)','Banish_from_soul',False)+" FaBMoveChoice($player, $chosen ?? '', 'Soul', 'Banish');"))
  add('ResolveAbility',"FaBWTRCreateArena($player, 'spectral_shield');" if base.startswith('prism') else "FaBTagUID(intval(FaBGetState()['attackUID']), 'GO_AGAIN');")
 if base in ['chane','chane_bound_by_shadow']:add('ResolveAbility',"FaBWTRCreateArena($player, 'soul_shackle'); FaBMONAdd($player, 'CHANE', 0);")
 if base in ['doomsday','eclipse']:add('ResolveCard',f"FaBWTRCreateArena($player, '{'blasmophet_the_soul_harvester' if base=='doomsday' else 'ursur_the_soul_reaper'}');")
 if base=='genesis':add('StartTurn',choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Put_card_into_soul')+" $f = FaBIdentityFromMZ($chosen ?? ''); if ($f !== null) { $light = FaBHasType($f['object'], 'Light'); $illusion = FaBHasType($f['object'], 'Illusionist'); FaBMoveChoice($player, $chosen, 'Hand', 'Soul'); if ($illusion) { FaBWTRCreateArena($player, 'spectral_shield'); } if ($light) { DoDrawCard($player, 1); } }")
 if base=='dimenxxional_crossroads':add('ResolveAbility',arcane(1))
 if base=='merciful_retribution':add('ResolveAbility',"$destroyedUID = intval(DecisionQueueController::GetVariable('monDestroyedUID')); $light = DecisionQueueController::GetVariable('monLight'); "+arcane(1)+" if ($light) { FaBMONRetributionSoul($player, $destroyedUID); }")
 if base in ['aether_ironweave','blood_drop_brocade','gallantry_gold','dream_weavers','time_skippers','guardian_of_the_shadowrealm','exude_confidence']:
  codes={'aether_ironweave':'AddResources($player, intval(GetResources($player)) + 2);','blood_drop_brocade':'AddResources($player, intval(GetResources($player)) + 1);','gallantry_gold':"FaBMONAdd($player, 'GALLANTRY');",'dream_weavers':"FaBMONAdd($player, 'DREAM', 0);",'time_skippers':'AddActionPoints($player, intval(GetActionPoints($player)) + 2);','guardian_of_the_shadowrealm':"FaBMoveChoice($player, $mzID, 'Banish', 'Hand');",'exude_confidence':"FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'WTR_POWER:2');"}
  add('ResolveAbility',codes[base])
 if base in ['ebon_fold','halo_of_illumination','rally_the_rearguard','great_library_of_solana']:
  if base=='great_library_of_solana':code=multi("implode('&', FaBChoiceRefs($player, 'Hand', ['pitch'=>2]))",2,2,'Discard_two_yellow_cards')+" FaBMONDiscard($player, $chosen);"
  else:code=choice("FaBMONAffordableHand($player)",'Pay_additional_cost',False)+" $f = FaBIdentityFromMZ($chosen ?? ''); if ($f !== null) { "
  if base in ['ebon_fold','halo_of_illumination']:
   code+=f"$draw = FaBHasType($f['object'], '{'Shadow' if base=='ebon_fold' else 'Light'}'); FaBARCSetCard($uid, 'monDraw', $draw); FaBMoveChoice($player, $chosen, 'Hand', '{'Banish' if base=='ebon_fold' else 'Soul'}'); }}"
  elif base=='rally_the_rearguard':code+="FaBMONDiscard($player, $chosen); }"
  add('PrepareCard',finish_prepare(code))
  if base in ['ebon_fold','halo_of_illumination']:add('ResolveAbility',"if (FaBMONAbilityData($player, 'monDraw')) { DoDrawCard($player, 1); }")
  if base=='rally_the_rearguard':add('ResolveAbility',"FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'WTR_DEFENSE:3');")
 if base in ['smash_with_big_tree','talisman_of_dousing']:handled=True
 if base=='belittle':
  code=choice("FaBMONSelect($player, 'Hand', 'SMALL_AA')",'Reveal_small_attack_to_find_Minnowism')+" if (isset($chosen) && $chosen !== 'PASS') { FaBRevealChoices($player, $chosen); $targets = FaBStageSearch($player, ['base'=>'minnowism']); if ($targets !== '') { $card = await $player.MZChoose($targets, \"Find_Minnowism\"); FaBRevealChoices($player, $card); FaBMoveChoice($player, $card, 'Temp', 'Hand'); } FaBFinishSearch($player); }"
  add('PrepareCard',finish_prepare(code))
 if base=='rouse_the_ancients':
  code="$maximum = count(FaBChoiceRefs($player, 'Hand', ['attackAction'=>true])); "+multi("implode('&', FaBChoiceRefs($player, 'Hand', ['attackAction'=>true]))",0,'$maximum','Reveal_at_least_13_power')+" if (FaBMONRevealPower($chosen) >= 13) { FaBRevealChoices($player, $chosen); FaBCRUSelfTagUID($uid, 'WTR_POWER:7'); FaBTagUID($uid, 'GO_AGAIN'); }"
  add('PrepareCard',finish_prepare(code))
 if base in ['deadwood_rumbler','shadow_of_blasmophet','pulping','ravenous_meataxe','tear_limb_from_limb']:
  code="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $six = FaBMONDrawDiscard($player); if ($six) { "
  if base=='pulping':code+="FaBTagUID($uid, 'DOMINATE');"
  if base=='ravenous_meataxe':code+="$w = FaBFindUID(intval(FaBObjectCounters(FaBFindUID($uid)['object'])['WEAPON_UID'] ?? 0)); if ($w !== null) { FaBWTRTag($w['object'], 'WTR_POWER:2'); }"
  if base=='tear_limb_from_limb':code+="FaBMONAdd($player, 'TEAR', 0);"
  if base=='deadwood_rumbler':code+=choice('FaBMONAllGraves()','Banish_from_a_graveyard',False)+" FaBMONBanishSelected($chosen ?? '');"
  if base=='shadow_of_blasmophet':code+="$targets = FaBStageSearch($player, ['keyword'=>'Blood Debt']); if ($targets !== '') { $chosen = await $player.MZChoose($targets, \"Banish_blood_debt_from_deck\"); FaBRevealChoices($player, $chosen); FaBMoveChoice($player, $chosen, 'Temp', 'Banish'); } FaBFinishSearch($player);"
  add('ResolveCard' if base in ['tear_limb_from_limb','deadwood_rumbler','shadow_of_blasmophet'] else 'AttackDeclared',code+' }')
 if base in ['frontline_scout','phantasmaclasm']:
  code="$caster = $player; $owner = intval(FaBGetState()['defender']); $uids = FaBMONStageHand($caster, $owner); $bottom = 0; if (count($uids)) { "
  if base=='phantasmaclasm':code+=choice("implode('&', FaBChoiceRefs($caster, 'Temp'))",'Put_defenders_card_on_bottom',False,chooser='$caster')+" $bottom = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);"
  else:code+=multi("implode('&', FaBChoiceRefs($player, 'Temp'))",0,0,'View_defending_hand')
  add('AttackDeclared',code+" } FaBMONRestoreHand($owner, $uids, $bottom); $player = $caster;")
 if base in ['blasmophet_the_soul_harvester','lunartide_plunderer','soul_harvest','eclipse_existence']:
  code="$caster = $player; $owner = intval(FaBGetState()['defender']); "
  if base=='blasmophet_the_soul_harvester':code+=choice("implode('&', FaBChoiceRefs($player, 'Hand', ['type'=>'Shadow']))",'Banish_Shadow_to_reap_soul')+" $paid = FaBMoveChoice($player, $chosen ?? 'PASS', 'Hand', 'Banish'); if ($paid !== null) { "+choice('FaBMONSoul($owner)','Banish_from_defending_soul')+" FaBMONBanishSelected($chosen ?? 'PASS'); }"
  if base=='lunartide_plunderer':code+="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); "+choice('FaBMONSoul($owner)','Banish_from_defending_soul',False)+" FaBARCPreserveAttack($uid); FaBMoveUID($uid, 'Banish', $caster); FaBMONBanishSelected($chosen ?? '');"
  if base=='soul_harvest':code+="$n = FaBMONBanishSelected(FaBMONSoul($owner)); FaBARCLoseLife($owner, $n, $caster);"
  if base=='eclipse_existence':code+=choice('FaBMONSoul($owner)','Banish_from_Light_soul')+" $n = FaBMONBanishSelected($chosen ?? 'PASS'); FaBARCLoseLife($owner, $n, $caster);"
  add('AttackDeclared' if base=='blasmophet_the_soul_harvester' else 'Hit',"if ("+('true' if base=='blasmophet_the_soul_harvester' else 'FaBFaiHeroHit()')+") { "+code+' }')
 if base=='eclipse_existence':add('ResolveCard',"FaBMONAdd($player, 'ECLIPSE_EXISTENCE'); if (FaBMONHigherLight($player)) { "+choice("implode('&', FaBChoiceRefs($player, 'Graveyard', ['type'=>'Action']))",'Banish_an_action')+" FaBMoveChoice($player, $chosen ?? 'PASS', 'Graveyard', 'Banish'); }")
 if base=='invert_existence':
  code=choice('FaBARCHeroTargets($player, true, true)','Choose_opposing_graveyard',False)+" $owner = FaBARCTargetSeat($player, $chosen, true, true); $targets = implode('&', FaBChoiceRefs($owner, 'Graveyard')); if ($targets !== '') { $chosen = await $player.MZMultiChoose($targets, 0, 2, \"Banish_up_to_two\"); $both = FaBMONHasBoth($chosen); FaBMONBanishSelected($chosen); if ($both) { "+arcane(2,'$owner')+' } }'
  add('ResolveCard',code)
 if base=='blinding_beam':
  code=choice('FaBMONCombatTargets()','Choose_attacking_or_defending_attack',False)+" $f = FaBIdentityFromMZ($chosen ?? ''); if ($f !== null) { FaBARCSetCard($uid, 'monTargetUID', intval($f['object']->UniqueID)); if (FaBHasType($f['object'], 'Shadow')) { FaBMONSetCost($uid, 0); } }"
  add('PrepareCard',finish_prepare(code))
  add('ResolveCard',f"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); FaBTagUID(intval(FaBARCCard($uid, 'monTargetUID')), 'WTR_POWER:-{v}');")
 if base=='glisten':
  add('ResolveCard',f"$remaining = {v+1}; while ($remaining > 0) {{ "+choice('FaBMONWeaponChoices($player)','Allocate_power_counter_or_pass')+" if (!isset($chosen) || $chosen === 'PASS') { break; } $n = await $player.NumberChoose(1, $remaining, \"Counters_on_this_weapon\"); FaBMONGlisten($player, $chosen, intval($n)); $remaining -= intval($n); }")
 if base=='hexagore_the_death_hydra':add('AttackDeclared',"DoDamage($player, $mzID, $player, max(0, 6 - FaBMONBloodDebt($player)), 'PHYSICAL');")
 if base=='lumina_ascension':add('ResolveCard',"FaBMONAdd($player, 'LUMINA'); if (FaBMONCount($player, 'CHARGED')) { FaBMONExtraWeapons($player); }")
 if base=='ray_of_hope':add('ResolveCard',"FaBMONAdd($player, 'RAY'); if (FaBMONLessLife($player, 'Shadow')) { FaBMoveUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'Soul', $player); }")
 if base=='nourishing_emptiness':
  add('DominateModifier',"return count(FaBChoiceRefs($player, 'Graveyard', ['attackAction'=>true])) === 0 ? 1 : 0;")
  add('Hit',"if (FaBFaiHeroHit() && count(FaBChoiceRefs($player, 'Graveyard', ['attackAction'=>true])) === 0) { FaBWTRAddEffect($player, 'INTELLECT', 1); }")
 if base=='soul_reaping':
  code="$maximum = count(FaBChoiceRefs($player, 'Hand')); $minimum = FaBAvailablePitch($player) >= intval(FaBGetState()['pendingPayment']['cost']) ? 0 : 1; "+multi("implode('&', FaBChoiceRefs($player, 'Hand'))",'$minimum','$maximum','Banish_cards_instead_of_resource_cost')+" $n = FaBMONSelectedDebt($chosen); if (FaBMoveChoices($player, $chosen, 'Hand', 'Banish') > 0) { FaBMONSetCost($uid, 0); AddResources($player, intval(GetResources($player)) + $n); }"
  add('PrepareCard',finish_prepare(code))
 if base=='sonata_arcanix':
  add('PrepareCard',finish_prepare("$maximum = intdiv(FaBAvailablePitch($player), 2); $x = await $player.NumberChoose(0, $maximum, \"Choose_X_pay_twice_X\"); FaBARCSetCard($uid, 'monX', intval($x)); FaBMONSetCost($uid, intval($x) * 2);"))
  code="$sourceUID = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $n = intval(FaBARCCard($sourceUID, 'monX')) + 3; $uids = FaBARCStageTop($player, $n); $refs = implode('&', FaBChoiceRefs($player, 'Temp')); FaBRevealChoices($player, $refs); $n = min(FaBMONNonAttacks($refs), count(FaBChoiceRefs($player, 'Temp', ['attackAction'=>true]))); if ($n > 0) { "+multi("implode('&', FaBChoiceRefs($player, 'Temp', ['attackAction'=>true]))",'$n','$n','Put_attacks_into_hand')+" FaBMoveChoices($player, $chosen, 'Temp', 'Hand'); "+arcane('$n')+" } FaBFinishSearch($player); FaBMoveUID($sourceUID, 'Banish', $player);"
  add('ResolveCard',code)
 if base=='hooves_of_the_shadowbeast':add('ResolveAbility',"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $mode = await $player.Modal(1, 1, \"Keep_equipment&Destroy_for_action_point\", \"Hooves_of_the_Shadowbeast\"); if ($mode === '1' && FaBFindUID($uid) !== null) { FaBMONDestroy($uid); AddActionPoints($player, intval(GetActionPoints($player)) + 1); }")
 if base=='valiant_dynamo':add('ResolveAbility',"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $mode = await $player.Modal(1, 1, \"Keep_counter&Remove_defense_counter\", \"Valiant_Dynamo\"); $f = FaBFindUID($uid); if ($mode === '1' && $f !== null) { FaBSetObjectCounter($f['object'], 'DEFENSE', max(0, intval(FaBObjectCounters($f['object'])['DEFENSE'] ?? 0) - 1)); }")
 if base.startswith('ironhide_') or base=='phantasmal_footsteps':
  code="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $paid = false; if (FaBAvailablePitch($player) >= 1) { $mode = await $player.Modal(1, 1, \"Decline&Pay_one_resource\", \"Pay_for_equipment_defense\"); if ($mode === '1') { while (intval(GetResources($player)) < 1) { $refs = FaBARCPitchChoices($player); $chosen = await $player.MZChoose($refs, \"Pitch_for_equipment\"); FaBARCPitchForEffect($player, $chosen); } AddResources($player, intval(GetResources($player)) - 1); $paid = true; } }"
  add('Defended',code+ ("if ($paid) { FaBTagUID($uid, 'MON_FOOT_DEFENSE'); } FaBMONFootstepsBlock($uid);" if base=='phantasmal_footsteps' else "if ($paid) { FaBTagUID($uid, 'WTR_DEFENSE:2'); FaBTagUID($uid, 'DESTROY_ON_CHAIN_CLOSE'); }"))
  if base=='phantasmal_footsteps':add('ResolveAbility',"if (!FaBMONCount($player, 'FOOTSTEPS')) { "+code.replace('Pay_for_equipment_defense','Pay_for_action_point')+" if ($paid) { FaBMONAdd($player, 'FOOTSTEPS'); AddActionPoints($player, intval(GetActionPoints($player)) + 1); } }")
 if not handled:pending.append(id)
 snapshot.append(dict(cardId=id,abilities=abilities))
for entry in snapshot:
 old={a['macroName']:a for a in previous.get(entry['cardId'],{}).get('abilities',[])}
 for a in entry['abilities']:
  before=old.get(a['macroName'])
  if before and before['abilityCode'].strip()!=a['abilityCode'].strip():a['previousCodeHash']=hashlib.sha256(before['abilityCode'].strip().encode()).hexdigest()
  elif before and 'previousCodeHash' in before:a['previousCodeHash']=before['previousCodeHash']
support=[]
for entry in existing.values():
 changed=[]
 for a in entry['abilities']:
  old=a['abilityCode']
  if '$dealt = FaBARCDealArcane(' not in old:continue
  a=dict(a);a['previousCodeHash']=hashlib.sha256(old.strip().encode()).hexdigest()
  a['abilityCode']=old.replace('$dealt = FaBARCDealArcane(',clean(spellvoid)+'$dealt = FaBARCDealArcane(')
  changed.append(a)
 if changed:support.append(dict(cardId=entry['cardId'],abilities=changed))
(HERE/'mon_support_abilities.json').write_text(json.dumps(support,indent=2)+'\n',encoding='utf-8')
(HERE/'mon_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n',encoding='utf-8')
print('MON identities:',len(snapshot),'Unhandled:',len(pending));print('\n'.join(pending))
if pending:raise SystemExit(1)
