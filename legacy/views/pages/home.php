<?php
use App\Util\Html;

/** @var array $ctx */
$api = $ctx['api'];

$data = null;
$err = null;

try {
    $data = $api->serverData();
} catch (\Throwable $e) {
    $err = $e->getMessage();
}
?>
<div class="card">
  <h2>Inicio</h2>
  <p class="muted">
    Este tool usa solo la API pública. Si querés buscar planetas, vas a necesitar cargar <span class="mono">universe.xml</span> (pesado).
  </p>

  <?php if ($err): ?>
    <div class="alert err">Error: <?= Html::e($err) ?></div>
  <?php elseif ($data): ?>
    <div class="kpis">
      <div class="kpi"><div class="t">Dominio</div><div class="v mono"><?= Html::e($data['data']['domain'] ?: '—') ?></div></div>
      <div class="kpi"><div class="t">Version</div><div class="v mono"><?= Html::e($data['data']['version'] ?: '—') ?></div></div>
      <div class="kpi"><div class="t">Vel. flota</div><div class="v mono"><?= Html::e($data['data']['speedFleet'] ?: '—') ?></div></div>
      <div class="kpi"><div class="t">Eco / Res</div><div class="v mono"><?= Html::e(($data['data']['speedEconomy'] ?: '—') . ' / ' . ($data['data']['speedResearch'] ?: '—')) ?></div></div>
    </div>

    <div class="muted" style="margin-top:10px">
      Cache: <b><?= Html::e((string)($data['meta']['cache'] ?? '—')) ?></b> ·
      ts: <span class="mono"><?= Html::e((string)($data['data']['timestamp'] ?? '')) ?></span>
    </div>
  <?php endif; ?>
</div>

<div class="grid2" style="margin-top:12px">
  <div class="card">
    <h3>Herramientas incluidas</h3>
    <ul class="muted">
      <li>Stats de jugadores via <span class="mono">highscore.xml</span></li>
      <li>Búsqueda de planetas por coords/jugador/nombre via <span class="mono">universe.xml</span></li>
      <li>Inactivos cerca (misma galaxia) por status en <span class="mono">players.xml</span></li>
      <li>Calculadora de distancia (coords)</li>
    </ul>
  </div>
  <div class="card">
    <h3>Notas</h3>
    <p class="muted">
      Todo es server-side. Sin JavaScript. Para “stats” el ranking está paginado, así que la búsqueda puede tardar si el jugador está muy abajo.
    </p>
  </div>
</div>
