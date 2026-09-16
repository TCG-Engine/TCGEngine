"""Reproducible High Seas CardEditor source. Unhandled families fail the build."""
import ast, json, re, hashlib
from pathlib import Path
HERE=Path(__file__).parent
for filename,names in [('build_mon_abilities.py',['clean']),('build_hnt_abilities.py',['choose','many','refs','token','hit']),('build_upr_abilities.py',['deal']),('build_arc_abilities.py',['opt'])]:
 source=(HERE/filename).read_text()
 for node in ast.parse(source).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(source,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V="$victim = intval(FaBGetState()['defender']);"
def next_attack(kind='any',power=0,tag=''):return f"FaBSEANext($player, '{kind}', {power}, '{tag}');"
def tap(kind='readyCog',tip='Tap_a_cog',may=False):return choose(f"FaBSEARefs($player, '{kind}')",tip,may)+"$tapped = FaBSEATap($chosen);"
def untap(kind='cog',zone='Arena'):return choose(f"FaBSEARefs($player, '{kind}', '{zone}')",'Untap_card')+"FaBSEAUntap($chosen);"
def discard(may=False,kind='any'):
 return choose(f"FaBSEARefs($player, '{kind}', 'Hand')",'Discard_card',may)+"$discarded = FaBIdentityFromMZ($chosen)['object']->CardID ?? ''; FaBDiscardChoice($player, $chosen);"
def load(power=0,tag=''):
 return "if (FaBELEArsenalSpace($player)) { "+choose("FaBSEARefs($player, 'arrow', 'Hand')",'Put_arrow_face_up_into_arsenal')+f"FaBSEAArsenal($player, $chosen, {power}, '{tag}'); }}"
def destroy_target(kind='item',v='$victim'):
 return choose(f"FaBSEARefs({v}, '{kind}')",'Destroy_card',False)+"FaBMONDestroy(FaBUPRUIDs($chosen)[0] ?? 0);"
def bones(tag):
 return "$options = FaBSEABonesOptions($player); $mode = await $player.Modal(1, 1, $options, \"Discard_or_destroy_top_card\"); $choice = explode('&', $options)[intval($mode)] ?? 'Decline'; $watery = false; if ($choice === 'Discard_card') { "+discard()+"$watery = FaBSEAWatery($discarded); } if ($choice === 'Destroy_top_card') { $milled = FaBSEAMill($player); $watery = FaBSEAWatery($milled); } if ($watery) { "+f"FaBTagUID($uid, '{tag}'); }}"
existing={}
for name in ['wtr','arc','cru','mon','ele','evr','upr','dyn','out','dtd','evo','hvy','mst','ros','arakni','hnt','amx']:
 for e in json.loads((HERE/(name+'_abilities.json')).read_text()):
  merged={a['macroName']:a for a in existing.get(e['cardId'],{}).get('abilities',[])}
  merged.update({a['macroName']:a for a in e['abilities']});existing[e['cardId']]={'cardId':e['cardId'],'abilities':list(merged.values())}
old={e['cardId']:e for e in json.loads((HERE/'sea_abilities.json').read_text())} if (HERE/'sea_abilities.json').exists() else {}
out=[];pending=[]
for c in json.loads((HERE/'sea_catalog.json').read_text()):
 id=c['id'];b=re.sub(r'_(red|yellow|blue)$','',id);txt=c['functional_text_plain'];types=c['types'];pitch=int(c['pitch'] or 0);v=4-pitch;a=[];handled=False
 def add(m,code):
  global handled
  handled=True;prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:out.append(existing[id]);continue
 if txt in ['', 'Go again'] or b in ['fools_gold','sea_legs','fiddlers_green','sirens_of_safe_harbor','goldfin_harpoon','battalion_barque','swiftwater_sloop','gold_hunter_ketch','gold_hunter_longboat','gold_hunter_marauder','claw_of_vynserakai','treasure_island']:handled=True
 if 'Watery Grave' in txt:
  add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'seaWatery') { FaBSEAFaceDown($mzID); return; }")
  if 'Ally' in types:handled=True
 if b in ['gravy_bones','gravy_bones_shipwrecked_looter','marlynn','marlynn_treasure_hunter','puffin','puffin_hightail','scurv_stowaway']:
  add('PrepareCard',UID+choose('FaBHVYGold($player)','Destroy_Gold_as_cost',False)+"FaBHVYDestroyGold($player, $chosen); FaBFinishPreparedCard($uid);")
  if b.startswith('gravy_bones'):add('ResolveAbility',"DoDrawCard($player, 1);"+discard())
  elif b.startswith('marlynn'):add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'seaMarlynn') { "+load()+" } else { FaBSEACreateCard($player, 'goldfin_harpoon_yellow'); }")
  else:add('ResolveAbility',"if (!FaBSEAHeroCreationBlocked($player)) { "+token('golden_cog' if b.startswith('puffin') else 'goldkiss_rum')+' }')
 if b in ['angry_bones','burly_bones','jittery_bones','restless_bones','blood_in_the_water','washed_up_wave']:
  add('Defended' if b in ['blood_in_the_water','washed_up_wave'] else 'AttackDeclared',UID+bones({'angry_bones':'WTR_POWER:1','burly_bones':'OVERPOWER','blood_in_the_water':'WTR_DEFENSE:2','washed_up_wave':'WTR_DEFENSE:2'}.get(b,'GO_AGAIN')))
 if b in ['board_the_ship','paddle_faster','hoist_em_up']:
  add('Defended' if b=='hoist_em_up' else 'AttackDeclared',UID+tap('readyAlly','Tap_ally',True)+"if ($tapped) { FaBTagUID($uid, '"+{'board_the_ship':'OVERPOWER','paddle_faster':'GO_AGAIN','hoist_em_up':'WTR_DEFENSE:1'}[b]+"'); }")
 if b in ['gold_hunter_lightsail']:add('AttackDeclared',UID+"if (FaBSEALessGold($player)) { FaBTagUID($uid, 'GO_AGAIN'); }")
 if b in ['swindlers_grift','golden_tipple']:add('AttackDeclared',discard(True,'yellow')+"if ($discarded !== '') { DoDrawCard($player, 1); "+token('gold')+' }')
 if b.startswith('expedition_to_') or b=='chart_a_course':
  add('ResolveCard' if b=='chart_a_course' else 'AttackDeclared', (next_attack('first',v) if b=='chart_a_course' else '')+"$mode = await $player.Modal(1, 1, \"Decline&Add_gold_counter\", \"Treasure_Island\"); if ($mode === '1') { FaBSEAIslandGold(1); }")
 if b in ['thievn_varmints','lost_in_transit']:
  add('Defended' if b=='lost_in_transit' else 'AttackDeclared',"$mode = await $player.Modal(1, 1, \"Decline&Remove_gold_counter\", \"Treasure_Island\"); if ($mode === '1') { $removed = FaBSEAIslandGold(-1); if ($removed > 0 && FaBHasType(GetHero($player)[0], 'Thief')) { "+token('gold')+' } }')
 if b in ['sea_floor_salvage','sunken_treasure','pilfer_the_wreck']:
  code=choose("FaBSEARefs("+('$victim' if b=='pilfer_the_wreck' else '$player')+", 'any', 'Graveyard', "+('false' if b=='pilfer_the_wreck' else 'true')+")",'Turn_graveyard_card_face_down',b!='sea_floor_salvage')+"FaBSEASalvage($player, $chosen);"
  add('Hit' if b=='pilfer_the_wreck' else ('Defended' if b=='sunken_treasure' else 'ResolveCard'),hit(code) if b=='pilfer_the_wreck' else code)
 if b=='divvy_up':add('ResolveCard',"FaBSEADivvy($player);")
 if b=='scrub_the_deck':add('ResolveCard',choose("FaBDYNHeroTargets($player, false)",'Choose_hero',False)+"$victim = FaBIdentityFromMZ($chosen)['player'] ?? 0; $milled = FaBSEAMill($victim); if (intval(CardPitch($milled)) === 2) { "+token('gold')+' }')
 if b in ['murderous_rabble','saltwater_swell']:
  code="$top = FaBSEATop($player); FaBRevealChoices($player, $top); $pitch = intval(CardPitch(FaBIdentityFromMZ($top)['object']->CardID ?? ''));"
  code+="if ($pitch === 3) { FaBSEAPitchTop($player); }" if b=='saltwater_swell' else "FaBTagUID($uid, 'WTR_POWER:' . $pitch);"
  add('AttackDeclared',UID+code)
 if b.startswith('mutiny_on_'):
  add('ResolveCard',"$stolen = FaBSEAMutiny($player); if ($stolen) { "+next_attack('any',2 if b.endswith('battalion_barque') else 0,'OVERPOWER' if b.endswith('nimbus_sovereign') else ('GO_AGAIN' if b.endswith('swiftwater') else ''))+' }')
 if b=='portside_exchange':add('ResolveCard',discard()+"DoDrawCard($player, 1); if (intval(CardPitch($discarded)) === 2) { "+token('gold')+' }')
 if b=='give_no_quarter':add('ResolveCard',"FaBSEAAdd($player, 'QUARTER', 2);")
 if b in ['avast_ye','heave_ho','yo_ho_ho']:add('ResolveCard',next_attack('pirateAlly',1 if b=='yo_ho_ho' else 0,'SEA_GOLD_HIT')+next_attack('pirateAlly',0,'GO_AGAIN' if b=='avast_ye' else ('OVERPOWER' if b=='heave_ho' else '')))
 if b in ['tighten_the_screws','perk_up','draw_back_the_hammer']:
  code=next_attack('mech',v+1)+untap('cog' if b=='tighten_the_screws' else ('any' if b=='perk_up' else 'gun'),'Hero' if b=='perk_up' else ('Weapons' if b=='draw_back_the_hammer' else 'Arena'))
  add('ResolveCard',code)
 if b=='goldwing_turbine':add('ResolveCard',next_attack('mech',v)+token('golden_cog'))
 if b in ['call_in_the_big_guns','fire_in_the_hole','monkey_powder','drop_the_anchor','gold_the_tip']:
  code=next_attack('arrow',1 if b=='monkey_powder' else (3 if b=='gold_the_tip' else v), 'OVERPOWER' if b=='monkey_powder' else ('SEA_ANCHOR' if b=='drop_the_anchor' else ''))
  if b=='call_in_the_big_guns':code+=load()
  if b=='fire_in_the_hole':code+=untap('bow','Weapons')
  if b=='monkey_powder':code+='DoDrawCard($player, 1);'
  if b=='gold_the_tip':code+="if (FaBSEAYellowArsenal($player)) { "+token('gold')+' }'
  add('ResolveCard',code)
 if b=='big_game_trophy_shot':add('ResolveCard',next_attack('arrow',4,'SEA_HARPOON_GOLD')+'DoDrawCard($player, 1);'+discard())
 if b in ['hook','line','sinker']:
  add('ResolveCard',"$peek = FaBSEADeckPeek($player); if (FaBELEArsenalSpace($player)) { "+choose("FaBSEARefs($player, 'arrow', 'Temp')",'Put_arrow_in_arsenal')+f"FaBSEAArsenal($player, $chosen, {1 if b=='hook' else 0}, '"+('GO_AGAIN' if b=='line' else ('OVERPOWER' if b=='sinker' else ''))+"'); } FaBSEARestorePeek($player, $peek);")
 if b in ['dry_powder_shot','entangling_shot','nettling_shot','scouting_shot','swift_shot']:handled=True
 if b in ['king_kraken_harpoon','king_shark_harpoon','red_fin_harpoon','yellow_fin_harpoon','blue_fin_harpoon']:
  add('Hit',hit("$cannon = FaBSEACount($player, 'CANNON') > 0; $chooser = $cannon ? $player : $victim; $fishRefs = FaBSEAFishChoices($player, $victim, $cannon); if ($fishRefs !== '') { $chosen = await $chooser.MZChoose($fishRefs, \"Go_Fish_choose_and_reveal\"); FaBSEAGoFish($player, $victim, $chosen, '"+b+"', $cannon); }"))
 if b in ['hms_barracuda','hms_kraken']:add('Hit',hit(destroy_target('ally' if b=='hms_barracuda' else 'item')))
 if b=='hms_marlin':add('Hit',hit('FaBSEAMill($victim);'))
 if b=='conqueror_of_the_high_seas':add('Hit',hit("$n = FaBSEADestroyArsenal($victim); FaBHVYToken($player, 'gold', $n);"))
 if b=='strike_gold':add('Hit',token('gold'))
 if b=='riches_of_tropal_dhani':add('CardPitched',token('gold'))
 if b in ['cloud_city_steamboat','cloud_skiff','cogwerx_dovetail','cogwerx_zeppelin','palantir_aeronought','sky_skimmer','jolly_bludger','cogwerx_blunderbuss','rust_belt']:
  add('PrepareCard',UID+tap()+"FaBFinishPreparedCard($uid);")
  if b not in ['cogwerx_blunderbuss','rust_belt']:
   code=UID
   if b in ['cloud_skiff','sky_skimmer','cogwerx_dovetail']:code+="$mode = await $player.Modal(1, 1, \"Gain_power&Gain_go_again\", \"Enhance_attack\"); FaBSEACogBuff($uid, $mode === '1');"
   else:code+="if (DecisionQueueController::GetVariable('rosEvent') !== 'seaJolly') { FaBSEACogBuff($uid); }"
   if b=='palantir_aeronought':code+="if (intval(FaBARCCard($uid, 'seaCogUses')) === 3) { "+choose("FaBSEADefenders()",'Destroy_defending_card',False)+"FaBMONDestroy(FaBUPRUIDs($chosen)[0] ?? 0); }"
   add('ResolveAbility',code)
  if b=='cogwerx_dovetail':add('Hit',hit("FaBSEAUntap(FaBSEARefs($player, 'cog'));"))
  if b=='cogwerx_zeppelin':add('Hit',hit(tap('readyCog','Tap_cog_to_create_Cog',True)+"if ($tapped) { "+token('golden_cog')+' }'))
  if b=='cloud_city_steamboat':add('Hit',hit(tap('readyCog','Tap_cog_for_steam',True)+"if ($tapped) { "+choose("FaBSEARefs($player, 'cog')",'Add_steam_counter',False)+"FaBSEAAddSteam($chosen); }"))
 if b in ['golden_cog','copper_cog']:add('StartTurn',UID+"FaBSEAUpkeepCog($uid);")
 if b=='lubricate':add('ResolveCard',many("FaBSEARefs($player, 'cog')",'3',0,'Untap_up_to_three_cogs')+'FaBSEAUntap($chosen);')
 if b=='cogwerx_workshop':add('ResolveCard',token('golden_cog')+many("FaBSEARefs($player, 'cog')",'2',0,'Add_steam_to_cogs')+'FaBSEAAddSteam($chosen);')
 if b=='cog_in_the_machine':add('ResolveCard',UID+token('golden_cog',n=2)+tap('readyCog','Tap_cog_to_bottom_this',True)+"if ($tapped) { FaBSEASetBottom($uid); }")
 if b in ['pinion_sentry','cogwerx_tinker_rings']:
  add('Defended',token('golden_cog') if b=='cogwerx_tinker_rings' else tap('readyCog','Tap_cog',True)+"if ($tapped) { "+token('golden_cog')+' }')
 if b in ['teeth_of_the_cog','tough_old_wrench','golden_skywarden']:
  code=UID+"$repeat = true; while ($repeat) { "+choose("FaBSEARefs($player, 'item')",'Galvanize_destroy_item')+"$item = FaBIdentityFromMZ($chosen); $golden = $item !== null && $item['object']->CardID === 'golden_cog'; $targetUID = intval($item['object']->UniqueID ?? 0); $repeat = false; if ($targetUID > 0) { FaBMONDestroy($targetUID); "
  code+=("FaBTagUID($uid, 'WTR_DEFENSE:1'); if ($golden) { "+token('gold')+' $repeat = true; }') if b=='golden_skywarden' else token('golden_cog')
  add('Defended',code+' } }')
 if b=='shifting_tides':add('StartTurn',UID+"$id = FaBSEAPitchTop($player); if (intval(CardPitch($id)) === 3) { FaBARCToDeck($player, $uid, false); } else { FaBMONDestroy($uid); }")
 if b=='compass_of_sunken_depths' or b in ['helmsmans_peak','on_the_horizon']:
  add('ResolveAbility' if b=='compass_of_sunken_depths' else 'Defended',"$peek = FaBSEADeckPeek($player); "+choose("$peek",'Look_at_top_card',False)+"FaBSEARestorePeek($player, $peek);")
 if b=='head_stone':add('ResolveAbility','FaBSEAMill($player);')
 if b in ['blue_sea_tricorn']:add('ResolveAbility','DoDrawCard($player, 1);')
 if b in ['buccaneers_bounty','captains_coat','old_knocker','rust_belt','dead_threads']:add('ResolveAbility','AddResources($player, intval(GetResources($player)) + 1);')
 if b in ['fish_fingers','peg_leg','quartermasters_boots','quick_clicks','swiftstrike_bracers','gold_baited_hook','goldkiss_rum']:
  add('ResolveAbility',next_attack('naa' if b=='quartermasters_boots' else ('pirate' if b=='gold_baited_hook' else ('gun' if b=='cogwerx_blunderbuss' else ('action' if b=='goldkiss_rum' else 'any'))),1 if b=='fish_fingers' else (2 if b=='swiftstrike_bracers' else 0),'SEA_STEAL_GOLD' if b=='gold_baited_hook' else ('' if b in ['fish_fingers','swiftstrike_bracers'] else 'GO_AGAIN')))
 if b in ['redspine_manta','glidewell_fins']:add('ResolveAbility',load(1 if b=='glidewell_fins' else 0))
 if b=='unicycle':add('ResolveAbility',untap())
 if b=='hammerhead_harpoon_cannon':add('ResolveAbility',next_attack('arrow',4,'SEA_HARPOON_OVERPOWER'))
 if b=='patch_the_hole':add('ResolveAbility',choose(refs('Arsenal'),'Return_arsenal_card',False)+"FaBMoveChoices($player, $chosen, 'Arsenal', 'Hand');")
 if b=='bandana_of_the_blue_beyond':
  add('PrepareCard',UID+discard()+"FaBFinishPreparedCard($uid);")
  add('ResolveAbility',choose("FaBSEARefs($player, 'blue', 'Graveyard')",'Bottom_blue_card',False)+"FaBROSReturn($chosen, 'Deck');")
 if b in ['anka_drag_under','chum_friendly_first_mate']:
  add('PrepareCard',UID+discard(False,'watery')+"FaBFinishPreparedCard($uid);")
  add('ResolveAbility',("if (DecisionQueueController::GetVariable('rosEvent') === 'seaDiscard') { "+discard()+" } else { FaBSEAAdd($player, 'ANKA'); }") if b=='anka_drag_under' else UID+"FaBSEAAdd($player, 'CHUM', 1, ['uid'=>$uid]);")
 if b=='chowder_hearty_cook':add('ResolveAbility','FaBCRUGainLife($player, 1);')
 if b=='cutty_shark_quick_clip':add('ResolveAbility',next_attack('ally',1))
 if b=='moray_le_fay':add('ResolveAbility',choose("FaBSEARefs($player, 'ally', 'Arena', true)",'Give_power_counter',False)+"FaBSEAPowerCounter($chosen);")
 if b=='shelly_hardened_traveler':add('ResolveAbility',"FaBSEAAdd($player, 'SHELLY');")
 if b=='sawbones_dock_hand':add('ResolveAbility',"FaBSEAAdd($player, 'SAWBONES');")
 if b=='kelpie_tangled_mess':add('ResolveAbility',choose("FaBSEAPermanents($player, 'heroAlly')",'Tap_hero_or_ally',False)+'FaBSEATap($chosen);')
 if b=='oysten_heart_of_gold':handled=True
 if b=='scooba_salty_sea_dog':add('AttackDeclared',choose("FaBSEARefs($player, 'yellow', 'Graveyard', true)",'Bottom_yellow_card')+"if ($chosen !== '-') { FaBROSReturn($chosen, 'Deck'); "+token('gold')+' }')
 if b in ['rally_the_coast_guard']:
  add('PrepareCard',UID+discard()+"FaBFinishPreparedCard($uid);")
  add('ResolveAbility',UID+"FaBTagUID($uid, 'WTR_DEFENSE:3');")
 if b in ['amethyst_amulet','diamond_amulet','ruby_amulet','pounamu_amulet','sapphire_amulet']:
  add('ResolveAbility',{'amethyst_amulet':next_attack('any',2),'diamond_amulet':'AddActionPoints($player, intval(GetActionPoints($player)) + 1);','ruby_amulet':'AddResources($player, intval(GetResources($player)) + 2);','pounamu_amulet':'FaBCRUGainLife($player, 2);','sapphire_amulet':"FaBSEAAdd($player, 'INTELLECT', 1);"}[b])
 if b=='onyx_amulet':add('ResolveAbility',"FaBSEATapAll($player);")
 if b=='pearl_amulet':add('ResolveAbility',choose("FaBSEAPermanents($player)",'Untap_permanent',False)+'FaBSEAUntap($chosen);')
 if b=='platinum_amulet':add('ResolveAbility',choose('FaBSEADefenders()','Boost_defending_card',False)+"FaBDYNTag($chosen, 'WTR_DEFENSE:1');")
 if b=='opal_amulet':add('ResolveAbility',opt(2))
 if b=='cogwerx_blunderbuss':add('ResolveAbility',UID+"FaBSEANext($player, 'gun', 0, 'GO_AGAIN', $uid);")
 if b=='not_so_fast':add('ResolveCard',"FaBSEAAdd($player, 'NOT_SO_FAST');")
 if b=='regain_composure':add('ResolveCard',next_attack('any',1,'SEA_UNTAP_HERO'))
 if b=='flying_high':add('ResolveCard',next_attack('any',0,'GO_AGAIN')+next_attack('any',0,'SEA_RED_POWER'))
 if b=='undercover_acquisition':add('Hit',hit(choose("FaBSEARefs($victim, 'item')",'Steal_item',False)+"FaBSEASteal($player, $chosen);"))
 if b=='midas_touch':add('ResolveCard',choose("FaBSEARefs($player, 'ally', 'Arena', true)",'Destroy_ally',False)+"FaBSEAMidas($chosen);")
 if b=='walk_the_plank':add('Hit',hit("if (FaBHasType(GetHero($victim)[0], 'Pirate')) { "+choose("FaBSEAPermanents($victim, 'heroAlly', false)",'Tap_hero_or_ally',False)+'FaBSEATap($chosen); }'))
 if b=='light_fingers':add('Defended',"if (FaBHasType(GetHero($player)[0], 'Thief')) { FaBSEAStealGold($player, intval(FaBGetState()['attacker'])); }")
 if b=='loan_shark':add('ResolveCard',token('gold',n=2));add('ResolveAbility',UID+"FaBMONDestroy($uid); "+discard(True)+"if ($discarded === '') { FaBARCLoseLife($player, 2, $player); }")
 if b=='riddle_with_regret':add('ResolveAbility',UID+"$n = intval(DecisionQueueController::GetVariable('rosTarget')); FaBARCLoseLife(intval(GetTurnPlayer()), $n, $player); if ($n >= 3) { FaBMONDestroy($uid); }")
 if b=='escalate_bloodshed':handled=True
 if b=='clap_em_in_irons':add('ResolveCard',UID+choose("FaBSEAPermanents($player, 'pirate')",'Tap_Pirate_hero_or_ally',False)+"FaBSEAIrons($uid, $chosen);");add('StartTurn',UID+'FaBMONDestroy($uid);')
 if b in ['entangling_shot','nettling_shot','scouting_shot']:
  body=(choose("FaBDYNHeroTargets($player, false)" if b=='entangling_shot' else "FaBSEARefs($player, 'ally', 'Arena', true)",'Tap_target')+'FaBSEATap($chosen);') if b!='scouting_shot' else "$peek = FaBSEADeckPeek($player); "+choose('$peek','Look_at_top_card')+'FaBSEARestorePeek($player, $peek);'
  add('ResolveAbility',body)
 if b=='chart_the_high_seas':add('ResolveCard',"$peek = FaBSEADeckPeek($player, 2); "+choose("FaBSEARefs($player, 'blue', 'Temp')",'Pitch_blue_card')+"FaBSEAPitchChoice($player, $chosen); FaBSEAChartRest($player, $peek);")
 if b=='arcane_compliance':
  add('PrepareCard',UID+choose("FaBSEAStackActions($uid)",'Choose_action_on_stack',False)+"FaBARCSetCard($uid, 'seaTarget', FaBUPRUIDs($chosen)[0] ?? 0); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"FaBSEACompliance(intval(FaBARCCard($uid, 'seaTarget')));")
 if b in ['consign_to_cosmos_shock','everbloom_life']:
  prep=UID+'$options = FaBROSMeldOptions($player, $uid); $mode = await $player.Modal(1, 1, $options, "Choose_half_or_meld"); FaBROSMeldChoose($player, $uid, $mode);'
  if b.endswith('_shock'):prep+="if (FaBARCCard($uid, 'rosMeld') !== 0) { "+choose('FaBUPRAnyTargets($player)','Choose_Shock_target',False)+"FaBARCSetCard($uid, 'rosShock', FaBUPRUIDs($chosen)[0] ?? 0); }"
  add('PrepareCard',prep+'FaBFinishPreparedCard($uid);')
  right="FaBCRUGainLife($player, 1);" if b.endswith('_life') else "$targetUID = intval(FaBARCCard($uid, 'rosShock')); "+deal(1)
  left=(choose("FaBSEAMeldGrave($player, 'life')",'Bottom_action_card',False)+"FaBROSReturn($chosen, 'Deck');") if b.endswith('_life') else many("FaBSEAMeldGrave($player, 'cosmos')","FaBSEACosmosCount($player)","FaBSEACosmosCount($player)",'Banish_instants_and_auras')+"FaBSEABanishChoices($chosen);"
  add('ResolveCard',UID+"if (FaBARCCard($uid, 'rosSide') === 1) { "+right+"FaBROSMeldResume($uid); } else { "+left+' }')
 if b=='crash_down_the_gates':
  add('AttackDeclared',hit(UID+"$top = FaBSEATop($victim); FaBRevealChoices($victim, $top); if (FaBSEATopStronger($top, $uid)) { FaBTagUID($uid, 'WTR_POWER:2'); }"))
  add('Hit',hit('FaBSEAMill($victim);'))
 if b=='bam_bam':
  add('Hit',hit(destroy_target()))
  add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'seaBam') { $victim = intval(DecisionQueueController::GetVariable('rosTarget')); "+destroy_target()+" } else { FaBSEAAdd($player, 'BAM'); }")
 if b=='burn_bare':
  add('PrepareCard',UID+choose('FaBUPRAnyTargets($player)','Choose_arcane_target',False)+"FaBUPRStoreTarget($uid, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"$targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); "+deal(6))
  add('ResolveAbility','FaBSEADestroyPhantasm($player);')
 if b=='deny_redemption':
  add('AttackDeclared',UID+V+"if (intval(GetHealth($victim)) > intval(GetHealth($player)) && FaBFaiHeroHit()) { FaBSEAUnpreventableArcane($player, $uid, $victim); }")
  add('ResolveAbility',"FaBSEAAdd($player, 'NO_HEAL');")
 if b=='herald_of_sekem':add('AttackDeclared',UID+choose("FaBSEARefs($player, 'yellow', 'Hand')",'Put_yellow_card_into_soul')+"if ($chosen !== '-') { FaBMoveChoices($player, $chosen, 'Hand', 'Soul'); "+choose('FaBUPRAnyTargets($player)','Choose_arcane_target',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; "+deal(2)+' }')
 if b=='blow_for_a_blow':
  add('Hit',UID+choose('FaBUPRAnyTargets($player)','Deal_one_damage',False)+"FaBFaiRedHotDamage($player, $chosen, 1);")
 if b in ['jack_be_nimble','jack_be_quick']:
  add('AttackDeclared',UID+choose("FaBSEARefs($player, 'nimblism', 'Graveyard')",'Banish_Nimblism')+"if ($chosen !== '-') { FaBSEABanishChoices($chosen); FaBTagUID($uid, 'WTR_POWER:1'); FaBTagUID($uid, 'GO_AGAIN'); }")
  add('Hit',hit(choose("FaBSEARefs($victim, '"+('item' if b=='jack_be_nimble' else 'ally')+"')",'Steal_until_end_of_turn',False)+('FaBSEAUntap($chosen);' if b=='jack_be_quick' else '')+"FaBSEASteal($player, $chosen, true);"))
 if b=='money_or_your_life':add('Hit',hit(UID+"$repeats = FaBHasType(GetHero($player)[0], 'Thief') ? 2 : 1; for ($i = 0; $i < $repeats; $i = $i + 1) { $gold = FaBHVYGold($victim); $chosen = '-'; if ($gold !== '') { $chosen = await $victim.MZMultiChoose($gold, 0, 1, \"Give_Gold_or_take_two_damage\"); } if ($chosen === '-') { DoDamage($player, $mzID, $victim, 2, 'PHYSICAL'); } else { FaBSEASteal($player, $chosen); } }"))
 if b=='nimby':add('AttackDeclared',choose("FaBStageSearch($player, ['base'=>'nimblism'])",'Search_for_Nimblism')+'FaBMSTSearchHand($player, $chosen);')
 if b=='polly_cranka':add('ResolveAbility',UID+"FaBSEAPolly($uid);")
 if b=='sticky_fingers':add('AttackDeclared',V+"if (FaBFaiHeroHit()) { FaBSEAStealGold($player, $victim); }")
 if b=='spitfire':
  add('PrepareCard',UID+tap()+"FaBFinishPreparedCard($uid);")
  add('AttackDeclared',UID+tap('readyCog','Tap_cog_for_one_power',True)+"if ($tapped) { FaBTagUID($uid, 'WTR_POWER:1'); }")
 if b=='preach_modesty':add('ResolveCard',UID+"FaBSEAEnterCounter($uid, 'BALANCE', 1);")
 if b=='return_fire':add('Defended',choose("FaBSEARefs($player, 'arrow', 'Hand')",'Banish_arrow_for_next_turn')+'FaBSEAReturnFire($player, $chosen);')
 if b=='sealace_sarong':
  add('PrepareCard',UID+choose('FaBSEASarongRefs($player)','Turn_blue_arrow_face_up',False)+"FaBARCSetCard($uid, 'seaSarong', FaBUPRUIDs($chosen)[0] ?? 0); FaBSEASarong($player, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"$abilityUID = intval(DecisionQueueController::GetVariable('arcAbilityUID')); FaBTagUID(intval(FaBARCCard($abilityUID, 'seaSarong')), 'GO_AGAIN');")
 if b=='surface_shaking':
  add('ResolveCard',token('seismic_surge',n=3))
  add('ResolveAbility',UID+"FaBMONDestroy($uid); $maximum = intval(DecisionQueueController::GetVariable('rosTarget')); "+many(refs('Hand'),'$maximum',0,'Bottom_hand_cards')+"$n = count(FaBUPRUIDs($chosen)); FaBUPRBottom($chosen); DoDrawCard($player, $n);")
 if b=='throw_caution_to_the_wind':add('ResolveCard',"$top = FaBSEATop($player); FaBRevealChoices($player, $top); FaBSEAAdd($player, 'CAUTION', intval(CardPitch(FaBIdentityFromMZ($top)['object']->CardID ?? '')));")
 if b=='tip_the_barkeep':add('ResolveCard',UID+token('goldkiss_rum')+choose('FaBHVYGold($player)','Give_a_Gold_token')+"if ($chosen !== '-') { $goldUID = FaBUPRUIDs($chosen)[0] ?? 0; "+choose('FaBDYNHeroTargets($player, true)','Choose_other_hero',False)+"if (FaBSEAGiveGold($goldUID, $chosen)) { FaBSEASetBottom($uid); } }")
 if b=='tit_for_tat':add('ResolveCard',choose('FaBDYNHeroTargets($player, false)','Tap_hero',False)+"$firstUID = FaBUPRUIDs($chosen)[0] ?? 0; FaBSEATap($chosen); "+choose('FaBSEAHeroExcept($player, $firstUID)','Untap_another_hero',False)+'FaBSEAUntap($chosen);')
 if b=='barbed_barrage':add('PrepareCard',UID+'$options = FaBSEABarrageOptions($player, $uid); $mode = await $player.Modal(1, 1, $options, "Pay_three_for_additional_target"); if ($mode === "1") { '+choose('FaBSEABarrageTargets($player, $uid)','Choose_additional_target',False)+'FaBSEABarrage($uid, $chosen); } FaBFinishPreparedCard($uid);')
 if b=='jolly_bludger':
  add('AttackDeclared',UID+tap('readyCog','Tap_cog_for_overpower',True)+"if ($tapped) { FaBTagUID($uid, 'OVERPOWER'); }")
  add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'seaJolly') { $victim = intval(DecisionQueueController::GetVariable('rosTarget')); $n = intval(FaBARCCard(intval(DecisionQueueController::GetVariable('rosSource')), 'seaDamage')); "+many("FaBSEARefs($victim, 'item')",'$n',"min($n, count(FaBUPRUIDs(FaBSEARefs($victim, 'item'))))",'Steal_items')+"FaBSEAStealChoices($player, $chosen); }")
 if b=='escalate_bloodshed':add('ResolveAbility',"DoDrawCard(intval(DecisionQueueController::GetVariable('rosTarget')), 1);")
 if not handled:pending.append(id)
 for ability in a:
  # The decision may belong to a different seat (Go Fish, damage prevention).
  # Await resumes with that seat as $player; retain the effect's controller.
  ability['abilityCode']='$seaPlayer = $player;\n'+re.sub(r'\$player\b', '$seaPlayer', ability['abilityCode'])
  prior=next((x for x in old.get(id,{}).get('abilities',[]) if x['macroName']==ability['macroName']),None)
  if prior and prior['abilityCode']!=ability['abilityCode']:ability['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
 out.append({'cardId':id,'abilities':a})
if pending:raise SystemExit('Unhandled SEA cards: '+', '.join(pending))
(HERE/'sea_abilities.json').write_text(json.dumps(out,indent=2)+'\n')
print(f'SEA: {len(out)} identities; {sum(len(e["abilities"]) for e in out)} authored macros')
