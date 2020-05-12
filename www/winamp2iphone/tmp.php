<?php


$arr = array(
"D:\Orkan\Music\AC DC\ACDC-TheFuror.mp3",
"D:\Orkan\Music\AC DC\ACDC-There'sGonnaBeSomeRockin'.mp3",
"D:\Orkan\Music\AC DC\ACDC-YouAin'tGotAHoldOnMe.mp3",
"D:\Orkan\Music\Alice In Chains\AliceInChains-Would.mp3",
"D:\Orkan\Music\Altan\Altan-Stor,AStor,AGhra.mp3",
"D:\Orkan\Music\Altan\Altan-SuilGhorm.mp3",
"D:\Orkan\Music\Altan\Altan-TuirseMoChroi.mp3",
"D:\Orkan\Music\Audioslave\Audioslave-ShowMeHowToLive.mp3",
"D:\Orkan\Music\Billy Idol\BillyIdol-ShockToTheSystem.mp3",
"D:\Orkan\Music\Bob Dylan\BobDylan-KnockinOnHeavensDoor.mp3",
"D:\Orkan\Music\Bob Marley\BobMarley&TheWailers-SmileJamaica.mp3",
"D:\Orkan\Music\Bob Marley\BobMarley-GetUpStandUp.mp3",
"D:\Orkan\Music\Bob Marley\BobMarley-NoWomanNoCry(Live).mp3",
"D:\Orkan\Music\Bon Jovi\BonJovi-InAndOutOfLove.mp3",
"D:\Orkan\Music\Bon Jovi\BonJovi-Runaway.mp3",
"D:\Orkan\Music\Bruce Springsteen\BruceSpringsteen-BrilliantDisguise.mp3",
"D:\Orkan\Music\Clannad\Clannad-InALifetime.mp3",
"D:\Orkan\Music\Clannad\Clannad-Wilderness.mp3",
"D:\Orkan\Music\Conflict\Conflict-AStateOfMind.mp3",
"D:\Orkan\Music\Conflict\Conflict-AgainstAllOdds.mp3",
"D:\Orkan\Music\Creed\Creed-NeverDie.mp3",
"D:\Orkan\Music\David Bowie\DavidBowie-RebelRebel.mp3",
"D:\Orkan\Music\David Bowie\DavidBowie-ZiggyStardust.mp3",
"D:\Orkan\Music\Destinys Child\Destiny'sChild-BillsBillsBills.mp3",
"D:\Orkan\Music\Destinys Child\Destiny'sChild-BugABoo.mp3",
"D:\Orkan\Music\Destinys Child\Destiny'sChild-JumpinJumpin(Remix).mp3",
"D:\Orkan\Music\Dick Dale\DickDale&HisDelTones-BanzaiWashout.mp3",
"D:\Orkan\Music\Dick Dale\DickDale&HisDelTones-KingOfTheSurfGuitar.mp3",
"D:\Orkan\Music\Dick Dale\DickDale&HisDelTones-Shake'n'Stomp.mp3",
);


// Lepsze mieszanie playlist
function ork_shuffle(&$a) {

	$count = count($a);
	if(!$count) return;

	$out = array();

    // 
    foreach($a as $v) {
        $s = md5($v.uniqid());
        $c = crc32($s);
        $k = (float) sprintf('%u', $c);
        $out[$k] = $v;
    }
    ksort($out, SORT_NUMERIC);
    
    // Usun klucze
    $out = array_merge(array(), $out);
    
	$a = $out;
}



echo '<pre style="text-align:left">';
/*
echo crc32('a56f7df2e256712.50877749')."\n";
echo crc32('b56f7df2e256710.58344458')."\n";
exit;
*/
$src = array("a", "b", "c", "d", "e");
ork_shuffle($src);

print_r($src);

