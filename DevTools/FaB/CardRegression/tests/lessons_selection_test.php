<?php
/** Actual selection helpers with isolated zone/type/name I/O; not extra game coverage. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);$source=$argv[2]??$repo;
$tokens=token_get_all(file_get_contents($source.'/FaBSim/Custom/WTRCards.php'));$loaded=[];
for($i=0;$i<count($tokens);++$i){
 if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;$j=$i+1;
 while(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)++$j;
 if(!is_array($tokens[$j])||!in_array($tokens[$j][1],['FaBWTRLessonsRefs','FaBWTRFinishLessons'],true))continue;
 $name=$tokens[$j][1];$body='';$depth=0;$started=false;
 for($k=$i;$k<count($tokens);++$k){$t=$tokens[$k];$body.=is_array($t)?$t[1]:$t;if($t==='{'){++$depth;$started=true;}elseif($t==='}')--$depth;if($started&&$depth===0)break;}
 eval($body);$loaded[]=$name;$i=$k;
}
if(count($loaded)!==2)throw new RuntimeException('Missing actual selection functions');
function FaBChoiceRefs($player,$zone):array{return array_keys($GLOBALS['objects']);}
function FaBIdentityFromMZ($ref):?array{return $GLOBALS['objects'][$ref]??null;}
function FaBHasType($object,$type):bool{return $object->type===$type;}
function FaBOUTNames($object):array{return $object->names;}
function FaBMoveUID($uid,$zone,$player):void{$GLOBALS['moves'][]=[$uid,$zone,$player];}
function FaBShuffleDeck($player):void{$GLOBALS['shuffles'][]=$player;}
function LI($uid,$names,$player=1,$zone='Graveyard',$type='Attack Reaction'):array{return ['player'=>$player,'zone'=>$zone,'object'=>(object)['UniqueID'=>$uid,'names'=>$names,'type'=>$type]];}
function LS():void{$GLOBALS['objects']=['a'=>LI(6,['Razor Reflex']),'b'=>LI(7,['Pummel']),'c'=>LI(8,['Lunging Press']),'samePitchSibling'=>LI(9,['Pummel']),'nameless'=>LI(10,[]),'foreign'=>LI(11,['Other'],2),'wrongZone'=>LI(12,['Other'],1,'Banish'),'wrongType'=>LI(13,['Other'],1,'Graveyard','Instant')];$GLOBALS['moves']=[];$GLOBALS['shuffles']=[];}
$checks=0;function LC($ok,$why):void{++$GLOBALS['checks'];if(!$ok)throw new RuntimeException($why);}
LS();LC(FaBWTRLessonsRefs(1)==='a&b&c&samePitchSibling','Only own named attack reactions are offered');
foreach(['b&samePitchSibling','a&a','a&b&c&samePitchSibling','a&foreign','a&wrongZone','a&wrongType','a&nameless','a&missing']as$bad){LS();LC(!FaBWTRFinishLessons(1,$bad,[6,7,8,9,10,11,12,13])&&!$GLOBALS['moves']&&!$GLOBALS['shuffles'],'Invalid complete selection has no partial move or shuffle: '.$bad);}
LS();LC(!FaBWTRFinishLessons(1,'a&b',[6])&&!$GLOBALS['moves']&&!$GLOBALS['shuffles'],'Changed/stale eligible UID rejected atomically');
LS();LC(FaBWTRFinishLessons(1,'a&b&c',[6,7,8])&&$GLOBALS['moves']===[[6,'Deck',1],[7,'Deck',1],[8,'Deck',1]]&&$GLOBALS['shuffles']===[1],'Three distinct names move by captured UID, then shuffle once');
LS();LC(FaBWTRFinishLessons(1,'-',[6,7,8])&&!$GLOBALS['moves']&&$GLOBALS['shuffles']===[1],'Zero chosen still shuffles once');
LS();$GLOBALS['objects']['c']['object']->names=['Lunging Press','Pummel'];LC(!FaBWTRFinishLessons(1,'b&c',[7,8])&&!$GLOBALS['moves']&&!$GLOBALS['shuffles'],'Overlapping effective name sets rejected');
echo 'PASS '.$checks.' actual selection-function checks with isolated zone/type/name I/O'.PHP_EOL;
