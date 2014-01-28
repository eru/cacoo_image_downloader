<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <phk@FreeBSD.ORG> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Poul-Henning Kamp
 * ----------------------------------------------------------------------------
 */

// Debug
define('DEBUG', false);

// Log
define('LOG', false);

// Cacoo API
define('CACOO_API', 'https://cacoo.com/api/v1/diagrams');
define('FORMAT', '.json');
define('API_KEY', '?apiKey=');

// option
$shortops = 'hk:f:d:';

// option のパースとチェック
$options = getopt($shortops);
check_options($options);

// 図の一覧の取得
$diagrams = get_diagrams($options['k']);

// 図の詳細情報の取得
foreach ($diagrams as &$v) {
    // 図名指定のある場合には、図名のチェックを行う
    if (array_key_exists('d', $options) && $options['d'] !== false) {
        if ($options['d'] != $v['title']) {
            continue;
        }
    }

    // フォルダ指定のある場合には、フォルダ名のチェックを行う
    if (array_key_exists('f', $options) && $options['f'] !== false) {
        if ($options['f'] != $v['folderName']) {
            continue;
        }
    }

    // テキストの保存
    get_texts($options['k'], $v['diagramId']);
}

function get_texts($apiKey, $diagramId) {
    debug(CACOO_API . '/' . $diagramId . '/contents.xml'. API_KEY . $apiKey);
    $resp = @file_get_contents(CACOO_API . '/' . $diagramId . '/contents.xml' . API_KEY . $apiKey);
    debug($resp);
    if ($resp === false) {
        echo "図内容の取得に失敗しました(取得エラー)\n";
        exit;
    }

    $content = new SimpleXMLElement(utf8_for_xml($resp));
    debug($content);

    foreach($content->xpath('//text') as $v) {
        echo $v . "\n";
    }
}

function utf8_for_xml($str) {
    return preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $str);
}

function get_diagram($apiKey, $diagramId = '') {
    $resp = @file_get_contents(CACOO_API . '/' . $diagramId . FORMAT . API_KEY . $apiKey);
    debug($resp);
    if ($resp === false) {
        echo "図情報の取得に失敗しました(取得エラー)\n";
        exit;
    }

    $result = json_decode($resp, true);
    if ($result == NULL) {
        echo "図一覧の取得に失敗しました(JSONデコードエラー)\n";
        exit;
    }

    return $result;
}

function get_diagrams($apiKey) {
    $resp = @file_get_contents(CACOO_API . FORMAT . API_KEY . $apiKey);
    debug($resp);
    if ($resp === false) {
        echo "図一覧の取得に失敗しました(取得エラー)\n";
        exit;
    }

    $result = json_decode($resp, true);
    if ($result == NULL && isset($result['result'])) {
        echo "図一覧の取得に失敗しました(JSONデコードエラー)\n";
        exit;
    }

    return $result['result'];
}

function check_options($options = false) {
    debug($options, count($options));

    // optionパース失敗
    if ($option === false || count($options) == 0) {
        print_help();
    }

    // APIキーなし
    if (!array_key_exists('k', $options) && $options['k'] !== false) {
        print_help();
    }

    // help表示
    if (array_key_exists('h', $options)) {
        print_help();
    }
}

function print_help() {
    echo "php cacoo_image_downloader.php -k YOUR_API_KEY\n";
    echo "\n";
    echo "-k APIキー\n";
    echo "-f ダウンロードする図のフォルダ、未指定の場合にはすべての図\n";
    echo "-d ダウンロードする図の名前\n";
    echo "-h このペルプを表示します\n";
    exit;
}

function debug() {
    if (DEBUG) {
        var_dump(func_get_args());
    }
}

function print_log($str) {
    if (LOG) {
        echo $str . "\n";
    }
}

?>
