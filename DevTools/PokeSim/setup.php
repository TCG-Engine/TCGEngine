<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
chdir(dirname(__DIR__, 2));
$rootName = 'PokeSim';
require_once 'Database/DatabaseResolution.php';
if (ResolveDatabaseName() !== 'pokesim') throw new RuntimeException('PokeSim setup requires the pokesim database; check MYSQL_DATABASE_NAME');
$connection = mysqli_connect(getenv('MYSQL_SERVER_NAME') ?: 'localhost', getenv('MYSQL_SERVER_USER_NAME') ?: 'root', getenv('MYSQL_ROOT_PASSWORD') ?: '');
mysqli_query($connection, 'CREATE DATABASE IF NOT EXISTS `pokesim` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
mysqli_close($connection);
require_once 'CardEditor/Database/CardAbilityRepository.php';
$repository = OpenCardAbilityRepository($rootName);
foreach (require 'PokeSim/CardCode/DeckAbilities.php' as $id => $abilities) {
    $repository->replaceCardAbilities($rootName, $id, $abilities, true, $repository->revisionForCard($rootName, $id));
}
$repository->close();
putenv('DEVENV=true');
$generators = in_array('--macros-only', $argv, true) ? ['zzGameCodeGenerator.php','zzTurnGenerator.php'] : ['zzCardCodeGenerator.php','zzGameCodeGenerator.php','zzTurnGenerator.php'];
foreach ($generators as $generator) {
    passthru('"'.PHP_BINARY.'" '.$generator.' rootName=PokeSim downloadImages=0', $status);
    if ($status) throw new RuntimeException("$generator failed ($status)");
}
echo "PokeSim authored macros saved and runtime regenerated.\n";
