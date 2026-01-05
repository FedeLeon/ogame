<?php
use App\Util\Html;

/** @var array $ctx */
$api = $ctx['api'];
$get = $ctx['get'];

$endpoint = (string)($get['endpoint'] ?? 'players');
$toJson = (string)($get['toJson'] ?? '0') === '1';

$category = (int)($get['category'] ?? 1);
$type = (int)($get['type'] ?? 0);
$page = (int)($get['hs_page'] ?? 1);

$err = null;
$raw = null;
$meta = null;

$allowed = ['serverData','players','alliances','universe','highscore'];
if (!in_array($endpoint, $allowed, true)) $endpoint = 'players';

try {
    $params = [];
    if ($endpoint === 'highscore') {
        $params = ['category' => $category, 'type' => $type, 'page' => $page];
    }
    $r = $api->raw($endpoint, $params, $toJson);
    $raw = $r['body'];
    $meta = $r['meta'];
} catch (\Throwable $e) {
    $err = $e->getMessage();
}
?>
<div class="card">
  <h2>API raw (debug)</h2>

  <?php if ($err): ?>
    <div class="alert err">Error: <?= Html::e($err) ?></div>
  <?php endif; ?>

  <form method="get" class="row">
    <input type="hidden" name="page" value="api"/>
    <label>
      Endpoint
      <select name="endpoint">
        <?php foreach (['serverData','players','alliances','universe','highscore'] as $ep): ?>
          <option value="<?= Html::e($ep) ?>" <?= $ep === $endpoint ? 'selected' : '' ?>><?= Html::e($ep) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      toJson=1
      <select name="toJson">
        <option value="0" <?= !$toJson ? 'selected' : '' ?>>No</option>
        <option value="1" <?= $toJson ? 'selected' : '' ?>>Sí</option>
      </select>
    </label>

    <label>
      Highscore: category
      <input type="number" name="category" min="1" max="2" value="<?= (int)$category ?>"/>
    </label>
    <label>
      Highscore: type
      <input type="number" name="type" min="0" max="7" value="<?= (int)$type ?>"/>
    </label>
    <label>
      Highscore: page
      <input type="number" name="hs_page" min="1" max="200" value="<?= (int)$page ?>"/>
    </label>

    <button class="btn" type="submit">Cargar</button>
  </form>

  <?php if ($meta): ?>
    <div class="muted" style="margin-top:10px">
      Cache: <b><?= Html::e((string)($meta['cache'] ?? '—')) ?></b> ·
      URL: <span class="mono"><?= Html::e((string)($meta['url'] ?? '')) ?></span>
    </div>
  <?php endif; ?>
</div>

<?php if ($raw !== null): ?>
  <div class="card" style="margin-top:12px">
    <h3>Respuesta</h3>
    <textarea readonly><?= Html::e($raw) ?></textarea>
  </div>
<?php endif; ?>
