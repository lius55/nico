<?php
/*
 * 参加模合取得
 * リクエストボディで指定された seqno のユーザが参加している模合の一覧を模合基本情報テーブルから抽出して返却します。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno');
	$seqno = $request["seqno"];
	// ユーザ存在チェック
	$validate->app_user_exists($dbh, $seqno);

	// 模合取得
	$stmt = $dbh->prepare("select seqno,moai_name,start_date,frequency,moai_type,amount,photo,owner_seqno".
		" from moai_group,moai_user where moai_user.user_seqno=:seqno and moai_group.seqno=moai_user.moai_seqno ". 
		"order by create_date desc");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();
	$response->result->code = RESPONSE_SUCCESS;
	$response->response->moai_list = array();
	while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
		$row->seqno = (int)$row->seqno;
		$row->frequency = (int)$row->frequency;
		$row->amount = (int)$row->amount;
		$row->owner_seqno = (int)$row->owner_seqno;
		$response->response->moai_list[] = $row;
	}
	
	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
