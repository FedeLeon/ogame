<?php
use App\Util\Html;
use App\Util\Coords;

/** @var array $ctx */
$api = $ctx['api'];
$get = $ctx['get'];

$my = trim((string)($get['my'] ?? ''));
$radius = (int)($get['radius'] ?? 15);
$limit = (int)($get['limit'] ?? 50);
if ($radius < 0) $radius = 0;
if ($radius > 499) $radius = 499;
if ($limit < 1) $limit = 50;
if ($limit > 500) $limit = 500;

$err = null;
$results = [];
$origin = null;

try {
    if ($my !== '') {
        $origin = Coords::parse($my);
        if (!$origin) {
            throw new \RuntimeException('Coordenadas inválidas. Formato: [g:s:p]');
        }

        $playersData = $api->players();
        $playersById = [];
        foreach ($playersData['players'] as $p) $playersById[$p['id']] = $p;

        $universeData = $api->universe();
        $planets = $universeData['planets'];

        foreach ($planets as $pl) {
            if ($pl['galaxy'] !== $origin['g']) continue;
            if (abs($pl['system'] - $origin['s']) > $radius) continue;

            $owner = $playersById[$pl['playerId']] ?? null;
            if (!$owner) continue;
            $st = (string)($owner['status'] ?? '');
            $inactive = (strpos($st, 'i') !== false) || (strpos($st, 'I') !== false);
            if (!$inactive) continue;

            $d = Coords::distance($origin, ['g'=>$pl['galaxy'],'s'=>$pl['system'],'p'=>$pl['position']]);
            $results[] = $pl + [
                'distance' => $d,
                'ownerName' => (string)$owner['name'],
                'ownerStatus' => (string)$owner['status'],
            ];
        }

        usort($results, fn($a,$b) => $a['distance'] <=> $b['distance']);
        $results = array_slice($results, 0, $limit);
    }
} catch (\Throwable $e) {
    $err = $e->getMessage();
}

function statusLabel(string $s): string { return $s !== '' ? $s : '—'; }
?>
<div class="card">
  <h2>Inactivos cerca</h2>
  <p class="muted">
    Filtra planetas de <span class="mono">universe.xml</span> cuyo dueño tenga status i/I en <span class="mono">players.xml</span>.
    (Solo misma galaxia, radio en sistemas).
  </p>

  <?php if ($err): ?>
    <div class="alert err">Error: <?= Html::e($err) ?></div>
  <?php endif; ?>

  <form method="get" class="row">
    <input type="hidden" name="page" value="inactives"/>
    <label>
      Mis coords
      <input name="my" value="<?= Html::e($my) ?>" placeholder="[1:200:8]" />
    </label>
    <label>
      Radio (sistemas)
      <input type="number" name="radius" min="0" max="499" value="<?= (int)$radius ?>"/>
    </label>
    <label>
      Límite
      <input type="number" name="limit" min="1" max="500" value="<?= (int)$limit ?>"/>
    </label>
    <button class="btn" type="submit">Buscar</button>
  </form>
</div>

<?php if ($my !== '' && !$err): ?>
  <div class="card" style="margin-top:12px">
    <h3>Resultados (<?= (int)count($results) ?>)</h3>
    <?php if (count($results) === 0): ?>
      <div class="muted">No se encontraron planetas inactivos en ese rango.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Coords</th>
            <th>Distancia</th>
            <th>Planeta</th>
            <th>Dueño</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $r): ?>
          <tr>
            <td class="mono"><?= Html::e(Coords::toString($r['galaxy'],$r['system'],$r['position'])) ?></td>
            <td class="mono"><?= number_format((int)$r['distance'], 0, ',', '.') ?></td>
            <td><?= Html::e($r['name'] ?: '—') ?></td>
            <td><?= Html::e($r['ownerName'] ?: '—') ?></td>
            <td class="mono"><?= Html::e(statusLabel($r['ownerStatus'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>
