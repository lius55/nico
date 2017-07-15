<?php
/*
 * 模合イベント開催日決定API
 * リクエストボディで指定された seqno の模合イベントの開催日付を決定状態にします。
 */
include_once '../../../common/config.php';
include_once '../../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,decided_seqno');
	$seqno = (int)$request["seqno"];
	$decided_seqno = (int)$request["decided_seqno"];
	// 模合イベント存在チェック
	$validate->moai_event_exists($dbh, $seqno);
	
	$stmt = $dbh->prepare("update moai_event set decided_seqno=:decided_seqno where seqno=:seqno");
	$stmt->bindParam(":decided_seqno", $decided_seqno);
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	// レスポンス返却
	echo json_encode(get_event_candidate_list($dbh, $seqno), JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>