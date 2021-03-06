<?php
$BEAUT_PATH = realpath(".")."/Services/COPage/syntax_highlight/php";
if (!isset ($BEAUT_PATH)) return;
require_once("$BEAUT_PATH/Beautifier/HFile.php");
  class HFile_asmdsp56k extends HFile{
   function HFile_asmdsp56k(){
     $this->HFile();	
/*************************************/
// Beautifier Highlighting Configuration File 
// DSP56K asm
/*************************************/
// Flags

$this->nocase            	= "1";
$this->notrim            	= "0";
$this->perl              	= "0";

// Colours

$this->colours        	= array("blue", "purple", "gray", "brown", "blue");
$this->quotecolour       	= "blue";
$this->blockcommentcolour	= "green";
$this->linecommentcolour 	= "green";

// Indent Strings

$this->indent            	= array();
$this->unindent          	= array();

// String characters and delimiters

$this->stringchars       	= array("'");
$this->delimiters        	= array("~", "!", "%", "^", "&", "*", "(", ")", "+", "-", "=", "|", "\\", "/", "{", "}", "[", "]", ";", "\"", "'", "<", ">", "#", " ", ",", "	", "?");
$this->escchar           	= "";

// Comment settings

$this->linecommenton     	= array(";");
$this->blockcommenton    	= array("");
$this->blockcommentoff   	= array("");

// Keywords (keyword mapping to colour number)

$this->keywords          	= array(
			"abs" => "1", 
			"adc" => "1", 
			"add" => "1", 
			"addl" => "1", 
			"addr" => "1", 
			"and" => "1", 
			"andi" => "1", 
			"asl" => "1", 
			"asr" => "1", 
			"bchg" => "1", 
			"bclr" => "1", 
			"bset" => "1", 
			"btst" => "1", 
			"clr" => "1", 
			"cmp" => "1", 
			"cmpm" => "1", 
			"debug" => "1", 
			"dec" => "1", 
			"div" => "1", 
			"do" => "1", 
			"debugcc" => "1", 
			"debughs" => "1", 
			"debugcs" => "1", 
			"debuglo" => "1", 
			"debugec" => "1", 
			"debugeq" => "1", 
			"debuges" => "1", 
			"debugge" => "1", 
			"debuggt" => "1", 
			"debuglc" => "1", 
			"debugle" => "1", 
			"debugls" => "1", 
			"debuglt" => "1", 
			"debugmi" => "1", 
			"debugne" => "1", 
			"debugnr" => "1", 
			"debugpl" => "1", 
			"debugnn" => "1", 
			"enddo" => "1", 
			"eor" => "1", 
			"illegal" => "1", 
			"inc" => "1", 
			"jclr" => "1", 
			"jmp" => "1", 
			"jsclr" => "1", 
			"jset" => "1", 
			"jsr" => "1", 
			"jsset" => "1", 
			"jcc" => "1", 
			"jhs" => "1", 
			"jcs" => "1", 
			"jlo" => "1", 
			"jec" => "1", 
			"jeq" => "1", 
			"jes" => "1", 
			"jge" => "1", 
			"jgt" => "1", 
			"jlc" => "1", 
			"jle" => "1", 
			"jls" => "1", 
			"jlt" => "1", 
			"jmi" => "1", 
			"jne" => "1", 
			"jnr" => "1", 
			"jpl" => "1", 
			"jnn" => "1", 
			"jscc" => "1", 
			"jshs" => "1", 
			"jscs" => "1", 
			"jslo" => "1", 
			"jsec" => "1", 
			"jseq" => "1", 
			"jses" => "1", 
			"jsge" => "1", 
			"jsgt" => "1", 
			"jslc" => "1", 
			"jsle" => "1", 
			"jsls" => "1", 
			"jslt" => "1", 
			"jsmi" => "1", 
			"jsne" => "1", 
			"jsnr" => "1", 
			"jspl" => "1", 
			"jsnn" => "1", 
			"lsl" => "1", 
			"lsr" => "1", 
			"lua" => "1", 
			"mac" => "1", 
			"macr" => "1", 
			"move" => "1", 
			"movec" => "1", 
			"movem" => "1", 
			"movep" => "1", 
			"mpy" => "1", 
			"mpyr" => "1", 
			"neg" => "1", 
			"nop" => "1", 
			"norm" => "1", 
			"not" => "1", 
			"or" => "1", 
			"ori" => "1", 
			"rep" => "1", 
			"reset" => "1", 
			"rnd" => "1", 
			"rol" => "1", 
			"ror" => "1", 
			"rti" => "1", 
			"rts" => "1", 
			"sbc" => "1", 
			"stop" => "1", 
			"sub" => "1", 
			"subl" => "1", 
			"subr" => "1", 
			"swi" => "1", 
			"tfr" => "1", 
			"tst" => "1", 
			"tcc" => "1", 
			"ths" => "1", 
			"tcs" => "1", 
			"tlo" => "1", 
			"tec" => "1", 
			"teq" => "1", 
			"tes" => "1", 
			"tge" => "1", 
			"tgt" => "1", 
			"tlc" => "1", 
			"tle" => "1", 
			"tls" => "1", 
			"tlt" => "1", 
			"tmi" => "1", 
			"tne" => "1", 
			"tnr" => "1", 
			"tpl" => "1", 
			"tnn" => "1", 
			"wait" => "1", 
			"by" => "2", 
			"downto" => "2", 
			"then" => "2", 
			"to" => "2", 
			".break" => "2", 
			".continue" => "2", 
			".else" => "2", 
			".endf" => "2", 
			".endi" => "2", 
			".endl" => "2", 
			".endw" => "2", 
			".for" => "2", 
			".if" => "2", 
			".loop" => "2", 
			".repeat" => "2", 
			".until" => "2", 
			".while" => "2", 
			"baddr" => "3", 
			"bsb" => "3", 
			"bsc" => "3", 
			"buffer" => "3", 
			"cobj" => "3", 
			"comment" => "3", 
			"dc" => "3", 
			"dcb" => "3", 
			"define" => "3", 
			"ds" => "3", 
			"dsm" => "3", 
			"dsr" => "3", 
			"dup" => "3", 
			"dupa" => "3", 
			"dupc" => "3", 
			"dupf" => "3", 
			"else" => "3", 
			"end" => "3", 
			"endbuf" => "3", 
			"endif" => "3", 
			"endm" => "3", 
			"endsec" => "3", 
			"equ" => "3", 
			"exitm" => "3", 
			"fail" => "3", 
			"force" => "3", 
			"global" => "3", 
			"himem" => "3", 
			"ident" => "3", 
			"if" => "3", 
			"include" => "3", 
			"list" => "3", 
			"local" => "3", 
			"lomem" => "3", 
			"lstcol" => "3", 
			"maclib" => "3", 
			"macro" => "3", 
			"mode" => "3", 
			"msg" => "3", 
			"nolist" => "3", 
			"opt" => "3", 
			"org" => "3", 
			"page" => "3", 
			"pmacro" => "3", 
			"prctl" => "3", 
			"radix" => "3", 
			"rdirect" => "3", 
			"scsjmp" => "3", 
			"scsreg" => "3", 
			"section" => "3", 
			"set" => "3", 
			"stitle" => "3", 
			"symobj" => "3", 
			"tabs" => "3", 
			"title" => "3", 
			"undef" => "3", 
			"warn" => "3", 
			"xdef" => "3", 
			"xref" => "3", 
			"@abs" => "4", 
			"@acs" => "4", 
			"@asn" => "4", 
			"@atn" => "4", 
			"@arg" => "4", 
			"@cel" => "4", 
			"@coh" => "4", 
			"@cos" => "4", 
			"@cvf" => "4", 
			"@cvi" => "4", 
			"@cvs" => "4", 
			"@cnt" => "4", 
			"@ccc" => "4", 
			"@chk" => "4", 
			"@ctr" => "4", 
			"@def" => "4", 
			"@flr" => "4", 
			"@fld" => "4", 
			"@frc" => "4", 
			"@exp" => "4", 
			"@int" => "4", 
			"@l10" => "4", 
			"@log" => "4", 
			"@lfr" => "4", 
			"@lng" => "4", 
			"@lun" => "4", 
			"@len" => "4", 
			"@lcv" => "4", 
			"@lst" => "4", 
			"@max" => "4", 
			"@min" => "4", 
			"@mac" => "4", 
			"@mxp" => "4", 
			"@msp" => "4", 
			"@rel" => "4", 
			"@pow" => "4", 
			"@pos" => "4", 
			"@rnd" => "4", 
			"@sgn" => "4", 
			"@sin" => "4", 
			"@snh" => "4", 
			"@sqt" => "4", 
			"@scp" => "4", 
			"@tan" => "4", 
			"@tnh" => "4", 
			"@unf" => "4", 
			"@xph" => "4", 
			"a" => "5", 
			"a0" => "5", 
			"a1" => "5", 
			"a2" => "5", 
			"b" => "5", 
			"b0" => "5", 
			"b1" => "5", 
			"b2" => "5", 
			"ccr" => "5", 
			"lc" => "5", 
			"la" => "5", 
			"m0" => "5", 
			"m1" => "5", 
			"m2" => "5", 
			"m3" => "5", 
			"m4" => "5", 
			"m5" => "5", 
			"m6" => "5", 
			"m7" => "5", 
			"mr" => "5", 
			"n0" => "5", 
			"n1" => "5", 
			"n2" => "5", 
			"n3" => "5", 
			"n4" => "5", 
			"n5" => "5", 
			"n6" => "5", 
			"n7" => "5", 
			"omr" => "5", 
			"pc" => "5", 
			"r0" => "5", 
			"r1" => "5", 
			"r2" => "5", 
			"r3" => "5", 
			"r4" => "5", 
			"r5" => "5", 
			"r6" => "5", 
			"r7" => "5", 
			"sr" => "5", 
			"sp" => "5", 
			"ssh" => "5", 
			"ssl" => "5", 
			"x" => "5", 
			"x0" => "5", 
			"x1" => "5", 
			"y" => "5", 
			"y0" => "5", 
			"y1" => "5", 
			"(r0)" => "5", 
			"(r1)" => "5", 
			"(r2)" => "5", 
			"(r3)" => "5", 
			"(r4)" => "5", 
			"(r5)" => "5", 
			"(r6)" => "5", 
			"(r7)" => "5", 
			"(r0)+" => "5", 
			"(r1)+" => "5", 
			"(r2)+" => "5", 
			"(r3)+" => "5", 
			"(r4)+" => "5", 
			"(r5)+" => "5", 
			"(r6)+" => "5", 
			"(r7)+" => "5", 
			"(r0)-" => "5", 
			"(r1)-" => "5", 
			"(r2)-" => "5", 
			"(r3)-" => "5", 
			"(r4)-" => "5", 
			"(r5)-" => "5", 
			"(r6)-" => "5", 
			"(r7)-" => "5", 
			"(r0)+n0" => "5", 
			"(r1)+n1" => "5", 
			"(r2)+n2" => "5", 
			"(r3)+n3" => "5", 
			"(r4)+n4" => "5", 
			"(r5)+n5" => "5", 
			"(r6)+n6" => "5", 
			"(r7)+n7" => "5", 
			"(r0)-n0" => "5", 
			"(r1)-n1" => "5", 
			"(r2)-n2" => "5", 
			"(r3)-n3" => "5", 
			"(r4)-n4" => "5", 
			"(r5)-n5" => "5", 
			"(r6)-n6" => "5", 
			"(r7)-n7" => "5", 
			"-x0" => "5", 
			"-x1" => "5", 
			"-y0" => "5", 
			"-y1" => "5", 
			"-(r0)" => "5", 
			"-(r1)" => "5", 
			"-(r2)" => "5", 
			"-(r3)" => "5", 
			"-(r4)" => "5", 
			"-(r5)" => "5", 
			"-(r6)" => "5", 
			"-(r7)" => "5", 
			"+x0" => "5", 
			"+x1" => "5", 
			"+y0" => "5", 
			"+y1" => "5");

// Special extensions

// Each category can specify a PHP function that returns an altered
// version of the keyword.
        
        

$this->linkscripts    	= array(
			"1" => "donothing", 
			"2" => "donothing", 
			"3" => "donothing", 
			"4" => "donothing", 
			"5" => "donothing");
}


function donothing($keywordin)
{
	return $keywordin;
}

}?>
