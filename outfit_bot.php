<?php
require_once('outfit.php');
class OutfitBot
{
    public $state;

    const INTERVAL = 86400;

    protected $patterns;

    public function __construct($state,$twitter)
    {
        $this->state = $state;
        $this->twitter = $twitter;
        $path = dirname(__FILE__);
        $flags=FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES;
        $this->patterns = file("$path/outfit.txt",$flags);

    }

    public function execute($tweets)
    {
        $state = $this->state;
        $outfit = new Outfit;

        $used = array();
        foreach($tweets as $tweet) {
            foreach($this->patterns as $pattern) {
                if($tweet->score >= 0 && $tweet->score < 300 && preg_match("@\s*$pattern\s*@i",$tweet->text)) {
                    $used[$tweet->text]=$tweet;
                    //echo $tweet->text,"\n";
                }
            }
        }

        foreach($used as $tweet) {
            $txt = $outfit->generate();
            $user=$tweet->user->screen_name;
            $status = '@'. $user." $txt";
            $time = strtotime($tweet->created_at);
            if($time-@$state[$user]>static::INTERVAL) {
                $params=array(
                    'in_reply_to_status_id'=>$tweet->id_str,
                    'status'=>$status,
                );
                if(strlen($params['status'])<=140) {
                    $this->twitter->post('statuses/update', $params);
                    $state[$user]=$time;
                }
            }
        }

        $this->state = $state;
        return $used;
    }

}
