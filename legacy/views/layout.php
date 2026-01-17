<?php
use App\Config;
use App\Util\Html;

/** @var string $content */
/** @var array $ctx */
$page = (string)($ctx['page'] ?? 'home');

$tabs = [
  'home' => 'Inicio',
  'player' => 'Jugador (stats)',
  'planets' => 'Planetas (buscar)',
  'inactives' => 'Inactivos cerca',
  'distance' => 'Distancia',
  'api' => 'API raw',
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>OGame AR 164 — Tool (PHP)</title>
  <link rel="stylesheet" href="/assets/styles.css?v=<?= Html::e(Config::VERSION) ?>">
</head>
<body>
<header class="header">
  <h1>OGame AR — Universo 164 — Tool (API pública) · PHP</h1>
  <div class="sub">
    Solo lectura de endpoints públicos <span class="mono">/api/*.xml</span>. Sin login, sin automatización de acciones.
  </div>
</header>

<div class="wrap">
  <nav>
    <?php foreach ($tabs as $k => $label): ?>
      <a class="navlink <?= $k === $page ? 'active' : '' ?>" href="<?= Html::e(Html::url($k)) ?>">
        <?= Html::e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <main>
    <?= $content ?>
  </main>
</div>

<footer class="footer">
  Cache local en archivos (para no saturar la API). Versión <?= Html::e(Config::VERSION) ?>.
</footer>
</body>
</html>
