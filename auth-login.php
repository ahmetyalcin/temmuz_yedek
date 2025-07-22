<?php
session_start();
include 'con/db.php';

$hata = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];

    try {
        $sorgu = $pdo->prepare("SELECT * FROM personel WHERE kullanici_adi = ? AND aktif = 1");
        $sorgu->execute([$kullanici_adi]);
        $personel = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($personel && password_verify($sifre, $personel['sifre'])) {
            // Session'a kullanıcı bilgilerini kaydet
            $_SESSION['user_id'] = $personel['id'];
            $_SESSION['ad_soyad'] = $personel['ad'] . ' ' . $personel['soyad'];
            $_SESSION['rol'] = $personel['rol'];
            $_SESSION['avatar'] = $personel['avatar'];
            $_SESSION['personel_id'] = $personel['id'];

            

            // Yönlendirme
            header("Location: dashboard.php");
            exit;
        } else {
            $hata = "Kullanıcı adı veya şifre hatalı!";
        }
    } catch(PDOException $e) {
        $hata = "Bir hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhysioVita - Giriş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #6c757d;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
        }

        .login-image {
            background: url('https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') center/cover;
            width: 50%;
            min-height: 500px;
            display: none;
        }

        @media (min-width: 768px) {
            .login-image {
                display: block;
            }
        }

        .login-form {
            padding: 3rem;
            width: 100%;
        }

        @media (min-width: 768px) {
            .login-form {
                width: 50%;
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: #4723D9;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #6c757d;
            font-size: 1rem;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: #4723D9;
            box-shadow: 0 0 0 0.25rem rgba(71, 35, 217, 0.25);
        }

        .btn-login {
            background: #4723D9;
            border: none;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: #3a1db3;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
        }

        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-image"></div>
            <div class="login-form">
                <div class="login-header">
                    <h1>TheraVita</h1>
                    <p>Fizyoterapi Merkezi Yönetim Sistemi</p>
                </div>

                <?php if ($hata): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $hata; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı Adı" required>
                        <label for="kullanici_adi">Kullanıcı Adı</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="sifre" name="sifre" placeholder="Şifre" required>
                        <label for="sifre">Şifre</label>
                    </div>

                    <button type="submit" class="btn btn-login btn-primary">
                        Giriş Yap
                    </button>
                </form>

                <div class="login-footer">
                    <p>© 2024 Theravita. Tüm hakları saklıdır.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>