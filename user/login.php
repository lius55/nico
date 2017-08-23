<?php
/*
 * ユーザログインAPI
 * 電話番号とパスワードでログイン認証を行います。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// 必須チェック
	$validate->required('account,password,device_token');
	$account = get_msn($request["account"]);
	$password = $request["password"];
	$device_token = $request["device_token"];

	// 認証
	$stmt = $dbh->prepare("select count(*) as cnt from app_user where account=:account and password =:password");
	$stmt->bindParam(":account", $account);
	$stmt->bindParam(":password", md5($password));
	$stmt->execute();
	$row = $stmt->fetch();
	if ($row["cnt"] == 0) {
		$response->result->code = 201;
	} else {
		// UUID形式(8-4-4-4-12)のトークン作成
		$uuid = 
			substr(md5(uniqid(mt_rand(), true)),0,8).'-'.
			substr(md5(uniqid(mt_rand(), true)),0,4).'-'.
			substr(md5(uniqid(mt_rand(), true)),0,4).'-'.
			substr(md5(uniqid(mt_rand(), true)),0,4).'-'.
			substr(md5(uniqid(mt_rand(), true)),0,12);
		$stmt = $dbh->prepare("update app_user set access_token=:token where account=:account");
		$stmt->bindParam(":token", $uuid);
		$stmt->bindParam(":account", $account);
		$stmt->execute();

		// access_tokenをセッションに保存
		session_start();
		$_SESSION["access_token"] = $uuid;
		// access_tokenをヘッダーに設定
		header("Access-Token: ".$uuid);
		$response->result->code = RESPONSE_SUCCESS;

		$stmt = $dbh->prepare("select seqno,account,nickname,photo from app_user where account=:account");
		$stmt->bindParam(":account", $account);
		$stmt->execute();
		$user = $stmt->fetch(PDO::FETCH_OBJ);
		$user->seqno = (int)$user->seqno;
		$response->response->app_user = $user;

		// デバイストークン検索&更新
		$stmt = $dbh->prepare("select seqno from app_device where token=:device_token");
		$stmt->bindParam(":device_token", $device_token);
		$stmt->execute();
		if ($stmt->rowCount() > 0) {
			$update_seqno = $stmt->fetch()["seqno"];
			$stmt = $dbh->prepare("update app_user set device_seqno=:seqno where account=:account");
			$stmt->bindParam(":seqno", $update_seqno);
			$stmt->bindParam(":account", $account);
			$stmt->execute();
		}
	}

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>