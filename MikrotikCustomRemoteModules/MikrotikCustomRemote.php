<?php

use WHMCS\Database\Capsule;

function MikrotikCustomRemote_MetaData(){
  return array(
      'DisplayName' => 'Mikrotik VPN Custom Remote',
      'DefaultSSLPort' => '443',
      'language' => 'english',
  );
}

function MikrotikCustomRemote_ConfigOptions() {
  $configarray = array(
      'Comment PREFIX' => array( 'Type' => 'text', 'Default' => 'WHMCS'),
      'Profile' => array( 'Type' => 'text', 'Default' => 'default' ,'Size' => '20','Description' => 'PPP Secret Profile',),
      'Max Limit Upload' => array( 'Type' => 'text', 'Default' => '10' ,'Size' => '10','Description' => 'M',),
      'Max Limit Download' => array( 'Type' => 'text', 'Default' => '10' ,'Size' => '10','Description' => 'M',),

      'Service' => array( 'Type' => 'dropdown',
          'Options' => array(
              'any' => 'any',
              'async' => 'async',
              'l2tp' => 'l2tp',
              'ovpn' => 'ovpn',
              'pppoe' => 'pppoe',
              'ppptp' => 'ppptp',
              'sstp' => 'sstp',
          ), 'Default' => 'any' ,'Size' => '20','Description' => 'PPP Secret Servive',),
          
    'Kuota' => array( 'Type' => 'text', 'Default' => '8000000000000' ,'Size' => '25',),

  );
  return $configarray;
}

function MikrotikCustomRemote_apiCurl($params,$data,$url,$method){

  $curl_url = 'https://' . $params['serverhostname'] . ':'. $params['serverport'] . '/rest' . $url;
  $postdata = json_encode($data);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $curl_url);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('content-type: application/json'));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($curl, CURLOPT_USERPWD, $params['serverusername'].':'.$params['serverpassword']);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
  curl_setopt($curl,CURLOPT_TIMEOUT,5);
  $answer = curl_exec($curl);
  $array = json_decode($answer,TRUE);
  curl_close($curl);
  return $array;
}

function MikrotikCustomRemote_GetIP($params) {
  $serverid = $params['serverid'];
  $ips_sql = json_decode(json_encode( Capsule::table('tblservers')
      ->select('tblservers.assignedips')
      ->where('id',$serverid)
      ->get(), true));

  $ips = explode("\r\n",$ips_sql[0]->assignedips);


  $hosting_ips_sql = json_decode(json_encode( Capsule::table('tblhosting')
      ->select('tblhosting.dedicatedip')
      ->where(array(
          array('server',$serverid),
          array('domainstatus','!=','Terminated')
          )
      )
      ->get(), true));

  $hosting_ips = array();
  foreach ($hosting_ips_sql as $ip) {
    array_push($hosting_ips, $ip->dedicatedip);
  }

  foreach ($ips as $ip) {
    if (!in_array($ip,$hosting_ips)){
      return $ip;
    }
  }
  return '0.0.0.0';
  //logModuleCall('MikrotikCustomRemote', 'GetIP', $ips, $hosting_ips);
}

function MikrotikCustomRemote_GetPort($params) {
  $serverid = $params['serverid'];
  $ports_sql = json_decode(json_encode( Capsule::table('tblservers')
      ->select('tblservers.accesshash')
      ->where('id',$serverid)
      ->get(), true));

  $ports = explode("\r\n",$ports_sql[0]->accesshash);


  $hosting_ports_sql = json_decode(json_encode( Capsule::table('tblhosting')
      ->select('tblhosting.notes')
      ->where(array(
          array('server',$serverid),
          array('domainstatus','!=','Terminated')
          )
      )
      ->get(), true));


  $hosting_ports = array();
  foreach ($hosting_ports_sql as $xport) {
    array_push($hosting_ports, $xport->notes);
  }
  
  $portimpl = implode("\r\n",$hosting_ports);
  
  $arrport = explode("\r\n",$portimpl);

  foreach ($ports as $port) {
    if (!in_array($port,$arrport)){
      return $port;
    }
  }
  return '0';
  //logModuleCall('MikrotikCustomRemote', 'GetIP', $ips, $hosting_ips);
}

function MikrotikCustomRemote_CreateAccount($params) {
  $ip = MikrotikCustomRemote_GetIP($params);
  $ports = MikrotikCustomRemote_GetPort($params);
  $serviceid = $params['serviceid'];
  $username = $params['username'];
  $password = $params['password'];
  $mikrotik_profile = $params['configoption2'];
  $mikrotik_service = $params['configoption5'];
  $mikrotik_comment = $params['configoption1'] . '|Product ID:'. $params['serviceid'] . '|' . $params['clientsdetails']['email'];
  $mikrotik_max_limit = $params['configoption3'] . 'M/' . $params['configoption4'].'M';
  $mikrotik_kuota = $params['configoption6'];
  
  $toport1 = $params['customfields']['Port1'];
  $toport2 = $params['customfields']['Port2'];
  $toport3 = $params['customfields']['Port3'];
  $toport4 = $params['customfields']['Port4'];
  
  $ports1 = intval($ports);
  $ports2 = intval($ports)+1;
  $ports3 = intval($ports)+2;
  $ports4 = intval($ports)+3;

  $port = $ports1 . "\r\n" . $ports2. "\r\n" . $ports3. "\r\n" . $ports4  ;

  Capsule::table('tblhosting')->where('id', $serviceid)->update(["dedicatedip"=>$ip]);
  Capsule::table('tblhosting')->where('id', $serviceid)->update(["notes"=>$port]);


  $data = array(
    'name'=> $username,
    'password' => $password,
    'remote-address'=>$ip,
    'profile'=> $mikrotik_profile,
    'service'=> $mikrotik_service,
    'comment'=> $mikrotik_comment,
    'limit-bytes-in'=> $mikrotik_kuota,
    'limit-bytes-out'=> $mikrotik_kuota
    );

    #add ppp user
    $create_user = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/secret', 'PUT');
    if(!$create_user){
      return 'API problem';
    }
    if($create_user['error']){
      return 'Error: ' . $create_user['error'] . '| Message' . $create_user['message'];
    }

    #add queue
    MikrotikCustomRemote_apiCurl($params,$data,'/queue/simple/'.$username, 'DELETE');
    $data = array(
        'name'=> $username,
        'target'=>$ip,
        'max-limit'=> $mikrotik_max_limit,
        'comment'=> $mikrotik_comment
    );

    $add_queue = MikrotikCustomRemote_apiCurl($params,$data,'/queue/simple', 'PUT');
    if(!$add_queue){
      return 'API problem';
    }
    if($add_queue['error']){
      return 'Error: ' . $add_queue['error'] . '| Message' . $add_queue['message'];
    }
    
//     #add firewall
//     $datafirewall = array(
//       'src-port'=> $toport1.','.$toport2.','.$toport2.','.$toport4
//     );
//     $firewall_arr = MikrotikCustomRemote_apiCurl($params,$data,'/ip/firewall/filter?comment=whmcs', 'GET');
//     $id_firewall = array_column($firewall_arr, '.id');
//     foreach ($id_firewall as $id) {
//      MikrotikCustomRemote_apiCurl($params,$datafirewall,'/ip/firewall/filter/'.$id, 'PATCH');
//   }
    
    $datanat1 = array(
      'chain'=> 'dstnat',
      'protocol'=> 'tcp',
      'dst-port'=> $ports1,
      'action'=> 'dst-nat',
      'to-addresses'=> $ip,
      'to-ports'=> $toport1,
      'comment'=> $username
    );

    $add_nat = MikrotikCustomRemote_apiCurl($params,$datanat1,'/ip/firewall/nat', 'PUT');
    if(!$add_nat){
      return 'API problem';
    }
    if($add_nat['error']){
      return 'Error: ' . $add_nat['error'] . '| Message' . $add_nat['message'];
    }
    
    $datanat2 = array(
      'chain'=> 'dstnat',
      'protocol'=> 'tcp',
      'dst-port'=> $ports2,
      'action'=> 'dst-nat',
      'to-addresses'=> $ip,
      'to-ports'=> $toport2,
      'comment'=> $username
    );

    $add_nat2 = MikrotikCustomRemote_apiCurl($params,$datanat2,'/ip/firewall/nat', 'PUT');
    if(!$add_nat2){
      return 'API problem';
    }
    if($add_nat2['error']){
      return 'Error: ' . $add_nat2['error'] . '| Message' . $add_nat2['message'];
    }
    
    $datanat3 = array(
      'chain'=> 'dstnat',
      'protocol'=> 'tcp',
      'dst-port'=> $ports3,
      'action'=> 'dst-nat',
      'to-addresses'=> $ip,
      'to-ports'=> $toport3,
      'comment'=> $username
    );

    $add_nat3 = MikrotikCustomRemote_apiCurl($params,$datanat3,'/ip/firewall/nat', 'PUT');
    if(!$add_nat3){
      return 'API problem';
    }
    if($add_nat3['error']){
      return 'Error: ' . $add_nat3['error'] . '| Message' . $add_nat3['message'];
    }
    
    $datanat4 = array(
      'chain'=> 'dstnat',
      'protocol'=> 'tcp',
      'dst-port'=> $ports4,
      'action'=> 'dst-nat',
      'to-addresses'=> $ip,
      'to-ports'=> $toport4,
      'comment'=> $username
    );

    $add_nat4 = MikrotikCustomRemote_apiCurl($params,$datanat4,'/ip/firewall/nat', 'PUT');
    if(!$add_nat4){
      return 'API problem';
    }
    if($add_nat3['error']){
      return 'Error: ' . $add_nat4['error'] . '| Message' . $add_nat4['message'];
    }

    return 'success';
}

function MikrotikCustomRemote_resetConnection($params) {

  $data = array();
  $curl = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/active/'.$params['username'], 'DELETE');
  if($curl['error']){
    return 'Error: ' . $curl['error'] . '| Message' . $curl['message'] . '|' . $curl['detail'];
  }
  return 'success';

}


function MikrotikCustomRemote_AdminCustomButtonArray() {
  $buttonarray = array(
      'Reset connection' => 'resetConnection',
  );
  return $buttonarray;
}

function MikrotikCustomRemote_SuspendAccount($params) {

  $data = array(
      'disabled'=> 'yes',
  );
  $curl = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/secret/'.$params['username'], 'PATCH');
  if(!$curl){
    return 'API problem';
  }
  if($curl['error']){
    return 'Error: ' . $curl['error'] . '| Message' . $curl['message'] . '|' . $curl['detail'];
  }

  MikrotikCustomRemote_resetConnection($params);
  return 'success';
}

function MikrotikCustomRemote_UnsuspendAccount($params) {

  $data = array(
      'disabled'=> 'no',
  );
  $curl = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/secret/'.$params['username'], 'PATCH');
  if(!$curl){
    return 'API problem';
  }
  if($curl['error']){
    return 'Error: ' . $curl['error'] . '| Message' . $curl['message'] . '|' . $curl['detail'];
  }
  return 'success';
}


function MikrotikCustomRemote_TerminateAccount($params) {
    
    $nat_arr = MikrotikCustomRemote_apiCurl($params,$data,'/ip/firewall/nat?comment='.$params['username'], 'GET');
    $id_nat = array_column($nat_arr, '.id');

  $data = array();
  $curl = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/secret/'.$params['username'], 'DELETE');
  if($curl['error']){
    return 'Error: ' . $curl['error'] . '| Message' . $curl['message'] . '|' . $curl['detail'];
  }

  MikrotikCustomRemote_apiCurl($params,$data,'/queue/simple/'.$params['username'], 'DELETE');
  foreach ($id_nat as $id) {
     MikrotikCustomRemote_apiCurl($params,$data,'/ip/firewall/nat/'.$id, 'DELETE');
  }
  MikrotikCustomRemote_resetConnection($params);

  return 'success';

}

function MikrotikCustomRemote_ChangePassword($params) {

  $username = $params['username'];
  $password = $params['password'];

  $data = array(
      'password' => $password,
  );
  $curl = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/secret/'.$username, 'PATCH');
  if(!$curl){
    return 'API problem';
  }
  if($curl['error']){
    return 'Error: ' . $curl['error'] . '| Message' . $curl['message'] . '|' . $curl['detail'];
  }
  MikrotikCustomRemote_resetConnection($params);
  return 'success';

}

function MikrotikCustomRemote_ChangePackage($params) {
  MikrotikCustomRemote_TerminateAccount($params);
  MikrotikCustomRemote_CreateAccount($params);
}

function MikrotikCustomRemote_loadLangPUQ($params) {

  $lang = $params['model']['client']['language'];

  $langFile = dirname(__FILE__) . "/lang/" . $lang . ".php";
  if (!file_exists($langFile))
    $langFile = dirname(__FILE__) . "/lang/" . ucfirst($lang) . ".php";
  if (!file_exists($langFile))
    $langFile = dirname(__FILE__) . "/lang/english.php";

  require dirname(__FILE__) . '/lang/english.php';
  require $langFile;

  return $_LANG_PUQ;
}


function MikrotikCustomRemote_ClientArea($params) {
  $lang = MikrotikCustomRemote_loadLangPUQ($params);

  $data = array();
  $curl = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/active/'.$params['username'], 'GET');
  $curlnat = MikrotikCustomRemote_apiCurl($params,$data,'/ip/firewall/nat?comment='.$params['username'], 'GET');
  $port = array_column($curlnat, 'dst-port');
  $toport = array_column($curlnat, 'to-ports');
  if($curl){
    return array(
        'templatefile' => 'clientarea',
        'vars' => array(
            'lang' => $lang,
            'params'=> $params,
            'curl' => $curl,
            'port' => $port,
            'toport' => $toport,
        ),
    );
  }
  return 'API problem';
}


function MikrotikCustomRemote_AdminServicesTabFields($params) {
  $data = array();
  $curl = MikrotikCustomRemote_apiCurl($params,$data,'/ppp/active/'.$params['username'], 'GET');

  if($curl['error']){
    $fieldsarray = array(
        'API Connection Status' => '<div class="successbox">API Connection OK</div>',
        'Connection information' => 'NOT ONLINE',
    );
  }
  if(!$curl){
    $fieldsarray = array('API Connection Status' => '<div class="errorbox">API connection problem.</div>');
  }

  if($curl['.id']){
    $fieldsarray = array(
        'API Connection Status' => '<div class="successbox">API Connection OK</div>',
        'Connection information' =>
            '<table style="width:30%">

    <tr>
    <td><b>Comment:</b></td>
    <td>' . $curl['comment'] . '</td>
    </tr>

    <tr>
    <td><b>Service:</b></td>
    <td>' . $curl['service'] . '</td>
    </tr>

    <tr>
    <td><b>Name:</b></td>
    <td>' . $curl['name'] . '</td>
    </tr>
    
    <tr>
    <td><b>Caller-id:</b></td>
    <td>' . $curl['caller-id'] . '</td>
    </tr>
        
    <tr>
    <td><b>Address:</b></td>
    <td>' . $curl['address'] . '</td>
    </tr>

    <tr>
    <td><b>Uptime:</b></td>
    <td>' . $curl['uptime'] . '</td>
    </tr>
    </table>'
    );
  }
  return $fieldsarray;
}
?>
