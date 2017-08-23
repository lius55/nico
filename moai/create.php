<?php
/*
 * 模合新規作成API
 * 模合の基本情報を模合基本情報テーブルに登録します。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('moai_name,start_date,frequency,moai_type,amount,owner_seqno');

	$moai = new stdClass();
	$moai->moai_name = $request["moai_name"];
	$moai->start_date = $request["start_date"];
	$moai->frequency = $request["frequency"];
	$moai->moai_type = $request["moai_type"];
	$moai->amount = $request["amount"];
	$moai->owner_seqno = $request["owner_seqno"];
	$moai->photo = HOST_NAME . 'img/moai/default.png';

	// ユーザ存在チェック
	$validate->app_user_exists($dbh, $moai->owner_seqno);

	$dbh->beginTransaction();
	$stmt = $dbh->prepare("insert into moai_group(moai_name,photo,start_date,frequency,moai_type,amount,owner_seqno) " . "values(:moai_name,:photo,:start_date,:frequency,:moai_type,:amount,:owner_seqno)");
	$stmt->bindParam(":moai_name", $moai->moai_name);
	$stmt->bindParam(":photo", $moai->photo);
	$stmt->bindParam(":start_date", $moai->start_date);
	$stmt->bindParam(":frequency", $moai->frequency);
	$stmt->bindParam(":moai_type", $moai->moai_type);
	$stmt->bindParam(":amount", $moai->amount);
	$stmt->bindParam(":owner_seqno", $moai->owner_seqno);
	$stmt->execute();
	$moai->seqno = (int)$dbh->lastInsertId();

	// 画像アップロードされた場合、データを更新する
	if (isset($_FILES['photo'])) {
		$seqno = $dbh->lastInsertId();
		$stmt = $dbh->prepare("update moai_group set photo=:photo where seqno=:seqno");
		$imageFile = $_FILES['photo']['tmp_name'];
		$ext = end(explode('.', $_FILES['photo']['name']));
		$imageFileName = $seqno . '.' . $ext;
		move_uploaded_file($imageFile, '../img/moai/' . $imageFileName);
		$photo = HOST_NAME . 'img/moai/' . $imageFileName;
		$stmt->bindParam(":photo", $photo);
		$stmt->bindParam(":seqno", $seqno);
		$stmt->execute();
	}

	// $requestパラメーター取得の場合、intがstringに変換されたため、手動で戻す
	$moai->frequency = (int)$moai->frequency;
	$moai->amount = (int)$moai->amount;
	$moai->owner_seqno = (int)$moai->owner_seqno;
	$response->response->moai_group = $moai;
	$dbh->commit();

	// レスポンス返却
	$response->result->code = RESPONSE_SUCCESS;
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>
