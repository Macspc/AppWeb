<?php
// Zona de tempo e conexão
date_default_timezone_set("America/Sao_Paulo");
$conn = new mysqli("localhost", "root", "", "buscar");
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

$linha = $_GET['linha'] ?? '';
$diaSemana = date("w");
$dia = match ($diaSemana) {
    0 => 'DOM',
    6 => 'SAB',
    default => 'DU'
};
$agora = date("H:i");

// Buscar horários por linha, dia e ordenar
$stmt = $conn->prepare("
    SELECT Horario, Partida 
    FROM linhas 
    WHERE Linha = ? AND Dias = ? 
    ORDER BY Partida, STR_TO_DATE(Horario, '%H:%i')
");
$stmt->bind_param("ss", $linha, $dia);
$stmt->execute();
$result = $stmt->get_result();

// Organizar por partida
$horariosPorPartida = [];
while ($row = $result->fetch_assoc()) {
    $horariosPorPartida[$row['Partida']][] = $row['Horario'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Itinerário - Linha <?= htmlspecialchars($linha) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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
                aria-expanded="false" aria-label="Toggle navigation">
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

<div class="container mt-5 pt-5">
    <h2 class="text-center mb-4">Horários - Linha <?= htmlspecialchars($linha) ?> <small class="text-muted">(<?= $dia ?>)</small></h2>

    <?php foreach ($horariosPorPartida as $partida => $horarios): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Partida: <?= htmlspecialchars($partida) ?>
            </div>
            <div class="card-body d-flex flex-wrap gap-3 justify-content-center">
                <?php
                $proximo = null;
                foreach ($horarios as $hora) {
                    if ($hora > $agora) {
                        $proximo = $hora;
                        break;
                    }
                }

                $ultimoAntesDoProximo = null;
                foreach ($horarios as $hora) {
                    if ($hora < $agora || ($proximo && $hora < $proximo)) {
                        $ultimoAntesDoProximo = $hora;
                    }
                }

                foreach ($horarios as $hora):
                    $ativo = $hora == $ultimoAntesDoProximo;
                ?>
                <button
                    class="btn <?= $ativo ? 'btn-success' : 'btn-outline-secondary' ?>"
                    <?= $ativo ? 'data-bs-toggle="modal" data-bs-target="#modalMapa"' : 'disabled' ?>
                    data-horario="<?= $hora ?>"
                    data-partida="<?= htmlspecialchars($partida) ?>"
                >
                    <?= $hora ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-4">
        <button class="btn btn-outline-primary btn-lg" onclick="window.close()">Voltar</button>
    </div>
</div>

<!-- Modal para o mapa -->
<div class="modal fade" id="modalMapa" tabindex="-1" aria-labelledby="modalMapaLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalMapaLabel">Localização</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body p-0 d-flex flex-column" style="height: 100%;">
        <div id="map" style="flex: 1; width: 100%;"></div>
        <div class="p-3 bg-light">
          <label class="form-label">Partida e horário selecionado:</label>
          <textarea id="infoTexto" class="form-control mb-3" rows="2" readonly></textarea>
          <div class="d-flex justify-content-end">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const modal = document.getElementById('modalMapa');
  const infoTextarea = document.getElementById('infoTexto');

  modal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const horario = button.getAttribute('data-horario');
    const partida = button.getAttribute('data-partida');

    infoTextarea.value = `Partida: ${partida}\nHorário: ${horario}`;

    setTimeout(() => {
      const map = L.map('map').setView([-23.1001, -45.7076], 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
      }).addTo(map);

      const rodoviaria = L.marker([-23.1001, -45.7076]).bindPopup("Rodoviária de Caçapava");
      rodoviaria.addTo(map);

      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            map.setView([lat, lng], 16);

            L.marker([lat, lng]).addTo(map)
              .bindPopup("Você está aqui")
              .openPopup();
          },
          () => console.warn("Sem acesso à localização, usando rodoviária.")
        );
      } else {
        console.warn("Geolocalização não suportada, mostrando rodoviária.");
      }
    }, 300);
  });
</script>

</body>
</html>
