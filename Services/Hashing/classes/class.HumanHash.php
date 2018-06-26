<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Service class for hashing output, designed for use with Modules/Test
* 
* @author		Frederik Klama <github@fklama.de>
* @version 		0.1
*/

class HumanHash
{
	private $words;
	private $word_list;
	private $split_list;

	public function __construct($words=8)
	{
		$this->words = $words;
		$this->split_list = array(0, 1, 2, 3, 4, 5, 3, 4, 4, 3, 5, 3, 3, 5, 5, 5, 4, 5, 4, 5, 5, 3);
		$this->word_list = array(
		"Null", "Eins", "Zwei", "Drei", "Vier", "Fünf", "Sechs", "Sieben", "Acht", "Neun", 					#  10
		"Zehn", "Elf", "Zwölf", "Rot", "Grün", "Blau", "Gelb", "Weiß", "Schwarz", "Pink",  					#  20
		"Lila", "Cyan", "Magenta", "Kabul", "Kairo", "Nassau", "Brüssel", "Peking", "Berlin", "Helsinki",	#  30
		"Paris", "Athen", "Jakarta", "Bagdad", "Dublin", "Rom", "Kingston", "Tokio", "Seoul", "Riga",		#  40
		"Beirut", "Tripolis", "Monaco", "Oslo", "Wien", "Lima", "Lissabon", "Warschau", "Manila", "Moskau",	#  50
		"Bern", "Dakar", "Belgrad", "Singapur", "Madrid",													#  55
		"Kapstadt", "Bangkok", "Ankara", "Kiew", "Budapest",  												#  60
		"London", "Hanoi", "Minsk", "Hamburg", "München", "Köln", "Essen", "Bremen", "Leipzig", "Dresden",	#  70
		"Hannover", "Nürnberg", "Duisburg", "Bochum", "Bonn",												#  75
		"Münster", "Mannheim", "Augsburg", "Chemnitz", "Aachen", 											#  80
		"Kiel", "Halle", "Krefeld", "Lübeck", "Erfurt",	"Mainz", "Rostock", "Kassel", "Hagen", "Hamm",		#  90
		"Hamm", "Potsdam", "Solingen", "Herne", "Neuss",													#  95
		"Würzburg", "Fürth", "Ulm", "Göttingen", "Bottrop",													# 100
		"Koblenz", "Jena", "Trier", "Erlangen", "Moers", "Cottbus", "Siegen", "Witten", "Gera", "Schwerin",	# 110
		"Zwickau", "Plauen", "Görlitz", "Mailand", "Sofia", "Prag", "Neapel", "Truin", "Valencia", "Leeds",	# 120
		"Krakau", "Sevilla", "Palermo", "Breslau", "Glasgow",												# 125
		"Dortmund", "Malaga", "Göteborg", "Bradford", "Donau",												# 130
		"Rhein", "Elbe", "Oder", "Weser", "Mosel", "Main", "Saale", "Spree", "Ems", "Neckar",				# 140
		"Werra", "Isar", "Leine", "Lippe", "Fulda", "Elch", "Ente", "Esel", "Fisch", "Gans",				# 150
		"Hase", "Hirsch", "Hund", "Huhn", "Katze", "Pferd", "Reh", "Kuh", "Schaf", "Schwein",				# 160
		"Wal", "Ziege", "Ahorn", "Apfel", "Birke", "Birne", "Buche", "Eibe", "Eiche", "Erle",				# 170
		"Fichte", "Buche", "Hasel", "Kastanie", "Kiefer", "Kirsche", "Linde", "Pappel", "Tanne", "Weide",	# 180
		"Ulme", "Haus", "Dose", "Telefon", "Tee", "Tasse", "Hand", "Löffel", "Gabel", "Messer",				# 190
		"Computer", "Sieb", "Sand", "Vase", "Flasche", "Teller", "Wald", "Turm", "Lampe", "Fenster",		# 200
		"Wand", "Glas", "Brille", "Auge", "Arm", "Bein", "Kopf", "Nase", "Ohr", "Mund",						# 210
		"Finger", "Zeh", "Fuß", "Haar", "Hose", "Hemd", "Rock", "Kleid", "Socken", "Schuhe", 				# 220
		"Auto", "Taste", "Maus", "Ratte", "Ananas", "Mango", "Papaya", "Kokos", "Tür", "Schild",			# 230
		"Blatt", "Stamm", "Antenne", "Kabel", "Rad", "Kreis", "Quadrat", "Dreieck", "Kugel", "Kubus",		# 240
		"Zylinder", "Hut", "Punkt", "Linie", "Tafel", "Kreide", "Liste", "Cola", "Wein", "Kaffee",			# 250
		"Kuchen", "Keks", "Nudeln", "Reis", "Brot", "Tomate"
		);
	}

	public function getWords()
	{
		return $this->words;
	}

	public function setWords($words)
	{
		$this->words = $words;
	}

	public function humanize($hash, $split_newline=false)
	{
		$hex_list   = str_split($hash, 2);
		$lambda	    = function($value) { return $this->word_list[hexdec($value)]; };
		$res_list   = array_map($lambda, $hex_list);
		$out_string = "";
		$words		= $this->words;
		if(count($hex_list) < $this->words)
			$words = count($hex_list);
		$mod_val = 0;
		if($words >= count($this->split_list))
			$mod_val = 4;
		else
			$mod_val = $this->split_list[$words];
		for($i=0; $i<$this->words; $i++)
		{
			if($i != 0)
			{
				if($i%$mod_val == 0)
				{
					if($split_newline)
						$out_string .= "<br/>";
					else
						$out_string .= " &nbsp; ";
				}
				else
					$out_string .= "-";
			}
			$out_string .= $res_list[$i];
		}
		return $out_string;
	}
}

?>