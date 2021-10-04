<?php
/**
 * Utopia PHP Framework
 *
 * @package Analytics
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @version 1.0 RC1
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics;

class  PlausibleAdapter extends Analytics
{
  private const URL='https://plausible.io/api/event';


  public function __construct(string $clientIP){
    $clientIP=$_SERVER['REMOTE_ADDR'];
    $this->headers=array(' X_FORWARDED_FOR: '  .$clientIP , ' Content-Type: application/json ');
  }

  public function createPageView(string $url,string $domain):bool {
     if(!$this->enabled){
         return false;
     }
    $query=[
        '__name' =>'pageview',
        '__url' => $url,
        '__domain' =>$domain
    ];
    $this->createConnection($query);
    return true;
   }

  private function  createConnection(array $query): void {
     $ch=curl_init();
     $this->createCurlCommand($ch,$query);
     curl_exec($ch);
     curl_close($ch);
     
   }
   private function createCurlCommand(&$ch,array $query):void{
       curl_setopt($ch,CURLOPT_URL,self::URL);
       curl_setopt($ch,CURLOPT_POST,true);
       curl_setopt($ch,CURLOPT_HTTPHEADER ,$this->headers);
       curl_setopt($ch,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
       curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($query));

   }

}

