<?php

$p1 = array(
'D:\Orkan\Music\mp3\Peter Gabriel\PeterGabriel-Taboo.mp3',
'D:\Orkan\Music\mp3\Peter Gabriel\PeterGabriel-Taboo.mp3',
'D:\Orkan\Music\mp3\Peter Gabriel\PeterGabriel-SignalToNoise.mp3',
'D:\Orkan\Music\mp3\Peter Gabriel\PeterGabriel-PlanetCaravan.mp3',
'D:\Orkan\Music\mp3\Scorpions\Scorpions-YellowRaven.mp3',
'D:\Orkan\Music\mp3\Metallica\Metallica-Astronomy.mp3',
'D:\Orkan\Music\mp3\Metallica\Metallica-Astronomy.mp3',
'D:\Orkan\Music\mp3\Radiohead\Radiohead-IMightBeWrong.mp3',
);

$p2 = array(
'D:\Orkan\Music\mp3\Peter Gabriel\PeterGabriel-Taboo.mp3',
'D:\Orkan\Music\mp3\Peter Gabriel\PeterGabriel-SignalToNoise.mp3',
'D:\Orkan\Music\mp3\Peter Gabriel\PeterGabriel-PlanetCaravan.mp3',
'D:\Orkan\Music\mp3\Scorpions\Scorpions-YellowRaven.mp3',
'D:\Orkan\Music\mp3\Metallica\Metallica-Astronomy.mp3',
'D:\Orkan\Music\mp3\Radiohead\Radiohead-IMightBeWrong.mp3',
);


/*
$p1 = array("green", "red", "blue", "red");
$p2 = array("green", "red", "blue");
*/

$d1 = array_count_values($p1);

echo '<pre style="text-align:left">';
print_r($d1);

