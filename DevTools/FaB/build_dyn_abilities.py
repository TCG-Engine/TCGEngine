"""Dynasty CardEditor bodies. Every functional identity must be accounted for."""
import ast,json,hashlib
from pathlib import Path
HERE=Path(__file__).parent
for filename in ['build_arc_abilities.py','build_mon_abilities.py','build_ele_abilities.py','build_upr_abilities.py']:
 src=(HERE/filename).read_text(encoding='utf-8')
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and not (filename=='build_mon_abilities.py' and node.name=='damage_body'):exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
def pick(expr,tip='Choose_card',optional=True,var='$chosen',who='$player'):
 return f"$refs = {expr}; {var} = 'PASS'; if ($refs !== '') {{ {var} = await {who}."+('MZMayChoose' if optional else 'MZChoose')+f'($refs, "{tip}"); }}'
def multi(expr,maximum,tip='Choose_cards',minimum=0):
 return f"$refs = {expr}; $maximum = min({maximum}, count(array_filter(explode('&', $refs)))); $chosen = '-'; if ($maximum > 0) {{ $chosen = await $player.MZMultiChoose($refs, {minimum}, $maximum, \"{tip}\"); }}"
def next_(kind,power=0,tags=''):
 return f"FaBDYNNext($player, '{kind}', {power}, '{tags}');"
def top(owner='$player'):
 return f"$topUIDs = FaBARCStageTop({owner}, 1); if (count($topUIDs) > 0) {{ $orderParam = FaBARCOrderParam($topUIDs); $order = await $player.Rearrange($orderParam); FaBARCFinishOrder({owner}, $topUIDs, $order); }}"
def spell(n,surge=''):
 return UID+"$targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); "+deal(n)+(f"if ($dealt > {n}) {{ "+surge+" }" if surge else '')
existing={}
for path in sorted(HERE.glob('*_abilities.json')):
 if not path.name.startswith('dyn'):
  for c in json.loads(path.read_text(encoding='utf-8')):existing[c['cardId']]=c
cards=json.loads((HERE/'dyn_catalog.json').read_text(encoding='utf-8'));snapshot=[];pending=[]
for c in cards:
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);a=[];handled=False
 def add(m,code):
  global handled
  handled=True;a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:snapshot.append(existing[id]);continue
 if b in ['aether_quickening','prognosticate','sap','mind_warp','swell_tidings']:
  add('PrepareCard',UID+pick('FaBDYNHeroTargets($player)','Choose_damage_target',False)+"FaBUPRStoreTarget($uid, $chosen); FaBFinishPreparedCard($uid);")
  n={'aether_quickening':v+1,'prognosticate':v,'sap':v,'mind_warp':2,'swell_tidings':5}[b]
  surge={'aether_quickening':"FaBDYNSurgeAgain($player, $uid);",'prognosticate':opt(1),'sap':"$victim = FaBFindUID($targetUID)['player']; "+pick("FaBDYNEnergy($victim)",'Remove_energy')+"FaBDYNCounter($chosen, 'ENERGY', -1);",'mind_warp':"FaBDYNMindWarp($targetUID);",'swell_tidings':"FaBWTRCreateArena($player, 'ponder');"}[b]
  add('ResolveCard',spell(n,surge))
 if b=='tempest_aurora':add('ResolveCard',f"FaBDYNAdd($player, 'TEMPEST_{v-1}', 1);")
 if b=='brainstorm':add('ResolveCard',"FaBDYNAdd($player, 'BRAINSTORM');")
 if b in ['brainstorm','surgent_aethertide','suraya_archangel_of_knowledge']:
  n=1;code=UID
  if b=='suraya_archangel_of_knowledge':code+=pick("FaBARCSelect($player, 'Soul', 'Light')",'Banish_Light_from_soul')+"$paid = FaBMoveChoice($player, $chosen, 'Soul', 'Banish'); if ($paid) {"
  code+=pick('FaBDYNAnyTargets($player)' if b!='surgent_aethertide' else 'FaBARCHeroTargets($player, true)','Choose_arcane_target',False)+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0); "+deal(n)
  if b=='suraya_archangel_of_knowledge':code+=' }'
  add('AttackDeclared' if b=='suraya_archangel_of_knowledge' else 'ResolveAbility',code)
 if b=='seerstone':add('ResolveAbility',top()+"FaBWTRCreateArena($player, 'ponder');")
 if b=='blessing_of_aether':add('StartTurn',UID+f"FaBMONDestroy($uid); FaBDYNAdd($player, 'BLESS_AETHER', {v});")
 if b=='blessing_of_focus':add('StartTurn',UID+"FaBMONDestroy($uid);"+opt(v)+"FaBDYNTopArrow($player, true);")
 if b=='blessing_of_ingenuity':add('StartTurn',UID+"FaBMONDestroy($uid);"+multi("FaBDYNDrivers($player)",v,'Return_Hyper_Drivers')+"FaBDYNMoveSelected($player, $chosen, 'Arena');")
 if b=='blessing_of_occult':add('StartTurn',UID+f"FaBMONDestroy($uid); FaBARCCreateRunes($player, {v});")
 if b=='blessing_of_patience':add('StartTurn',UID+"FaBMONDestroy($uid);"+pick('FaBDYNHeroTargets($player)','Choose_hero_to_heal',False)+f"FaBCRUGainLife(intval(FaBIdentityFromMZ($chosen)['player'] ?? $player), {v});")
 if b=='blessing_of_qi':add('StartTurn',"FaBIraBlessing($player, $mzID);")
 if b=='blessing_of_savagery':add('StartTurn',UID+"FaBMONDestroy($uid);"+next_('SIX',v))
 if b=='blessing_of_spirits':add('StartTurn',UID+f"FaBMONDestroy($uid); FaBEVRCreate($player, 'spectral_shield', {v});")
 if b=='blessing_of_steel':add('StartTurn',UID+"FaBMONDestroy($uid);"+next_('WEAPON',v))
 if b=='mindstate_of_tiger':add('StartTurn',UID+"FaBMONDestroy($uid); AddHand($player, CardID:'crouching_tiger');")
 if b=='tome_of_aeo':add('StartTurn',UID+"FaBMONDestroy($uid); DoDrawCard($player, 1);")
 if b=='never_yield':add('StartTurn',UID+"FaBMONDestroy($uid); FaBDYNNeverYield($player); if (FaBDYNFewestEquipment($player)) {"+pick("FaBDYNEquipment($player, 'DAMAGED')",'Repair_equipment')+"FaBDYNCounter($chosen, 'DEFENSE', 1); }")
 if b=='predatory_streak':add('ResolveCard',f'FaBCreateTigers($player, {v});')
 if b=='roar_of_the_tiger':add('ResolveCard',"AddHand($player, CardID:'crouching_tiger'); FaBDYNAdd($player, 'ROAR');")
 if b in ['pouncing_qi','qi_unleashed','tiger_swipe']:add('AttackDeclared',"FaBIraCombo($mzID, "+str({'pouncing_qi':1,'qi_unleashed':4,'tiger_swipe':2}[b])+", "+('false' if b=='qi_unleashed' else 'true')+");")
 if b in ['flex_claws','tiger_swipe']:add('Hit',"FaBCreateTigers($player, "+('1' if b=='flex_claws' else "FaBDYNTigerCount($player, $mzID)")+");")
 if b=='tearing_shuko':add('ResolveAbility',"FaBWTRAddEffect($player, 'IRA_NEXT_TIGER', 2);")
 if 'Contract' in c['functional_text_plain']:
  add('Hit',UID+"if (FaBFaiHeroHit()) { $victim = intval(FaBGetState()['defender']); FaBDYNBanishTop($player, $victim, "+('$amount' if b=='eradicate' else '1')+"); "+(pick("implode('&', FaBChoiceRefs($victim, 'Arsenal'))",'Banish_arsenal')+"FaBDYNBanishChoice($chosen, $player);" if b=='leave_no_witnesses' else '')+("$privateUIDs = FaBDYNPrivateHand($player, $victim); "+pick('FaBEVRUIDRefs($privateUIDs)','Banish_card_from_hand',False)+"FaBDYNBanishChoice($chosen, $player); FaBEVRForgetHand($privateUIDs);" if b=='surgical_extraction' else '')+" }")
 if b in ['arakni','arakni_huntsman']:add('ResolveAbility',pick('FaBARCHeroTargets($player, true)','Look_at_opponent_deck')+"if ($chosen !== 'PASS') { $victim = intval(FaBIdentityFromMZ($chosen)['player']); $uids = FaBDYNPeekTop($player, $victim); if (count($uids) > 0) { $orderParam = FaBARCOrderParam($uids); $order = await $player.Rearrange($orderParam); FaBDYNFinishPeekOrder($victim, $uids, $order); } }")
 if b=='cut_to_the_chase':add('ResolveCard',pick("FaBDYNAttacks($player, 'CONTRACT')",'Choose_contract_attack',False)+f"FaBDYNTag($chosen, 'WTR_POWER:{v}'); $victim = intval(FaBGetState()['defender']); $uids = FaBDYNPeekTop($player, $victim); if (count($uids) > 0) {{ $orderParam = FaBARCOrderParam($uids); $order = await $player.Rearrange($orderParam); FaBDYNFinishPeekOrder($victim, $uids, $order); }}")
 if b=='shred':add('ResolveCard',pick("FaBDYNDefenders($player, 'ASSASSIN')",'Choose_defending_card',False)+f"FaBDYNTag($chosen, 'DYN_DEFENSE:-{v+1}');")
 if b=='pay_day':add('ResolveCard',"if (FaBDYNCount($player, 'CONTRACTS')) { FaBEVRCreate($player, 'silver', 4); }")
 if b in ['blacktek_whisperers','mask_of_perdition']:
  add('ResolveAbility',pick("FaBDYNAttacks($player, 'ASSASSIN')",'Choose_Assassin_attack',False)+"FaBDYNTag($chosen, '"+('WTR_HIT_GO_AGAIN' if b=='blacktek_whisperers' else 'DYN_MASK')+"');")
  add('StartTurn',UID+multi("implode('&', FaBMONArena($player, 'silver'))",2,'Destroy_two_Silvers_to_equip')+"FaBDYNReequip($player, $uid, $chosen);")
 if b=='regicide':add('Hit',"if (FaBFaiHeroHit() && FaBDYNRoyal(intval(FaBGetState()['defender']))) { FaBEliminateSeat(intval(FaBGetState()['defender'])); }")
 if b in ['madcap_charger','madcap_muscle','savage_beatdown']:add('ResolveCard',UID+"if (FaBARCCard($uid, 'discardedPower') >= 6) { FaBTagUID($uid, '"+('GO_AGAIN' if b=='madcap_charger' else 'WTR_POWER:'+('6' if b=='savage_beatdown' else '3'))+"'); }")
 if b=='berserk':
  add('ResolveCard',"FaBDYNAdd($player, 'BERSERK');")
  add('ResolveAbility',"$discardUID = intval(DecisionQueueController::GetVariable('dynDiscardUID')); $reincarnate = intval(DecisionQueueController::GetVariable('dynReincarnate')); if ($reincarnate) { $mode = await $player.Modal(1, 1, \"Resolve_Berserk_first&Resolve_Reincarnate_first\", \"Order_discard_triggers\"); if ($mode === '1') { FaBARCToDeck($player, $discardUID, false); } } FaBDYNBerserk($player, $discardUID); if ($reincarnate) { FaBDYNReincarnate($player, $discardUID); }")
 if b=='beaten_trackers':add('ResolveAbility',UID+"$mode = await $player.Modal(1, 1, \"Keep_equipment&Destroy_for_action_point\", \"Beaten_Trackers\"); if ($mode === '1' && FaBDYNEquipmentStillPresent($uid)) { FaBMONDestroy($uid); AddActionPoints($player, intval(GetActionPoints($player)) + 1); }")
 if b=='rumble_grunting':add('ResolveCard',next_('BRUTE',v+1))
 if b in ['reincarnate','skull_crack']:handled=True # Random-discard replacement/trigger.
 if b in ['buckle','cleave','dead_eye','felling_swing','precision_press','runic_reaping']:
  kind={'buckle':'GUARDIAN','cleave':'AXE','dead_eye':'ARROW','felling_swing':'AXE','precision_press':'BLADE','runic_reaping':'RUNEAA'}[b]
  power={'buckle':1,'cleave':4,'dead_eye':3,'felling_swing':v+3,'precision_press':0,'runic_reaping':0}[b]
  tags={'buckle':'DOMINATE,DYN_BUCKLE','cleave':'DYN_CLEAVE','dead_eye':'DYN_DEAD_EYE','felling_swing':'','precision_press':f'GO_AGAIN,DYN_PIERCING:{v}','runic_reaping':f'DYN_REAP:{v}'}[b]
  add('ResolveCard',(UID+"$bonus = FaBDYNPitched($uid, 'AA') ? 1 : 0; " if b=='runic_reaping' else '')+next_(kind,'$bonus' if b=='runic_reaping' else power,tags))
 if b=='puncture':add('ResolveCard',pick("FaBDYNAttacks($player, 'BLADE')",'Choose_sword_or_dagger',False)+f"FaBDYNTag($chosen, 'WTR_POWER:{v}'); FaBDYNTag($chosen, 'DYN_PIERCING:1');")
 if b=='visit_the_imperial_forge':add('ResolveCard',f"FaBDYNAdd($player, 'FORGE', {v});")
 if b=='withstand':add('ResolveCard',pick("FaBDYNEquipment($player, 'OFFHAND')",'Choose_Guardian_offhand',False)+f"FaBDYNTag($chosen, 'DYN_WITHSTAND:{v+3}');")
 if b=='reinforce_steel':add('ResolveCard',pick(f"FaBDYNEquipment($player, 'REPAIR_{v}')",'Repair_Guardian_offhand',False)+"FaBDYNCounter($chosen, 'DEFENSE', 1);")
 if b=='shield_bash':add('ResolveCard',"if (FaBDYNShieldDefending($player)) { $victim = intval(FaBGetState()['attacker']); "+pick("implode('&', FaBChoiceRefs($victim, 'Hand'))",'Discard_or_take_damage',who='$victim')+"if ($chosen !== 'PASS') { FaBMoveChoice($victim, $chosen, 'Hand', 'Graveyard'); } else { DoDamage($player, $mzID, $victim, 1, 'PHYSICAL'); } }")
 if b=='point_the_tip':add('ResolveCard',pick("FaBDYNAimTargets($player)",'Choose_face_up_arrow',False)+f"FaBDYNTag($chosen, 'WTR_POWER:{v}'); FaBDYNCounter($chosen, 'AIM', 1);")
 if b=='drill_shot':add('Hit',"if (FaBFaiHeroHit()) { $victim = intval(FaBGetState()['defender']); "+pick("FaBDYNEquipment($victim)",'Choose_equipment',False)+"FaBDYNCounter($chosen, 'DEFENSE', -1); }")
 if b=='hemorrhage_bore':add('Hit',UID+"if (FaBFaiHeroHit() && FaBDYNAimed($uid)) { $victim = intval(FaBGetState()['defender']); "+pick("implode('&', FaBChoiceRefs($victim, 'Arsenal'))",'Destroy_arsenal',False)+"FaBDYNDestroyChoice($chosen); }")
 if b=='immobilizing_shot':add('Hit',UID+"if (FaBFaiHeroHit() && FaBDYNAimed($uid)) { FaBWTRAddEffect(intval(FaBGetState()['defender']), 'DYN_IMMOBILE', 1, [], true); }")
 if b=='heat_seeker':add('Hit',"FaBDYNAdd($player, 'HEAT_SEEKER');")
 if b=='sandscour_greatbow':add('ResolveAbility',"$uids = FaBDYNPeekTop($player, $player); $refs = FaBEVRUIDRefs($uids); if ($refs !== '') { $shown = await $player.MZMultiChoose($refs, 0, 0, \"Look_at_top_card\"); } "+pick("FaBDYNLoadTargets($player, $uids)",'Load_arrow')+"FaBARCLoadArsenal($player, $chosen, true); FaBDYNFinishPeek($player, $uids, false);")
 if b=='hornets_sting':add('Defended',UID+"$top = FaBChoiceRefs($player, 'Deck')[0] ?? ''; $arrow = FaBDYNRevealArrow($player, $top); if ($arrow) { $targetUID = FaBDYNAttackerUID(); "+deal(1,physical=True)+" } else { FaBUPRBottomHand($player, ''); FaBDYNBottomRef($top); }")
 if b=='aether_slash':add('AttackDeclared',UID+"if (FaBDYNPitched($uid, 'NAA')) { "+pick('FaBDYNAnyTargets($player)','Choose_arcane_target',False)+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0); "+deal(1)+" }")
 if b=='deathly_duet':add('AttackDeclared',UID+f"if (FaBDYNPitched($uid, 'AA')) {{ FaBTagUID($uid, 'WTR_POWER:2'); }} if (FaBDYNPitched($uid, 'NAA')) {{ FaBARCCreateRunes($player, 2); }}")
 if b=='cryptic_crossing':add('Hit',UID+"if (FaBFaiHeroHit() && FaBDYNPitched($uid, 'AA') && FaBDYNPitched($uid, 'NAA') && !FaBARCCard($uid, 'dynCrossed')) { FaBARCSetCard($uid, 'dynCrossed', true); $victim = intval(FaBGetState()['defender']); "+pick("implode('&', FaBChoiceRefs($victim, 'Hand'))",'Discard_card',False,who='$victim')+"FaBMoveChoice($victim, $chosen, 'Hand', 'Graveyard'); DoDrawCard($player, 1); }")
 if b=='diabolic_ultimatum':
  code=UID+"$caster = $player; $seats = FaBLiveSeats();"
  for kind,typ in [('AA','Ally'),('NAA','Aura')]:code+=f"if (FaBDYNPitched($uid, '{kind}')) {{ for ($i = 0; $i < count($seats); ++$i) {{ $victim = $seats[$i]; "+pick(f"implode('&', FaBChoiceRefs($victim, 'Arena', ['type'=>'{typ}']))",'Choose_permanent_to_destroy',False,who='$victim')+"FaBDYNDestroyChoice($chosen); } }"
  add('ResolveCard',code+"$player = $caster;")
 if b=='annals_of_sutcliffe':add('ResolveAbility',"DoDrawCard($player, 1); if (DecisionQueueController::GetVariable('dynPitchAA') && DecisionQueueController::GetVariable('dynPitchNAA')) { FaBARCCreateRunes($player, 1); }")
 if b=='amethyst_tiara':add('ResolveAbility',"FaBDYNAdd($player, 'TIARA');")
 if b in ['sky_fire_lanterns','water_glow_lanterns']:add('ResolveCard',f"FaBDYNLantern($player, '{'runechant' if b=='sky_fire_lanterns' else 'spectral_shield'}', {4-v});")
 if b=='looming_doom':
  add('ResolveCard',UID+"FaBDYNLoom($player, $uid);")
  add('ResolveAbility',UID+"if (FaBDYNTickDoom($uid)) { "+pick('FaBDYNAnyTargets($player)','Choose_arcane_target',False)+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0); "+deal(2)+" }")
 if b=='bios_update':add('ResolveCard',"FaBDYNAdd($player, 'BIOS_POWER'); FaBDYNAdd($player, 'BIOS_ITEM');")
 if b=='crankshaft':add('ResolveAbility',pick("implode('&', FaBChoiceRefs($player, 'Arena', ['base'=>'hyper_driver']))",'Add_steam_to_Hyper_Driver',False)+"FaBDYNDriverSteam($chosen);")
 if b=='urgent_delivery':add('Hit',"$maximum = FaBCRUCount($player, 'CHAIN_BOOST'); "+pick("FaBARCSelect($player, 'Hand', 'Mechanologist', 'Item', $maximum)",'Put_item_into_arena')+"FaBDYNMoveSelected($player, $chosen, 'Arena');")
 if b=='pulsewave_harpoon':add('AttackDeclared',"if (FaBFaiHeroHit()) { $victim = intval(FaBGetState()['defender']); $maximum = min(FaBCRUCount($player, 'CHAIN_BOOST'), FaBHandCount($victim)); if ($maximum > 0) { $refs = implode('&', FaBChoiceRefs($victim, 'Hand')); $shown = await $victim.MZMultiChoose($refs, $maximum, $maximum, \"Reveal_cards_for_Harpoon\"); FaBRevealChoices($victim, $shown); $refs = FaBDYNHarpoonTargets($shown, FaBCRUCount($player, 'CHAIN_BOOST')); if ($refs !== '') { $chosen = await $player.MZChoose($refs, \"Choose_defending_action\"); FaBDYNHarpoon($victim, $chosen); } } }")
 if b=='plasma_mainline':add('ResolveAbility',"$targetUID = intval(DecisionQueueController::GetVariable('dynItemUID')); "+UID+"$mode = await $player.Modal(1, 1, \"Keep_counter&Move_steam_counter\", \"Plasma_Mainline\"); if ($mode === '1') { FaBDYNMainline($uid, $targetUID); }")
 if b=='powder_keg':add('ResolveAbility',UID+pick('FaBDYNDefendingEquipment()','Destroy_defending_equipment')+"if ($chosen !== 'PASS') { FaBMONDestroy($uid); FaBDYNDestroyChoice($chosen); }")
 if b=='construct_nitro_mechanoid':add('ResolveCard',UID+multi("FaBDYNConstructTargets($player)",8,'Choose_five_equipment_and_three_Hyper_Drivers')+"FaBDYNConstruct($player, $uid, $chosen);")
 if b=='invoke_suraya':
  add('PrepareCard',UID+pick("implode('&', FaBMONArena($player, 'spectral_shield'))",'Choose_Spectral_Shield',False)+"FaBARCSetCard($uid, 'dynShield', intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0)); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"FaBDYNSuraya($player, $uid);")
 if b=='phantasmal_symbiosis':add('AttackDeclared',"$name = await $player.NameCard(\"\", \"Name_a_card\"); FaBDYNNameIllusionist($name);")
 if b=='tranquil_passing':add('ResolveCard',UID+pick(f"FaBDYNTranquilTargets($player, {v})",'Banish_opposing_aura')+"FaBDYNTranquil($uid, $chosen);")
 if b=='ironsong_pride':add('ResolveCard',pick("FaBEVRWeapons($player, 'Sword')",'Choose_sword',False)+"FaBDYNCounter($chosen, 'POWER', 1);")
 if b=='gold':add('ResolveAbility',"DoDrawCard($player, 1);")
 if b=='ornate_tessen':add('ResolveAbility',pick("implode('&', FaBChoiceRefs($player, 'Hand'))",'Bottom_card_then_draw',False)+"if ($chosen !== 'PASS') { FaBDYNBottomRef($chosen); DoDrawCard($player, 1); }")
 if b=='imperial_ledger':add('ResolveAbility',"FaBWTRCreateArena($player, FaBDYNRoyal($player) ? 'gold' : 'copper');")
 if b=='imperial_edict':add('ResolveAbility',"FaBDYNRevealRoyalHands($player); $name = await $player.NameCard(\"\", \"Name_a_card_to_prohibit\"); FaBDYNProhibit($player, $name);")
 if b=='imperial_warhorn':add('ResolveAbility',multi('FaBDYNHeroTargets($player, false, true)',4,'Choose_heroes')+"$caster = $player; $seats = FaBDYNSelectedSeats($chosen); $selectedUIDs = []; for ($i = 0; $i < count($seats); ++$i) { $victim = $seats[$i]; $chooser = FaBDYNRoyal($caster) ? $caster : $victim; "+pick("FaBDYNPermanents($victim)",'Choose_permanent_to_destroy',False,who='$chooser')+"$selectedUIDs = array_merge($selectedUIDs, FaBUPRUIDs($chosen)); } FaBDYNDestroyUIDs($selectedUIDs); $player = $caster;")
 if b=='yoji_royal_protector':add('ResolveAbility',pick('FaBDYNHeroTargets($player, true)','Choose_another_hero',False)+"FaBDYNProtect($player, $chosen);")
 if b=='emperor_dracai_of_aesir':add('ResolveAbility',"$targetSpec = DecisionQueueController::GetVariable('dynEmperorTarget'); $refs = FaBStageSearch($player, ['base'=>'command_and_conquer']); $chosen = 'PASS'; if ($refs !== '') { $chosen = await $player.MZMayChoose($refs, \"Choose_Command_and_Conquer\"); } $attackUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0); $target = is_array($targetSpec) ? FaBAttackTargetMZ($targetSpec) : ''; FaBDYNEmperor($player, $attackUID, $target); FaBFinishSearch($player);")
 if b=='nitro_mechanoid':add('PrepareCard',UID+"$options = FaBDYNMaterialOptions($uid); if ($options !== '') { $choice = await $player.Modal(1, 1, $options, \"Banish_material_to_attack\"); FaBDYNPayMaterial($uid, intval($choice)); } FaBFinishPreparedCard($uid);")
 if b=='buckle':add('ResolveAbility',"$mode = DecisionQueueController::GetVariable('dynHitMode'); $victim = intval(DecisionQueueController::GetVariable('dynVictim')); $uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID ?? 0); if ($mode === 'DYN_BUCKLE') { "+pick("FaBDYNEquipment($victim, 'DAMAGED')",'Destroy_damaged_equipment',False)+"FaBDYNDestroyChoice($chosen); } if ($mode === 'DYN_DEAD_EYE') { $uids = FaBDYNPrivateHand($player, $victim); "+pick('FaBEVRUIDRefs($uids)','Discard_card_from_hand',False)+"FaBDYNDiscardPrivate($victim, $chosen); FaBEVRForgetHand($uids); } if ($mode === 'DYN_CLEAVE') { "+pick("FaBDYNCleaveTargets($victim)",'Damage_another_ally')+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0); $packet = intval(DecisionQueueController::GetVariable('dynHitAmount')); "+deal('$packet',physical=True)+" }")
 if b in ['blazen_yoroi','celestial_kimono','crown_of_dominion','dust_from_the_golden_plains','dust_from_the_red_desert','dust_from_the_shadow_crypts','galvanic_bender','hanabi_blaster','hyper_driver','jubeel_spellbane','jump_start','long_shot','merciless_battleaxe','nitro_mechanoid','ponder','quicksilver_dagger','rok','scramble_pulse','seasoned_saviour','shield_wall','spectral_procession','spectral_prowler','spectral_rider','spell_fray_cloak','spell_fray_gloves','spell_fray_leggings','spell_fray_tiara','spellbane_aegis','spiders_bite','spirit_of_eirina','steelbraid_buckler','wave_of_reality']:handled=True
 if not handled:pending.append(id)
 snapshot.append(dict(cardId=id,abilities=a))
old={c['cardId']:c for c in json.loads((HERE/'dyn_abilities.json').read_text())} if (HERE/'dyn_abilities.json').exists() else {}
for c in snapshot:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'dyn_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n')
print('DYN identities:',len(snapshot),'Unhandled:',len(pending));print('\n'.join(pending))
if pending:raise SystemExit(1)
