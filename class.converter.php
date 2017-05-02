<?php

/*
	Jewish Dates Converter
	by Matan Mizrachi
	http://while1.co.il
*/

class w1_jewishDatesConverter {
	
	// gematria values
	private $letters = [
		"א" => 1,
		"ב" => 2,
		"ג" => 3,
		"ד" => 4,
		"ה" => 5,
		"ו" => 6,
		"ז" => 7,
		"ח" => 8,
		"ט" => 9,
		"י" => 10,
		"כ" => 20,
		"ל" => 30,
		"מ" => 40,
		"נ" => 50,
		"ס" => 60,
		"ע" => 70,
		"פ" => 80,
		"צ" => 90,
		"ק" => 100,
		"ר" => 200,
		"ש" => 300,
		"ת" => 400
	];
	
	// possible months names
	// only hebrew alphabet and spaces
	private $months = [
		"תשרי"		=> 1,
		"חשוון"		=> 2,
		"חשון"		=> 2,
		"מר חשוון"	=> 2,
		"מר חשון"	=> 2,
		"מרחשוון"	=> 2,
		"מרחשון"	=> 2,
		"כסליו"		=> 3,
		"כסלו"		=> 3,
		"טבת"		=> 4,
		"שבט"		=> 5,
		"אדר"		=> 6,
		"אדר א"		=> 6,
		"אדרא"		=> 6,
		"אדר ב"		=> 7,
		"אדרב"		=> 7,
		"ניסן"		=> 8,
		"אייר"		=> 9,
		"איר"		=> 9,
		"סיוון"		=> 10,
		"סיון"		=> 10,
		"תמוז"		=> 11,
		"אב"		=> 12,
		"אלול"		=> 13
	];

	// -------------------------------------
	// convert hebrew-string to gregorian day,month,year array
	// $removeYearPrefix should be true, unless the hebrew-year starts with "ה" (not as a thousands prefix)
	// -------------------------------------
	public function toGregorian($hebrewDate , $removeYearPrefix = true){
		// split to hebrew date parts
		$hebrew = $this->getDateParts($hebrewDate , $removeYearPrefix);
		
		// convert date parts to numbers
		$numbers = $this->datePartsAsNumbers($hebrew['day'] , $hebrew['month'] , $hebrew['year']);
		
		// convert to julian day count
		$jd = jewishtojd($numbers['month'] , $numbers['day'] , $numbers['year']);
		
		// convert to gregorian
		$gregorian = cal_from_jd($jd , CAL_GREGORIAN);
		
		return [
			"day" => $gregorian['day'],
			"month" => $gregorian['month'],
			"year" => $gregorian['year'],
		];
	}
	
	// -------------------------------------
	// convert gregorian day,month,year to hebrew-string
	// format output with available $fl options http://php.net/manual/en/function.jdtojewish.php
	// -------------------------------------
	public function toHebrew($day , $month , $year , $fl = 0){
		// convert to julian day count
		$jd = gregoriantojd($month , $day , $year);
		
		// convert to hebrew string
		$hebrewDate = jdtojewish($jd, true, $fl);
		
		// convert to utf-8
		return iconv('WINDOWS-1255', 'UTF-8', $hebrewDate);
	}
	
	// -------------------------------------
	// extract day,month,year from hebrew date string
	// -------------------------------------
	public function getDateParts($date , $removeYearPrefix = true){
		
		$hebrew_date = $this->clear($date);
		
		$date_parts = explode(" " , $hebrew_date , 4);
		$total_parts = count($date_parts);
		
		if($total_parts == 3){
			// for example: א תשרי תשעז
			return [
				"day"	=> $date_parts[0],
				"month"	=> $this->handleMonthPrefix($date_parts[1]),
				"year"	=> $this->handleYearPrefix($date_parts[2] , $removeYearPrefix)
			];
		}
		else if($total_parts == 4){
			// for example יד אדר ב תשעז
			return [
				"day"	=> $date_parts[0],
				"month"	=> $this->handleMonthPrefix("{$date_parts[1]} {$date_parts[2]}"),
				"year"	=> $this->handleYearPrefix($date_parts[3] , $removeYearPrefix)
			];
		}

		// unkown string format
		return [
			"day"	=> 0,
			"month"	=> 0,
			"year"	=> 0
		];
	}
	
	// -------------------------------------
	// convert hebrew date parts to their numeric values
	// -------------------------------------
	public function datePartsAsNumbers($day , $month , $year){
		if(isset($this->months[ $month ])){
			return [
				"day"	=> $this->gematria_toNumbers($day),
				"month"	=> $this->months[ $month ],
				"year"	=> 5000 + $this->gematria_toNumbers($year)
			];
		}
		
		// invalid input
		return [
			"day"	=> 0,
			"month"	=> 0,
			"year"	=> 0
		];
	}
	
	// -------------------------------------
	// remove hebrew thousands-prefix (ה'תשעז)
	// -------------------------------------
	private function handleYearPrefix($hebrewYear , $remove = true){
		if($remove){
			return $this->removeIfFirstLetterIs("ה" , $hebrewYear);
		}
		
		return $hebrewYear;
	}
	
	// -------------------------------------
	// remove hebrew 'at' prefix (בתשרי, בחשוון, ...)
	// -------------------------------------
	private function handleMonthPrefix($hebrewMonth){		
		return $this->removeIfFirstLetterIs("ב" , $hebrewMonth);
	}
	
	// -------------------------------------
	// remove a specific letter from the beginning of a string
	// -------------------------------------
	private function removeIfFirstLetterIs($letter , $string){
		$first_char = mb_substr($string , 0 , 1);
		
		if($first_char === $letter){
			return mb_substr($string , 1);
		}
		
		return $string;
	}
	
	// -------------------------------------
	// keep only hebrew and spaces chars
	// -------------------------------------
	private function clear($hebrewDate){
		// remove non-hebrew or non-spaces chars
		$hebrewDate = preg_replace("/[^א-ת ]/" , '' , $hebrewDate);
		
		// remove multiple spaces
		$hebrewDate = trim(str_replace("  " , " " , $hebrewDate));
		
		return $hebrewDate;
	}
	
	// -------------------------------------
	// convert hebrew string to its gematria value
	// -------------------------------------
	public function gematria_toNumbers($hebrew){
		$gematria_result = 0;
		
		$hebLetters = $this->mb_splitLetters($hebrew);
		$length = count($hebLetters);
		
		for($i = 0; $i < $length; $i++){
			if(isset($this->letters[ $hebLetters[$i] ])){
				$gematria_result += $this->letters[ $hebLetters[$i] ];
			}
		}
		
		return $gematria_result;
	}
	
	// -------------------------------------
	// split multibyte string into letters-array
	// http://stackoverflow.com/a/2556753/7550127
	// -------------------------------------
	private function mb_splitLetters($mbstr){
		return preg_split('//u', $mbstr, -1, PREG_SPLIT_NO_EMPTY);
	}
}

?>