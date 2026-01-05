<?php
use App\Util\Html;

/** @var array $ctx */
$api = $ctx['api'];
$get = $ctx['get'];

$q = trim((string)($get['q'] ?? ''));
$selectedId = trim((string)($get['player_id'] ?? ''));
$maxPages = (int)($get['max_pages'] ?? 20);
if ($maxPages < 1) $maxPages = 20;
if ($maxPages > 50) $maxPages = 50;

// NUEVO: si querés incluir Honor (type=7)
$includeHonor = (string)($get['include_honor'] ?? '0') === '1';

// NUEVO: pequeño delay para evitar rate-limit
$delayMs = (int)($get['delay_ms'] ?? 120);
if ($delayMs < 0) $delayMs = 0;
if ($delayMs > 1000) $delayMs = 1000;

$matches = [];
$player = null;
$alliancesById = [];
$playersById = [];

$err = null;
$stats = null;

// Por defecto NO incluimos Honor porque es el que más suele fallar.
$statTypes = [
  0 => 'Total (Puntos)',
  1 => 'Economía',
  2 => 'Investigación',
  3 => 'Militar',
];
if ($includeHonor) {
  $statTypes[7] = 'Honor';
}

try {
    $pl = $api->players();
    foreach ($pl['players'] as $p) $playersById[$p['id']] = $p;

    $al = $api->alliances();
    foreach ($al['alliances'] as $a) $alliancesById[$a['id']] = $a;

    if ($q !== '') {
        $ql = mb_strtolower($q);
        foreach ($pl['players'] as $p) {
            if (mb_stripos($p['name'], $q) !== false || mb_strtolower($p['name']) === $ql) {
                $matches[] = $p;
                if (count($matches) >= 25) break;
            }
        }
    }

    if ($selectedId !== '' && isset($playersById[$selectedId])) {
        $player = $playersById[$selectedId];

        $stats = [];
        foreach ($statTypes as $type => $label) {
            $found = null;
            $typeError = null;

            try {
                for ($page = 1; $page <= $maxPages; $page++) {
                    if ($delayMs > 0) {
                        usleep($delayMs * 1000);
                    }

                    $hs = $api->highscore(1, (int)$type, $page);

                    foreach ($hs['rows'] as $row) {
                        if ($row['id'] === $selectedId) {
                            $found = [
                                'type' => $type,
                                'label' => $label,
                                'page' => $page,
                                'position' => $row['position'],
                                'score' => $row['score'],
                                'ships' => $row['ships'],
                                'cache' => (string)($hs['meta']['cache'] ?? ''),
                                'url' => (string)($hs['meta']['url'] ?? ''),
                            ];
                            break 2;
                        }
                    }

                    // si hay menos de 100 filas, asumimos fin
                    if (count($hs['rows']) < 100) break;
                }
            } catch (\Throwable $e) {
                $typeError = $e->getMessage();
            }

            if ($typeError !== null) {
                $stats[] = [
                    'type' => $type,
                    'label' => $label,
                    'page' => null,
                    'position' => null,
                    'score' => null,
                    'ships' => null,
                    'error' => $typeError,
                ];
            } else {
                $stats[] = $found ?: [
                    'type' => $type,
                    'label' => $label,
                    'page' => null,
                    'position' => null,
                    'score' => null,
                    'ships' => null,
                ];
            }
        }
    }
} catch (\Throwable $e) {
    $err = $e->getMessage();
}

function statusLabel(string $status): string {
    return $status !== '' ? $status : '—';
}

$allianceTag = '—';
$allianceName = '—';
if ($player && $player['alliance'] !== '' && isset($alliancesById[$player['alliance']])) {
    $allianceTag = '[' . $alliancesById[$player['alliance']]['tag'] . ']';
    $allianceName = $alliancesById[$player['alliance']]['name'];
}
?>
<div class="card">
  <h2>Jugador: buscar + stats</h2>

  <?php if ($err): ?>
    <div class="alert err">Error: <?= Html::e($err) ?></div>
  <?php endif; ?>

  <form method="get" class="row">
    <input type="hidden" name="page" value="player"/>

    <label>
      Nombre (parcial o exacto)
      <input name="q" value="<?= Html::e($q) ?>" placeholder="Ej: Federico, PlayerX" />
    </label>

    <button class="btn" type="submit">Buscar</button>
  </form>

  <?php if ($q !== ''): ?>
    <div class="muted" style="margin-top:10px">Coincidencias: <?= (int)count($matches) ?></div>

    <?php if (count($matches) > 0): ?>
      <form method="get" class="row" style="margin-top:10px">
        <input type="hidden" name="page" value="player"/>
        <input type="hidden" name="q" value="<?= Html::e($q) ?>"/>

        <label>
          Elegir jugador
          <select name="player_id">
            <?php foreach ($matches as $m): ?>
              <option value="<?= Html::e($m['id']) ?>" <?= $selectedId === $m['id'] ? 'selected' : '' ?>>
                <?= Html::e($m['name']) ?> (id <?= Html::e($m['id']) ?>) — <?= Html::e($m['status'] ?: '—') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          Máx páginas a buscar (1..50)
          <input type="number" name="max_pages" min="1" max="50" value="<?= (int)$maxPages ?>"/>
        </label>

        <label>
          Delay entre requests (ms)
          <input type="number" name="delay_ms" min="0" max="1000" value="<?= (int)$delayMs ?>"/>
        </label>

        <label>
          Incluir Honor (type=7)
          <select name="include_honor">
            <option value="0" <?= !$includeHonor ? 'selected' : '' ?>>No</option>
            <option value="1" <?= $includeHonor ? 'selected' : '' ?>>Sí</option>
          </select>
        </label>

        <button class="btn secondary" type="submit">Cargar stats</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php if ($player): ?>
  <div class="card" style="margin-top:12px">
    <h3>Perfil</h3>
    <div class="kpis">
      <div class="kpi"><div class="t">Jugador</div><div class="v"><?= Html::e($player['name']) ?></div></div>
      <div class="kpi"><div class="t">ID</div><div class="v mono"><?= Html::e($player['id']) ?></div></div>
      <div class="kpi"><div class="t">Status</div><div class="v mono"><?= Html::e(statusLabel($player['status'])) ?></div></div>
      <div class="kpi"><div class="t">Alianza</div><div class="v"><?= Html::e($allianceTag . ' ' . $allianceName) ?></div></div>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <h3>Stats (highscore)</h3>
    <table>
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Posición</th>
          <th>Score</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($stats ?? [] as $s): ?>
        <tr>
          <td><?= Html::e($s['label']) ?></td>
          <td class="mono"><?= $s['position'] !== null ? (int)$s['position'] : '—' ?></td>
          <td class="mono"><?= $s['score'] !== null ? number_format((int)$s['score'], 0, ',', '.') : '—' ?></td>
          <td class="muted">
            <?php if (!empty($s['error'])): ?>
              <span style="color:#ffb3c0">Error API</span>:
              <span class="mono"><?= Html::e((string)$s['error']) ?></span>
            <?php else: ?>
              <?= $s['page'] !== null ? 'en página ' . (int)$s['page'] : 'no encontrado (en páginas probadas)' ?>
              <?= !empty($s['cache']) ? ' · cache ' . Html::e((string)$s['cache']) : '' ?>
              <?= !empty($s['url']) ? '<br><span class="mono">' . Html::e((string)$s['url']) . '</span>' : '' ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <p class="muted" style="margin-top:10px">
      Nota: si pegás <span class="mono">/api/highscore.xml</span> sin params, te va a devolver el mensaje de “category/type must be set” (es normal).
    </p>
  </div>
<?php endif; ?>
