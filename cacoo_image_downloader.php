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

// Cacoo API
define('CACOO_API', 'https://cacoo.com/api/v1/diagrams');
define('FORMAT', '.json');
define('API_KEY', '?apiKey=');

// option
$shortops = 'hk:f:';

// option のパースとチェック
$options = getopt($shortops);
check_options($options);

// 図の一覧の取得
$diagrams = get_diagrams($options['k']);

// 図の詳細情報の取得
foreach ($diagrams as &$v) {
    // フォルダ指定のある場合には、フォルダ名のチェックを行う
    if (array_key_exists('f', $options) && $options['f'] !== false) {
        if ($options['f'] != $v['folderName']) {
            continue;
        }
    }

    // 図情報の取得
    $v['diagram'] = get_diagram($options['k'], $v['diagramId']);

    // 画像の保存
    get_images($options['k'], $v['diagram']);
}

function get_images($apiKey, $diagram = array()) {
    if (!isset($diagram['sheets'])) {
        echo "不正なデータです(シート情報エラー)\n";
        exit;
    } else if (!isset($diagram['title'])) {
        echo "不正なデータです(図名エラー)\n";
        exit;
    }

    foreach ($diagram['sheets'] as $v) {
        if (!isset($v['imageUrlForApi'])) {
            echo "不正なデータです(画像URLエラー)\n";
            exit;
        } else if (!isset($v['name'])) {
            echo "不正なデータです(シート名エラー)\n";
            exit;
        }

        $resp = @file_get_contents($v['imageUrlForApi'] . API_KEY . $apiKey);
        if ($resp === false) {
            echo "画像の取得に失敗しました(取得エラー)\n";
            exit;
        }

        $path = './out/' . $diagram['title'];
        debug($path);
        if (!file_exists($path)) {
            $result = @mkdir($path, 0755, true);
            if ($result === false) {
                echo "ディレクトリの作成に失敗しました(書き込みエラー)\n";
                exit;
            }
        } else if (!is_dir($path)) {
            echo "ディレクトリの作成に失敗しました(同名ファイルエラー)\n";
            exit;
        }

        $filepath = $path . '/' . $v['name'] . '.png';
        debug($filepath);
        $result = @file_put_contents($filepath, $resp);
        if ($result === false) {
            echo "画像の保存に失敗しました(書き込みエラー)\n";
            exit;
        }
    }
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
    echo "-h このペルプを表示します\n";
    exit;
}

function debug() {
    if (DEBUG) {
        var_dump(func_get_args());
    }
}

?>
