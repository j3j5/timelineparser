<?php

/**
 * TimelineParser
 *
 * Parse the timeline of Twitter to extract old tweet info.
 *
 * @author Julio Foulquié
 * @version 0.1.0
 *
 *
 */

namespace j3j5;

use \Curl\Curl;

class TimelineParser {

	private static $user_timeline_url_base = "https://twitter.com/i/profiles/show/";
	private static $search_timeline_url_base = "https://twitter.com/i/search/timeline?q=";

	private $curl;

	private $username;
	private $user;
	private $tweets;

	private static $attr = array(
		'tweet_id'	=> 'data-item-id',
		'timestamp'	=> 'data-time',
		'username'	=> 'data-screen-name',
		'name'		=> 'data-name',
		'user_id'	=> 'data-user-id',
	);


	public function __construct() {
		$this->curl = new Curl();
		$this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
		$this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
	}

	public function get_timeline() {
		$this->username = "0001Julio";
		$this->user['screen_name'] = $this->username;
		$parameters = array(
			"count" => 200,
			"contextual_tweet_id" => 568003962030436352,
			"include_available_features" => 0,
			"max_id" => 568003962030436352,
		);
		$url = self::$user_timeline_url_base .  "{$this->username}/timeline?" . http_build_query($parameters);
		$response = $this->request($url);
		if(isset($response['items_html']) && is_string($response['items_html'])) {

			$dom = \SimpleHtmlDom\str_get_html($response['items_html']);

			foreach($dom->find("div[class=ProfileTweet]") AS $tweet) {
				$this->extract_user_info($tweet);
				$this->extract_tweet_info($tweet);
			}
			var_dump($this->user);
			var_dump($this->tweets);
		} else {
			var_dump($response);
			exit;
		}
	}

	private function extract_tweet_info(&$tweet) {
		$tweet_id = self::$attr['tweet_id'];
		$timestamp = self::$attr['timestamp'];
		$username = self::$attr['username'];

		$id = (isset($tweet->$tweet_id) && !empty($tweet->$tweet_id)) ? $tweet->$tweet_id : 0;
		// Don't process twice the same tweet
		if(isset($this->tweets[$id])) {
			return;
		}
		$avatar = $tweet->find('img[class=ProfileTweet-avatar]', 0)->src;
		if(!is_string($avatar)) {
			$avatar = '';
		}
		$created_at = $tweet->find('span[class=js-short-timestamp]', 0)->$timestamp;
		if(!is_numeric($created_at)) {
			$created_at = 0;
		}

		$text= $tweet->find("p[class=ProfileTweet-text]",0)->innertext;
		if(!is_string($text)) {
			$text = '';
		}

		if($this->username == $tweet->$username) {
			$user = $this->user;
		} else {
			$user = $this->extract_user_info($tweet, TRUE);
		}

		$this->tweets[$id] = array(
			'tweet_id'	=> $tweet->$tweet_id,
			'avatar'	=> $avatar,
			'created_at'	=> date("D M d H:i:s O Y" , $created_at),
			'text'	=> $text,
			'user'	=> $user,
		);
	}

	private function extract_user_info(&$tweet, $export_result = FALSE) {
		$username = self::$attr['username'];
		$name = self::$attr['name'];
		$user_id = self::$attr['user_id'];
		$user = array();

		if(!$export_result && !empty($this->user) && ($tweet->$username) && $tweet->$username !== $this->username) {
			// This is probably a retweet or something, so don't extract user info from here!
			return;
		}

		if(isset($tweet->$username) && is_string($tweet->$username)) {
			$user['screen_name'] = $tweet->$username;
		}

		if(isset($tweet->$user_id) && is_numeric($tweet->$user_id)) {
			$user['user_id'] = $tweet->$user_id;
		}

		if(isset($tweet->$name) && is_string($tweet->$name)) {
			$user['name'] = $tweet->$name;
		}
		// !isset($this->user['name']) OR empty($this->user['name'])
		$avatar = $tweet->find('img[class=ProfileTweet-avatar]', 0)->src;
		if(is_string($avatar)) {
			$user['avatar'] = $avatar;
		}

		if($export_result) {
			return $user;
		}
		$this->user = $user;
	}

	private function request($url) {
		$referer = "https://twitter.com/{$this->username}";
		$this->curl->setOpt(CURLOPT_REFERER, $referer);

		$this->curl->get($url);

		if($this->curl->error) {
			return array('error' => $this->curl->error_message, 'error_code' => $this->curl->error_code);
		}
		return json_decode($this->curl->response, TRUE);
	}

	public function get_search() {
// 		. $keyword . '&count=' . $count;
	}
}
