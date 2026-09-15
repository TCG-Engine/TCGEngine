<?php
function FaBUPRAbilityRows(): array {return [
 'alluvion_constellas'=>[['INSTANT',0,false,false,false,0,'Reduce next staff ability cost']],
 'conduit_of_frostburn'=>[['INSTANT',0,true,false,false,0,'Destroy frozen arsenal on arcane damage']],
 'coronet_peak'=>[['ACTION',3,false,false,false,0,'Tax a hero']],
 'flamescale_furnace'=>[['INSTANT',1,false,false,true,0,'Gain resources for red pitch']],
 'ghostly_touch'=>[['ACTION',0,false,true,true,0,'Become an ally']],
 'glacial_horns'=>[['ACTION',0,true,true,false,0,'Freeze arsenal and ally']],
 'heat_wave'=>[['INSTANT',0,true,false,false,0,'Empower Phoenix Flames']],
 'helios_mitre'=>[['INSTANT',2,false,false,false,0,'Prevent one source damage']],
 'sash_of_sandikai'=>[['INSTANT',0,true,false,false,0,'Gain a resource']],
 'silken_form'=>[['INSTANT',0,true,false,false,0,'Transform ash']],
 'spellfire_cloak'=>[['INSTANT',0,true,false,false,0,'Gain a resource']],
 'tide_flippers'=>[['REACTION',0,true,false,false,0,'Give small attack go again']],
 'waning_moon'=>[['INSTANT',2,false,false,true,0,'Deal arcane damage']],
];}
function FaBUPRAbilityLegal(int $p,array $f,array $spec): bool {
 $o=$f['object'];if(FaBUPRLocked($p)||FaBUPRFrozen($o))return false;
 if(in_array($o->CardID,['flamescale_furnace','sash_of_sandikai'],true)&&!FaBUPRRed($p))return false;
 if($o->CardID==='spellfire_cloak'&&$p===intval(GetTurnPlayer()))return false;
 if($o->CardID==='alluvion_constellas'&&intval(FaBObjectCounters($o)['ENERGY']??0)<2)return false;
 if($o->CardID==='ghostly_touch'&&($f['zone']!=='Equipment'||intval(FaBObjectCounters($o)['HAUNT']??0)<1))return false;
 if($o->CardID==='waning_moon'&&!FaBARCPlayed($p,true))return false;
 if($o->CardID==='silken_form'&&FaBUPRAsh($p)==='')return false;
 if($o->CardID==='tide_flippers'&&FaBUPRAttackChoices($p,'SMALL')==='')return false;
 return true;
}
function FaBUPRAbilityPaid(int $p,object $o): void {
 if($o->CardID==='alluvion_constellas')FaBSetObjectCounter($o,'ENERGY',intval(FaBObjectCounters($o)['ENERGY']??0)-2);
 if($o->CardID==='ghostly_touch')FaBSetObjectCounter($o,'HAUNT',intval(FaBObjectCounters($o)['HAUNT']??0)-1);
 if(FaBHasType($o,'Staff'))FaBUPRClear($p,'STAFF_DISCOUNT');
}
