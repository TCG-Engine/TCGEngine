<?php
function FaBEVRAbilityRows(): array {return [
 'genis_wotchuneed'=>[['ACTION',2,false,true,true,0,'Trade cards for Silver']],
 'dreadbore'=>[['ACTION',1,false,true,true,0,'Load arrow']],
 'krakens_aethervein'=>[['INSTANT',3,false,false,true,0,'Arcane damage and draw']],
 'silver'=>[['ACTION',3,true,true,false,0,'Draw a card']],
 'vexing_quillhand'=>[['ACTION',0,true,true,false,0,'Create two Runechants']],
 'firebreathing_red'=>[['INSTANT',1,false,false,false,0,'Increase attack power']],
 'crown_of_reflection'=>[['INSTANT',0,true,false,false,0,'Exchange Illusionist aura']],
 'helm_of_sharp_eye'=>[['REACTION',1,true,false,false,0,'Banish a card to play']],
 'micro_processor_blue'=>[['ACTION',0,false,false,true,0,'Opt one'],['ACTION',0,false,false,true,0,'Draw then topdeck'],['ACTION',0,false,false,true,0,'Banish top card']],
 'clarity_potion_blue'=>[['INSTANT',0,true,false,false,0,'Opt two']],
 'healing_potion_blue'=>[['ACTION',0,true,true,false,0,'Gain two life']],
 'potion_of_deja_vu_blue'=>[['INSTANT',0,true,false,false,0,'Return pitch to top']],
 'potion_of_ironhide_blue'=>[['INSTANT',0,true,false,false,0,'Attack actions gain defense']],
 'potion_of_luck_blue'=>[['INSTANT',0,true,false,false,0,'Shuffle and redraw']],
 'potion_of_seeing_blue'=>[['INSTANT',0,true,false,false,0,'Look at a hand']],
 'amulet_of_assertiveness_yellow'=>[['REACTION',0,true,false,false,0,'Banish on hit']],
 'amulet_of_echoes_blue'=>[['INSTANT',0,true,false,false,0,'Hero discards two']],
 'amulet_of_havencall_blue'=>[['DEFENSE',0,true,false,false,0,'Defend with Rally the Rearguard']],
 'amulet_of_ignition_yellow'=>[['INSTANT',0,true,false,false,0,'Reduce next ability cost']],
 'amulet_of_intervention_blue'=>[['INSTANT',0,true,false,false,0,'Prevent one damage']],
 'amulet_of_oblation_blue'=>[['INSTANT',0,true,false,false,0,'Return attack to bottom']],
];}
function FaBEVRAbilityLegal(int $p,array $f,array $spec): bool {
 $o=$f['object'];$b=FaBWTRBase($o->CardID);$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));
 if($b==='firebreathing')return $f['zone']==='CombatChain'&&intval($o->UniqueID)===intval($s['attackUID'])&&intval($s['attacker'])===$p;
 if($b==='crown_of_reflection'&&($p!==intval(GetTurnPlayer())||GetCurrentPhase()!=='MAIN'||FaBARCSelect($p,'Arena','Illusionist','Aura')===''))return false;
 if($b==='helm_of_sharp_eye'&&(!$a||!FaBWTRIsWeapon($a['object'])||FaBAttackPower($s)<=2*intval(CardPower($a['object']->CardID))))return false;
 if($b==='amulet_of_assertiveness'&&FaBHandCount($p)<4)return false;
 if($b==='amulet_of_echoes'&&FaBEVRTargets($p,'Echoes')==='')return false;
 if($b==='amulet_of_havencall'&&(FaBHandCount($p)!==0||!FaBIsDefendingHero($p,$s)||$s['window']!=='REACTION'))return false;
 if($b==='amulet_of_ignition'&&(FaBEVRCount($p,'PLAYED')||FaBEVRCount($p,'ACTIVATED')))return false;
 if($b==='amulet_of_intervention'&&!FaBEVRLethalSource($p))return false;
 if($b==='amulet_of_oblation'&&(!FaBEVRCount($p,'GRAVEYARD')||FaBELECombatChoices('AA')===''))return false;
 return true;
}
