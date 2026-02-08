<?php

define("BOT_TOKEN", "8548197752:AAFw4PyjB0CglbAmGvpJG-4cQ_fvsYgeA5g");
define("GROUP_CHAT_ID", "-1003850836793");

$success = isset($_GET["success"]);
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "—");
    $phone = trim($_POST["phone"] ?? "—");
    $msg = trim($_POST["message"] ?? "—");
    $room = trim($_POST["room"] ?? "—");

    $text =
        "🆕 Новая заявка с сайта!\n\n" .
        "👤 Имя: <b>" .
        htmlspecialchars($name) .
        "</b>\n" .
        "📞 Телефон: <b>" .
        htmlspecialchars($phone) .
        "</b>\n" .
        "💬 Сообщение:\n" .
        htmlspecialchars($msg) .
        "\n" .
        "🏢 Кабинет: <b>" .
        htmlspecialchars($room) .
        "</b>\n\n";

    $reply_markup = json_encode([
        "inline_keyboard" => [
            [
                [
                    "text" => "🚀 Взять в работу",
                    "callback_data" => "set_inwork",
                ],
                [
                    "text" => "❌ Отклонить сразу",
                    "callback_data" => "set_rejected",
                ],
            ],
        ],
    ]);

    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            "chat_id" => GROUP_CHAT_ID,
            "text" => $text,
            "parse_mode" => "HTML",
            "reply_markup" => $reply_markup,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
    ]);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    $response = json_decode($result, true);

    if ($http_code === 200 && ($response["ok"] ?? false)) {
        header("Location: " . $_SERVER["REQUEST_URI"] . "?success=1");
        exit();
    }

    $error_msg = "Не удалось отправить заявку";
    if ($curl_err) {
        $error_msg .= " (cURL: $curl_err)";
    } elseif (isset($response["description"])) {
        $error_msg .= " ({$response["description"]})";
    } elseif ($http_code > 0) {
        $error_msg .= " (HTTP $http_code)";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Форма заявки</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .message {
            text-align:center;
            margin:20px auto;
            padding:16px;
            border-radius:8px;
            max-width:500px;
        }
        .success { background:#e6ffe6; color:#006600; border:1px solid #b3ffb3; }
        .error   { background:#ffe6e6; color:#990000; border:1px solid #ffb3b3; }
        form {
            max-width:500px;
            margin:40px auto;
            padding:30px;
            background:#fff;
            border-radius:8px;
            box-shadow:0 2px 10px rgba(0,0,0,.08);
        }
        input, textarea {
            width:100%;
            padding:12px;
            margin:10px 0;
            border:1px solid #ddd;
            border-radius:6px;
            box-sizing:border-box;
            font-size:16px;
        }
        button {
            width:100%;
            padding:14px;
            background:#0066ff;
            color:#fff;
            border:none;
            border-radius:6px;
            font-size:17px;
            cursor:pointer;
        }
        button:hover { background:#0055dd; }
        .fade-out { opacity:0; transition:opacity .7s; }
    </style>
</head>
<body>

<?php if ($success): ?>
<div class="message success" id="msg-success">
    <h2>Заявка отправлена!</h2>
    <p>Мы свяжемся с вами в ближайшее время.</p>
</div>
<script>
setTimeout(() => {
    const el = document.getElementById('msg-success');
    if (el) {
        el.classList.add('fade-out');
        setTimeout(() => el.style.display = 'none', 700);
    }
}, 2200);
</script>
<?php endif; ?>

<?php if ($error_msg): ?>
<div class="message error">
    <h2>Ошибка</h2>
    <p><?= htmlspecialchars($error_msg) ?></p>
    <p>Попробуйте позже или свяжитесь с нами.</p>
</div>
<?php endif; ?>

<form method="post">
    <input type="text"     name="name"    placeholder="Ваше имя"     required>
    <input type="tel"      name="phone"   placeholder="Телефон"      required pattern="\+?[0-9\s\-\(\)]{7,}" title="Введите корректный номер">
    <input type="text"     name="room"    placeholder="Кабинет"      maxlength="4" required inputmode="numeric" pattern="[0-9A-Za-z\s-]*">
    <textarea style='resize: none;' name="message" rows="5" placeholder="Что вас интересует?" required></textarea>
    <button type="submit">Отправить заявку</button>
</form>

</body>
</html>
