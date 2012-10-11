<?php;
error_reporting(E_ALL);
// http://mochakimono.chipx86.com/agen2.html;
abstract class Base
{

    protected $aCheckArray;
    protected $aCheckText;
    protected $aItemWords;
    protected $aItemCodes;

    //Regular functions;

    protected function getNumber($aCurrArray, $intCheckNumber)
    {
        $intReturn; $intLooper;
        $bEnd=false;

        while ($bEnd==false)
        {
            $intReturn=rand(0,count($this->aItemCodes)-1);

            if ((@$this->aItemCodes[$intReturn]  &  $intCheckNumber)==$intCheckNumber)
            {
                $bEnd=true;
            }

            for ($intLooper=0;$intLooper<count($aCurrArray);$intLooper++)
            {
                if ($aCurrArray[$intLooper]==$intReturn)
                {
                    $bEnd=false;
                }
            }
        }

        return $intReturn;
    }



    public function generate()
    {
        $aUseNumber=array();
        $intArrayUse;
        $strReturn="";
        $strPass;
        $intNumber=-1;
        $intLooper;
        $bEnd = false;

        $intArrayUse=rand(0, count($this->aCheckArray)-1);

        for ($intLooper=0;$intLooper<count($this->aCheckArray[$intArrayUse]);$intLooper++)
        {
            $aUseNumber[$intLooper]=-1;
        }

        for ($intLooper=0;$intLooper<count($this->aCheckArray[$intArrayUse]);$intLooper++)
        {
                        $intNumber=$this->getNumber($aUseNumber,$this->aCheckArray[$intArrayUse][$intLooper]);
                        $aUseNumber[$intLooper]=$intNumber;
        }

        $strReturn = $this->aCheckText[$intArrayUse][0];

        for ($intLooper=0;$intLooper<count($aUseNumber);$intLooper++)
        {
            if ($aUseNumber[$intLooper]>-1)
            {
                $strReturn.= $this->aItemWords[$aUseNumber[$intLooper]];
                $strReturn.= $this->aCheckText[$intArrayUse][$intLooper+1];
            }
        }

        return trim($strReturn);
    }

    public function evolve($input, $rounds = 1)
    {
        $output= '';
        $score = -1000;
        $rounds=max(1,$rounds);

        //echo "EVOLVE($rounds): $input = ";

        $input_set=soundex_collect($input);

        for($i=0;$i<$rounds;$i++) {
            $newstr = $this->generate();
            $newset=soundex_collect($newstr);
            $newscore = 12.3*count(array_intersect($newset,$input_set)) -2*(strlen($newstr)>70) -6*(strlen($newstr)>130) +2*(strlen($newstr)>30);
            if($newscore>$score) {
                $score=$newscore;
                $output=$newstr;
            }
        }

        return $output;
    }
}
