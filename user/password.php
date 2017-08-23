<?php
/*
 * パスワード再発行API
 * パスワード再発行処理を行います。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// 必須チェック
	$validate->required('account');
	$account = $request["account"];

	// 存在チェック
	$validate->account_not_exists($dbh, $account);

	// device_token取得
	$stmt = $dbh->prepare("select token from app_user,app_device where app_user.device_seqno=app_device.seqno " .
		"and account=:account");
	$stmt->bindParam(":account", $account);
	$stmt->execute();

	if ($stmt->rowCount() < 1) {
		$response->result->code = 101;
		$response->response = new stdClass();
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit;
	}

	$device_token = $stmt->fetch()["token"];
	// パスワード再発行
	$password = substr(md5(uniqid(mt_rand(), true)),0,8);
	// パスワード更新
	$stmt = $dbh->prepare("update app_user set password=:password where account=:account");
	$stmt->bindParam(":password", md5($password));
	$stmt->bindParam(":account", $account);
	$stmt->execute();

	// プッシュ通知
	$api_key = "AAAADYyC_Z0:APA91bFjuZ6rFdordouQ2DwHOxT3yPimrbzbxbJ69Bhi8lJyVLt1szf8l8D6B5mXIvAm2hVXPl-34J2R2Biz1E9fk_u44DeM_wqMSobpE4Blp2zP_RYaZDqzeJqObxIPREue32NL1d_b";
	$base_url = "https://fcm.googleapis.com/fcm/send";

	// toに指定しているのはトピック名:testに対して一括送信するという意味
	// 個別に送信したい場合はここに端末に割り振られたトークンIDを指定する
	$data = array(
	    "to"          	=> $device_token,
	    "priority"    	=> "high",
	    "notification"	=> array(
	    						"title" => "パスワード再発行",
	    						"body"  => "新しいパスワードが再発行されました。"
	    ),
	    "data" 			=> array(
	    						"type" 		=> "1",
	    						"telno"		=> $account,
	    						"password" 	=> $password
	    )
	);

	$header = array(
	     "Content-Type:application/json"
	    ,"Authorization:key=".$api_key
	);
	$context = stream_context_create(array(
	    "http" => array(
	         'method' => 'POST'
	        ,'header' => implode("\r\n",$header)
	        ,'content'=> json_encode($data)
	    )
	));
	$google_response = file_get_contents($base_url,false,$context);

	// レスポンス返却
	$response->response = new stdClass();
	$response->result->code = RESPONSE_SUCCESS;
	$response->response->detail = $google_response;
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>