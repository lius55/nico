<?php
/*
 * 模合参加者取得API
 * リクエストボディで指定された seqno の模合の参加者を抽出してアカウント情報をリストで返却します。
 */
include_once '../../common/config.php';
include_once '../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno');
	$seqno = $request["seqno"];

	// 模合存在チェック
	$validate->moai_exists($dbh, $seqno);

	// 一覧取得
	$stmt = $dbh->prepare("select app_user.seqno as seqno,account,nickname,photo from moai_user,app_user " .
		"where moai_user.user_seqno=app_user.seqno and moai_user.moai_seqno=:seqno");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->seqno = (int)$seqno;
	$response->response->app_user_list = array();
	while($row = $stmt->fetch(PDO::FETCH_OBJ)){
		$row->seqno = (int)$row->seqno;
		$response->response->app_user_list[] = $row;			
	}

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch(Throwable $e) {
	http_response_code(500);
}
?>
