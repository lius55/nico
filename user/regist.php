<?php
/*
 * ユーザ登録API
 * アプリ側で入力された電話番号、パスワード、ニックネーム、<br/>
 * プロフィール画像をアプリユーザテーブルに登録します。
 */
include_once '../common/config.php';
include_once '../common/common.php';

try{
	// パラメーターチェック
	$validate->required('account,password,nickname');
	// ハスワート8文字以上20文字以下チェック
	$validate->length('password', 8, 20, 211);
	// ニックネーム20文字以下
	$validate->length('nickname', 1, 20, 212);
	// 電話番号フォーマットチェック
	$validate->msn('account', 210);

	// 電話番号フォーマット統一
	$account = get_msn($request["account"]);
	$password = $request["password"];
	$nickname = $request["nickname"];
	$photo = HOST_NAME.'img/user/default.png';

	// ユーザ存在チェック
	$validate->account_exists($dbh, $account);

	// ユーザ登録
	$dbh->beginTransaction();
	$stmt = $dbh->prepare("insert into app_user(account,password,nickname,photo) " .
		"values(:account,:password,:nickname,:photo)");
	$stmt->bindParam(":account", $account);
	$stmt->bindParam(":password", md5($password));
	$stmt->bindParam(":nickname", $nickname);
	$stmt->bindParam(":photo", $photo);
	$stmt->execute();

	// 画像アップロードされた場合、データを更新する
	if (isset($_FILES['photo'])) {
		$stmt = $dbh->prepare("update app_user set photo=:photo where account=:account");
		$imageFile = $_FILES['photo']['tmp_name'];
		$ext = end(explode('.', $_FILES['photo']['name']));
		$imageFileName = $dbh->lastInsertId() . '.' . $ext;
		move_uploaded_file($imageFile, '../img/user/' . $imageFileName);
		$photo = HOST_NAME . 'img/user/' . $imageFileName;
		$stmt->bindParam(":photo", $photo);
		$stmt->bindParam(":account", $account);
		$stmt->execute();
	}
	$dbh->commit();
	$response->result->code = RESPONSE_SUCCESS;

	// レスポンス返却
	$response->response = new stdClass();
	echo json_encode($response, JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>