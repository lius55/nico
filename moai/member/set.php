<?php
/*
 * 模合参加者設定登録 API
 * リクエストボディで指定された seqno の模合に参加者を設定します。
 */
include_once '../../common/config.php';
include_once '../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,user_seqno_list');
	$seqno = $request["seqno"];
	$user_seqno_list = $request["user_seqno_list"];
	// 模合存在チェック
	$validate->moai_exists($dbh, $seqno);

	$dbh->beginTransaction();
	// 既存参加者情報削除
	$stmt = $dbh->prepare("delete from moai_user where moai_seqno=:moai_seqno");
	$stmt->bindParam(":moai_seqno", $seqno);
	$stmt->execute();
	// 参加者情報追加
	foreach ($user_seqno_list as $user_seqno) {
		// ユーザ存在チェック
		$validate->app_user_exists($dbh, $user_seqno);
		$stmt = $dbh->prepare("insert into moai_user(moai_seqno,user_seqno) values(:moai_seqno,:user_seqno)");
		$stmt->bindParam(":moai_seqno", $seqno);
		$stmt->bindParam(":user_seqno", $user_seqno);
		$stmt->execute();
	}	
	$dbh->commit();

	// 再検索
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

}catch(Throwable $e) {
	http_response_code(500);
}
?>
