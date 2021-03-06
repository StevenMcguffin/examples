<?php
/*
 solapi php example
 delete group messages
*/
$configFile = file_get_contents("../config.json");
$config = json_decode($configFile, true);

function get_header() {
  global $config;
  # apiKey && apiSecret are acquired from solapi.com/credentials
  $apiKey = $config["apiKey"];
  $apiSecret = $config["apiSecret"];
  date_default_timezone_set('Asia/Seoul');
  $date = date('Y-m-d\TH:i:s.Z\Z', time());
  $salt = uniqid();
  $signature = hash_hmac('sha256', $date.$salt, $apiSecret);
  return "Authorization: HMAC-SHA256 apiKey={$apiKey}, date={$date}, salt={$salt}, signature={$signature}";
}

function create_group() {
  $url = "https://api.solapi.com/messages/v4/groups";
  $result = request("POST", $url);
  $groupId = json_decode($result)->groupId;
  print_r("GroupID : {$groupId}\n");
  return $groupId;
}

function add_message($groupId) {
  global $config;
  $fields = new stdClass();
  $message = new stdClass();
  $message->text = $config["text"] . " from PHP";
  $message->type = $config["type"];
  $message->to = $config["to"];
  $message->from = $config["from"];
  $fields->messages = json_encode(array($message));
  $url = "https://api.solapi.com/messages/v4/groups/{$groupId}/messages";
  $result = request("PUT", $url, $fields);
  print_r("Group Msg add : {$result}\n");
  return json_decode($result)->resultList;
}

function delete_messages($groupId, $messageId) {
  $fields = new stdClass();
  $fields->messageIds = array($messageId);
  print_r($fields);
  $url = "https://api.solapi.com/messages/v4/groups/{$groupId}/messages";
  $result = request("DELETE", $url, $fields);
  print_r("Delete Group Msgs : {$result}\n");

}

function request($method, $url, $data = false) {
  echo "{$method}  {$url}\n";
  try {
    $curl = curl_init();
    switch ($method) {
      case "POST":
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        break;
      case "PUT":
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        break;
      case "DELETE":
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        break;
      default:
        if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(get_header(), "Content-Type: application/json"));
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if (curl_error($curl)) {
      print curl_error($curl);
    }
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
  } catch (Exception $err) {
    echo $err;
  }
}

$groupId = create_group();
$messages = add_message($groupId);
delete_messages($groupId, $messages[0]->messageId);

?>
