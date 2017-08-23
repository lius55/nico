<?php
/*
 * ユーザログインAPI
 * 電話番号とパスワードでログイン認証を行います。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// 必須チェック
	$validate->required('seqno');
	$seqno = $request["seqno"];

	// 存在チェック
	$validate->app_user_exists($dbh, $seqno);

	$stmt = $dbh->prepare("select nickname,account from app_user where seqno=:seqno");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();
	$user = $stmt->fetch(PDO::FETCH_OBJ);

	$stmt_up = $dbh->prepare("update app_user set device_seqno=null,nickname=:nickname," . 
		"account=:seqno,photo=:photo,access_token=null,password=null,deleted_date=now() where seqno=:seqno");
	$stmt_up->bindParam(":seqno", $seqno);
	$stmt_up->bindParam(":nickname", $user->nickname);
	$photo = HOST_NAME.'img/user/default.png';
	$stmt_up->bindParam(":photo", $photo);
	$stmt_up->execute();

	// レスポンス返却
	$response->response = new stdClass();
	$response->result->code = RESPONSE_SUCCESS;
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>