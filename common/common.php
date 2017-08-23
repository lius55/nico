<?php

require_once('validate.php'); 

/*
 * http request header取得
 */
function getRequestHeaders() {
    $headers = array();
    foreach($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    return $headers;
}

/*
 * api_version チェック
 */
function validate_api_version() {
	if (getRequestHeaders()[API_HEADER] != API_VERSION) {
		$response->result->code = RESPONSE_ERROR;
		$response->response->msg = 'invalid api version';
		http_response_code(400);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

/*
 * access_token チェク
 * リクエストヘッダーのaccess_tokenとセッションのaccess_tokenを比較し、<br/>
 * 違った場合、http status 401返却
 */
function validate_access_token() {
	session_start();
	if (!isset($_SESSION["access_token"])) {
		http_response_code(401);
		$response->response->msg = 'empty access_token';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit;
	} else if ($_SESSION["access_token"] != getRequestHeaders()[TOKEN_HEADER]) {
		$response->response->msg = 'invalid access_token(' . $_SESSION["access_token"] . 
			":" . getRequestHeaders()[TOKEN_HEADER];
		http_response_code(401);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

/*
 * ua判定
 */
function validate_ua() {
	$valid_ua = false;
	foreach(explode(",", UA) as $ua) {
		if ($ua == getRequestHeaders()[UA_HEADER]) {
			$valid_ua = true;
		}
	}
	// ua不正の場合、400エラー返却
	if (!$valid_ua) {
		$response->result->code = RESPONSE_ERROR;
		$response->response->msg = 'invalid user agent';
		http_response_code(400);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

/*
 * http header チェック
 */
function validate_http_header() {
	if (!isset(getRequestHeaders()[API_HEADER]) || !isset(getRequestHeaders()[UA_HEADER])) {
		$response->result->code = RESPONSE_ERROR;
		$response->response->msg = 'invalid http header';
		http_response_code(400);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit;
	} else {
		validate_ua();
		validate_api_version();
	}
}

/*
 * json形式チェック
 */
function validate_json() {
	if (!is_json(file_get_contents('php://input'))) {
		$response->result->code = RESPONSE_ERROR;
		$response->response->msg = 'invalid http body(not json)';
		http_response_code(400);
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

/*
 * エラー返却
 * @param $msg エラーメッセージ
 * @param $status ステータス
 */
function response_error($msg, $status) {
	$response = new stdClass();
	$response->result = new stdClass();
	$response->result->code = RESPONSE_ERROR;
	$response->response = new stdClass();
	$response->response->msg = $msg;
	http_response_code($status);
	echo json_encode($response, JSON_UNESCAPED_UNICODE);
	exit;
}

/*
 * 必須チェック
 * @param $keyList 対象キーのリスト(カンマ区切り)
 */
function validate_required($keyList) {
	foreach(explode(",", $keyList) as $key) {
		if (!isset($request[$key])) {
			response_error('invalid required parameter', 400);
		}
	}
	exit;
}

/*
 * 長さチェック
 * @param $key
 * @param $min
 * @param $max
 */
function validate_length($key, $min = -1, $max = -1) {
	if (($min > 0) && (strlen($request[$key]) < $min)) {
		$response_error('invalid parameter min', 400);
	}
	if (($max > 0) && (strlen($request[$key]) > $max)) {
		$response_error('invalid parameter max', 400);
	}
}

/*
 * json形式の文字列かどうかのチェック
 * @param $string 判断したい文字列
 * @return true:json形式、false:json形式ではない
 */
function is_json($string){
	if(!isset($string) || strlen($string) < 1) { return true; }
	return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
}

/*
 * 電話番号取得
 * @param $string 整形前の電話番号
 * @return 整形後の電話番号
 */
function get_msn($string) {
	$return = $string;
	if (preg_match('/^\+81\-\d{1}0\-\d{4}-\d{4}$/', $string) != 0) {
		$return = "0".substr($string, 4);
	}
	return $return;
}

/*
 * イベント候補日取得
 * @param $dbh db接続
 * @param $seqno seqno
 * @param $order ソート順
 */
function get_event_candidate_list($dbh, $seqno, $order=1) {

	$order_str = ($order == 0) ? "asc" : "desc";
	$stmt = $dbh->prepare("select seqno,date from event_candidate " .
		"where event_seqno=:seqno order by date " . $order_str);
	$stmt->bindParam(":seqno", $seqno);
	$stmt->execute();
	
	$response->result->code = RESPONSE_SUCCESS;
	$response->response->seqno = (int)$seqno;
	$response->response->event_candidate_list = array();
	while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
		$event_candidate = new stdClass();
		$event_candidate->date = $row->date;
		$event_candidate->candidate_seqno = (int)$row->seqno;
		$event_candidate->vote_list = array();
		$vote_stmt = $dbh->prepare("select user_seqno,attend from candidate_vote where candidate_seqno=:seqno");
		$vote_stmt->bindParam(":seqno", $row->seqno);
		$vote_stmt->execute();

		while($vote = $vote_stmt->fetch(PDO::FETCH_OBJ)){
			$vote->user_seqno = (int)$vote->user_seqno;
			$vote->attend = (boolean)$vote->attend;
			$event_candidate->vote_list[] = $vote;			
		}
		$response->response->event_candidate_list[] = $event_candidate;
	}
	return $response;
}

//-------------
//   初期処理
//-------------
// 重大エラーのみレポーティング
error_reporting(1);
// json形式でレスポンスを返却する
header('Content-type: text/html; charset=utf-8');
header('Content-Type: application/json');

try {
	// リクエスト
	$request = json_decode(file_get_contents('php://input'), true);

	if (!isset($request)) { 
		$request = $_POST;
	}
	// レスポンス
	$response = new stdClass();
	// validator作成
	$validate = new Validate($request, $response);

	// ヘッダーチェック
	validate_http_header();
	// json形式チェック
	validate_json();

	//----------------
	//    DB接続
	//----------------
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND=>"SET CHARACTER SET 'utf8'");
	$dbh = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, $options);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbh->query("set names utf8");
} catch(Throwable $e) {
	http_response_code(500);
	exit;
}
?>