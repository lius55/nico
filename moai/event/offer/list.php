<?php
/*
 * 模合イベントオファー取得API
 * 模合イベントへの居酒屋からのオファーをオファーテーブルから抽出してリストで返却します。
 */
include_once '../../../common/config.php';
include_once '../../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno');
	$seqno = (int)$request["seqno"];

	// 模合イベント存在チェック
	$validate->moai_event_exists($dbh, $seqno);

	// 更新
	$stmt = $dbh->prepare("select izakaya.seqno as seqno,event_candidate.date as offer_date, " .
		"shop_name,shop_pref,shop_city,shop_address,shop_access,shop_time,shop_url,offer_amount,contact_tel, message " . 
		"from izakaya_offer left join event_candidate on event_candidate.seqno=izakaya_offer.date_seqno,izakaya " . 
		"where izakaya_offer.event_seqno=:seqno and izakaya.seqno=izakaya_offer.izakaya_seqno " .
		"order by event_candidate.date desc");
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->izakaya_offer_list = array();
	while($offer = $stmt->fetch(PDO::FETCH_OBJ)) {
		$offer->seqno = (int)$offer->seqno;
		$offer->offer_amount = (int)$offer->offer_amount;
		$response->response->izakaya_offer_list[] = $offer;
	}
	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
