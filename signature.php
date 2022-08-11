<?php
/*****************************************************************************************
 * 【注意】                                                                              *
 *  お使いの環境で使用する際はバリデーションなど適切なセキュリティ対策を行ってください   *
 *****************************************************************************************/
$file = $_POST;

// タイムスタンプ
$now = time();

// アップロード先のバケット
$region = 'ap-northeast-1';
$bucket = 'XXXX';

// APIキー
$accessKey = 'XXXX';
$secretKey = 'XXXX';

// アップロード先のKey(パス)
$fileKey = 'csv/' . $file['name'];

// ポリシー作成 (配列)
$policy = [
    // アップロード期限
    'expiration' => gmdate('Y-m-d\TH:i:s.000\Z', $now + 60),

    'conditions' => [
        // アップロード先のバケット
        ['bucket' => $bucket],
        // ファイルパス
        ['key' => $fileKey],
        // アップロードを許可するコンテンツタイプ
        ['Content-Type' => $file['type']],
        // アップロードを許可するファイルサイズ (下限/上限)
        ['content-length-range', $file['size'], $file['size']],
        // アップロードしたファイルのACL
        ['acl' => 'public-read'],
        // アップロード成功時のレスポンスをXMLで返すオプション
        ['success_action_status' => '201'],
        // ハッシュ化アルゴリズム (固定) ※新規追加
        ['x-amz-algorithm' => 'AWS4-HMAC-SHA256'],
        // 許可するポリシーの種類 ※新規追加
        ['x-amz-credential' => implode('/', [$accessKey, gmdate('Ymd', $now), $region, 's3', 'aws4_request'])],
        // ポリシー生成時の日時 ※新規追加
        ['x-amz-date' => gmdate('Ymd\THis\Z', $now)],
    ],
];

// ポリシー文字列
$stringToSign = base64_encode(json_encode($policy));

// 署名生成
$dateKey = hash_hmac('sha256', gmdate('Ymd', $now), 'AWS4' . $secretKey, true);
$dateRegionKey = hash_hmac('sha256', $region, $dateKey, true);
$dateRegionServiceKey = hash_hmac('sha256', 's3', $dateRegionKey, true);
$signingKey = hash_hmac('sha256', 'aws4_request', $dateRegionServiceKey, true);

// ハッシュ化されたバイナリはBase64エンコードではなく、16進数の文字列で出力
$signature = hash_hmac('sha256', $stringToSign, $signingKey, false);

// POSTデータ生成
$data = [
    'bucket' => $bucket, // ※新規追加
    'key' => $fileKey,
    'Content-Type' => $file['type'],
    'acl' => 'public-read',
    'success_action_status' => '201',
    'policy' => $stringToSign,
    'x-amz-credential' => implode('/', [$accessKey, gmdate('Ymd', $now), $region, 's3', 'aws4_request']), // ※AWSAccessKeyIdの代わり
    'x-amz-signature' => $signature, // ※signatureの代わり
    'x-amz-algorithm' => 'AWS4-HMAC-SHA256', // ※新規追加
    'x-amz-date' => gmdate('Ymd\THis\Z', $now), // ※新規追加
];

echo json_encode([
    'upload_url' => 'https://' . $bucket . '.s3.amazonaws.com',
    'data' => $data
]);