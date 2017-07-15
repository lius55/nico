<?php
/*
 * 模合イベントオファー募集API
 * リクエストボディで指定された seqno の模合の居酒屋からのオファー受け取り可否を設定します。
 */
include_once '../../../common/config.php';
include_once '../../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,offer');
	$seqno = (int)$request["seqno"];
	$offer = (boolean)$request["offer"];

	// 模合イベント存在チェック
	$validate->moai_event_exists($dbh, $seqno);

	// 更新
	$stmt = $dbh->prepare("update moai_event set reqruit_offer=:offer where seqno=:seqno");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->bindParam(":offer", $offer);
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response = new stdClass();

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
