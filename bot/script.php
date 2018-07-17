<?php

namespace BotBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use BotBundle\Entity\TelegramUser;
use BotBundle\Entity\AntiSpam;

class ReCoalController extends Controller
{
    public function callAction()
    {
      //return new JsonResponse(array("status"=>200));
      ini_set('allow_url_fopen', 1);
	  $botName = "ReCoaltestbot";
      $botToken = "645390804:AAFzso1dS4VKqaUmowT5ny17BfY50ssZwTI";
      $website = "https://api.telegram.org/bot".$botToken;
      $update = file_get_contents("php://input");
      $update = json_decode($update,true);

      $text = $update["message"]["text"];
      $chat_id = $update["message"]["chat"]['id'];
      $message_id = $update["message"]["message_id"];

      $textReturn = "";
      $send = $remove = false;
      $text = explode('@',$text);
      $username = $update["message"]["from"]['username'];
      if(trim($username) == "" || $username == false){
        $username = ' '.$update["message"]["from"]['first_name'];
      }
      $textReturn = "@".$username."\n";

      $user_id = $update["message"]["from"]['id'];
      $em = $this->getDoctrine()->getManager();
      if($user_id){
        $chat_name = $update["message"]["chat"]['username'];
        $oTelegramUser = $em->getRepository('BotBundle:TelegramUser')->findOneBy(array('userId'=>$user_id, 'chatId'=>$chat_name));
        if(!$oTelegramUser){
          $oTelegramUser = new TelegramUser();
          $oTelegramUser->setChatId($chat_name);
          $oTelegramUser->setUserId($update["message"]["from"]['id']);
          $oTelegramUser->setToken($this->generateSelector());
          $oTelegramUser->setIsBot($update["message"]["from"]['is_bot']);
          $oTelegramUser->setFirstname($update["message"]["from"]['first_name']);
          $oTelegramUser->setLastname($update["message"]["from"]['last_name']);
          $oTelegramUser->setUsername($update["message"]["from"]['username']);
          $oTelegramUser->setCountMessage(1);
          $em->persist($oTelegramUser);
          $em->flush();
          $text[0] = "/welcome";
        }else {
          $oTelegramUser->setCountMessage($oTelegramUser->getCountMessage()+1);
          $em->persist($oTelegramUser);
          $em->flush();
        }
      }

      switch(trim($text[0])){
        case '/spam':
        if($update["message"]["chat"]['type'] == "supergroup"){
          
          $remove = true;
          $messageReplyTo = $update["message"]["reply_to_message"]['message_id'];
          $userToBan = $update["message"]["reply_to_message"]['from']['id'];
          $spammer = $em->getRepository('BotBundle:TelegramUser')->findOneBy(array('userId'=>$userToBan));
          $from = $em->getRepository('BotBundle:TelegramUser')->findOneBy(array('userId'=>$user_id));
          $oSpam = $em->getRepository('BotBundle:AntiSpam')->findOneBy(array('from'=>$from,'messageId'=>$messageReplyTo));
          if(!$oSpam){
            $oAntiSpam = new AntiSpam();
            $oAntiSpam->setFrom($from);
            $oAntiSpam->setSpammer($spammer);
            $oAntiSpam->setMessageId($messageReplyTo);
            $em->persist($oAntiSpam);
            $em->flush();
          }
          $oSpams = $em->getRepository('BotBundle:AntiSpam')->findBy(array('messageId'=>$messageReplyTo,'spammer'=>$spammer));
          if(count($oSpams) > 2){
             $urlDel = $website."/deleteMessage?chat_id=".$chat_id."&message_id=".$messageReplyTo;
   		       $deleteResponse = file_get_contents($urlDel);

             $messageBan = "@".$spammer->getUsername()." : Please do not spam !";
             $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($messageBan));
          }

        }
        break;

        case '/support':

          
		      $remove = true;

          $textReturn .= "⛑  support@recoal.org";
          $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));
        break;

        case '/help':
        $remove = true;
        $textReturn .= "🛡 Commands list: \n\n";
        $textReturn .= "/start - Welcome to ReCoal Coin\n";
        $textReturn .= "/about - Get info about ReCoal Coin\n";
        $textReturn .= "/current - Current Volume and Price\n";
        $textReturn .= "/supply - Get supply informations\n";

        $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));
        break;

        case '/donate':
        $remove = true;
        $textReturn .= "Donate to support developments\n";
        $textReturn .= "@pranavms on OpenAlias <strong>donate.recoal.org</strong> \n";
        $textReturn .= "Thank you ! \n";


        $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));

        break;
        case '/current':
        $sMarket = $this->get('market_service');
        $oInfos = $sMarket->getTicker("SUP");

        $remove = true;
        /*$textReturn .= "📣  1 RECL is worth :\n";
        $textReturn .= "  •  "."<strong>".$oInfos->global->price."</strong> BTC \n";
        $textReturn .= "  •  "."<strong>".$oInfos->global->priceUsd."</strong> USD \n";
        $textReturn .= "  •  "."<strong>".$oInfos->global->priceEuro."</strong> EUR \n";
        $textReturn .= "📊  24H Volume :\n";
        $textReturn .= "  •  "."<strong>".$oInfos->global->volume."</strong> SUP \n";
        $textReturn .= "  •  "."<strong>".$oInfos->global->volumeBtc."</strong> BTC \n";
        $textReturn .= "  •  "."<strong>".$oInfos->global->volumeUsd."</strong> USD \n";
        $textReturn .= "  •  "."<strong>".$oInfos->global->volumeEuro."</strong> EUR \n";*/
        $textReturn .= " Comming Soon ..! "
        $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));

        break;
        case '/about':
        $remove = true;
        $textReturn .= "<a href='https://recoal.org'>ReCoal Coin</a> is a secure, private, untraceable currency upgrade for BitCOAL\n";
        $textReturn .= "  •  "."<strong>CryptoNight V7</strong> Proof of Work (PoW) algorithm\n";
  		  $textReturn .= "  •  "."<strong>Block time : </strong> 2 minutes\n";
		    //$textReturn .= "  •  "."<strong>Circulating supply : </strong> 144 Millions\n";
  		  $textReturn .= "  •  "."<strong>Total supply : </strong> 18,446,744 RECL\n";
  		  $textReturn .= "  •  "."<strong>Emition Rate : </strong> 35+ years\n";
  		  $textReturn .= "  •  "."<strong>Pre-Mine : </strong> 2 Million (for Giveaways, charities and Future Development\n";
        $textReturn .= "🔎  Get /supply informations \n\n";

        $textReturn .= "🏛  <a href='https://recoal.org'>Official website</a>\n";
        $textReturn .= "⛏  <a href='https://devpool.recoal.org'>Official Pool website</a>\n";
        $textReturn .= "🔐  <a href='https://recoal.org/#wallet'>Wallets</a> or <a href='https://wallet.recoal.org'>Web wallet</a>\n\n";
        //$textReturn .= "🚀  Earn Coins doing Task with <a href='https://kryptonia.io'>Kryptonia</a>\n";
        $textReturn .= "🗣  Go to the <a href='https://bitcointalk.org/index.php?topic=4429386'>Bitcoin talk RECL</a> thread\n";
        $textReturn .= "💪  Get /current RECL prices right now \n";
        //$textReturn .= "📈  Get the ReCoal project /roadmap \n";
        $textReturn .= "🗺️  Get live /exchangerates or /volume datas\n";
        $textReturn .= "🚀  Get last /developments";
        $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn)."&disable_web_page_preview=true");
        break;
        case '/welcome':
		  // $remove = true;
		 $textReturn .= "Welcome to the <strong>ReCoal</strong> group.\n";
		 $textReturn .= "Here are a few useful resources /about ReCoal \n";
		 $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));
	   break;
		 case '/start':

		$remove = true;
          $textReturn .= "Welcome to the <strong>ReCoal</strong> group.\n";
          $textReturn .= "Here are a few useful resources /about ReCoal \n";
          $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));
        break;

        case '/poolhashrate':
          
		  $remove = true;
          $pool = $this->curl("https://devpool.recoal.org:8117/stats");
          $pool = json_decode($pool, TRUE);
          $textReturn .= "<a href='https://devpool.recoal.org'>Official ReCoal Pool</a> hashrate : <strong>".$this->convertHashrate($pool['pool']['hashrate'])."</strong>";
          $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));
        break;
        case '/onlinewallet':
          
		  $remove = true;
          $link = 'Use our fast and secure <a href="https://wallet.recoal.org">Online wallet</a>';
          $textReturn .= $link;
          $sendResponse = file_get_contents($website."/sendmessage?parse_mode=HTML&chat_id=".$chat_id."&text=".urlencode($textReturn));
        break;

      }
	  if($remove) {
          $urlDel = $website."/deleteMessage?chat_id=".$chat_id."&message_id=".$message_id;
		       $deleteResponse = file_get_contents($urlDel);

      }
      return new JsonResponse(array("status"=>200));

    }

    private function curl($url){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,$url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	  curl_setopt($ch, CURLOPT_ENCODING, '');
      $return = curl_exec($ch);
      curl_close ($ch);
      return $return;
  }

}
