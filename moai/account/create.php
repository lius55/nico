<?php
/*
 * 模合データ作成更新API
 * リクエストボディで指定されたseqnoの模合の模合帳データを作成します。
 */
include_once '../../common/config.php';
include_once '../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,moai_note_list');
	$seqno = (int)$request["seqno"];
	$moai_note_list = (object)$request["moai_note_list"];

	// 模合存在チェック
	$validate->moai_exists($dbh, $seqno);

	$dbh->beginTransaction();
	foreach($moai_note_list as $moai_note) {
		if (isset($moai_note["seqno"])) {
			// echo "exists";
			$stmt = $dbh->prepare("update moai_note set date=:date,waist_seqno=:waist_seqno,receipt=:receipt,payment=:payment," . 
				"withdrawal=:withdrawal,balance=:balance,memo=:memo,group_seqno=:group_seqno where seqno=:seqno");
			$stmt->bindParam(":seqno", $moai_note["seqno"]);
		} else {
			$stmt = $dbh->prepare("insert into moai_note(date,waist_seqno,receipt,payment," . 
				"withdrawal,balance,memo,group_seqno) " . 
				"values(:date,:waist_seqno,:receipt,:payment,:withdrawal,:balance,:memo,:group_seqno)");
		}
		$stmt->bindParam(":group_seqno", $seqno);
		$stmt->bindParam(":date", $moai_note["date"]);
		$stmt->bindParam(":waist_seqno", $moai_note["waist_seqno"]);
		$stmt->bindParam(":receipt", $moai_note["receipt"]);
		$stmt->bindParam(":payment", $moai_note["payment"]);
		$stmt->bindParam(":withdrawal", $moai_note["withdrawal"]);
		$stmt->bindParam(":balance", $moai_note["balance"]);
		$stmt->bindParam(":memo", $moai_note["memo"]);
		$stmt->execute();
	}
	$dbh->commit();

	$stmt = $dbh->prepare("select seqno,date,waist_seqno,receipt,payment,withdrawal,balance,memo " . 
		"from moai_note where group_seqno=:seqno order by seqno asc");
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
