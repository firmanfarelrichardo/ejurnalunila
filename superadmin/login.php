<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="form-container">
        <form action="../api/proses_login.php" method="POST">
            <h2>Masuk sebagai Administrator</h2>
            <div class="input-grup">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-grup">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="submit-btn">Masuk</button>
        </form>
    </div>
</body>
</html>