<?php
/*
 * 模合イベント候補日設定API
 * リクエストボディで指定された seqno の模合イベントに開催候補日を設定します。
 */
include_once '../../../common/config.php';
include_once '../../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,event_candidate_list');
	$seqno = (int)$request["seqno"];
	$event_candidate_list = $request["event_candidate_list"];
	// 模合イベント存在チェック
	$validate->moai_event_exists($dbh, $seqno);

	$dbh->beginTransaction();
	// 削除→追加
	$stmt = $dbh->prepare("delete from event_candidate where event_seqno=:seqno");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	$stmt = $dbh->prepare("delete from candidate_vote where event_seqno=:seqno");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	foreach($event_candidate_list as $date) {
		$stmt = $dbh->prepare("insert into event_candidate(event_seqno,date) values(:seqno,:date)");
		$stmt->bindParam(":seqno", $seqno);
		$stmt->bindParam(":date", $date);
		$stmt->execute();
	}
	$dbh->commit();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->event_candidate_list = array();
	$stmt = $dbh->prepare("select date,seqno from event_candidate where event_seqno=:seqno order by date desc");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();
	while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
		$row->vote_list = array();
		$row->candidate_seqno = (int)$row->seqno;
		$response->response->event_candidate_list[] = $row;
	}

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
