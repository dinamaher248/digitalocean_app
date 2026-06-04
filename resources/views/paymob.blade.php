{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Redirect</title>
</head>
<body>
    <h1>Redirecting...</h1>
    <p>Please wait while we redirect you back to the app.</p>

    <script>
        // Redirect to the Flutter app's Deep Link
        const deepLink = "myapp://payment-status";
        window.location.href = deepLink;

        // Fallback: Show a message if redirect fails
        setTimeout(() => {
            document.body.innerHTML = '<h1>Redirect Failed</h1><p>Please return to the app manually.</p>';
        }, 3000);
    </script>
</body>
</html> --}}

{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Complete</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
        h1 { color: #008080; }
        p { font-size: 18px; }
        button { padding: 10px 20px; font-size: 16px; background-color: #008080; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>تم الدفع!</h1>
    <p>يرجى العودة إلى التطبيق لمتابعة رحلتك.</p>
    <button onclick="window.close()">العودة إلى التطبيق</button>
</body>
</html> --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Complete</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
        h1 { color: #008080; }
        p { font-size: 18px; }
        a { display: inline-block; padding: 10px 20px; font-size: 16px; background-color: #008080; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>تم الدفع!</h1>
    <p>يرجى العودة إلى التطبيق لمتابعة رحلتك.</p>
    <a href="javascript:history.back()">العودة إلى التطبيق</a>
</body>
</html>