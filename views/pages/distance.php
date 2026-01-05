<?php
use App\Util\Html;
use App\Util\Coords;

/** @var array $ctx */
$get = $ctx['get'];

$aIn = trim((string)($get['a'] ?? ''));
$bIn = trim((string)($get['b'] ?? ''));

$err = null;
$res = null;

if ($aIn !== '' && $bIn !== '') {
    try {
        $a = Coords::parse($aIn);
        $b = Coords::parse($bIn);
        if (!$a || !$b) throw new \RuntimeException('Coordenadas inválidas. Formato: [g:s:p]');
        $res = [
            'a' => $a,
            'b' => $b,
            'distance' => Coords::distance($a, $b),
            'dg' => abs($a['g'] - $b['g']),
            'ds' => abs($a['s'] - $b['s']),
            'dp' => abs($a['p'] - $b['p']),
        ];
    } catch (\Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<div class="card">
  <h2>Distancia (coords)</h2>

  <?php if ($err): ?>
    <div class="alert err">Error: <?= Html::e($err) ?></div>
  <?php endif; ?>

  <form method="get" class="row">
    <input type="hidden" name="page" value="distance"/>
    <label>
      Origen
      <input name="a" value="<?= Html::e($aIn) ?>" placeholder="[1:200:8]"/>
    </label>
    <label>
      Destino
      <input name="b" value="<?= Html::e($bIn) ?>" placeholder="[1:205:12]"/>
    </label>
    <button class="btn" type="submit">Calcular</button>
  </form>
</div>

<?php if ($res): ?>
  <div class="card" style="margin-top:12px">
    <h3>Resultado</h3>
    <div class="kpis">
      <div class="kpi"><div class="t">Origen</div><div class="v mono"><?= Html::e(Coords::toString($res['a']['g'],$res['a']['s'],$res['a']['p'])) ?></div></div>
      <div class="kpi"><div class="t">Destino</div><div class="v mono"><?= Html::e(Coords::toString($res['b']['g'],$res['b']['s'],$res['b']['p'])) ?></div></div>
      <div class="kpi"><div class="t">Distancia</div><div class="v mono"><?= number_format((int)$res['distance'], 0, ',', '.') ?></div></div>
      <div class="kpi"><div class="t">Δ g/s/p</div><div class="v mono"><?= (int)$res['dg'] ?>/<?= (int)$res['ds'] ?>/<?= (int)$res['dp'] ?></div></div>
    </div>
  </div>
<?php endif; ?>
