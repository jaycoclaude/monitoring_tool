<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Development</title>

    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Poppins", sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
            text-align: center;
        }

        .under-dev-container {
            padding: 2rem;
        }

        .under-dev-box {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 3rem 2rem;
            max-width: 400px;
            margin: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .under-dev-box:hover {
            transform: scale(1.03);
        }

        .under-dev-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #f0b400;
            animation: spin 4s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        h2 {
            font-size: 1.8rem;
            margin-bottom: 0.8rem;
            color: #222;
        }

        p {
            font-size: 1rem;
            margin-bottom: 1.8rem;
            opacity: 0.8;
        }

        .btn-back {
            display: inline-block;
            background: #0078d7;
            color: #fff;
            padding: 0.8rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #005fa3;
            box-shadow: 0 4px 10px rgba(0, 120, 215, 0.3);
        }

        .btn-back i {
            margin-right: 0.5rem;
        }

        @media (max-width: 480px) {
            .under-dev-box {
                padding: 2rem 1.5rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .under-dev-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>

    <div class="under-dev-container">
        <div class="under-dev-box">
            <i class="fas fa-tools under-dev-icon"></i>
            <h2>Module Coming Soon!</h2>
            <p>We're working hard to bring this Module to you. Please check back later!</p>
            <a href="../landing_page.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Homepage
            </a>
        </div>
    </div>

</body>
</html>
