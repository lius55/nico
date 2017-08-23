<?php
/*
 * Validationチェッククラス
 */
class Validate {

	private $request;
	private $response;

	function __construct($request, $response) {
		$this->request = $request;
		$this->response = $response;
	}

	private function response_error($msg, $status = 200, $code) {
		$this->response = new stdClass();
		$this->response->result = new stdClass();
		$this->response->result->code = $code;
		$this->response->response = new stdClass();
		$this->response->response->msg = $msg;
		http_response_code($status);
		echo json_encode($this->response, JSON_UNESCAPED_UNICODE);
		exit;
	}

	private function get_value($key) {
		$value = $this->request;
		foreach (explode(".", $key) as $key_unit) {
			if (!isset($value[$key_unit])) { return null; }
			$value = $value[$key_unit];
		}
		return $value;
	}

	/*
	 * 必須チェック
	 * @param $keyList 対象キーのリスト(カンマ区切り)
	 */
	public function required($keyList) {
		foreach(explode(",", $keyList) as $key) {
			$value =  $this->get_value($key);
			if (!isset($value)) {
				$this->response_error('invalid required parameter '.$key, 400, 999);
			}
		}
	}

	/*
	 * 長さチェック
	 * @param $key
	 * @param $min
	 * @param $max
	 */
	public function length($key, $min = -1, $max = -1, $error_code = 999) {
		if (($min > 0) && (mb_strlen($this->request[$key], "UTF-8") < $min)) {
			$this->response_error('invalid parameter ' . $key, 200, $error_code);
		}
		if (($max > 0) && (mb_strlen($this->request[$key], "UTF-8") > $max)) {
			$this->response_error('invalid parameter ' . $key, 200, $error_code);
		}
	}

	/*
	 * 電話番号チェック
	 */
	public function msn($key, $error_code = 999) {
		if ((preg_match('/^\+81\-\d{1}0\-\d{4}-\d{4}$/', $this->request[$key]) == 0)
			&& (preg_match('/^0\d{1}0-\d{4}-\d{4}$/', $this->request[$key]) == 0)) {
			$this->response_error('invalid parameter '.$key, 200, $error_code);
		}
	}

	/*
	 * ユーザ存在チェック
	 * 存在しない場合、code=201返却
	 * @param $dbh データソース接続
	 * @param $seqno app_userのseqno
	 */
	public function app_user_exists($dbh, $seqno) {
		$stmt = $dbh->prepare("select count(*) as cnt from app_user where seqno=:seqno and deleted_date is null");
		$stmt->bindParam(":seqno", $seqno);
		$stmt->execute();
		$row = $stmt->fetch();
		if ($row["cnt"] == 0) {
			$response->result->code = 201;
			$response->response = new stdClass();
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/*
	 * アカウント存在チェック
	 * 存在する場合、code=201返却
	 * @param $dbh データソース接続
	 * @param $account 電話番号
	 */
	public function account_exists($dbh, $account) {
		$stmt = $dbh->prepare("select count(*) as cnt from app_user where account=:account and deleted_date is null");
		$stmt->bindParam(":account", $account);
		$stmt->execute();
		$row = $stmt->fetch();
		if ($row["cnt"] != 0) {
			$response->result->code = 201;
			$response->response = new stdClass();
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/*
	 * アカウント存在チェック
	 * 存在しない場合、code=201返却
	 * @param $dbh データソース接続
	 * @param $account 電話番号
	 */
	public function account_not_exists($dbh, $account) {
		$stmt = $dbh->prepare("select count(*) as cnt from app_user where account=:account and deleted_date is null");
		$stmt->bindParam(":account", $account);
		$stmt->execute();
		$row = $stmt->fetch();
		if ($row["cnt"] == 0) {
			$response->result->code = 201;
			$response->response = new stdClass();
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/*
	 * 模合存在チェック
	 * 存在しない場合、code=301返却
	 * @param $dbh データソース接続
	 * @param $seqno moai_groupのseqno
	 */
	public function moai_exists($dbh, $seqno) {
		$stmt = $dbh->prepare("select count(*) as cnt from moai_group where seqno=:seqno");
		$stmt->bindParam(":seqno", $seqno);
		$stmt->execute();
		$row = $stmt->fetch();
		if ($row["cnt"] == 0) {
			$response->result->code = 301;
			$response->response = new stdClass();
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/*
	 * 模合イベント存在チェック
	 * 存在しない場合、code=401返却
	 * @param $dbh データソース接続
	 * @param $seqno moai_eventのseqno
	 */
	public function moai_event_exists($dbh, $seqno) {
		$stmt = $dbh->prepare("select count(*) as cnt from moai_event where seqno=:seqno");
		$stmt->bindParam(":seqno", $seqno);
		$stmt->execute();
		$row = $stmt->fetch();
		if ($row["cnt"] == 0) {
			$response->result->code = 401;
			$response->response = new stdClass();
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}
}
?>