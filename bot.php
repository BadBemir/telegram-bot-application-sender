<?php
// bot.php
// Обработчик webhook для inline-кнопок изменения статуса

define("BOT_TOKEN", "8548197752:AAFw4PyjB0CglbAmGvpJG-4cQ_fvsYgeA5g");
define("GROUP_CHAT_ID", "-1003850836793");

// =====================================================================

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

// =====================================================================
// Какие действия поддерживаем
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

// =====================================================================
// Определяем текущий статус из текста сообщения
$current_status = "new";
$original_text = $cb["message"]["text"] ?? "";

if (preg_match("/Статус:\s*<b>(\w+)<\/b>/", $original_text, $match)) {
    $current_status = $match[1];
}

// Если пытаются установить тот же статус — просто подтверждаем
if ($current_status === $new_status) {
    answerCallback($cb["id"], "Уже установлен статус «{$new_status}»");
    exit();
}

// =====================================================================
// Формируем новый текст сообщения
$status_labels = [
    "new" => "🆕 Новая",
    "inwork" => "🔄 В работе",
    "done" => "✅ Выполнено",
    "rejected" => "❌ Отклонено",
];

$status_line =
    $status_labels[$new_status] .
    " • " .
    date("d.m.Y H:i") .
    ($username ? " @{$username}" : "");

$main_content = preg_replace('/───────────────.*$/s', "", $original_text);
$main_content = rtrim($main_content);

$new_text = $main_content . "\n\n───────────────\n" . $status_line;

// =====================================================================
// Новая клавиатура в зависимости от нового статуса
$keyboard_json = get_keyboard_for_status($new_status);

// =====================================================================
// Редактируем сообщение
editMessageText($chat_id, $message_id, $new_text, $keyboard_json);

// Подтверждаем callback
answerCallback($cb["id"], "Статус изменён → " . $status_labels[$new_status]);

// =====================================================================
// Вспомогательные функции

function get_keyboard_for_status(string $status): string
{
    $prefix = "set_";

    switch ($status) {
        case "new":
            return json_encode([
                "inline_keyboard" => [
                    [
                        [
                            "text" => "🚀 Взять в работу",
                            "callback_data" => $prefix . "inwork",
                        ],
                        [
                            "text" => "❌ Отклонить",
                            "callback_data" => $prefix . "rejected",
                        ],
                    ],
                ],
            ]);

        case "inwork":
            return json_encode([
                "inline_keyboard" => [
                    [
                        [
                            "text" => "✅ Выполнено",
                            "callback_data" => $prefix . "done",
                        ],
                        [
                            "text" => "❌ Отклонить",
                            "callback_data" => $prefix . "rejected",
                        ],
                    ],
                    [
                        [
                            "text" => "↩️ Вернуть в новую",
                            "callback_data" => $prefix . "new",
                        ],
                    ],
                ],
            ]);

        case "done":
        case "rejected":
            return json_encode([
                "inline_keyboard" => [
                    [
                        [
                            "text" => "↩️ Вернуть в работу",
                            "callback_data" => $prefix . "inwork",
                        ],
                    ],
                ],
            ]);

        default:
            return json_encode(["inline_keyboard" => []]);
    }
}

function editMessageText(
    int $chat_id,
    int $message_id,
    string $text,
    string $reply_markup,
): void {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText";

    $postFields = [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $text,
        "parse_mode" => "HTML",
        "reply_markup" => $reply_markup,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function answerCallback(string $callback_id, string $text = ""): void
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/answerCallbackQuery";

    $postFields = [
        "callback_query_id" => $callback_id,
        "text" => $text,
        "show_alert" => false,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// =====================================================================

http_response_code(200);
exit();
