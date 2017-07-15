<?php
/*
 * 模合イベント出席日設定API
 * リクエストボディで指定された seqno に対する参加者 user_seqno の出席可能日を設定します。
 */
include_once '../../../common/config.php';
include_once '../../../common/common.php';

try{
	// トークンチェック
	validate_access_token();
	// パラメーターチェック
	$validate->required('seqno,user_seqno,candidate_vote_list');
	$seqno = (int)$request["seqno"];
	$user_seqno = (int)$request["user_seqno"];
	$candidate_vote_list = $request["candidate_vote_list"];
	// 模合イベント存在チェック
	$validate->moai_event_exists($dbh, $seqno);
	// ユーザ存在チェック
	$validate->app_user_exists($dbh, $user_seqno);

	$dbh->beginTransaction();
	// 削除→追加
	foreach($candidate_vote_list as $candidate_vote) {
		$stmt = $dbh->prepare("delete from candidate_vote where user_seqno=:user_seqno and " . 
			"event_seqno=:event_seqno and candidate_seqno=:candidate_seqno");
		$stmt->bindParam(":user_seqno", $user_seqno);
		$stmt->bindParam(":event_seqno", $seqno);
		$stmt->bindParam(":candidate_seqno", $candidate_vote["seqno"]);
		$stmt->execute();

		$stmt = $dbh->prepare("insert into candidate_vote(user_seqno,event_seqno,candidate_seqno,attend) " . 
			"values(:user_seqno,:event_seqno,:candidate_seqno,:attend)");
		$stmt->bindParam(":user_seqno", $user_seqno);
		$stmt->bindParam(":event_seqno", $seqno);
		$stmt->bindParam(":candidate_seqno", $candidate_vote["seqno"]);
		$stmt->bindParam(":attend", $candidate_vote["attend"]);
		$stmt->execute();
	}
	$dbh->commit();

	// レスポンス返却
	echo json_encode(get_event_candidate_list($dbh, $seqno), JSON_UNESCAPED_UNICODE);

}catch(Throwable $e) {
	http_response_code(500);
}
?>