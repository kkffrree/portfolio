<?php
// index.php - Yasumi を使わないシンプル版

// 入力（簡単にサニタイズ）
$year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
$month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('m');
$total_budget = isset($_POST['total_budget']) && is_numeric($_POST['total_budget']) ? (int)$_POST['total_budget'] : 0;

// フォームからの販促期間配列化
$campaigns = [];
$starts = $_POST['campaign_start'] ?? [];
$ends = $_POST['campaign_end'] ?? [];
$rates = $_POST['campaign_rate'] ?? [];
foreach ($starts as $i => $s) {
    $e = $ends[$i] ?? '';
    $r = $rates[$i] ?? '';
    if ($s && $e && $r) {
        $campaigns[] = [
            'start' => $s,
            'end'   => $e,
            'rate'  => (float)$r,
        ];
    }
}

// 平日/休日の基準（必要ならフォームで変更可能にしてね）
$base = [
    'weekday' => 150000,
    'holiday' => 250000,
];

$daily_budgets = [];

if ($total_budget > 0) {
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $weights = [];

    for ($d = 1; $d <= $days_in_month; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $day_of_week = (int)date('N', strtotime($date)); // 1=月曜 ... 7=日曜

        // Yasumi を使わないので土日を休日扱いにする
        $isHoliday = ($day_of_week >= 6); // 6=土, 7=日

        // ここで必ず初期化する（未定義エラーの原因はこれ）
        $weight = $isHoliday ? $base['holiday'] : $base['weekday'];

        // 販促期間の倍率をかける
        foreach ($campaigns as $c) {
            if ($date >= $c['start'] && $date <= $c['end']) {
                $weight *= $c['rate'];
            }
        }

        $weights[$date] = $weight;
    }

    // 重みに応じて配分
    $total_weight = array_sum($weights);

    if ($total_weight <= 0) {
        // 万一重みが0になったら均等配分するフォールバック
        $per = floor($total_budget / $days_in_month);
        for ($d = 1; $d <= $days_in_month; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $daily_budgets[$date] = $per;
        }
        $rem = $total_budget - array_sum($daily_budgets);
        $keys = array_keys($daily_budgets);
        $last = end($keys);
        $daily_budgets[$last] += $rem;
    } else {
        foreach ($weights as $date => $w) {
            $daily_budgets[$date] = (int)round($total_budget * ($w / $total_weight));
        }
        // 丸めで合計がずれることがあるので最後の日に差分を足す
        $allocated = array_sum($daily_budgets);
        $diff = $total_budget - $allocated;
        if ($diff !== 0) {
            $keys = array_keys($daily_budgets);
            $last = end($keys);
            $daily_budgets[$last] += $diff;
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>予算作成アプリ（Yasumiなし）</title>
<style>
    body{font-family:sans-serif;padding:20px;}
    input{margin:5px;}
    table{border-collapse:collapse;margin-top:20px;}
    th,td{border:1px solid #ccc;padding:6px 10px;text-align:right;}
    th{background:#f0f0f0;}
</style>
</head>
<body>
<h1>月間予算作成（Yasumiなし）</h1>
<form method="post">
    年: <input type="number" name="year" value="<?= htmlspecialchars($year) ?>" required>
    月: <input type="number" name="month" value="<?= htmlspecialchars($month) ?>" required><br>
    月間目標金額: <input type="number" name="total_budget" value="<?= htmlspecialchars($total_budget) ?>" required> 円<br>

    <h3>販促期間設定</h3>
    <div id="campaigns">
        <div>
            開始日: <input type="date" name="campaign_start[]">
            終了日: <input type="date" name="campaign_end[]">
            倍率: <input type="number" step="0.1" name="campaign_rate[]">
        </div>
    </div>
    <button type="button" onclick="addCampaign()">＋ 追加</button><br><br>

    <input type="submit" value="計算">
</form>

<?php if ($daily_budgets): ?>
    <h2>結果（<?= $year ?>年<?= $month ?>月）</h2>
    <table>
        <tr><th style="text-align:left">日付</th><th>日予算</th></tr>
        <?php foreach ($daily_budgets as $date => $budget): ?>
            <tr>
                <td style="text-align:left"><?= $date ?></td>
                <td><?= number_format($budget) ?> 円</td>
            </tr>
        <?php endforeach; ?>
        <tr><th>合計</th><th><?= number_format(array_sum($daily_budgets)) ?> 円</th></tr>
    </table>
<?php endif; ?>

<script>
function addCampaign(){
    const div = document.createElement('div');
    div.innerHTML = '開始日: <input type="date" name="campaign_start[]"> ' +
                    '終了日: <input type="date" name="campaign_end[]"> ' +
                    '倍率: <input type="number" step="0.1" name="campaign_rate[]">';
    document.getElementById('campaigns').appendChild(div);
}
</script>
</body>
</html>