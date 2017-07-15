<?php
/*
 * 模合イベント作成API
 * リクエストボディで指定された seqno の模合にイベントを追加します。
 */
include_once '../../common/config.php';
include_once '../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,moai_event.area,moai_event.waist_seqno,moai_event.message,moai_event.start_time');
	$seqno = (int)$request["seqno"];
	$moai_event = (object)$request["moai_event"];

	// 模合存在チェック
	$validate->moai_exists($dbh, $seqno);

	$stmt = $dbh->prepare("delete from moai_event where group_seqno=:seqno");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	// 追加
	$stmt = $dbh->prepare("insert into moai_event(group_seqno,waist_seqno,area,message,start_time) ".
		"values(:seqno,:waist_seqno,:area,:message,:start_time)");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->bindParam(":waist_seqno", $moai_event->waist_seqno);
	$stmt->bindParam(":area", $moai_event->area);
	$stmt->bindParam(":message", $moai_event->message);
	$stmt->bindParam(":start_time", $moai_event->start_time);
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->seqno = (int)$seqno;
	$moai_event->seqno = (int)$dbh->lastInsertId();
	$moai_event->waist_seqno = (int)$request["moai_event"]["waist_seqno"];
	$moai_event->decided_seqno = null;
	$moai_event->decided_date = null;
	$response->response->moai_event = $moai_event;

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
