<?php
require_once('twitteroauth/twitteroauth/twitteroauth.php');
require_once('config.php');
require_once('magic.php');
require_once('lib.php');

$path = dirname(__FILE__);

$flags=FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES;
$searches  = file("$path/whitelist.txt",$flags);
$bad_words = file("$path/blacklist.txt",$flags);

$user_wait_time = 86400; // time before responding to user again

/////////////////////////////////////////////////////////////////////////////////////////
date_default_timezone_set('America/New_York');

$state = json_decode(@file_get_contents("$path/STATE"), true);
if(!$state) {
    $state = array(
        'time'=>0,
        'users'=>array(),
        'consider'=>array(),
    );
}
$consider=$state['consider'];

$magic = new Magic();
$twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);



$twitter->host = "https://api.twitter.com/1/";
$tweets = $twitter->get('statuses/friends_timeline',array('count' => 140));
//$tweets = $twitter->get('statuses/mentions',array('include_rts' => false));


$tweets = array_filter($tweets, function($tweet) {
    global $bad_words, $searches;

    $tweet->score=0;


    foreach($bad_words as $word) {
        $flag = (($word==strtolower($word))?"i":'');
        if(preg_match("/$word/$flag",$tweet->text) != false) {
            //echo "REJECT($word): ", $tweet->text,"\n";
            $tweet->score-=250;
            return false;
            break;
        }
    }

    $time = strtotime($tweet->created_at);

    if($tweet->retweeted) $tweet->score-=20;
    if(!$tweet->user->following) $tweet->score-=250;
    if($tweet->in_reply_to_screen_name) $tweet->score-=100;
    $tweet->score+=300*palindrome($tweet->text);
    $tweet->score+=3*min(30,$tweet->retweet_count);
    $tweet->score+=50*($tweet->text==strtoupper($tweet->text));
    $tweet->score+=70*($tweet->text==strtolower($tweet->text));
    $tweet->score+=100*(strtoupper($tweet->text)==strtolower($tweet->text));
    $tweet->score+=10*preg_match('/  /',$tweet->text);
    $tweet->score+=800*preg_match('/stolas/i',$tweet->in_reply_to_screen_name);
    $tweet->score-=180*preg_match('/stolas/i',$tweet->user->screen_name);
    $tweet->score+=40*preg_match('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', $tweet->text);
    $tweet->score+=30*($tweet->source!='web');
    $tweet->score+=0.003*min(10000,$tweet->user->statuses_count);
    $tweet->score+=0.03*min(1000,$tweet->user->favourites_count);
    $tweet->score-=20*$tweet->user->verified;

    $ok = 0;

    foreach($searches as $word) {
        $flag = (($word==strtolower($word))?"i":'');
        if(preg_match("/$word/$flag",$tweet->text) != false) {
            //echo "ACCEPT($word): ", $tweet->text,"\n";
            $tweet->score+=$ok?50:200;
            if($ok>4) {
                echo "stop gaming\n";
                print_r($tweet);
                return false;
            }
            $ok++;
        }
    }


    return $ok>0;
    return $tweet->score>0;
});


$used=array();
$time_parts=localtime(time(),true);
$yes=false;
$allowed=false;

foreach($tweets as $tweet) {
    if(in_array($tweet,$used)) break;

    $user=$tweet->user->screen_name;
    $time = strtotime($tweet->created_at);
    $considerable=($time>@$state['consider'][$user]);
    $consider[$user]=max($time,@$consider[$user]);

    $difficulty = 1000
        + 2250 *( !@$state['consider'][$user] ) // be much less likely with new tweeters
        +  250 *( $yes&&$allowed ) // be less likely with sequential tweets
        -  200 *( time() - $state['time'] > $user_wait_time ) // be more likely if we havent succeeded in a while
        -  150 *( time() - $time <= 60 ) // be more likely if the tweet was in last 60 seconds
        -  100 *( time() - $time <= 300 ) // be more likely if the tweet was in last 5 min
        +  550 *( !in_array($time_parts['tm_wday'],array(0,6)) && in_array($time_parts['tm_hour'],range(6,20)) ) // be more difficult during work week
        -  350 *( in_array($time_parts['tm_hour'],range(0,4)) ) // more late night
    ;
    $yes=$tweet->score > rand(0,$difficulty);

    $allowed=
        $time-@$state['users'][$user]>$user_wait_time &&
        time()-@$state['users'][$user]>$user_wait_time &&
        time()-$time<$user_wait_time;

    $txt = $magic->evolve($tweet->text,$tweet->score*($yes&&$allowed&&$considerable&&time()%2&&rand(0,1))); // only run evolve if we can post
    $status = '@'. $user." $txt";

    $params['status']=$status;
    $params=array(
        'in_reply_to_status_id'=>$tweet->id_str,
        'status'=>$status,
    );

    if($yes && strlen($status)<=140 && $allowed && $considerable){
            $used[]=$tweet;
            $state['users'][$user]=time();
            $twitter->post('statuses/update', $params); $state['time']=time();
    }

    file_put_contents("$path/STATE",json_encode($state));

    if(true/*&&$allowed&&$yes&&$considerable*/) {
        echo "DIFF=$difficulty,SCORE={$tweet->score},CONSIDER=$considerable,YES=$yes,ALLOW=$allowed\n";
        echo $tweet->user->screen_name, ":: ", $tweet->text,"\n";
        echo $status,"\n\n";
        //sleep(rand(60,120));
    }
    if($yes&&$allowed)
        sleep(5);

}
$state['consider']=$consider;
file_put_contents("$path/STATE",json_encode($state));
