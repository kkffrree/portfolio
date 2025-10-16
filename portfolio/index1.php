<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
use Yasumi\Yasumi;

$year = isset($_POST['year']) && is_numeric($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
$month = isset($_POST['month']) && is_numeric($_POST['month']) ? (int)$_POST['month'] : (int)date('n');
$total_budget = isset($_POST['total_budget']) && is_numeric($_POST['total_budget']) ? (int)$_POST['total_budget'] : 0;

$campaigns = [];
$starts = $_POST['campaign_start'] ?? [];
$ends   = $_POST['campaign_end'] ?? [];
$rates  = $_POST['campaign_rate'] ?? [];
foreach ($starts as $i => $s) {
    $e = $ends[$i] ?? '';
    $r = $rates[$i] ?? '';
    if ($s && $e && is_numeric($r)) {
        $campaigns[] = ['start' => $s, 'end' => $e, 'rate' => (float)$r];
    }
}

// 基準値（任意）
$base = ['weekday' => 100000, 'holiday' => 300000];

/**
 * computeDailyBudgets
 *  - $mode: 'last_adjust' or 'units'
 */
function computeDailyBudgets($year, $month, $totalBudget, $base, $campaigns, $roundUnit = 10000, $mode = 'last_adjust', $useYasumi = false) {
    // optional Yasumi
    if ($useYasumi && class_exists('\\Yasumi\\Yasumi')) {
        try {
            $holidays = \Yasumi\Yasumi::create('Japan', $year, 'ja_JP');
        } catch (Throwable $e) {
            $holidays = null;
        }
    } else {
        $holidays = null;
    }

    $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $weights = [];

    for ($d = 1; $d <= $days; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dow = (int)date('N', strtotime($date));

        $isHoliday = ($dow >= 6);
        if ($holidays) {
            try {
                if ($holidays->isHoliday(new DateTime($date))) $isHoliday = true;
            } catch (Exception $e) { /* ignore */ }
        }

        // 必ず初期化する
        $weight = $isHoliday ? $base['holiday'] : $base['weekday'];

        foreach ($campaigns as $c) {
            if ($date >= $c['start'] && $date <= $c['end']) {
                $weight *= (float)$c['rate'];
            }
        }
        $weights[$date] = $weight;
    }

    $totalWeight = array_sum($weights);
    if ($totalWeight <= 0) {
        // フォールバック: 均等重み
        foreach ($weights as $date => $_) $weights[$date] = 1;
        $totalWeight = count($weights);
    }

    // raw（理想値）
    $raw = [];
    foreach ($weights as $date => $w) {
        $raw[$date] = ($totalBudget * ($w / $totalWeight));
    }

    $precision = - (int) log10($roundUnit); // round($v, $precision) で万単位丸め

    if ($mode === 'last_adjust') {
        $daily = [];
        foreach ($raw as $date => $r) {
            $daily[$date] = (int) round($r, $precision); // 万単位で丸める
        }
        // 合計差分を最後の日に調整
        $allocated = array_sum($daily);
        $diff = $totalBudget - $allocated;
        $keys = array_keys($daily);
        $last = end($keys);
        $daily[$last] += $diff;

        // 万が一負の値になったら（稀）、units方式へフォールバック
        if ($daily[$last] < 0) {
            $mode = 'units'; // fall through to units logic below
        } else {
            return $daily;
        }
    }

    if ($mode === 'units') {
        // Largest Remainder（万単位の「個数」で分配）
        $idealUnits = [];
        $floorUnits = [];
        $fraction = [];
        foreach ($raw as $date => $r) {
            $iu = $r / $roundUnit;
            $idealUnits[$date] = $iu;
            $floorUnits[$date] = (int) floor($iu);
            $fraction[$date] = $iu - $floorUnits[$date];
        }
        $totalFloor = array_sum($floorUnits);
        $targetUnits = (int) round($totalBudget / $roundUnit);
        $need = $targetUnits - $totalFloor;

        arsort($fraction);
        $keysByFraction = array_keys($fraction);

        if ($need > 0) {
            for ($i = 0; $i < $need; $i++) {
                $k = $keysByFraction[$i % count($keysByFraction)];
                $floorUnits[$k] += 1;
            }
        } elseif ($need < 0) {
            asort($fraction);
            $keysAsc = array_keys($fraction);
            $toRemove = -$need;
            $i = 0;
            while ($toRemove > 0 && $i < count($keysAsc)) {
                $k = $keysAsc[$i];
                if ($floorUnits[$k] > 0) {
                    $floorUnits[$k] -= 1;
                    $toRemove--;
                }
                $i++;
            }
            if ($toRemove > 0) {
                $each = intdiv($targetUnits, count($floorUnits));
                foreach ($floorUnits as $k => $_) $floorUnits[$k] = $each;
                $rem = $targetUnits - array_sum($floorUnits);
                $keys = array_keys($floorUnits);
                for ($j=0; $j<$rem; $j++) $floorUnits[$keys[$j]] += 1;
            }
        }

        $daily = [];
        foreach ($floorUnits as $date => $units) {
            $daily[$date] = $units * $roundUnit;
        }
        return $daily;
    }

    return [];
}

$useYasumi = true; 
$daily_budgets = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $daily_budgets = computeDailyBudgets($year, $month, $total_budget, $base, $campaigns, 10000, 'last_adjust', $useYasumi);
}
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>予算アプリ</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
  <form method="post">
    年: <input type="number" name="year" value="<?= htmlspecialchars((string)$year) ?>"> 
    月: <input type="number" name="month" value="<?= htmlspecialchars((string)$month) ?>"><br>
    月間目標: <input type="number" name="total_budget" value="<?= htmlspecialchars((string)$total_budget) ?>"> 円<br>
    <!-- 簡単な販促入力 -->
    ☆販促期間ありの場合<br>
    開始日: <input type="date" name="campaign_start[]">
    終了日: <input type="date" name="campaign_end[]">
    倍率: <input type="number" step="0.1" name="campaign_rate[]"><br>
    <button type="submit">計算</button>
  </form>

  <?php if (!empty($daily_budgets)): ?>
    <h2><?= htmlspecialchars((string)$year) ?>年<?= htmlspecialchars((string)$month) ?>月の予算一覧</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>日付</th>
            <th>予算（円）</th>
        </tr>
        <?php foreach ($daily_budgets as $date => $budget): ?>
            <tr>
                <td><?= htmlspecialchars($date) ?></td>
                <td style="text-align:right;"><?= number_format($budget) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th>合計</th>
            <th style="text-align:right;"><?= number_format(array_sum($daily_budgets)) ?></th>
        </tr>
    </table>
  <?php endif; ?>
</body>
</html>
