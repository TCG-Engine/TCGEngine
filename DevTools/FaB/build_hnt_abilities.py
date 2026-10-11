"""The Hunted saved card-editor source; every identity must be accounted for."""
import ast,json,hashlib,re
from pathlib import Path
HERE=Path(__file__).parent
for filename,names in [('build_mon_abilities.py',['clean']),('build_dyn_abilities.py',['pick']),('build_out_abilities.py',['pay']),('build_upr_abilities.py',['deal'])]:
 src=(HERE/filename).read_text()
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V="$victim = intval(FaBGetState()['defender']);"
def choose(expr,tip='Choose_card',may=True,p='$player',var='$chosen'):
 return f'{var} = "-"; $refs = {expr}; if ($refs !== "") {{ '+(f'{var} = await {p}.MZMultiChoose($refs, 0, 1, "{tip}");' if may else f'{var} = await {p}.MZChoose($refs, "{tip}");')+' }'
def many(expr,maximum,minimum=0,tip='Choose_cards'):
 return f'$minimum = {minimum}; $refs = {expr}; $chosen = "-"; $maximum = min({maximum}, count(array_filter(explode("&", $refs)))); if ($maximum >= $minimum && $maximum > 0) {{ $chosen = await $player.MZMultiChoose($refs, $minimum, $maximum, "{tip}"); }}'
def refs(z,p='$player',filters='[]'):return f"implode('&', FaBChoiceRefs({p}, '{z}', {filters}))"
def token(t,p='$player',n=1):return f"FaBHVYToken({p}, '{t}', {n}, $player);"
def hit(code):return 'if (FaBFaiHeroHit()) { '+V+code+' }'
original_deal=deal
def deal(*args,**kwargs):
 code=original_deal(*args,**kwargs)
 code=re.sub(r'MZMayChoose\((\$\w+), ',r'MZMultiChoose(\1, 0, 1, ',code)
 return code.replace("=== 'PASS'","=== '-'")
existing={}
for name in ['wtr','arc','cru','mon','ele','evr','upr','dyn','out','dtd','evo','hvy','mst','ros','arakni']:
 for entry in json.loads((HERE/(name+'_abilities.json')).read_text()):
  previous=existing.get(entry['cardId'],{'abilities':[]})
  merged={a['macroName']:a for a in previous['abilities']}
  merged.update({a['macroName']:a for a in entry['abilities']})
  existing[entry['cardId']]={'cardId':entry['cardId'],'abilities':list(merged.values())}
# The DTD revision of Runechant includes Vynnset's prevention timing.
for entry in json.loads((HERE/'dtd_abilities.json').read_text()):
 if entry['cardId']=='runechant':
  for ability in entry['abilities']:
   existing['runechant']['abilities']=[a for a in existing['runechant']['abilities'] if a['macroName']!=ability['macroName']]+[ability]
old={c['cardId']:c for c in json.loads((HERE/'hnt_abilities.json').read_text())} if (HERE/'hnt_abilities.json').exists() else {}
out=[];pending=[]
def flick(may=True,destroy=True,draw=False,exclude=False):
 return choose('FaBArakniDaggers($player)' if exclude else 'FaBHNTDaggers($player)','Choose_dagger',may)+"if ($chosen !== '-') { $daggerUID = FaBUPRUIDs($chosen)[0]; $hitVictim = $victim; $targetUID = FaBUPRHeroUID($hitVictim); "+deal(1,sourceUID='$daggerUID',physical=True)+"FaBHNTPseudoHit($player, $daggerUID, $hitVictim, $dealt); "+('if ($dealt > 0) { DoDrawCard($player, 1); }' if draw else '')+('FaBMONDestroy($daggerUID);' if destroy else '')+' }'
def retrieve():
 return choose('FaBArakniRetrieveTargets($player)','Retrieve_dagger_for_one_resource')+"if ($chosen !== '-') { $daggerUID = FaBUPRUIDs($chosen)[0]; while (intval(GetResources($player)) < 1) { $pitchRefs = FaBARCPitchChoices($player); $pitched = await $player.MZChoose($pitchRefs, \"Pitch_to_retrieve\"); FaBARCPitchForEffect($player, $pitched); } AddResources($player, intval(GetResources($player)) - 1); FaBHNTEquip($player, FaBDTDSource($daggerUID)); }"
def reaction(n,kind='DAGGER',tail=''):
 return choose(f"FaBHNTAffectsAttack($player, '{kind}')",'Choose_attack',False)+f"FaBDYNTag($chosen, 'WTR_POWER:' . ({n}));"+tail
def mark_target():
 return choose('FaBDYNHeroTargets($player, true)','Choose_opposing_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player'] ?? 0); FaBHNTMark($victim);"
def discard_hand(v='$victim',banish=True):
 return choose(refs('Hand',v),'Banish_a_card' if banish else 'Discard_a_card',False,v)+(f'FaBDYNBanishChoice($chosen, $player);' if banish else f'FaBDiscardChoice({v}, $chosen);')
for c in json.loads((HERE/'hnt_catalog.json').read_text()):
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);txt=c['functional_text_plain'];a=[];handled=False
 def add(m,code):
  global handled
  handled=True;prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing and b not in ['danger_digits','up_sticks_and_run','throw_dagger']:
  out.append(existing[id]);continue
 native=['arakni_5lp3d_7hru_7h3_cr4x','arakni_marionette','arakni_web_of_deceit','fang','fang_dracai_of_blades','graphene_chelicera','kunai_of_retribution','obsidian_fire_vein','blood_drop','blood_line','compounding_anger','grow_claws','grow_wings','cut_through','march_of_loyalty','outed','plunge_the_prospect','agility_stance','flurry_stance','power_stance','blessing_of_vynserakai','sharpened_senses','marked','put_in_context','ring_of_roses']
 if b in native or b.startswith(('blade_beckoner_','red_alert_')) or txt in ['', 'Go again']:handled=True
 if b in ['cindra','cindra_dracai_of_retribution']:add('ResolveAbility',many("FaBHNTEqpDaggers($player, 'Graveyard', true)","min(2, FaBHNTOpenHands($player))",0,'Equip_Draconic_daggers')+'FaBHNTEquip($player, $chosen);')
 if b=='fealty':add('ResolveAbility',"FaBHNTAdd($player, 'FEALTY_NEXT');")
 if b in ['arakni_black_widow','arakni_funnel_web','arakni_orb_weaver','arakni_redback','arakni_tarantula']:
  add('PrepareCard',UID+choose("FaBROSRefs($player, 'Hand', 'Assassin')",'Discard_Assassin_as_cost',False)+"FaBDiscardChoice($player, $chosen); FaBFinishPreparedCard($uid);")
  if b=='arakni_orb_weaver':code="FaBHNTGraphene($player); FaBHNTNext($player, 'STEALTH', 3);"
  else:code=reaction(3,'DAGGER' if b=='arakni_tarantula' else 'ASSASSIN')+("$f = FaBIdentityFromMZ($chosen); if ($f !== null && FaBHasKeyword($f['object'], 'Stealth')) { FaBDYNTag($chosen, '"+{'arakni_black_widow':'HNT_BLACK_WIDOW','arakni_funnel_web':'HNT_FUNNEL_WEB','arakni_redback':'GO_AGAIN'}[b]+"'); }" if b!='arakni_tarantula' else '')
  add('ResolveAbility',code)
 if b=='arakni_trap_door':add('ResolveAbility',choose("FaBStageSearch($player, [])",'Banish_card_face_down')+"if ($chosen !== '-') { $targetUID = FaBUPRUIDs($chosen)[0]; FaBHNTTrap($player, $targetUID, true); } FaBFinishSearch($player);")
 if b=='pledge_fealty':add('ResolveCard',token('fealty'))
 if b in ['tag_the_target','trap_and_release','pursue_to_the_edge_of_oblivion','pursue_to_the_pits_of_despair','mark_the_prey','hunters_klaive']:add('Hit',hit('FaBHNTMark($victim);'))
 if b=='mark_of_the_huntsman':
  code="$mode = await $player.Modal(1, 1, \"Keep_dagger&Destroy_and_mark\", \"Mark_of_the_Huntsman\"); if ($mode === '1') { FaBHNTDestroyWeapon($uid); FaBHNTMark($victim); }"
  add('Hit',UID+hit(code));add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $victim = intval(DecisionQueueController::GetVariable('rosTarget')); "+code)
 if b in ['mark_of_the_black_widow','mark_of_the_funnel_web']:
  code=discard_hand() if b=='mark_of_the_black_widow' else choose(refs('Arsenal','$victim'),'Banish_arsenal_card',False)+'FaBDYNBanishChoice($chosen, $player);'
  add('Hit',UID+hit('if (FaBHNTHitMarked($uid, $victim)) { '+code+' }'))
  add('ResolveAbility',"$victim = intval(DecisionQueueController::GetVariable('rosTarget')); "+code)
 if b in ['defang_the_dragon','extinguish_the_flames']:add('Hit',UID+hit("if (FaBHNTHitMarked($uid, $victim) && FaBMONHero($victim, '"+('fang' if b=='defang_the_dragon' else 'cindra')+"')) { DoDrawCard($player, 1); }"))
 if b=='kiss_of_death':add('Hit',hit('FaBARCLoseLife($victim, 1, $player);'))
 if b in ['hot_on_their_heels','mark_with_magma']:add('Hit',hit('if (FaBFaiChainCount($player) >= 2) { FaBHNTMark($victim); }'))
 if b in ['for_the_dracai','for_the_emperor','for_the_realm']:add('AttackDeclared','if (FaBHNTMarkedTarget()) { '+token('fealty')+' }')
 if b in ['demonstrate_devotion','display_loyalty']:add('AttackDeclared','if (FaBFaiHeroHit() && FaBFaiChainCount($player) >= 2) { '+token('fealty')+' }')
 if b=='whittle_from_bone':add('AttackDeclared','if (FaBHNTMarkedTarget()) { FaBHNTGraphene($player); }')
 if b=='hunt_the_hunter':add('AttackDeclared',hit("if (count(array_filter(FaBARCPlayed($player), fn($id) => intval(CardPitch($id)) === 1)) > 1) { FaBHNTMark($victim); }"))
 if b=='hunt_to_the_ends_of_rathe':add('AttackDeclared',hit('if (FaBHNTArakni($victim)) { FaBHNTMark($victim); }'))
 if b in ['bite','throw_yourself_at_them','silver_talons']:
  add('AttackDeclared',UID+hit(("if (FaBHNTUIDDraconic($uid)) { " if b=='silver_talons' else '')+flick()+(' }' if b=='silver_talons' else '')))
 if b=='blood_runs_deep':add('AttackDeclared',hit("$daggers = FaBUPRUIDs(FaBHNTDaggers($player)); $hitVictim = $victim; for ($i = 0; $i < count($daggers); $i = $i + 1) { $daggerUID = $daggers[$i]; $targetUID = FaBUPRHeroUID($hitVictim); "+deal(1,sourceUID='$daggerUID',physical=True)+"FaBHNTPseudoHit($player, $daggerUID, $hitVictim, $dealt); FaBMONDestroy($daggerUID); }"))
 if b=='burning_blade_dance':add('Hit',hit('if (FaBFaiChainCount($player) >= 2) { '+flick()+' }'))
 if b=='pain_in_the_backside':add('Hit',hit(flick(False,False)))
 if b in ['throw_dagger','danger_digits']:add('ResolveCard' if b=='throw_dagger' else 'ResolveAbility',hit(flick(False,True,b=='throw_dagger',True)))
 if b=='blood_splattered_vest':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $mode = await $player.Modal(1, 1, \"Decline&Gain_resource\", \"Blood_Splattered_Vest\"); if ($mode === '1') { FaBHNTVest($uid); }")
 if b=='devotion_never_dies':add('Hit',UID+'if (FaBHNTPreviousDraconic()) { FaBHNTBanishPlay($uid); }')
 if b=='pick_up_the_point':add('AttackDeclared',retrieve())
 nexts={'cut_deep':(v+1,''),'hunt_a_killer':(v+1,'HNT_MARK'),'sworn_vengeance':(v,'HNT_MARK'),'knife_through_butter':(v+1,''),'twist_and_turn':(v+1,'HNT_EXTRA'),'up_sticks_and_run':(v+1,''),'savor_bloodshed':(4,''),'point_of_engagement':(v,'')}
 if b in nexts:
  n,t=nexts[b];add('ResolveCard',(retrieve() if b=='up_sticks_and_run' else '')+f"FaBHNTNext($player, 'DAGGER', {n}, '{t}');"+({'knife_through_butter':"FaBHNTAdd($player, 'KNIFE');",'savor_bloodshed':"FaBHNTAdd($player, 'SAVOR');",'point_of_engagement':"FaBHNTAdd($player, 'ENGAGEMENT');"}.get(b,'')))
 if b=='twist_and_turn':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $mode = await $player.Modal(1, 1, \"Decline&Attack_again\", \"Additional_dagger_attack\"); if ($mode === '1') { FaBHNTExtra($uid); }")
 if b in ['public_bounty','proclaim_vengeance','relentless_pursuit','cut_from_the_same_cloth']:
  add('PrepareCard',UID+choose('FaBDYNHeroTargets($player, true)','Choose_opposing_hero',False)+'FaBUPRStoreTarget($uid, $chosen); FaBFinishPreparedCard($uid);')
  tail={'public_bounty':f"FaBHNTAdd($player, 'BOUNTY', {v});",'proclaim_vengeance':"if (FaBHNTArakni($victim)) { AddResources($player, intval(GetResources($player)) + 1); }",'relentless_pursuit':"if (FaBHNTCount($player, 'ATTACKED_' . $victim)) { FaBARCToDeck($player, $uid, false); }",'cut_from_the_same_cloth':f"FaBHNTNext($player, 'DAGGER', {v+1});"}[b]
  add('ResolveCard',UID+"$victim = intval(FaBUPRTarget($uid)['player'] ?? 0); "+("if (FaBHNTRevealReactions($victim)) { FaBHNTMark($victim); }" if b=='cut_from_the_same_cloth' else 'FaBHNTMark($victim);')+tail)
 if b in ['tooth_of_the_dragon','trot_along','poisoned_blade','rake_over_the_coals','oath_of_loyalty','drop_of_dragon_blood','coat_of_allegiance','heart_of_vengeance','imperial_seal_of_command','orb_weaver_spinneret','calming_breeze']:
  code={'tooth_of_the_dragon':f"FaBHNTNext($player, 'DRACONIC', {v});",'trot_along':"FaBHNTNext($player, 'SMALL', 0, 'GO_AGAIN');",'poisoned_blade':"FaBHNTAdd($player, 'POISON_CHAIN');",'rake_over_the_coals':"FaBHNTAdd($player, 'COALS');",'oath_of_loyalty':"FaBHNTAdd($player, 'ONLY_DRACONIC');",'drop_of_dragon_blood':'AddResources($player, intval(GetResources($player)) + 1); DoDrawCard($player, 1);','coat_of_allegiance':"AddResources($player, intval(GetResources($player)) + 1); FaBHNTAdd($player, 'ONLY_DRACONIC');",'heart_of_vengeance':"FaBHNTAdd($player, 'VENGEANCE');",'imperial_seal_of_command':"FaBHNTAdd($player, 'NO_DR'); if (FaBDYNRoyal($player)) { FaBHNTAdd($player, 'SEAL'); }",'orb_weaver_spinneret':"FaBHNTGraphene($player); FaBHNTNext($player, 'STEALTH', 3);",'calming_breeze':f"FaBHNTAdd($player, 'CALM', {v});"}[b]
  add('ResolveAbility' if b in ['coat_of_allegiance','heart_of_vengeance','imperial_seal_of_command'] else 'ResolveCard',code)
 if b=='dual_threat':add('ResolveCard',"if (FaBHNTCount($player, 'WEAPON_ATTACK')) { FaBHNTNext($player, 'AA', 3); } if (FaBHNTCount($player, 'AA_ATTACK')) { FaBHNTNext($player, 'WEAPON', 3); }")
 if b in ['fire_tenet_strike_first','ignite','wrath_of_retribution']:add('AttackDeclared',{'fire_tenet_strike_first':"FaBHNTNext($player, 'DRACONIC', 1, '', true);",'ignite':"FaBHNTAdd($player, 'IGNITE_CHAIN');",'wrath_of_retribution':"FaBHNTWrath($player);"}[b])
 if b=='fire_and_brimstone':add('ResolveCard',"FaBHNTBuffDaggers($player, 1, true);")
 if b=='perforate':add('ResolveCard',choose('FaBHNTDaggers($player)','Choose_dagger',False)+"FaBHNTPerforate($chosen); DoDrawCard($player, 1);")
 basic={'incision':v,'dynastic_dedication':v,'imperial_intent':v-1,'brothers_of_flame':v+1,'sisters_of_fire':v,'hunts_end':4,'scar_tissue':v,'take_a_stab':v,'affirm_loyalty':v-1,'endear_devotion':v,'searing_gaze':v-1,'stabbing_pain':v}
 if b in basic:
  tail="FaBDYNTag($chosen, 'HNT_MARK');" if b=='scar_tissue' else "FaBDYNTag($chosen, 'HNT_EXTRA_MARKED');" if b=='take_a_stab' else ''
  if b in ['affirm_loyalty','endear_devotion']:tail+='if (FaBFaiChainCount($player) >= 2) { '+token('fealty')+' }'
  if b in ['searing_gaze','stabbing_pain']:tail+="if (FaBFaiChainCount($player) >= 2) { FaBDYNTag($chosen, 'HNT_MARK'); }"
  add('ResolveCard',reaction(basic[b],tail=tail))
 if b in ['to_the_point','blistering_blade','sizzling_steel','scalding_iron','diced']:
  n='FaBFaiChainCount($player)' if b=='scalding_iron' else f'FaBHNTMarkedTarget() ? {v+1} : {v}' if b=='to_the_point' else f'FaBFaiChainCount($player) >= 2 ? {v if b=="blistering_blade" else v+1} : {v-1 if b=="blistering_blade" else v}' if b!='diced' else 1
  add('ResolveCard',reaction(n)+(f"FaBHNTNext($player, 'DAGGER', {v});" if b=='diced' else ''))
 if b in ['exposed','nip_at_the_heels','jagged_edge','stains_of_the_redback']:
  add('ResolveCard',reaction(1 if b in ['exposed','nip_at_the_heels'] else v,'ANY' if b=='exposed' else 'SMALL' if b=='nip_at_the_heels' else 'WEAPON' if b=='jagged_edge' else 'STEALTH')+{'exposed':V+'FaBHNTMark($victim);','jagged_edge':"FaBDYNTag($chosen, 'UPR_UNPREVENTABLE');",'stains_of_the_redback':"FaBDYNTag($chosen, 'GO_AGAIN');"}.get(b,''))
 if b=='lay_low':add('ResolveCard',V+"if (FaBHNTMarked($victim)) { FaBHNTNext($victim, 'ANY', -1); }")
 if b in ['hand_of_vengeance','path_of_vengeance','dragonscaler_flight_path']:
  add('ResolveAbility',reaction(1 if b=='hand_of_vengeance' else 0,'DRACONIC' if b=='dragonscaler_flight_path' else 'ANY')+("FaBDYNTag($chosen, 'GO_AGAIN');" if b!='hand_of_vengeance' else '')+("FaBHNTExtraIfWeaponOrAlly($chosen);" if b=='dragonscaler_flight_path' else ''))
 if b=='vow_of_vengeance':add('ResolveAbility',choose('FaBHNTArakniTargets($player)','Mark_Arakni',False)+"FaBHNTMark(intval(FaBIdentityFromMZ($chosen)['player'] ?? 0));")
 if b.startswith('leap_frog_'):add('ResolveAbility',UID+"$mode = await $player.Modal(1, 1, \"Keep_equipment&Defend\", \"Leap_Frog\"); if ($mode === '1') { FaBArakniLeap($uid); }")
 if b in ['smoke_out','den_of_the_spider','lair_of_the_spider','kabuto_of_imperial_authority']:
  add('Defended',"FaBHNTDefended($player, '"+b+"');")
 if b=='mask_of_deceit':add('Defended',"$attacker = intval(FaBGetState()['attacker']); if (FaBHNTMarked($attacker)) { $options = implode('&', FaBHNTAgents()); $mode = await $player.Modal(1, 1, $options, \"Choose_Agent_of_Chaos\"); FaBHNTBecome($player, intval($mode)); } else { FaBHNTBecome($player); }")
 if b in ['misfire_dampener','enchanted_quiver','tremorshield_sabatons']:add('ResolveAbility',"FaBWTRAddEffect($player, 'ARC_PREVENT', "+{'misfire_dampener':"FaBARCEffect($player, 'ARC_BOOSTED') > 0",'enchanted_quiver':"FaBDYNAimTargets($player) !== ''",'tremorshield_sabatons':"FaBHNTCount($player, 'SEISMIC') > 0 || count(FaBMONArena($player, 'seismic_surge')) > 0"}[b]+" ? 2 : 1);")
 if b in ['reapers_call','tip_off']:add('ResolveAbility',mark_target())
 if b=='shelter_from_the_storm':add('ResolveAbility',f"FaBHNTAdd($player, 'CALM', {v});")
 if b=='under_the_trap_door':add('ResolveAbility',choose("FaBROSRefs($player, 'Graveyard', 'Trap')",'Banish_trap',False)+"if ($chosen !== '-') { FaBHNTTrap($player, FaBUPRUIDs($chosen)[0], false); }")
 if b=='thick_hide_hunter':
  for m in ['AttackDeclared','Defended']:add(m,'FaBDiscardRandom($player, 1);')
 if b=='loyalty_beyond_the_grave':add('ResolveAbility',many("implode('&', FaBChoiceRefs($player, 'Graveyard', ['base'=>'loyalty_beyond_the_grave']))",2,0,'Banish_two_to_draw')+"if (count(FaBUPRUIDs($chosen)) === 2) { FaBROSReturn($chosen, 'Banish'); DoDrawCard($player, 1); }")
 if b=='prowess_of_agility':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $mode = await $player.Modal(1, 1, \"Decline&Destroy_to_draw\", \"Prowess_of_Agility\"); if ($mode === '1' && FaBFindUID($uid) !== null) { FaBMONDestroy($uid); DoDrawCard($player, 1); }")
 if b=='null_time_zone':add('ResolveCard',UID+"$preview = ''; $name = await $player.NameCard($preview, \"Name_a_card\"); FaBARCSetCard($uid, 'hntName', $name);")
 if b=='schism_of_chaos':add('CardPitched','FaBHNTSchism();')
 if b=='bubble_to_the_surface':add('ResolveCard','FaBHNTBubble($player);')
 if b=='cull':add('ResolveCard',"$seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $victim = $seats[$i]; "+discard_hand()+' }')
 if b.startswith('art_of_the_dragon_') or b=='dragon_power':
  body={'art_of_the_dragon_blood':"FaBTagUID($uid, 'GO_AGAIN'); FaBHNTAdd($player, 'BLOOD_DISCOUNT', 3);",'art_of_the_dragon_claw':"FaBTagUID($uid, 'HNT_CLAW');",'art_of_the_dragon_scale':"FaBTagUID($uid, 'HNT_SCALE');",'dragon_power':f"FaBTagUID($uid, 'WTR_POWER:{v}');",'art_of_the_dragon_fire':choose('FaBUPRAnyTargets($player)','Deal_two_damage',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; "+deal(2,physical=True)}[b]
  add('AttackDeclared',UID+'if (FaBHNTUIDDraconic($uid)) { '+body+' }')
  if b=='art_of_the_dragon_claw':add('Hit',UID+hit("if (FaBHNTTagged($uid, 'HNT_CLAW')) { FaBDYNDestroyUIDs(FaBUPRUIDs(implode('&', FaBChoiceRefs($victim, 'Arsenal')))); }"))
  if b=='art_of_the_dragon_scale':add('Hit',UID+hit("if (FaBHNTTagged($uid, 'HNT_SCALE')) { "+choose("FaBDYNEquipment($victim)",'Weaken_equipment',False)+"FaBHNTScale($chosen); }"))
 if b=='scuttle_the_canal':handled=True
 if b=='anaphylactic_shock':add('ResolveCard','FaBHNTShock($player);')
 if b=='bunker_beard':add('ResolveAbility',choose("FaBROSRefs($player, 'Arsenal', 'Action')",'Add_defending_action')+"if ($chosen !== '-') { FaBHNTDefend($player, FaBUPRUIDs($chosen)[0]); }")
 if b=='quickdodge_flexors':add('ResolveAbility',UID+'FaBHNTDefend($player, $uid, true);')
 if b=='chain_reaction':add('Defended',"$s = FaBGetState(); $attack = FaBFindUID(intval($s['attackUID'])); if ($attack !== null && FaBAttackHasGoAgain($s, $attack['object'])) { "+choose("FaBHNTFaceDownActions($player)",'Turn_non_attack_action_face_up')+'FaBHNTFaceUpInstant($chosen); }')
 if b=='douse_in_runeblood':add('AttackDeclared',UID+"$before = FaBARCRunechants($player); $n = count(array_filter(FaBARCPlayed($player), fn($id) => FaBHasType($id, 'Action') && !FaBHasType($id, 'Attack'))); FaBARCCreateRunes($player, $n); if (FaBARCRunechants($player) - $before >= 3) { FaBTagUID($uid, 'GO_AGAIN'); }")
 if b=='provoke':add('ResolveCard',"if (FaBDYNAttacks($player, 'WEAPON') !== '') { "+V+choose(refs('Hand','$victim'),'Reveal_a_card',False,'$victim')+"FaBRevealChoices($victim, $chosen); $f = FaBIdentityFromMZ($chosen); if ($f !== null) { if (FaBHasType($f['object'], 'Action')) { FaBHNTDefend($victim, intval($f['object']->UniqueID)); } else { FaBDiscardChoice($victim, $chosen); } } }")
 if b=='retrace_the_past':add('AttackDeclared',UID+"if (FaBHNTPreviousGustwave()) { $preview = ''; $name = await $player.NameCard($preview, \"Name_a_card\"); FaBOUTNameCard($uid, $name); FaBTagUID($uid, 'WTR_POWER:2'); FaBTagUID($uid, 'GO_AGAIN'); }")
 if b=='sound_the_alarm':add('AttackDeclared',hit('if (FaBHNTRevealReactions($victim)) { '+choose("FaBStageSearch($player, ['type'=>'Defense Reaction'])",'Search_defense_reaction')+"$topUID = FaBUPRUIDs($chosen)[0] ?? 0; FaBRevealChoices($player, $chosen); FaBMoveChoices($player, $chosen, 'Temp', 'Hand'); FaBFinishSearch($player); if ($topUID > 0) { FaBARCToDeck($player, $topUID, true); } }"))
 if b=='roiling_fissure':
  add('PrepareCard',UID+'$maximum = FaBHVYMaxX($player, $uid); $x = await $player.NumberChoose(0, $maximum, "Choose_X"); FaBHVYSetX($uid, intval($x)); FaBFinishPreparedCard($uid);')
  add('ResolveCard',UID+"$repeat = true; $x = intval(FaBARCCard($uid, 'evoX')); while ($repeat) { "+choose('FaBROSAllAuras($player, true, $x)','Destroy_aura',False)+"FaBHVYDestroyChoices($chosen); "+choose("implode('&', FaBMONArena($player, 'seismic_surge'))",'Destroy_Seismic_Surge_to_repeat')+"$repeat = $chosen !== '-'; if ($repeat) { FaBHVYDestroyChoices($chosen); } }")
 if b=='rotten_remains':add('AttackDeclared',UID+"$repeat = true; while ($repeat && FaBHNTRottenReady()) { $mode = await $player.Modal(1, 1, \"Decline&Banish_from_each_graveyard\", \"Rotten_Remains\"); $repeat = $mode === '1'; if ($repeat) { $seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = $seats[$i]; "+choose('FaBHNTOnePower($seat)','Banish_one_power_card',False)+"FaBDYNBanishChoice($chosen, $player); } FaBTagUID($uid, 'WTR_POWER:1'); } }")
 if b=='spur_locked':add('ResolveCard',"$seats = FaBLiveSeats(); $choices = []; for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = $seats[$i]; $number = await $seat.NumberChoose(1, 6, \"Secretly_choose_a_number\"); $choices[$seat] = intval($number); } FaBHNTRevealSpur($choices); $winner = FaBHNTSpurWinner($choices); if ($winner > 0) { $number = $choices[$winner]; FaBARCLoseLife($winner, $number, $player); "+choose("FaBStageSearch($winner, ['maxCost'=>$number])",'Search_card',False,'$winner')+"FaBRevealChoices($winner, $chosen); FaBMoveChoices($winner, $chosen, 'Temp', 'Hand'); FaBFinishSearch($winner); }")
 if b=='take_up_the_mantle':add('ResolveCard',choose("FaBHNTAffectsAttack($player, 'STEALTHAA')",'Choose_stealth_attack',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; $marked = FaBHNTMarkedTarget(); FaBTagUID($targetUID, 'WTR_POWER:' . ($marked ? 3 : 2)); if ($marked) { "+choose("FaBHNTStealthAA($player, 'Graveyard')",'Banish_stealth_attack_to_copy')+"if ($chosen !== '-') { FaBHNTCopy($targetUID, $chosen); } }")
 if b=='two_sides_to_the_blade':add('ResolveCard',"$options = FaBHNTSidesOptions($player); if ($options !== '') { $mode = await $player.Modal(1, 1, $options, \"Choose_mode\"); $kind = FaBHNTSidesKind($player, intval($mode)); "+choose('FaBHNTAffectsAttack($player, $kind)','Choose_attack',False)+"FaBDYNTag($chosen, 'WTR_POWER:3'); if ($kind === 'STEALTHAA') { FaBDYNTag($chosen, 'HNT_MARK'); } }")
 if b=='tarantula_toxin':add('ResolveCard',"$options = FaBHNTToxinOptions($player); if ($options !== '') { $maximum = count(explode('&', $options)); $modes = await $player.Modal(1, $maximum, $options, \"Choose_one_or_both\"); $kinds = FaBHNTToxinKinds($player, $modes); if (in_array('DAGGER', $kinds, true)) { "+reaction(3)+" } if (in_array('DEFENDER', $kinds, true)) { "+choose("FaBHNTStealthDefenders($player)",'Weaken_defending_card',False)+"FaBDYNTag($chosen, 'WTR_DEFENSE:-3'); } }")
 if b=='long_whisker_loyalty':add('ResolveCard',"$count = FaBFaiChainCount($player); for ($i = 0; $i < $count; $i = $i + 1) { $options = FaBHNTWhiskerOptions($player); if ($options !== '') { $mode = await $player.Modal(1, 1, $options, \"Choose_dagger_effect\"); $kind = FaBHNTWhiskerKind($player, intval($mode)); if ($kind === 'POWER') { "+reaction(2)+" } else { "+choose('FaBHNTDaggers($player)','Choose_dagger',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; if ($kind === 'EXTRA') { FaBHNTExtra($targetUID); } else { FaBARCSetCard($targetUID, 'hntMarkNextTurn', intval(GetTurnNumber())); } } } }")
 if b in ['war_cry_of_bellona','war_cry_of_themis']:
  add('ResolveCard',choose("FaBHNTNamedWeapons($player, 'Raydn')",'Choose_Raydn',False)+"FaBDYNTag($chosen, 'WTR_POWER:2');" if b=='war_cry_of_bellona' else "FaBHNTNext($player, 'ANGEL', 4);")
  # Additional soul cost is selected on the ability stack before paying/discarding its source.
  add('PrepareCard',UID+"if (FaBHNTIsAbility($uid)) { "+many("FaBROSRefs($player, 'Soul')","count(FaBChoiceRefs($player, 'Soul'))",0,'Banish_X_soul_cards')+"FaBARCSetCard($uid, 'hntSoulX', count(FaBUPRUIDs($chosen))); FaBROSReturn($chosen, 'Banish'); } FaBFinishPreparedCard($uid);")
  add('ResolveAbility', ("$event = strval(DecisionQueueController::GetVariable('rosEvent')); if (str_starts_with($event, 'reflect:')) { FaBHNTReflect($player, intval(DecisionQueueController::GetVariable('rosTarget')), intval(substr($event, 8))); return; }" if b=='war_cry_of_bellona' else '')+"$stackUID = intval(DecisionQueueController::GetVariable('arcAbilityUID')); $x = intval(FaBARCCard($stackUID, 'hntSoulX')); "+(choose('FaBHNTAllWeapons($player)','Choose_weapon',False)+"FaBHNTAdd($player, 'BELLONA', $x, ['uid'=>FaBUPRUIDs($chosen)[0] ?? 0]);" if b=='war_cry_of_bellona' else many('FaBHNTAllBanish($player)','$x','$x','Turn_X_banished_cards_face_down')+'FaBHNTFaceDown($chosen);'))
 if not handled:pending.append(id)
 out.append(dict(cardId=id,abilities=a))
if pending:print('Unhandled',pending);raise SystemExit(1)
for c in out:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],existing.get(c['cardId'],{})).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior.get('previousCodeHash'):a['previousCodeHash']=prior['previousCodeHash']
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'hnt_abilities.json').write_text(json.dumps(out,indent=2)+'\n');print('HNT:',len(out),'identities;',sum(len(c['abilities']) for c in out),'macros;',len(pending),'unhandled')
