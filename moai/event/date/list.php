<?php
/*
 * 模合イベント候補日取得API
 * リクエストボディで指定された seqno の模合イベントの開催候補日を取得します。
 */
include_once '../../../common/config.php';
include_once '../../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,order');
	$seqno = (int)$request["seqno"];
	$order = (int)$request["order"];
	// 模合イベント存在チェック
	$validate->moai_event_exists($dbh, $seqno);
	
	// レスポンス返却
	echo json_encode(get_event_candidate_list($dbh, $seqno, $order), JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
