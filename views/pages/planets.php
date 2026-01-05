<?php
use App\Util\Html;
use App\Util\Coords;

/** @var array $ctx */
$api = $ctx['api'];
$get = $ctx['get'];

$q = trim((string)($get['q'] ?? ''));
$onlyInactives = (string)($get['only_inactives'] ?? '0');
$limit = (int)($get['limit'] ?? 50);
if ($limit < 1) $limit = 50;
if ($limit > 500) $limit = 500;

$err = null;
$results = [];

try {
    $playersData = $api->players();
    $playersById = [];
    $playersByName = [];
    foreach ($playersData['players'] as $p) {
        $playersById[$p['id']] = $p;
        $playersByName[mb_strtolower($p['name'])] = $p;
    }

    $alliancesData = $api->alliances();
    $alliancesById = [];
    foreach ($alliancesData['alliances'] as $a) $alliancesById[$a['id']] = $a;

    if ($q !== '') {
        $universeData = $api->universe();
        $planets = $universeData['planets'];

        $coords = Coords::parse($q);
        $ql = mb_strtolower($q);

        if ($coords) {
            foreach ($planets as $pl) {
                if ($pl['galaxy'] === $coords['g'] && $pl['system'] === $coords['s'] && $pl['position'] === $coords['p']) {
                    $results[] = $pl;
                    break;
                }
            }
        } else {
            // 1) si coincide exacto con nombre de jugador
            if (isset($playersByName[$ql])) {
                $pid = $playersByName[$ql]['id'];
                foreach ($planets as $pl) {
                    if ($pl['playerId'] === $pid) $results[] = $pl;
                    if (count($results) >= $limit) break;
                }
            } else {
                // 2) fuzzy: planeta name contains OR owner name contains
                foreach ($planets as $pl) {
                    $owner = $playersById[$pl['playerId']] ?? null;
                    $ownerName = $owner ? mb_strtolower($owner['name']) : '';
                    if (mb_stripos($pl['name'], $q) !== false || ($ownerName !== '' && mb_stripos($ownerName, $q) !== false)) {
                        $results[] = $pl;
                        if (count($results) >= $limit) break;
                    }
                }
            }
        }

        if ($onlyInactives === '1') {
            $results = array_values(array_filter($results, function(array $pl) use ($playersById) {
                $owner = $playersById[$pl['playerId']] ?? null;
                if (!$owner) return false;
                $st = (string)($owner['status'] ?? '');
                return (strpos($st, 'i') !== false) || (strpos($st, 'I') !== false);
            }));
        }
    }
} catch (\Throwable $e) {
    $err = $e->getMessage();
}

function statusLabel(string $s): string { return $s !== '' ? $s : '—'; }
?>
<div class="card">
  <h2>Planetas: buscar</h2>
  <p class="muted">
    Busca en <span class="mono">universe.xml</span> (puede tardar la primera vez por cache).
  </p>

  <?php if ($err): ?>
    <div class="alert err">Error: <?= Html::e($err) ?></div>
  <?php endif; ?>

  <form method="get" class="row">
    <input type="hidden" name="page" value="planets"/>
    <label>
      Consulta (coords / nombre planeta / nombre jugador)
      <input name="q" value="<?= Html::e($q) ?>" placeholder="Ej: [1:200:8] | nombre planeta | nombre jugador"/>
    </label>
    <label>
      Solo inactivos
      <select name="only_inactives">
        <option value="0" <?= $onlyInactives === '0' ? 'selected' : '' ?>>No</option>
        <option value="1" <?= $onlyInactives === '1' ? 'selected' : '' ?>>Sí (i/I)</option>
      </select>
    </label>
    <label>
      Límite (1..500)
      <input type="number" name="limit" min="1" max="500" value="<?= (int)$limit ?>"/>
    </label>
    <button class="btn" type="submit">Buscar</button>
  </form>
</div>

<?php if ($q !== ''): ?>
  <div class="card" style="margin-top:12px">
    <h3>Resultados (<?= (int)count($results) ?>)</h3>
    <?php if (count($results) === 0): ?>
      <div class="muted">No se encontró nada.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Coords</th>
            <th>Planeta</th>
            <th>Dueño</th>
            <th>Status</th>
            <th>Alianza</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $pl): ?>
          <?php
            // (re-fetching minimal maps would be repetitive; keep it simple for now: show playerId)
            // For a "full map", we would pass maps through ctx or rebuild; here, keep owner resolution simple:
          ?>
          <tr>
            <td class="mono"><?= Html::e(Coords::toString($pl['galaxy'], $pl['system'], $pl['position'])) ?></td>
            <td><?= Html::e($pl['name'] ?: '—') ?></td>
            <td class="mono">playerId <?= Html::e($pl['playerId'] ?: '—') ?></td>
            <td class="mono">—</td>
            <td>—</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <p class="muted" style="margin-top:10px">
        Nota: si querés mostrar “dueño/status/alianza” por nombre en esta tabla (en vez de playerId),
        lo armamos con un map compartido (queda mejor y es fácil).
      </p>
    <?php endif; ?>
  </div>
<?php endif; ?>
