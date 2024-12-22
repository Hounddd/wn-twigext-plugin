<?php

use Cache;
use Mercator\TwigExt\Models\Settings;

function telegramGetChats( $bot, $title = null ) {

		if (is_numeric($title))
			return $title;
		
		if ($chat=Cache::get("mer-port-chat-$bot-$title"))
			return $chat;

		$ch = curl_init( "https://api.telegram.org/bot$bot/getUpdates" );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$result = curl_exec( $ch );
		curl_close( $ch );
		$result = json_decode( $result, true );

		$chats = array();
		foreach ( $result["result"] as $key => $ritem ) {
			if ( array_key_exists( "message", $ritem ) && array_key_exists( "chat", $ritem["message"] ) )
			   $chats[$ritem["message"]["chat"]["id"]] = $ritem["message"]["chat"]["title"];
		}

		if ( $title ) {
			$key = array_search( $title, $chats );
			if ( $key !== false ) {
				Cache::put("mer-port-chat-$bot-$title", $key, 60*60 );
				return $key;
			} else
			return null ;
		} else
		return $chats;
	}

$functions += [

	//
	// Mail
	//
	'mailMessage' => function ($message, $to=null) {
		if (!$to || empty($to))
			$to=Settings::get('mail_default');
		 Mail::rawTo($to, $message );
		return "";
	},

	//
	// TELEGRAM
	//

	'telegramIP' => function ($text, $bot=null, $chat=null, $time=3600) {

		if (empty($text)) 
			return "";
		if (!$bot || empty($bot))
			$bot=Settings::get('telegram_bot');
		if (!$chat || empty($chat))
			$chat=Settings::get('telegram_chat');

		$chat=telegramGetChats($bot, $chat);

		if (empty($text) or (!$bot))
			return "Could not send Telegram Maessage";

		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip_address = $_SERVER['HTTP_CLIENT_IP'];
		}
		//whether ip is from proxy
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		//whether ip is from remote address
		else {
			$ip_address = $_SERVER['REMOTE_ADDR'];
		}

		$new = false;
		$IPaddress = Cache::get("CACHED-USER-IP-" . $ip_address);

		if (!$IPaddress) {

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "http://ip-api.com/php/$ip_address?fields=country,regionName,city",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"accept: application/json",
					"content-type: application/json"
				) ,
			));

			$IPaddress = curl_exec($curl);
			Cache::add("CACHED-USER-IP-" . $ip_address, $IPaddress, $time); // cache for 60 minutes by default or whatever the user specified
			$new = true;
			curl_close($curl);

		}

		$response = unserialize($IPaddress);

		$text = ($text . "\nFrom  " . $response["city"] . ", " . $response["country"]);

		$website="https://api.telegram.org/bot".$bot;
                $params=[ 'chat_id'=>$chat, 'text'=>$text ];
                $ch = curl_init($website . '/sendMessage');
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($ch);
                curl_close($ch);

		return "";
	},

	'telegram' => function ($text, $bot=null, $chat=null) {

		if (!$bot || empty($bot))
			$bot=env('TELEGRAM_BOT', null);
		if (!$chat || empty($chat))
			$chat=env('TELEGRAM_CHAT', null);
		
		$chat=telegramGetChats($bot, $chat);

        if (empty($text) || (!$bot) || (!$chat))
            return "No message or no BOT";

  		$website="https://api.telegram.org/bot".$bot;
  		$params=[ 'chat_id'=>$chat, 'text'=>$text ];
  		$ch = curl_init($website . '/sendMessage');
  		curl_setopt($ch, CURLOPT_HEADER, false);
  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  		curl_setopt($ch, CURLOPT_POST, 1);
  		curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
  		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  		$result = curl_exec($ch);
  		curl_close($ch);

		return "";
		// return ("X $website/sendMessage&chat_id=$chat&text=$text XX $result X"); 
	},
	
	'telegramChats' => function ($bot=null) {

		if (!$bot || empty($bot))
			$bot=env('TELEGRAM_BOT', null);

  		$website="https://api.telegram.org/bot$bot";
  		$params=[ ];
  		$ch = curl_init($website . '/getUpdates');
  		curl_setopt($ch, CURLOPT_HEADER, false);
  		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  		curl_setopt($ch, CURLOPT_POST, 1);
  		// curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
  		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  		$result = curl_exec($ch);
  		curl_close($ch);

		$result = json_decode($result, true);
		$result = $result["result"];
		
		// return print_r($result, true);
		
		$chats = array();
		foreach ($result as $key => $ritem) {
			if (array_key_exists("message", $ritem) && array_key_exists("chat", $ritem["message"]))
				// $chats[] = $ritem["message"]["chat"];
				 $chats[$ritem["message"]["chat"]["id"]] = $ritem["message"]["chat"]["title"];
			
		}
		return print_r($chats, true);
		
	},

];

?>
