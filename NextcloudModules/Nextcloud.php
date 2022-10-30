<?php

function Nextcloud_MetaData()
{
   return array(
       'DisplayName' => 'Nextcloud',
       'language' => 'english',
   );
}


function Nextcloud_ConfigOptions() {

    $configarray = array(
     'Group' => array( 'Type' => 'text', 'Default' => 'PublicNextcloud'),
     'Quota' => array( 'Type' => 'text', 'Default' => '10' ,'Size' => '10','Description' => 'GB',),
     'Allow quota change' => array( 'Type' => 'radio', 'Default' => 'NO' ,'Options' =>'NO,YES', 'Description' => 'Allows client to change disk tquota (EXAMPLE: If billing for extra space)'),
     'Quotas for selection' => array( 'Type' => 'text', 'Default' => '10,20,30,40,50,60,70,80,90,100' ,'Size' => '20','Description' => 'GB'),
    );
    return $configarray;
}

function Nextcloud_AdminLink($params) {

    $code = '<form action="https://'.$params['serverhostname'].'" method="post" target="_blank">
    <input type="submit" value="Login to Control Panel" />
    </form>';
    return $code;
}



function Nextcloud_apiCurl($params,$data,$url,$method){

    $postdata = http_build_query($data);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://' . $params['serverhostname'] . '/ocs/v1.php'.$url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json','OCS-APIRequest: true'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);   
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_USERPWD, $params['serverusername'].':'.$params['serverpassword']);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);
    $array = json_decode($answer,TRUE);
    curl_close($curl);
    return $array;
}


function Nextcloud_CreateAccount($params) {
  if ($params['server'] == 1) {
    $data = array(
    'userid' => $params['username'],
    'password' => $params['password'],
    );
    #create user
    $create_user = Nextcloud_apiCurl($params,$data,'/cloud/users', 'POST');
    if(!$create_user){
      return 'API problem';
    }
        
    if($create_user['ocs']['meta']['statuscode'] == '100') {

      #set user quota
      $url = '/cloud/users/' . $params['username'];
      $data = array(
      'key' => 'quota',
      'value' => ($params['configoptions']['Size in GB'] != 0) ? $params['configoptions']['Size in GB'].'GB' : $params['configoption2'].'GB',
      );
      $set_user_quota = Nextcloud_apiCurl($params,$data,$url, 'PUT');

      #set user group
      $url = '/cloud/users/' . $params['username'] . '/groups';
      $data = array(
      'groupid' => $params['configoption1']
      );
      $set_user_group = Nextcloud_apiCurl($params,$data,$url, 'POST');

      #set user displayname
      $url = '/cloud/users/'.$params['username'];
      $data = array(
      'key' => 'displayname',
      'value' =>  $params['clientsdetails']['firstname'] . ' ' . $params['clientsdetails']['lastname']
      );
      $set_user_displayname = Nextcloud_apiCurl($params,$data,$url, 'PUT');
      
      #set user e-mail
      $url = '/cloud/users/' . $params['username'];
      $data = array(
      'key' => 'email',
      'value' =>  $params['clientsdetails']['email']
      );
      $set_user_email = Nextcloud_apiCurl($params, $data, $url, 'PUT');
      
      #success   
      $result = 'success';
    
    } else {
      $result = ' code: ' . $create_user['ocs']['meta']['statuscode'];
      if($create_user['ocs']['meta']['message'])
        $result = $result . ' Message: ' . $create_user['ocs']['meta']['message'];
    }
    return $result;
  }
}


function Nextcloud_TerminateAccount($params) {

    $data = array();
    $url = '/cloud/users/' . $params['username'] . '/disable';
    $curl = Nextcloud_apiCurl($params, $data, $url, 'PUT');
    if(!$curl){
      return 'API problem';
    }

    if($curl['ocs']['meta']['statuscode'] == '100') {
      $result = 'success';
    }else {
      $result = ' code: ' . $curl['ocs']['meta']['statuscode'];
      if($curl['ocs']['meta']['message'])
        $result = $result . ' Message: ' . $curl['ocs']['meta']['message'];
    }
    return $result;

}

function Nextcloud_SuspendAccount($params) {

    $data = array();
    $url = '/cloud/users/' . $params['username'] . '/disable';
    $curl = Nextcloud_apiCurl($params, $data, $url, 'PUT');
    if(!$curl){
      return 'API problem';
    }

    if($curl['ocs']['meta']['statuscode'] == '100') {
      $result = 'success';
    }else {
      $result = ' code: ' . $curl['ocs']['meta']['statuscode'];
      if($curl['ocs']['meta']['message'])
        $result = $result . ' Message: ' . $curl['ocs']['meta']['message'];
    }
    return $result;
}

function Nextcloud_UnsuspendAccount($params) {

    $data = array();
    $url = '/cloud/users/' . $params['username'] . '/enable';
    $curl = Nextcloud_apiCurl($params, $data, $url, 'PUT');
    if(!$curl){
      return 'API problem';
    }

    if($curl['ocs']['meta']['statuscode'] == '100') {
      $result = 'success';
    }else {
      $result = ' code: ' . $curl['ocs']['meta']['statuscode'];
      if($curl['ocs']['meta']['message'])
        $result = $result . ' Message: ' . $curl['ocs']['meta']['message'];
    }
    return $result;
}


function Nextcloud_ChangePassword($params) {

    $data = array(
      'key' => 'password',
      'value' => $params['password']   
       );
    $url = '/cloud/users/' . $params['username'];
    $curl = Nextcloud_apiCurl($params, $data, $url, 'PUT');
    if(!$curl){
      return 'API problem';
    }

    if($curl['ocs']['meta']['statuscode'] == '100') {
      $result = 'success';
    }else {
      $result = ' code: ' . $curl['ocs']['meta']['statuscode'];
      if($curl['ocs']['meta']['message'])
        $result = $result . ' Message: ' . $curl['ocs']['meta']['message'];
    }
    return $result;
}

function Nextcloud_ChangePackage($params) {

    #set user quota
    $url = '/cloud/users/' . $params['username'];
    $data = array(
    'key' => 'quota',
    'value' => ($params['configoptions']['Size in GB'] != 0) ? $params['configoptions']['Size in GB'].'GB' : $params['configoption2'].'GB',
    );
    $curl = Nextcloud_apiCurl($params,$data,$url, 'PUT');
    if(!$curl){
      return 'API problem';
    }

    if($curl['ocs']['meta']['statuscode'] == '100') {
      $result = 'success';
    }else {
      $result = ' code: ' . $curl['ocs']['meta']['statuscode'];
      if($curl['ocs']['meta']['message'])
        $result = $result . ' Message: ' . $curl['ocs']['meta']['message'];
    }
    return $result;

}

function Nextcloud_loadLangPUQ($params) {

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

function Nextcloud_changeLimit($params) {
  $lang = Nextcloud_loadLangPUQ($params);

  if ($_POST['limit']){
    if ($params['configoption3'] == 'YES'){
      #set user quota
      $url = '/cloud/users/' . $params['username'];
      $data = array(
      'key' => 'quota',
      'value' => $_POST['limit'].'GB',
      );
      $set_user_quota = Nextcloud_apiCurl($params,$data,$url, 'PUT');

      if($set_user_quota['ocs']['meta']['statuscode'] == '100') {
        $result = "success";
      }
      else{
        $result = $result . ' Message: ' . $create_user['ocs']['meta']['message'];
      }
      return $result;
    }else{
      return  $lang['quota_change_option_disabled'];
    }
  }
}

function Nextcloud_ClientAreaAllowedFunctions() {
    $buttonarray = array(
	 "change_limit" => "changeLimit",
	);
	return $buttonarray;
}

function Nextcloud_ClientArea($params) {
  $lang = Nextcloud_loadLangPUQ($params);

  $data = array();
  $url = '/cloud/users/' . $params['username'];
  $curl = Nextcloud_apiCurl($params, $data, $url, 'GET');

  if($curl){  
    return array(
        'templatefile' => 'clientarea',
        'vars' => array(
            'lang' => $lang,
            'params'=> $params,
            'info' => $curl
        ),
    );
  }
}

function Nextcloud_UsageUpdate($params) {

  $table = 'tblhosting';
  $fields = 'username';
  $where = array('server'=>$params['serverid']);
  $result = select_query($table,$fields,$where);
  while ($dat = mysql_fetch_array($result)){
    $username = $dat['username'];    
    $data = array();
    $url = '/cloud/users/' . $username;
    $curl = Nextcloud_apiCurl($params, $data, $url, 'GET');
    if($curl){
      update_query("tblhosting",array(
        'diskusage'=>$curl['ocs']['data']['quota']['used']/1000/1000,
        'disklimit'=>$curl['ocs']['data']['quota']['quota']/1000/1000,
        'bwusage'=>'0',
        'bwlimit'=>'0',
        'lastupdate'=>'now()',),array('server'=>$params['serverid'], 'username'=>$username));
    }
  }
}

function Nextcloud_AdminServicesTabFields($params) {

    $data = array();
    $url = '/cloud/users/' . $params['username'];
    $curl = Nextcloud_apiCurl($params, $data, $url, 'GET');
    if(!$curl){
      $fieldsarray = array('API Connection Status' => '<div class="errorbox">API connection problem.</div>');
      return $fieldsarray;
    }

    $fieldsarray = array(
     'API Connection Status' => '<div class="successbox">API Connection OK</div>',
     'Disk status' => '
                      <b>Total:</b> '.round($curl['ocs']['data']['quota']['quota'] / '1000' / '1024'/ '1024') .' Gb <b>|</b> 
                      <b>Used:</b> '.round($curl['ocs']['data']['quota']['used'] / '1000' / '1024'/ '1024', 2) .' Gb , '.round('100' * $curl['ocs']['data']['quota']['used'] / $curl['ocs']['data']['quota']['quota']).'%<b>|</b>',                        
    
    );
    return $fieldsarray;
}
?>
