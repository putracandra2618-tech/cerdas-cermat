<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerdas Cermat - Sistem Lomba Buzzer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .landing-container {
            text-align: center;
            color: white;
            padding: 3rem;
        }

        .landing-icon {
            font-size: 6rem;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .subtitle {
            font-size: 1.5rem;
            margin-bottom: 3rem;
            opacity: 0.9;
        }

        .buttons-container {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .landing-btn {
            background: white;
            color: #667eea;
            padding: 1.5rem 3rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .landing-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        }

        .landing-btn.admin {
            background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
            color: white;
        }
    </style>
</head>

<body>
    <div class="landing-container">
        <div class="landing-icon">🎯</div>
        <h1>Cerdas Cermat</h1>
        <p class="subtitle">Sistem Lomba Cerdas Cermat Berbasis Buzzer</p>

        <div class="buttons-container">
            <a href="/participant/join.php" class="landing-btn">
                👥 Gabung sebagai Peserta
            </a>
        </div>
    </div>
</body>

</html>