<?php

header('Content-Type: text/html; charset=utf-8');

require_once "class.converter.php";
$jewishConverter = new w1_jewishDatesConverter();

// [EXAMPLE] CONVERT HEBREW STRING TO GREGORIAN DATE
$input = 'ה באייר תשח';
$gregorian = $jewishConverter->toGregorian($input);

echo "input: <pre>{$input}</pre><br />";
echo "output: <pre>{$gregorian['day']}/{$gregorian['month']}/{$gregorian['year']}</pre><br />";

// [EXAMPLE] CONVERT GREGORIAN DATE TO HEBREW STRING
echo "back to hebrew: <pre>";
echo $jewishConverter->toHebrew($gregorian['day'] , $gregorian['month'] , $gregorian['year'] , CAL_JEWISH_ADD_GERESHAYIM);
echo "</pre>";

?>