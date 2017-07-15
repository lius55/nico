<?php
/*
 * デバイストークン登録
 * スマホアプリがプッシュ通知サービスから取得したデバイストークンをアプリデバイステーブ)に登録します。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{

	// パラメーターチェック
	$validate->required('device_token');

	// device_token追加
	$device_token = $request['device_token'];
	$stmt = $dbh->prepare("select count(*) as cnt from app_device where token=:token");
	$stmt->bindParam(":token", $device_token);
	$stmt->execute();
	$result = $stmt->fetch();
	if ($result["cnt"] == 0) {
		$stmt = $dbh->prepare("insert into app_device(token) values(:token)");
		$stmt->bindParam(":token", $device_token);
		$stmt->execute();
	}

	// レスポンス返却
	$response->result->code = RESPONSE_SUCCESS;
	$response->response = new stdClass();
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>