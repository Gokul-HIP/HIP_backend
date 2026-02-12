<?php


function sendApiResponse($status, $message, $data = null, $total = null, $page = null, $page_limit = null, $errors = null)
{
    $statusMessage = "success";
    if ($status >= 200 && $status < 300) {
        $statusMessage = "success";
    } else {
        $statusMessage = "failed";
    }
    $data = [
        'status' => $statusMessage,
        'message' => $message,
        'data' => $data,
    ];
    if(env('DEBUG', true) && $errors!=null){
        $data['errors'] = $errors;
    }
    if ($total) {
        $data['total'] = $total;
    }
    if ($page) {
        $data['page'] = $page;
    }
    if ($page_limit) {
        $data['page_limit'] = $page_limit;
    }

    return response()->json($data, $status);
}
