<?php
/*
 * 模合データ首藤API
 * リクエストボディで指定されたseqnoの模合の模合帳データを抽出してリストで返却します。
 */
include_once '../../common/config.php';
include_once '../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,order');
	$seqno = (int)$request["seqno"];
	$order = ($request["order"] == "0") ? "asc" : "desc";

	// 模合存在チェック
	$validate->moai_exists($dbh, $seqno);

	$stmt = $dbh->prepare("select seqno,date,waist_seqno,receipt,payment,withdrawal,balance,memo " . 
		"from moai_note where group_seqno=:seqno order by date " . $order);
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->moai_note_list = array();
	while($note = $stmt->fetch(PDO::FETCH_OBJ)) {
		$note->seqno = (int)$note->seqno;
		$note->waist_seqno = (int)$note->waist_seqno;
		$note->receipt = (int)$note->receipt;
		$note->payment = (int)$note->payment;
		$note->withdrawal = (int)$note->withdrawal;
		$note->balance = (int)$note->balance;
		$response->response->moai_note_list[] = $note;
	}
	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
