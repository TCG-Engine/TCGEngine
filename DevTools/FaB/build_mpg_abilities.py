"""Reproducible Mastery Pack: Guardian source; unhandled identities fail."""
import ast,json,re,hashlib
from pathlib import Path
HERE=Path(__file__).parent
for file,names in [('build_mon_abilities.py',['clean']),('build_hvy_abilities.py',['choose','many','refs','token','clash'])]:
 src=(HERE/file).read_text()
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V="$victim = intval(FaBGetState()['defender']);"
def crush(code,guardian=False):return "if (intval($amount) >= 4 && FaBFaiHeroHit()) { "+V+("if (FaBHasType(GetHero($victim)[0], 'Guardian')) { " if guardian else '')+code+(' }' if guardian else '')+' }'
def destroy(expr):return choose(expr,'Destroy_aura',False)+"FaBMONDestroy(FaBUPRUIDs($chosen)[0] ?? 0);"
def discard(p):return choose(refs('Hand',p),'Discard_card',False,p)+f"FaBDiscardChoice({p}, $chosen);"
def clash_effect(kind,slot='',opponent="intval(FaBGetState()['attacker'])"):
 code=clash(opponent=opponent)+"if ($winner > 0) { $loser = $winner === $player ? $other : $player; "
 if kind=='surge':code+=token('seismic_surge',p='$winner')
 if kind=='mill':code+="FaBMPGMill($loser);"
 if kind=='discard':code+=discard('$loser')
 if kind=='aura':code+=choose(refs('Arena','$loser',"['type'=>'Aura']"),'Destroy_aura',False,'$winner')+"FaBMONDestroy(FaBUPRUIDs($chosen)[0] ?? 0);"
 if kind=='slot':code+=choose(f"FaBMPGEquipment($loser, '{slot}')",'Put_defense_counter',False,'$loser')+"if ($chosen !== '-') { FaBMPGCounter($chosen); } else { FaBMPGLoseLife($loser, 1); }"
 return code+' }'
existing={}
for name in ['wtr','arc','cru','mon','ele','evr','upr','dyn','out','dtd','evo','hvy','mst','ros','arakni','hnt','amx','sea']:
 for e in json.loads((HERE/(name+'_abilities.json')).read_text()):
  merged={a['macroName']:a for a in existing.get(e['cardId'],{}).get('abilities',[])};merged.update({a['macroName']:a for a in e['abilities']});existing[e['cardId']]={'cardId':e['cardId'],'abilities':list(merged.values())}
old={e['cardId']:e for e in json.loads((HERE/'mpg_abilities.json').read_text())} if (HERE/'mpg_abilities.json').exists() else {}
out=[];pending=[]
for c in json.loads((HERE/'mpg_catalog.json').read_text()):
 id=c['id'];b=re.sub(r'_(red|yellow|blue)$','',id);v=4-int(c['pitch'] or 0);txt=c['functional_text_plain'];a=[];handled=False
 def add(m,code):
  global handled
  handled=True;a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:out.append(existing[id]);continue
 if b in ['valda_seismic_impact','captain_of_the_guard','hoarding_of_denial','draw_a_crowd','geyser_of_seismic_stirrings','ley_line_of_the_old_ones','little_big_foot','promising_terrain','seismic_shelter','solid_ground','testament_of_valahai','tremor_of_resistance']:handled=True
 if b=='aftershock':add('AttackDeclared',"if (FaBMPGSurges($player) > 0 || FaBARCEffect($player, 'MPG_CONTROLLED_SURGE') > 0) { "+token('seismic_surge')+' }')
 if b=='annexation_of_all_things_known':add('Hit',crush("FaBMPGDuration($player, 'ARSENAL', 'until', ['victim'=>$victim]);",True))
 if b=='annexation_of_grandeur':add('Hit',crush(choose(refs('Arena','$victim',"['type'=>'Aura']"),'Gain_control_of_aura',False)+"FaBMPGStealAura($player, $chosen);",True))
 if b=='annexation_of_the_forge':add('Hit',crush(choose("FaBMPGEquipChoices($player, $victim)",'Equip_their_equipment',False)+"FaBMPGEquip($player, $chosen);",True))
 if b=='base_of_the_mountain':add('Defended',many(refs('Hand',filters="['type'=>'Action']"),99)+"FaBMPGBaseDefend($player, $chosen);")
 if b=='blinding_of_the_old_ones':add('Hit',crush("FaBMPGDuration($victim, 'BLIND');",True))
 if b=='break_stature':add('Hit',crush(choose("FaBMPGTokenAuras($victim)",'Destroy_aura_token',False)+"$name = FaBIdentityFromMZ($chosen)['object']->CardID ?? ''; if ($name !== '') { FaBMONDestroy(FaBUPRUIDs($chosen)[0]); FaBMPGDuration($victim, 'NO_NAMED_AURA', 'until', ['card'=>$name]); }"))
 if b=='call_for_backup':add('Defended',choose("FaBMPGBackup($player)",'Choose_first_attack',False)+"$first = FaBUPRUIDs($chosen)[0] ?? 0; "+choose("FaBMPGBackup($player, $first)",'Choose_different_attack',False)+"$second = FaBUPRUIDs($chosen)[0] ?? 0; if ($first > 0) { "+choose("FaBDYNHeroTargets($player, true)",'Choose_opponent',False)+"$other = intval(FaBIdentityFromMZ($chosen)['player']); $pair = FaBEVRUIDRefs([$first, $second]); "+choose('$pair','Choose_attack_to_banish',False,'$other')+"$banished = FaBUPRUIDs($chosen)[0] ?? 0; FaBMoveUID($banished, 'Banish', $player); $top = $banished === $first ? $second : $first; if ($top > 0) { FaBARCToDeck($player, $top, true); } }")
 if b.startswith('clash_of_'):
  slot={'arms':'Arms','chests':'Chest','heads':'Head','legs':'Legs','shields':'Off-Hand'}.get(b[9:],'')
  code=clash_effect('slot' if slot else ('surge' if b=='clash_of_mountains' else 'aura'),slot)
  if b!='clash_of_bravado':code="if (FaBMPGGuardianAttack()) { "+code+' }'
  add('Defended',code)
 if b=='crash_and_bash':add('Defended',choose(refs('Hand',filters="['keyword'=>'Crush']"),'Reveal_crush_card')+"if ($chosen !== '-') { FaBRevealChoices($player, $chosen); "+token('seismic_surge')+' }')
 if b=='daily_grind':add('ResolveAbility',clash_effect('mill',opponent="intval(DecisionQueueController::GetVariable('rosTarget'))"))
 if b=='pec_perfect':add('ResolveAbility',clash_effect('mill',opponent="intval(DecisionQueueController::GetVariable('rosTarget'))"))
 if b in ['craterhoof','gauntlet_of_boulderhold','leave_a_dent','overswing']:
  add('ResolveAbility' if b in ['craterhoof','gauntlet_of_boulderhold'] else 'ResolveCard',"FaBMPGNext($player, "+str(v if b=='overswing' else (2 if b=='gauntlet_of_boulderhold' else 0))+", '"+('DOMINATE' if b=='craterhoof' else ('MPG_DENT' if b=='leave_a_dent' else ''))+"', "+('true' if b in ['craterhoof','gauntlet_of_boulderhold'] else 'false')+", "+('false' if b=='leave_a_dent' else 'true')+");")
 if b=='disenchantment_of_the_old_ones':add('Hit',crush("FaBMPGDestroyAuras($victim);",True))
 if b=='fault_line':add('Hit',crush("FaBMPGBottomArsenals();"))
 if b=='fearless_confrontation':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('arcAttackUID')); FaBTagUID($uid, 'WTR_POWER:-1'); FaBTagUID($uid, 'MPG_NO_DOMINATE');")
 if b=='flatten_the_field':add('Hit',crush(destroy(refs('Arena','$victim',"['base'=>'seismic_surge']"))))
 if b=='grind_them_down':add('Hit',crush("FaBMPGMill($victim);"))
 if b=='headbutt':add('Hit',crush(choose("FaBMPGEquipment($victim, 'Head')",'Put_defense_counter',False)+"FaBMPGCounter($chosen, true);"))
 if b=='hostile_encroachment':
  add('AttackDeclared',"if (FaBFaiHeroHit()) { "+V+"DoDrawCard($victim, 1); }");add('Hit',crush(discard('$victim')))
 if b=='jarl_vetreidi':add('ResolveAbility',"$options = FaBMPGJarlOptions($player); if ($options !== '') { $choice = await $player.Modal(1, 1, $options, \"Choose_exposed_equipment_zone\"); $label = explode('&', $options)[intval($choice)] ?? ''; FaBMPGJarl($player, $label); }")
 if b=='put_em_in_their_place':add('Hit',crush("$n = FaBHandCount($victim); FaBMPGDiscardHand($victim); DoDrawCard($victim, $n);"))
 if b=='renounce_grandeur':add('Hit',crush("FaBMPGDuration($victim, 'NO_AURA');"))
 if b in ['richter_scale','seismic_eruption']:add('ResolveAbility' if b=='richter_scale' else 'ResolveCard',token('seismic_surge',n=2 if b=='richter_scale' else 3))
 if b=='smelting_of_the_old_ones':add('Hit',crush("FaBMPGSmelt($victim);",True))
 if b.startswith('sunkwater_'):add('Defended',UID+choose("FaBMPGFaceUpArsenal($player)",'Bottom_face_up_arsenal',False)+"if ($chosen !== '-') { FaBUPRBottom($chosen); DoDrawCard($player, 1); FaBTagUID($uid, 'WTR_DEFENSE:1'); }")
 if b=='tectonic_instability':add('ResolveCard',"$seats = FaBLiveSeats(); $draws = 0; for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = intval($seats[$i]); "+choose(refs('Arsenal','$seat'),'Bottom_arsenal_card',False,'$seat')+"if ($chosen !== '-') { FaBUPRBottom($chosen); $before = FaBHandCount($seat); DoDrawCard($seat, 1); $draws = $draws + max(0, FaBHandCount($seat) - $before); } } "+token('seismic_surge',n='$draws'))
 if b=='test_of_iron_grip':add('Defended',clash_effect('discard'))
 if b=='visit_anvilheim':
  add('PrepareCard',UID+"$maximum = FaBAvailablePitch($player); $x = await $player.NumberChoose(0, $maximum, \"Choose_X_resources\"); FaBEVRSetX($uid, intval($x), 0); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+choose("FaBMPGOffhands($player)",'Remove_defense_counters',False)+"FaBMPGRepair($chosen, intval(FaBARCCard($uid, 'evrX')));")
 if 'Heave ' in txt:add('ResolveAbility',choose("FaBMPGHeaveChoices($player)",'Heave_a_card')+"if ($chosen !== '-') { $uid = FaBUPRUIDs($chosen)[0]; $cost = FaBMPGHeaveValue(FaBFindUID($uid)['object']->CardID); while (intval(GetResources($player)) < $cost) { $pitchRefs = FaBEVRHeavePitch($player, $uid); $pitched = await $player.MZChoose($pitchRefs, \"Pitch_for_heave\"); FaBARCPitchForEffect($player, $pitched); } AddResources($player, intval(GetResources($player)) - $cost); $live = FaBFindUID($uid); if ($live !== null && $live['zone'] === 'Hand' && FaBELEArsenalSpace($player)) { $ref = $live['mzID']; FaBARCLoadArsenal($player, $ref, true); FaBHVYToken($player, 'seismic_surge', $cost); } }")
 if not handled:pending.append(id)
 for ability in a:
  ability['abilityCode']='$mpgPlayer = $player;\n'+re.sub(r'\$player\b','$mpgPlayer',ability['abilityCode'])
  prior=next((x for x in old.get(id,{}).get('abilities',[]) if x['macroName']==ability['macroName']),None)
  if prior and prior['abilityCode']!=ability['abilityCode']:ability['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
 out.append({'cardId':id,'abilities':a})
if pending:raise SystemExit('Unhandled MPG: '+', '.join(pending))
(HERE/'mpg_abilities.json').write_text(json.dumps(out,indent=2)+'\n')
print(f'MPG: {len(out)} identities; {sum(len(e["abilities"]) for e in out)} macros')
