<?php
// relocate-orphans.php [--dry]
//
// Moves fully-orphaned cards (monolith registrations, no card file) into their
// cards/<set>/<Title_Subtitle>.php, INCLUDING each card's private setup local
// (a bare-var closure used only by that card) so top-level use() captures resolve
// inside the new file. Skips cards that share a local across >1 card or whose def
// is a pinned value-copy target (those must stay for load order).
//
// Safety: (array,key) snapshot before/after in fresh subprocesses; auto-revert on
// any mismatch. --dry writes to a temp dir + lints, touching nothing real.

error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
$repo = getenv('REPO_ROOT') ?: (function(){ $d=__DIR__; while($d!=='/'&&!(is_dir("$d/SWUSim")&&is_dir("$d/Core")))$d=dirname($d); return $d; })();
chdir($repo);
require __DIR__.'/../GeneratedCode/GeneratedCardDictionaries.php';
require $repo.'/AppCore/SWU/Overrides.php'; require $repo.'/AppCore/SWU/DeckValidation.php';
foreach(['HeaderGen','Scanner','Router','Emitter','Verify'] as $m) require __DIR__."/CardFileSplitter/$m.php";

$dry = in_array('--dry', $argv, true);
$monoliths = ['SWUSim/Custom/CardDQHandlers.php','SWUSim/Custom/LeaderAbilities.php','SWUSim/Custom/BaseAbilities.php'];
$cardsDir = 'SWUSim/Custom/cards';
$snap = 'SWUSim/DevTools/CardFileSplitter/snapshot_keys.php';

// --- scan (keep per-file statement lists) ---
$perFile = []; $all = [];
foreach($monoliths as $f){ $st = splitter_scan(file_get_contents($f)); $perFile[$f]=$st; $all=array_merge($all,$st); }
$pinned = splitter_pinned_keys($all);

// NB: [A-Z0-9]{2,4} not [A-Z] — set codes can contain digits (e.g. TS26). Extract
// the owner from the KEY's leading CardID prefix so letter/word-suffixed keys parse too.
$ownerOf = function($s){ if($s['kind']==='assign'&&preg_match('/\[\s*[\x27"]([A-Z0-9]{2,4}_\d+)/',$s['lhs'],$m))return CardIDOverride($m[1]); return null; };
$defVar  = function($s){ if(preg_match('/^\s*\$([a-z][a-zA-Z0-9_]*)\s*=/',ltrim(preg_replace('#^(\s*//[^\n]*\n)+#','',$s['text'])),$m))return $m[1]; return null; };

// who references each local (excluding its own definition) -> owner tags
$localRefs=[]; $skip=['player','mzID','parts','lastDecision','playerID'];
foreach($all as $s){ $tag=$ownerOf($s)??'ENGINE'; $own=$defVar($s);
  if(preg_match_all('/\$([a-z][a-zA-Z0-9_]*)\b/',$s['text'],$m2))
    foreach(array_unique($m2[1]) as $v){ if(in_array($v,$skip,true)||$v===$own)continue; $localRefs[$v][$tag]=true; } }

// candidate bases: ANY card that still owns a statement in the monolith (whether it
// routes 'move' or 'leave'). The classify step below keeps genuinely-must-stay cards
// (pinned value-copies, shared cross-card locals) and relocates the rest — including
// alias / captures-local groups whose private local is exclusive to the card
// (e.g. JTL_154 Profundity: two aliases to a single $jtl154_choose closure).
$candidates=[];
foreach($all as $s){ $b=$ownerOf($s); if($b!==null) $candidates[$b]=true; }

// classify: relocatable bases + their exclusive dep-locals
$relocDeps=[]; $staysLog=[];
foreach(array_keys($candidates) as $base){
  $stmts=array_filter($all,fn($s)=>$ownerOf($s)===$base);
  $isPinned=false; foreach($stmts as $s){$k=splitter_stmt_key($s); if($k&&isset($pinned[$k]))$isPinned=true;}
  if($isPinned){$staysLog[$base]='pinned value-copy def';continue;}
  $deps=[]; foreach($stmts as $s){ foreach($s['topLevelUses'] as $u)$deps[$u]=true;
    if(preg_match('/=\s*\$([a-zA-Z_]\w*)\s*(\[[^\]]*\])?\s*;\s*$/',trim($s['text']),$am))$deps[$am[1]]=true; }
  $shared=false;
  foreach(array_keys($deps) as $d){ $others=array_filter(array_keys($localRefs[$d]??[]),fn($t)=>$t!==$base); if($others)$shared=true; }
  if($shared){$staysLog[$base]='shared local';continue;}
  $relocDeps[$base]=array_keys($deps);
}

// per file: assign each statement to a relocatable card (its registration OR its exclusive local def)
$fileGroups=[]; $delSpans=[];
foreach($monoliths as $f){
  foreach($perFile[$f] as $s){
    $take=null; $b=$ownerOf($s);
    if($b!==null && isset($relocDeps[$b])) $take=$b;
    else { $dv=$defVar($s); if($dv!==null) foreach($relocDeps as $rb=>$locs) if(in_array($dv,$locs,true)){ $take=$rb; break; } }
    if($take===null) continue;
    $set=strtolower(SWUCardSet($take)); $bn=splitter_card_basename($take);
    if(!isset($fileGroups[$take])) $fileGroups[$take]=['set'=>$set,'bn'=>$bn,'texts'=>[]];
    $fileGroups[$take]['texts'][]=rtrim($s['text']);
    $delSpans[$f][]=$s['span'];
  }
}

// render card files. $writes[rel] = FULL final content (new file, or existing+appended).
$writes=[];
foreach($fileGroups as $base=>$g){
  $rel="{$g['set']}/{$g['bn']}.php"; $path="$cardsDir/$rel"; $body=implode("\n\n",$g['texts'])."\n";
  if(is_file($path)) $writes[$rel]=rtrim(file_get_contents($path))."\n\n".$body;                 // append to existing card file
  else { $reprints=array_values(array_filter(SWUReprintGroup($base),fn($p)=>$p!==$base));
         $writes[$rel]="<?php\n".splitter_card_header($base,$reprints)."\n".$body; }              // new card file
}
$remaining=[];
foreach($monoliths as $f){ $src=file_get_contents($f); $sp=$delSpans[$f]??[]; usort($sp,fn($a,$b)=>$b[0]-$a[0]);
  foreach($sp as [$a,$b])$src=substr($src,0,$a).substr($src,$b); $remaining[$f]=$src; }

echo "relocatable cards: ".count($fileGroups).", staying: ".count($staysLog)."\n";
foreach($staysLog as $b=>$r) echo "  STAY $b ($r)\n";

if($dry){
  $tmp=sys_get_temp_dir().'/reloc_dry'; exec('rm -rf '.escapeshellarg($tmp)); @mkdir($tmp,0777,true); $bad=0;
  foreach($writes as $rel=>$c){ $p="$tmp/$rel"; @mkdir(dirname($p),0777,true); file_put_contents($p,$c); if(!splitter_php_lints($c)){$bad++;echo "  LINT FAIL $rel\n";} }
  foreach($remaining as $f=>$src) if(!splitter_php_lints($src)){$bad++;echo "  LINT FAIL (remaining) $f\n";}
  echo $bad?"DRY FAILED ($bad)\n":"DRY OK — ".count($writes)." files, remaining clean. preview: $tmp\n";
  exit($bad?1:0);
}

// backup monoliths + any EXISTING target card files (appends overwrite them); track newly-created files
$bak=[]; foreach($monoliths as $f){$b="$f.bak";copy($f,$b);$bak[$f]=$b;}
$cardBak=[]; $created=[];
foreach(array_keys($writes) as $rel){ $p="$cardsDir/$rel"; if(is_file($p)){copy($p,"$p.bak");$cardBak[$p]="$p.bak";} else $created[]=$p; }
$before=json_decode(shell_exec('php -d xdebug.mode=off '.escapeshellarg($snap).' 2>/dev/null'),true);
if(!is_array($before)){foreach($bak as $f=>$b)copy($b,$f);foreach($cardBak as $p=>$b){copy($b,$p);unlink($b);}echo "no BEFORE snapshot\n";exit(1);}
foreach($writes as $rel=>$c){ $p="$cardsDir/$rel"; @mkdir(dirname($p),0777,true); file_put_contents($p,$c); }
foreach($remaining as $f=>$src) file_put_contents($f,$src);
$after=json_decode(shell_exec('php -d xdebug.mode=off '.escapeshellarg($snap).' 2>/dev/null'),true);
$revert=function()use($bak,$cardBak,$created){foreach($bak as $f=>$b)copy($b,$f);foreach($cardBak as $p=>$b)copy($b,$p);foreach($created as $p)@unlink($p);};
if(!is_array($after)){$revert();echo "no AFTER snapshot — reverted\n";exit(1);}
$diff=splitter_diff_keys($before,$after);
if($diff['missing']||$diff['added']){echo "KEY DIFF FAILED — reverting\n  missing: ".implode(',',array_slice($diff['missing'],0,15))."\n  added: ".implode(',',array_slice($diff['added'],0,15))."\n";$revert();exit(1);}
foreach($cardBak as $p=>$b)@unlink($b); // key-diff passed → drop the card-file backups
echo "relocated ".count($writes)." cards (".count($created)." new, ".count($cardBak)." appended). key diff OK (".count($before)." keys, 0/0). monolith backups at *.bak\n";
