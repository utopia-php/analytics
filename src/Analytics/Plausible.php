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

    /**
     * @param string $clientIP
     * Specifies the  client's IP address used for CLI mode
     * 
     * @return  PlausibleAdapter
     */

    public function __construct(string $useragent, string $clientIP = '127.0.0.1'){
        $this->useragent = $useragent;

    $this->headers=array(' X_FORWARDED_FOR: '  .$clientIP , ' Content-Type: application/json ');
  }

    /**
     * Sends an event to Plausible.
     * 
     * @param string $url
     * URL of the page where the event was triggered.
     * 
     * @param string $domain
     * Domain name of the site in Plausible.
     * 
     * @return bool
     */
    public function createEvent(string $url,string $domain): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'url' => $url,
            'domain' =>$domain,
            'name' => 'event'
        ];

        $this->createConnection($query);
        return true;
    }



/**
     * Sends a page view to Plausible.
     * 
     * @param string $url
     * URL of the page where the event was triggered.
     * 
     * @param string $domain
     * Domain name of the site in Plausible.
     * 
     * 
     * @return bool
     */
  public function createPageView(string $url,string $domain):bool {
     if(!$this->enabled){
         return false;
     }
    $query=[
        'name' =>'pageview',
        'url' => $url,
        'domain' =>$domain
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
       curl_setopt($ch,CURLOPT_USERAGENT,$this->useragent);
       curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($query));

   }

}

