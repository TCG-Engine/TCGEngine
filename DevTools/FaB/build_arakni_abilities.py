import json
from pathlib import Path
import ast,hashlib
source=Path('DevTools/FaB/build_mon_abilities.py').read_text(encoding='utf-8')
exec(ast.get_source_segment(source,next(n for n in ast.parse(source).body if isinstance(n,ast.FunctionDef) and n.name=='clean')))
old={c['cardId']:c for c in json.loads(Path('DevTools/FaB/arakni_abilities.json').read_text())} if Path('DevTools/FaB/arakni_abilities.json').exists() else {}
out=[]
def card(id,macros):out.append({'cardId':id,'abilities':[{'macroName':m,'abilityCode':clean(code),'isImplemented':True} for m,code in macros.items()]})
for id,power in [('incision_red',3),('incision_blue',1)]:card(id,{'ResolveCard':f"$refs = FaBDYNAttacks($player, 'DAGGER'); if ($refs !== '') {{ $chosen = await $player.MZChoose($refs, \"Choose_dagger_attack\"); FaBDYNTag($chosen, 'WTR_POWER:{power}'); }}"})
card('danger_digits',{'ResolveAbility':"$refs = FaBArakniDaggers($player); if ($refs !== '') { $chosen = await $player.MZChoose($refs, \"Throw_dagger\"); FaBArakniThrow($player, $chosen); }"})
card('starting_point',{'ResolveAbility':"$refs = FaBDYNAttacks($player, 'ANY'); if ($refs !== '') { $chosen = await $player.MZChoose($refs, \"Give_attack_go_again\"); FaBDYNTag($chosen, 'GO_AGAIN'); }"})
card('leap_frog_slime_skin',{'ResolveAbility':"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $mode = await $player.Modal(1, 1, \"Keep_equipment&Defend_with_Leap_Frog\", \"Leap_Frog_Slime_Skin\"); if ($mode === '1') { FaBArakniLeap($uid); }"})
card('hunted_or_hunter_red',{'Defended':'FaBArakniHunted($player);'})
card('the_hand_that_pulls_the_strings',{'ResolveAbility':"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); FaBArakniFlip($uid);",'StartTurn':"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $refs = implode('&', FaBMONArena($player, 'silver')); $chosen = 'PASS'; if ($refs !== '') { $chosen = await $player.MZChoose($refs, \"Destroy_Silver_for_Strings\"); } FaBArakniStringsUpkeep($player, $uid, $chosen);"})
card('up_sticks_and_run_blue',{'ResolveCard':"$refs = FaBArakniRetrieveTargets($player); $chosen = 'PASS'; if ($refs !== '') { $chosen = await $player.MZMayChoose($refs, \"Retrieve_dagger_for_one_resource\"); } if ($chosen !== 'PASS') { $daggerUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); while (intval(GetResources($player)) < 1) { $pitchRefs = FaBARCPitchChoices($player); $pitch = await $player.MZChoose($pitchRefs, \"Pitch_to_retrieve\"); FaBARCPitchForEffect($player, $pitch); } AddResources($player, intval(GetResources($player)) - 1); FaBArakniRetrieve($player, $daggerUID); } FaBDYNNext($player, 'DAGGER', 2, '');"})
for c in out:
 for a in c['abilities']:
  previous=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if previous and previous['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(previous['abilityCode'].strip().encode()).hexdigest()
Path('DevTools/FaB/arakni_abilities.json').write_text(json.dumps(out,indent=2)+'\n')
