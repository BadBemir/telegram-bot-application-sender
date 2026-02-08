<?php

define("BOT_TOKEN", "8548197752:AAFw4PyjB0CglbAmGvpJG-4cQ_fvsYgeA5g");
define("GROUP_CHAT_ID", "-1003850836793");

$update = json_decode(file_get_contents("php://input"), true) ?? [];

if (empty($update["callback_query"])) {
    http_response_code(200);
    exit();
}

$cb = $update["callback_query"];
$chat_id = $cb["message"]["chat"]["id"] ?? 0;
$message_id = $cb["message"]["message_id"] ?? 0;
$data = $cb["data"] ?? "";
$username = $cb["from"]["username"] ?? ($cb["from"]["first_name"] ?? "админ");

if ($chat_id != GROUP_CHAT_ID || $message_id <= 0) {
    answerCallback($cb["id"]);
    exit();
}

$action_map = [
    "set_new" => "new",
    "set_inwork" => "inwork",
    "set_done" => "done",
    "set_rejected" => "rejected",
];

if (!preg_match('/^set_(\w+)$/', $data, $m) || !isset($action_map[$m[0]])) {
    answerCallback($cb["id"], "Неизвестная команда");
    exit();
}

$new_status = $action_map[$m[0]];

// Текущий статус (для проверки повторного нажатия)
$current_status = "new";
if (
    preg_match(
        "/Статус:\s*<b>(\w+)<\/b>/",
        $cb["message"]["text"] ?? "",
        $match,
    )
) {
    $current_status = $match[1];
}

if ($current_status === $new_status) {
    answerCallback($cb["id"], "Уже «{$new_status}»");
    exit();
}

// Формируем новый статус
$labels = [
    "new" => "🆕 Новая",
    "inwork" => "🔄 В работе",
    "done" => "✅ Выполнено",
    "rejected" => "❌ Отклонено",
];

$status_line =
    $labels[$new_status] .
    " • " .
    date("d.m.Y H:i") .
    ($username ? " @$username" : "");

// Удаляем старую часть со статусом (всё после последней разделительной линии)
$original_text = $cb["message"]["text"] ?? "";
$main_content = preg_replace('/\n*───────────────.*$/s', "", $original_text);
$main_content = rtrim($main_content);

// Собираем чистый текст + новая строка статуса
$new_text = $main_content . "\n\n───────────────\n" . $status_line;

// Новая клавиатура
$keyboard = get_keyboard($new_status);

// Обновляем сообщение
editMessage($chat_id, $message_id, $new_text, $keyboard);

// Подтверждение
answerCallback($cb["id"], "Статус → " . $labels[$new_status]);

// ────────────────────────────────────────────────

function get_keyboard(string $status): string
{
    $p = "set_";

    return json_encode([
        "inline_keyboard" => match ($status) {
            "new" => [
                [
                    [
                        "text" => "Взять в работу",
                        "callback_data" => $p . "inwork",
                    ],
                    ["text" => "Отклонить", "callback_data" => $p . "rejected"],
                ],
            ],
            "inwork" => [
                [
                    ["text" => "Выполнено", "callback_data" => $p . "done"],
                    ["text" => "Отклонить", "callback_data" => $p . "rejected"],
                ],
            ],
            "done", "rejected" => [
                [
                    [
                        "text" => "Вернуть в работу",
                        "callback_data" => $p . "inwork",
                    ],
                ],
            ],
            default => [],
        },
    ]);
}

function editMessage(
    int $chat_id,
    int $msg_id,
    string $text,
    string $reply_markup,
): void {
    curl_setopt_array(
        $ch = curl_init(
            "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText",
        ),
        [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                "chat_id" => $chat_id,
                "message_id" => $msg_id,
                "text" => $text,
                "parse_mode" => "HTML",
                "reply_markup" => $reply_markup,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ],
    );

    curl_exec($ch);
    curl_close($ch);
}

function answerCallback(string $id, string $text = ""): void
{
    curl_setopt_array(
        $ch = curl_init(
            "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery",
        ),
        [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                "callback_query_id" => $id,
                "text" => $text,
                "show_alert" => false,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
        ],
    );

    curl_exec($ch);
    curl_close($ch);
}

http_response_code(200);
