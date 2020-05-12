<?php
// ========================
// Obsluga wyjatkow. Glownie dla Ajax
// Wywoalanie: throw new Exception('Moj wyjatek');
function exception_handler($exception) {
    global $cfg;

    $msg[] = $exception->getMessage();
    $msg[] = 'File: ['.$exception->getFile().'], Line: ['.$exception->getLine().']';

    if($cfg['error'])   $msg[] = "Error: [{$cfg['error']}]";
    if($cfg['qlast'])   $msg[] = "Query: [{$cfg['qlast']}]";

    $msg = array_map('cc', $msg);
    $msg = implode('<br>', $msg);

    header('HTTP/1.1 500 Internal Server Exception by Orkan');
    echo $cfg['isAjax'] ? json_encode(array('error' => $msg)) : $msg;
}

// ========================
// Ajax output
function ajax($data, $key=1) {
    switch($key) {
        case 0: $k = 'error' ; break;
        case 1: $k = 'result'; break;
        case 2: $k = 'update'; break;
    }
    echo json_encode(array($k => $data));
}
