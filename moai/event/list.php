<?php
/*
 * 模合イベント取得
 * リクエストボディで指定された seqno の模合のイベント情報を取得します。
 */
include_once '../../common/config.php';
include_once '../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,order');
	$seqno = (int)$request["seqno"];
	$order = (int)$request["order"];
	// 模合情報取得
	$validate->moai_exists($dbh, $seqno);

	$order_str = ($order == 0) ? "asc" : "desc";
	$stmt = $dbh->prepare("select moai_event.seqno as seqno,area,waist_seqno,message,start_time,decided_seqno," .
		"date as decided_date from moai_event left join event_candidate on moai_event.decided_seqno=event_candidate.seqno " .
		"where group_seqno=:seqno order by moai_event.seqno ".$order_str);
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->moai_event_list = array();
	while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
		$response->response->seqno = (int)$seqno;
		$row->seqno = (int)$row->seqno;
		$row->waist_seqno = (int)$row->waist_seqno;
		$row->decided_seqno = ($row->decided_seqno == 0) ? null : (int)$row->decided_seqno;
		$response->response->moai_event_list[] = $row;
	}
	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
