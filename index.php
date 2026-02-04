<?php

define('BOT_TOKEN',     '8548197752:AAFw4PyjB0CglbAmGvpJG-4cQ_fvsYgeA5g');
define('GROUP_CHAT_ID', '-1003850836793');

$success = isset($_GET['success']);
$error   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name']    ?? '—');
    $phone   = trim($_POST['phone']   ?? '—');
    $message = trim($_POST['message'] ?? '—');
    $room    = trim($_POST['room']    ?? '—');

    $text = "Новая заявка с сайта!\n\n" .
            "👤 Имя: <b>"       . htmlspecialchars($name)    . "</b>\n" .
            "📞 Телефон: <b>"    . htmlspecialchars($phone)   . "</b>\n" .
            "💬 Сообщение:\n"    . htmlspecialchars($message) . "\n" .
            "🏢 Кабинет: <b>"    . htmlspecialchars($room)    . "</b>";

    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";

    $data = [
        'chat_id'    => GROUP_CHAT_ID,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($result, true);

    if ($http_code === 200 && isset($response['ok']) && $response['ok'] === true) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        form {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #0066ff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 17px;
            cursor: pointer;
        }
        button:hover {
            background: #0055dd;
        }
        .message {
            text-align: center;
            margin: 20px auto;
            padding: 16px;
            border-radius: 6px;
            max-width: 500px;
            opacity: 1;
            transition: opacity 0.6s ease-out;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .fade-out {
            opacity: 0;
        }
    </style>
</head>
<body>

    <?php if ($success): ?>
        <div class="message success" id="success-msg">
            <h2>Заявка успешно отправлена!</h2>
            <p>Мы свяжемся с вами в ближайшее время.</p>
        </div>

        <script>
            // Исчезновение через 2 секунды
            setTimeout(function() {
                const msg = document.getElementById('success-msg');
                if (msg) {
                    msg.classList.add('fade-out');
                    setTimeout(() => {
                        msg.style.display = 'none';
                    }, 700); 
                }
            }, 2000);
        </script>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <h2>Ошибка при отправке заявки</h2>
            <p>Пожалуйста, попробуйте позже или свяжитесь с нами другим способом.</p>
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