<?php

// APIバージョン
define('API_VERSION', '1.0');
// 許容するユーザエージェント
define('UA', 'Nicomo(ios),Nicomo(android)');

// ヘッダー属性名
define('UA_HEADER', 'User-Agent');
define('API_HEADER', 'Api-Version');
define('TOKEN_HEADER', 'Access-Token');

// レスポンスコード
define('RESPONSE_ERROR', 999);
define('RESPONSE_SUCCESS', 0);

// ホスト名
// define('HOST_NAME', 'http://localhost:8888/nico/');
define('HOST_NAME', 'https://hitlearn.com/niconico-dev/api/');

// ** MySQL settings ** //
define('DB_NAME', 'nico');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');
?>