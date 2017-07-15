<?php
/*
 * 居酒屋検索API
 * 店舗テーブルに登録されている居酒屋を抽出してリストで返却します。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// トークンチェック
	validate_access_token();

	// 一覧取得
	$stmt = $dbh->prepare("select * from izakaya where visible=true");
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->izakaya_list = array();
	while($row = $stmt->fetch()) {
		$izakaya = new stdClass();
		$izakaya->seqno = (int)$row["seqno"];
		$izakaya->shop_name = $row["shop_name"];
		$izakaya->photo_list = array();
		for($i = 1; $i <= 6; $i++) {
			if ($row["photo".$i] != null) {
				$izakaya->photo_list[] = $row["photo".$i];
			}
		}
		$izakaya->introduction = $row["introduction"];
		$izakaya->shop_pref = $row["shop_pref"];
		$izakaya->shop_city = $row["shop_city"];
		$izakaya->shop_address = $row["shop_address"];
		$izakaya->shop_access = $row["shop_access"];
		$izakaya->shop_time = $row["shop_time"];
		$izakaya->shop_url = $row["shop_url"];
		$izakaya->contact_tel = $row["contact_tel"];
		$response->response->izakaya_list[] = $izakaya;
	}

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
