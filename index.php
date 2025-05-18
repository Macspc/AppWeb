<?php
// Conexão com banco de dados
$conn = new mysqli("localhost", "root", "", "buscar");
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

// Detectar dia da semana
$diaSemana = date("w");
$dia = match ($diaSemana) {
    0 => 'DOM',
    6 => 'SAB',
    default => 'DU'
};

// Detectar tipo de dispositivo
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$ehMobile = preg_match('/mobile|android|tablet|ipad|iphone/i', $userAgent);

// Buscar linhas disponíveis
$stmt = $conn->prepare("SELECT DISTINCT Linha FROM linhas WHERE Dias = ?");
$stmt->bind_param("s", $dia);
$stmt->execute();
$result = $stmt->get_result();

$linhas = [];
while ($row = $result->fetch_assoc()) {
    $linhas[] = $row['Linha'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Transporte</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light text-dark">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="logo.png" alt="Logo" height="30" class="d-inline-block align-text-top me-2">
            BusCar
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMenu" aria-controls="navbarMenu"
                aria-expanded="false" aria-label="Alternar menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Contato</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Conteúdo principal -->
<div class="container mt-5 pt-5">
    <h1 class="text-center mb-4">Bem-vindo ao sistema de horários de ônibus</h1>

    <p class="lead text-center">Escolha a linha desejada abaixo para ver os próximos horários:</p>


    <!-- Aqui poderia vir uma lista de botões com linhas, por exemplo -->
    <div class="d-flex flex-wrap justify-content-center gap-3">
       

<?php foreach ($linhas as $linha): ?>
        
    <button class="btn btn-outline-primary btn-lg" onclick="abrirItinerario('<?= $linha ?>')">Linha <?= $linha ?></button>

    <?php endforeach; ?>

<div id="detalhes"></div>




    
    
    
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function mostrarItinerario(linha) {
    fetch("itinerario.php?linha=" + linha)
        .then(res => res.text())
        .then(data => {
            document.getElementById("detalhes").style.display = "block";
            document.getElementById("detalhes").innerHTML = data;
        });
}
</script>
<script>
function abrirItinerario(linha) {
    const largura = 500, altura = 600;
    const x = (screen.width / 2) - (largura / 2);
    const y = (screen.height / 2) - (altura / 2);

    window.open("popup_itinerario.php?linha=" + linha, "Itinerario", `width=${largura},height=${altura},left=${x},top=${y}`);
}
</script>
</body>
</html>
