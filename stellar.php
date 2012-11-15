<?php
require_once('json.php');
class Stellar extends Json
{

    protected $json, $ok;

    public function __construct()
    {
        parent::__construct();
        $this->ok = false;
        $json=file_get_contents($this->path."/stellar-database.json");
        if($json===FALSE) return;
        $json=json_decode($json,true);
        if($json===FALSE) return;
        //print_r($json);exit;
        $this->json=$json;
        $this->ok=true;
    }

    public function generate()
    {
        if(!$this->ok) return;
        $star = $this->seek();

        $poe = $star['points_of_interest'];
        foreach(array_rand($star,rand(2,min(2,count($star)))) as $key) {
            if(is_string($star[$key])) {
                $poe .= " {$star[$key]}.";
            }
        }

        $names = array();
        if(isset($star['catalog_numbers'])) $names = $star['catalog_numbers'];
        $poe .= ' '. $names[array_rand($names)];
        $names[] = $star['name'];
        $names[] = $star['name'];
        $names[] = $star['name'];
        $name = $names[array_rand($names)];

        $poe = preg_replace("/^$name /", '', $poe);
        $poe = preg_replace("/^{$star['name']} /", '', $poe);

        $str = $name .': ';
        //print_r($star);exit;

        $poe= preg_replace('/ and /', ' & ',$poe);
        $str.=$this->factoid($poe,140-strlen($str));
        $str = preg_replace('/  /', ' ',$str);
        $str = preg_replace('/ [.]/', ' .',$str);
        $str = preg_replace('/ [,]/', ' ,',$str);
        $str= preg_replace('/ and /', ' & ',$str);
        return $str;
    }

    protected function seek()
    {
        for($count=0; $count<1000; $count++) {
            $star = $this->json[array_rand($this->json)];
            if(isset($star['points_of_interest']))
                return $star;
        }
        return;
    }

}
