<?php
function mikhmonOnline_MetaData()
{
   return array(
       'DisplayName' => 'Mikhmon Online',
       'language' => 'english',
   );
}


function mikhmonOnline_apiCurl($params,$data,$url,$method){

    $postdata = json_encode($data);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/'.$url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-type: application/json','X-Auth-Email: email','X-Auth-Key: apikey'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);   
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);
    $array = json_decode($answer,TRUE);
    curl_close($curl);
    return $array;
}

function extract_domain($domain)
{
    if(preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches))
    {
        return $matches['domain'];
    } else {
        return $domain;
    }
}

function extract_subdomains($domain)
{
    $subdomains = $domain;
    $domain = extract_domain($subdomains);

    $subdomains = rtrim(strstr($subdomains, $domain, true), '.');

    return $subdomains;
}


function get_domain($host){
  $myhost = strtolower(trim($host));
  $count = substr_count($myhost, '.');
  if($count === 2){
    if(strlen(explode('.', $myhost)[1]) > 3) $myhost = explode('.', $myhost, 2)[1];
  } else if($count > 2){
    $myhost = get_domain(explode('.', $myhost, 2)[1]);
  }
  return $myhost;
}

function mikhmonOnline_CreateAccount($params) {
  if ($params['server'] == 1) {
    $username = $params["username"];
    $password = $params["serverpassword"];
    $domain = $params["domain"];
    $getdomain = get_domain($domain);
    $getsubdomain = extract_subdomains($domain);
    $versiMikhmon = $params["customfields"]["Versi Mikhmon"];

    #create user
     $connection = ssh2_connect($params['serverip'], 22);
     ssh2_auth_password( $connection, "root", $password );                   
      $stream = ssh2_exec( $connection, "./addvhost.sh -u $domain -d $domain -v $versiMikhmon -p $username " );
      stream_set_blocking( $stream, true );
      $stream_out = ssh2_fetch_stream( $stream, SSH2_STREAM_STDIO );
      $output = [];
      while($buffer = fgets($stream_out)) {
          $output[] .= $buffer;
      }
      fclose($stream);
      $result = end($output);

    #create dns record
    if ($getdomain == "domain1") {
     $data = array(
        'type' => 'A',
        'name' => $getsubdomain,
        'content' => $params["serverip"],
        'ttl' => 1,
        'proxied' => true
    );
      $url = 'zones/zones_id/dns_records';
      $method = 'POST';
      $result = mikhmonOnline_apiCurl($params,$data,$url,$method);
      $dnsid = $result['result']['id'];
      #insert dnsid to database whmcs
      $query = "UPDATE tblhosting SET notes = '$dnsid' WHERE id = '$params[serviceid]'";
      $resultid = mysql_query($query);

      if ($result['success'] == true) {
        return 'success';
      } else {
        return $result['errors'][0]['message'];
      }
    } elseif ($getdomain == "domain2") {
      $data = array(
          'type' => 'A',
          'name' => $getsubdomain,
          'content' => $params["serverip"],
          'ttl' => 1,
          'proxied' => true
      );
        $url = 'zones/zones_id/dns_records';
        $method = 'POST';
        $result = mikhmonOnline_apiCurl($params,$data,$url,$method);
        if ($result['success'] == true) {
          return 'success';
        } else {
          return 'API Cloudflare Problem';
        }
      } else {
       return 'API Cloudflare Problem';
    }
    
    return $result;
  }
}


function mikhmonOnline_TerminateAccount($params) {
      $serverip = $params['serverip'];

    #remove account and delete dns record
      $connection = ssh2_connect($serverip, 22);
      $password = $params["serverpassword"];
      $domain = $params["domain"];
      $getdomain = get_domain($domain);
      $getsubdomain = extract_subdomains($domain);

      #delete user
      ssh2_auth_password( $connection, 'root', $password );                   
      $stream = ssh2_exec( $connection, "./terminatevhost.sh -u $domain -d $domain");
      stream_set_blocking( $stream, true );
      $stream_out = ssh2_fetch_stream( $stream, SSH2_STREAM_STDIO );
      $output = [];
      while($buffer = fgets($stream_out)) {
          $output[] .= $buffer;
      }
      fclose($stream);
      $result = end($output);

      #delete dns record
      if ($getdomain == "domain1") {
        $data = array();
        $urlgetid = "zones/Zones_id/dns_records?type=A&name=$domain&content=$serverip&proxied=true&page=1&per_page=1&order=type&direction=desc&match=all";
        $methodgetid = 'GET';
        $resultgetid = mikhmonOnline_apiCurl($params,$data,$urlgetid,$methodgetid);
        $dnsid = $resultgetid['result'][0]['id'];
        $url = 'zones/zones_id/dns_records/'.$dnsid;
        $method = 'DELETE';
        $result = mikhmonOnline_apiCurl($params,$data,$url,$method);
       
      } elseif ($getdomain == "domain2") {
        $data = array();
        $urlgetid = "zones/zones_id/dns_records?type=A&name=$domain&content=$serverip&proxied=true&page=1&per_page=1&order=type&direction=desc&match=all";
        $methodgetid = 'GET';
        $resultgetid = mikhmonOnline_apiCurl($params,$data,$urlgetid,$methodgetid);
        $dnsid = $resultgetid['result'][0]['id'];
        $url = 'zones/zones_id/dns_records/'.$dnsid;
        $method = 'DELETE';
        $result = mikhmonOnline_apiCurl($params,$data,$url,$method);
       
      } else {
        return 'API Cloudflare Problem';
      }

      return "success";
}

function mikhmonOnline_SuspendAccount($params) {

    $connection = ssh2_connect($params['serverip'], 22);
    $password = $params["serverpassword"];
    $domain = $params["domain"];
    #suspend user
     ssh2_auth_password( $connection, 'root', $password );                   
      $stream = ssh2_exec( $connection, "./suspendvhost.sh -u $domain" );
      stream_set_blocking( $stream, true );
      $stream_out = ssh2_fetch_stream( $stream, SSH2_STREAM_STDIO );
      $output = [];
      while($buffer = fgets($stream_out)) {
          $output[] .= $buffer;
      }
      fclose($stream);
      $result = $output[1];
    return 'success';
}

function mikhmonOnline_UnsuspendAccount($params) {

   $connection = ssh2_connect($params['serverip'], 22);
    $password = $params["serverpassword"];
    $domain = $params["domain"];
    #suspend user
     ssh2_auth_password( $connection, "root", "$password" );                   
      $stream = ssh2_exec( $connection, "./unsuspendvhost.sh -u $domain" );
      stream_set_blocking( $stream, true );
      $stream_out = ssh2_fetch_stream( $stream, SSH2_STREAM_STDIO );
      $output = [];
      while($buffer = fgets($stream_out)) {
          $output[] .= $buffer;
      }
      fclose($stream);
      $result = $output[1];
    return 'success';
}

function mikhmonOnline_ChangePackage($params) {

    mikhmonOnline_TerminateAccount($params);
    mikhmonOnline_CreateAccount($params);
    return "success";
}

function mikhmonOnline_loadLangPUQ($params) {

  $lang = $params['model']['client']['language'];

  $langFile = dirname(__FILE__) . "/lang/" . $lang . ".php";
  if (!file_exists($langFile))
    $langFile = dirname(__FILE__) . "/lang/" . ucfirst($lang) . ".php";
  if (!file_exists($langFile))
    $langFile = dirname(__FILE__) . "/lang/english.php";
    
  #require_once dirname(__FILE__) . '/lang/english.php';
  require dirname(__FILE__) . '/lang/english.php';
  #require_once $langFile;
  require $langFile;

  return $_LANG_PUQ;  
}


function mikhmonOnline_ClientArea($params) {
  $lang = mikhmonOnline_loadLangPUQ($params);

  $data = array();
 
    return array(
        'templatefile' => 'clientarea',
        'vars' => array(
            'lang' => $lang,
            'params'=> $params,
        ),
    );
}

?>
