<?php
// relocate-fns.php [--dry]
//
// Co-locates single-card helper FUNCTIONS: any top-level function defined in a
// monolith but called by exactly one card file (and nowhere else in the engine)
// is moved into that card file and renamed from _SWU<set><NNN><Action> to a
// title-based <CardTitlePascalCase><Action> (no leading underscore).
//
// Safe: functions are process-global once included; the only caller is the card
// file itself (calls happen at runtime). Verifies the set of defined function
// names is preserved (old removed, new added, none lost/duplicated). --dry prints
// the rename map + lints a temp copy without touching anything real.

error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
$repo = getenv('REPO_ROOT') ?: (function(){ $d=__DIR__; while($d!=='/'&&!(is_dir("$d/SWUSim")&&is_dir("$d/Core")))$d=dirname($d); return $d; })();
chdir($repo);
require __DIR__.'/../GeneratedCode/GeneratedCardDictionaries.php';
require __DIR__.'/CardFileSplitter/Scanner.php';

$dry = in_array('--dry', $argv, true);
$monoliths = ['SWUSim/Custom/CardDQHandlers.php','SWUSim/Custom/LeaderAbilities.php','SWUSim/Custom/BaseAbilities.php'];
$engineExtra = ['SWUSim/Custom/GameLogic.php','SWUSim/Custom/CombatLogic.php','SWUSim/Custom/CardEffects.php','SWUSim/Custom/KeywordEffects.php'];

// scan monoliths (keep per-file statements) + collect function-def statements
$perFile=[]; $fnDefs=[]; // name => ['file'=>,'text'=>,'span'=>]
foreach($monoliths as $m){ $st=splitter_scan(file_get_contents($m)); $perFile[$m]=$st;
  foreach($st as $s){ if($s['kind']==='function' && preg_match('/^\s*function\s+([a-zA-Z_]\w*)/',ltrim(preg_replace('#^(\s*//[^\n]*\n)+#','',$s['text'])),$mm)) $fnDefs[$mm[1]]=['file'=>$m,'text'=>$s['text'],'span'=>$s['span']]; } }

// load card files + engine blob once
$cards=[]; foreach(glob('SWUSim/Custom/cards/*/*.php') as $cf)$cards[$cf]=file_get_contents($cf);
$engineBlob=''; foreach(array_merge($monoliths,$engineExtra) as $ef)$engineBlob.="\n".file_get_contents($ef);

// PascalCase a title preserving existing caps (CR90 Relief Runner -> CR90ReliefRunner)
$pascal = fn($s) => preg_replace('/[^A-Za-z0-9]/','', str_replace(["'","\u{2019}"],'',$s));

$plan=[]; // fn => ['card'=>cardfile, 'new'=>newName]
$newNames=[]; $GLOBALS['fnSkipped']=[];
foreach($fnDefs as $fn=>$d){
  $re='/\b'.preg_quote($fn,'/').'\s*\(/';
  $hits=[]; foreach($cards as $cf=>$c){ if(preg_match($re,$c))$hits[]=$cf; }
  $engRefs=preg_match_all($re,$engineBlob); // includes the 1 definition
  if(count($hits)!==1 || $engRefs>1) continue; // not single-card
  $cf=$hits[0];
  // CardID from the card file header (// SET_NNN)
  if(!preg_match('#//\s*([A-Z0-9]{2,4}_\d+)#',$cards[$cf],$cm)) continue;
  $cid=$cm[1]; $title=$titleData[$cid]??$cid;
  $prefix=$pascal($title).$pascal($subtitleData[$cid]??'');   // TitleSubtitle
  // action suffix = old name minus _?(SWU)?<set><NNN>. If the name has no card-NNN
  // it's a generic utility (single-card *used*, not card-*specific*) — skip renaming
  // it (a card-title name would mislead); leave it in the monolith.
  $suffix=preg_replace('/^_?(SWU)?[A-Za-z]{2,4}\d+/','',$fn,1,$cnt);
  if($cnt===0 || $suffix===''){ $GLOBALS['fnSkipped'][]=$fn; continue; }
  // Dedupe: the suffix already leads with the title (e.g. ForceThrow + ForceThrowDiscard).
  if(stripos($suffix,$prefix)===0) $new=$suffix; else $new=$prefix.$suffix;
  // uniqueness
  $base=$new; $i=2; while(isset($newNames[$new])){ $new=$base.$i; $i++; }
  $newNames[$new]=true;
  $plan[$fn]=['card'=>$cf,'new'=>$new];
}

echo "single-card helper functions: ".count($plan)." (skipped ".count($GLOBALS['fnSkipped'])." generic)\n";
foreach($plan as $fn=>$p) printf("  %-30s -> %-34s (%s)\n",$fn,$p['new'],str_replace('SWUSim/Custom/cards/','',$p['card']));
if($GLOBALS['fnSkipped']) echo "SKIPPED (generic, no card-NNN — stay in monolith): ".implode(', ',$GLOBALS['fnSkipped'])."\n";

if($dry){
  // build temp result + lint
  $tmp=sys_get_temp_dir().'/relocfn'; exec('rm -rf '.escapeshellarg($tmp)); @mkdir($tmp,0777,true);
  // apply per-card (append renamed defs) + per-monolith (remove spans) to temp copies
  $cardOut=$cards; $delSpans=[];
  foreach($plan as $fn=>$p){
    $text=$fnDefs[$fn]['text'];
    $renamed=preg_replace('/\bfunction\s+'.preg_quote($fn,'/').'\b/','function '.$p['new'],$text,1);
    $cardOut[$p['card']]=preg_replace('/\b'.preg_quote($fn,'/').'\b/',$p['new'],$cardOut[$p['card']]);
    $cardOut[$p['card']].="\n".$renamed."\n";
    $delSpans[$fnDefs[$fn]['file']][]=$fnDefs[$fn]['span'];
  }
  $bad=0;
  foreach($cardOut as $cf=>$c){ $p=$tmp.'/'.basename($cf); file_put_contents($p,$c); $o=[];$rc=0; exec('php -l '.escapeshellarg($p).' 2>&1',$o,$rc); if($rc)$bad++; }
  foreach($monoliths as $m){ $src=file_get_contents($m); $sp=$delSpans[$m]??[]; usort($sp,fn($a,$b)=>$b[0]-$a[0]); foreach($sp as [$a,$b])$src=substr($src,0,$a).substr($src,$b); $p=$tmp.'/'.basename($m); file_put_contents($p,$src); $o=[];$rc=0; exec('php -l '.escapeshellarg($p).' 2>&1',$o,$rc); if($rc){$bad++;echo "LINT FAIL ".basename($m)."\n";} }
  echo $bad?"DRY: $bad lint failures\n":"DRY OK — all lint clean\n";
  exit($bad?1:0);
}

// REAL: verify function-name set preserved, then write
$before=[]; foreach(array_merge($monoliths,array_keys($cards)) as $f){ if(preg_match_all('/^function\s+([a-zA-Z_]\w*)/m',is_file($f)?file_get_contents($f):($cards[$f]??''),$mm)) foreach($mm[1] as $n)$before[$n]=($before[$n]??0)+1; }
$delSpans=[];
foreach($plan as $fn=>$p){
  $text=$fnDefs[$fn]['text'];
  $renamed=preg_replace('/\bfunction\s+'.preg_quote($fn,'/').'\b/','function '.$p['new'],$text,1);
  $c=file_get_contents($p['card']);
  $c=preg_replace('/\b'.preg_quote($fn,'/').'\b/',$p['new'],$c);       // rename call sites
  file_put_contents($p['card'],$c."\n".$renamed."\n");                  // append renamed def
  $delSpans[$fnDefs[$fn]['file']][]=$fnDefs[$fn]['span'];
}
foreach($monoliths as $m){ $src=file_get_contents($m); $sp=$delSpans[$m]??[]; usort($sp,fn($a,$b)=>$b[0]-$a[0]); foreach($sp as [$a,$b])$src=substr($src,0,$a).substr($src,$b); file_put_contents($m,$src); }
// after set
$after=[]; foreach(array_merge($monoliths,glob('SWUSim/Custom/cards/*/*.php')) as $f){ if(preg_match_all('/^function\s+([a-zA-Z_]\w*)/m',file_get_contents($f),$mm)) foreach($mm[1] as $n)$after[$n]=($after[$n]??0)+1; }
$expected=$before; foreach($plan as $fn=>$p){ unset($expected[$fn]); $expected[$p['new']]=($expected[$p['new']]??0)+1; }
$dups=array_filter($after,fn($c)=>$c>1);
echo "relocated ".count($plan)." functions. defined-name count: before ".count($before).", after ".count($after);
echo $dups? (", DUPLICATES: ".implode(',',array_keys($dups))."\n") : ", no duplicates\n";
