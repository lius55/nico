<?php
/*
 * LINEユーザログインAPI
 * 電話番号とパスワードでログイン認証を行います。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// 必須チェック
	$validate->required('account,nickname,photo,device_token');
	$account = get_msn($request["account"]);
	$nickname = $request["nickname"];
	$photo = $request["photo"];
	$device_token = $request["device_token"];

	// 認証
	$stmt = $dbh->prepare("select count(*) as cnt from app_user where account=:account");
	$stmt->bindParam(":account", $account);
	$stmt->execute();
	$row = $stmt->fetch();
	// アカウント存在する
	if ($row["cnt"] > 0) {
		$stmt = $dbh->prepare("update app_user set nickname=:nickname,photo=:photo where account=:account");
	} else {
		$stmt = $dbh->prepare("insert into app_user(nickname,account,photo) values(:nickname,:account,:photo)");
	}

	$stmt->bindParam(":nickname", $nickname);
	$stmt->bindParam(":account", $account);
	$stmt->bindParam(":photo", $photo);
	$stmt->execute();

	$stmt = $dbh->prepare("select seqno,account,nickname,photo from app_user where account=:account");
	$stmt->bindParam(":account", $account);
	$stmt->execute();
	$app_user = $stmt->fetch(PDO::FETCH_OBJ);

	$response->result->code = RESPONSE_SUCCESS;

	// UUID形式(8-4-4-4-12)のトークン作成
	$uuid = 
		substr(md5(uniqid(mt_rand(), true)),0,8).'-'.
		substr(md5(uniqid(mt_rand(), true)),0,4).'-'.
		substr(md5(uniqid(mt_rand(), true)),0,4).'-'.
		substr(md5(uniqid(mt_rand(), true)),0,4).'-'.
		substr(md5(uniqid(mt_rand(), true)),0,12);
	// access_tokenをセッションに保存
	session_start();
	$_SESSION["access_token"] = $uuid;
	// access_tokenをヘッダーに設定
	header("Access-Token: ".$uuid);

	// レスポンス返却
	$response->response = new stdClass();
	$response->response->app_user = $app_user;
	echo json_encode($response, JSON_UNESCAPED_UNICODE);
}catch(Throwable $e) {
	http_response_code(500);
}
?>