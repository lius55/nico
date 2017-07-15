<?php
/*
 * ユーザ検索API
 * リクエストボディで指定された account (電話番号) を持つユーザをアプリユーザテーブルから検索して返却します。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('account');
	// 電話番号取得
	$account = get_msn($request["account"]);

	// ユーザ検索
	$stmt = $dbh->prepare("select seqno,account,nickname,photo from app_user where account=:account");
	$stmt->bindParam(":account", $account);
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	if ($stmt->rowCount() > 0) {
		$row = $stmt->fetch(PDO::FETCH_OBJ);
		$response->response->app_user = $row;
	} else {
		$response->response = new stdClass();
	}

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
