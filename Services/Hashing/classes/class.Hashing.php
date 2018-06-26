<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Service class for hashing output, designed for use with Modules/Test
* 
* @author		Frederik Klama <github@fklama.de>
* @version 		0.1
*/

require_once "./Services/Hashing/classes/class.HumanHash.php";

class Hashing
{
	private $algos;
	private $human_hash;

	public function __construct()
	{
		$this->algos = hash_algos();
		$this->human_hash = 0;
	}

	public function getAlgorithms()
	{
		return $this->algos;
	}

	public function checkAlgorithm($algo)
	{
		return in_array($algo, $this->algos);
	}

	public function hashData($data, $algo="sha256")
	{
		return hash($algo, $data);
	}

	public function hashFile($path, $algo="sha256", $save_file=true)
	{
		$debug = fopen(ilUtil::getWebspaceDir()."/debug.txt", "w");
		fwrite($debug, $path);
		fclose($debug);
		$out = hash_file($algo, $path);
		if($save_file)
		{
			$hash_file_path = $path.".hash";
			$out_file = fopen($hash_file_path, "w");
			fwrite($out_file, $out);
			fclose($out_file);
		}
		return $out;
	}

	public function humanize($hash, $words=8, $split_newline=false)
	{
		if($this->human_hash === 0)
			$this->human_hash = new HumanHash($words);
		else
			$this->human_hash->setWords($words);

		return $this->human_hash->humanize($hash, $split_newline);
	}

	public function humanHashData($data, $algo="sha256", $words=8)
	{
		$out = array();
		$out[0] = $this->hashData($data, $algo);
		$out[1] = $this->humanize($out[0], $words);
		return $out;
	}

	public function humanHashFile($path, $algo="sha256", $words=8, $save_file=true)
	{
		$out = array();
		$out[0] = $this->hashFile($path, $algo, $save_file);
		$out[1] = $this->humanize($out[0], $words);
		return $out;
	}
}
?>