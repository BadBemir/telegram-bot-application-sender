<?php

define("BOT_TOKEN", "8548197752:AAFw4PyjB0CglbAmGvpJG-4cQ_fvsYgeA5g");
define("GROUP_CHAT_ID", "-1003850836793");

// Надёжный способ получить время Владивостока
function vladivostok_time() {
    return gmdate('d.m.Y H:i', time() + 10 * 3600);
}

$update = json_decode(file_get_contents("php://input"), true) ?? [];

if (empty($update["callback_query"])) {
    http_response_code(200);
    exit();
}

$cb = $update["callback_query"];
$chat_id    = $cb["message"]["chat"]["id"]     ?? 0;
$message_id = $cb["message"]["message_id"]    ?? 0;
$data       = $cb["data"]                     ?? "";
$username   = $cb["from"]["username"] ?? ($cb["from"]["first_name"] ?? "админ");

if ($chat_id != GROUP_CHAT_ID || $message_id <= 0) {
    answerCallback($cb["id"]);
    exit();
}

$action_map = [
    "set_new"      => "new",
    "set_inwork"   => "inwork",
    "set_done"     => "done",
    "set_rejected" => "rejected",
];

if (!preg_match('/^set_(\w+)$/', $data, $m) || !isset($action_map[$m[0]])) {
    answerCallback($cb["id"], "Неизвестная команда");
    exit();
}

$new_status = $action_map[$m[0]];

// Текущий статус (для защиты от повторного нажатия)
$current_status = "new";
if (preg_match("/Статус:\s*<b>(\w+)<\/b>/", $cb["message"]["text"] ?? "", $match)) {
    $current_status = $match[1];
}

if ($current_status === $new_status) {
    answerCallback($cb["id"], "Уже «{$new_status}»");
    exit();
}

// Метки статусов
$labels = [
    "new"      => "🆕 Новая",
    "inwork"   => "🔄 В работе",
    "done"     => "✅ Выполнено",
    "rejected" => "❌ Отклонено",
];

$time_str = vladivostok_time();
$status_line = $labels[$new_status] . " • " . $time_str . ($username ? " @$username" : "");


$original_text = rtrim($cb["message"]["text"] ?? "");

// Разбиваем на строки
$lines = explode("\n", $original_text);

// Ищем и удаляем старую строку статуса (идём с конца)
for ($i = count($lines) - 1; $i >= 0; $i--) {
    $trimmed = trim($lines[$i]);
    if ($trimmed === '') {
        continue;
    }

    if (preg_match('/^[🆕🔄✅❌]/u', $trimmed)) {

        array_splice($lines, $i);
        break;
    }

    if ($i === 0) {
        break;
    }
}

$main_content = rtrim(implode("\n", $lines));


if (empty($main_content)) {
    $main_content = $original_text;
}


$new_text = $main_content . "\n\n" . $status_line;


$keyboard = get_keyboard($new_status);

// Обновляем сообщение
editMessage($chat_id, $message_id, $new_text, $keyboard);

// Подтверждение
answerCallback($cb["id"], "Статус → " . $labels[$new_status]);



function get_keyboard(string $status): string
{
    $p = "set_";

    $keyboard = match ($status) {
        "new" => [
            [
                ["text" => "Взять в работу",   "callback_data" => $p . "inwork"],
                ["text" => "Отклонить",        "callback_data" => $p . "rejected"],
            ],
        ],
        "inwork" => [
            [
                ["text" => "Выполнено",        "callback_data" => $p . "done"],
                ["text" => "Отклонить",        "callback_data" => $p . "rejected"],
            ],
        ],
        "done", "rejected" => [
            [
                ["text" => "Вернуть в работу", "callback_data" => $p . "inwork"],
            ],
        ],
        default => [],
    };

    return json_encode(["inline_keyboard" => $keyboard]);
}

function editMessage(int $chat_id, int $msg_id, string $text, string $reply_markup): void
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText";

    $postFields = [
        "chat_id"      => $chat_id,
        "message_id"   => $msg_id,
        "text"         => $text,
        "parse_mode"   => "HTML",
        "reply_markup" => $reply_markup,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 10,
    ]);

    curl_exec($ch);
    curl_close($ch);
}

function answerCallback(string $id, string $text = ""): void
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery";

    $postFields = [
        "callback_query_id" => $id,
        "text"              => $text,
        "show_alert"        => false,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 6,
    ]);

    curl_exec($ch);
    curl_close($ch);
}

http_response_code(200);