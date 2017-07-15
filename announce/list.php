<?php
/*
 * お知らせAPI
 * お知らせテーブルに登録されているお知らせを抽出してリストで返却します。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// 一覧取得
	$stmt = $dbh->prepare("select * from announce order by create_date desc");
	$stmt->execute();

	$response->result->code = RESPONSE_SUCCESS;
	$response->response->announce_list = array();
	while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
		$announce = new stdClass();
		$announce->seqno = (int)$row->seqno;
		$announce->description = $row->description;
		$announce->post_date = $row->create_date;
		$response->response->announce_list[] = $announce;
	}

	// レスポンス返却
	echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
	http_response_code(500);
}
?>
