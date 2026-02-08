<?php

define("BOT_TOKEN", "8548197752:AAFw4PyjB0CglbAmGvpJG-4cQ_fvsYgeA5g");
define("GROUP_CHAT_ID", "-1003850836793");

$success = isset($_GET["success"]);
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "—");
    $phone = trim($_POST["phone"] ?? "—");
    $message = trim($_POST["message"] ?? "—");
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
        htmlspecialchars($message) .
        "\n" .
        "🏢 Кабинет: <b>" .
        htmlspecialchars($room) .
        "</b>\n\n" .
        "───────────────\n" .
        "Статус: <b>new</b> • " .
        date("d.m.Y H:i");

    // Начальная клавиатура для новой заявки
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

    $data = [
        "chat_id" => GROUP_CHAT_ID,
        "text" => $text,
        "parse_mode" => "HTML",
        "reply_markup" => $reply_markup,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    $response = json_decode($result, true);

    if ($http_code === 200 && ($response["ok"] ?? false) === true) {
        header("Location: " . $_SERVER["REQUEST_URI"] . "?success=1");
        exit();
    }

    // Если ошибка — показываем пользователю
    $error_msg = "Не удалось отправить заявку.";
    if ($curl_err) {
        $error_msg .= " (cURL: " . $curl_err . ")";
    } elseif (isset($response["description"])) {
        $error_msg .= " (" . $response["description"] . ")";
    } elseif ($http_code > 0) {
        $error_msg .= " (HTTP " . $http_code . ")";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php if ($success): ?>
        <div class="message success" id="success-msg">
            <h2>Заявка успешно отправлена!</h2>
            <p>Мы свяжемся с вами в ближайшее время.</p>
        </div>

        <script>
            setTimeout(() => {
                const msg = document.getElementById('success-msg');
                if (msg) {
                    msg.classList.add('fade-out');
                    setTimeout(() => msg.style.display = 'none', 800);
                }
            }, 2200);
        </script>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="message error">
            <h2>Ошибка отправки</h2>
            <p><?= htmlspecialchars($error_msg) ?></p>
            <p>Попробуйте позже или напишите нам напрямую.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="text"    name="name"    placeholder="Ваше имя"     required>
        <input type="tel"     name="phone"   placeholder="Телефон" required pattern="\+?[0-9\s\-\(\)]{7,}" title="Введите корректный номер телефона">
        <input type="text"    name="room"    placeholder="Кабинет" maxlength="4" required inputmode="numeric" pattern="[0-9A-Za-z\s-]*">
        <textarea style="resize: none;" name="message" rows="5" placeholder="Что вас интересует?" required></textarea>
        <button type="submit">Отправить заявку</button>
    </form>

</body>
</html>
