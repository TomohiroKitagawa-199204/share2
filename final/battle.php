<?php
session_start();
require 'db.php';

class Character {
    public $name;
    public $hp;
    public $power;
    public $defense;
    public $speed;
    public $image;
    public $isDefending = false;

    public function __construct($name, $hp, $power, $defense, $speed, $image) {
        $this->name = $name;
        $this->hp = $hp;
        $this->power = $power;
        $this->defense = $defense;
        $this->speed = $speed;
        $this->image = $image;
    }

    public function attack($target) {
        $damage = rand(0, $this->power) - $target->defense;
        if ($damage < 0) $damage = 0;
        if ($target->isDefending) {
            $damage = intval($damage / 2); 
        }
        $target->hp -= $damage;
        return $damage;
    }

    public function isAlive() {
        return $this->hp > 0;
    }
}

class Item {
    public $name;
    public $effect;
    public $amount;

    public function __construct($name, $effect, $amount) {
        $this->name = $name;
        $this->effect = $effect;
        $this->amount = $amount;
    }

    public function use($character) {
        switch($this->effect) {
            case 'heal':
                $character->hp += $this->amount;
                if ($character->hp > 50) $character->hp = 50;
                break;
            case 'boost_power':
                $character->power += $this->amount;
                break;
            case 'boost_defense':
                $character->defense += $this->amount;
                break;
        }
    }
}

// 初期化または再戦時にセッションをクリア
if (!isset($_SESSION['dora']) || !isset($_SESSION['characters']) || isset($_POST['reset'])) {
    $_SESSION['dora'] = serialize(new Character("どらえもん", 40, 40, 40, 40, "dora.png"));

    // データベースからキャラクターを取得
    $stmt = $pdo->query('SELECT name, hp, power, defense, speed, image FROM enemyPara');
    $characters = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $characters[] = new Character($row['name'], $row['hp'], $row['power'], $row['defense'], $row['speed'], $row['image']);
    }
    $_SESSION['characters'] = serialize($characters);
    $_SESSION['currentCharacterIndex'] = 0;
    $_SESSION['battleLog'] = [];

    // アイテムの定義
    $healingPotion = new Item("回復薬", "heal", 10);
    $powerBoost = new Item("攻撃力増強", "boost_power", 3);
    $defenseBoost = new Item("防御力増強", "boost_defense", 3);
    $_SESSION['items'] = serialize([$healingPotion, $powerBoost, $defenseBoost]);
}

$dora = unserialize($_SESSION['dora']);
$characters = unserialize($_SESSION['characters']);
$currentCharacterIndex = $_SESSION['currentCharacterIndex'];
$currentCharacter = $characters[$currentCharacterIndex];
$battleLog = $_SESSION['battleLog'];
$items = unserialize($_SESSION['items']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dora->isDefending = false;
    $currentCharacter->isDefending = false;

    switch ($_POST['action']) {
        case 'attack':
            $damage = $dora->attack($currentCharacter);
            array_unshift($battleLog, ["attacker" => $dora, "target" => $currentCharacter, "damage" => $damage]);  // ログを先頭に追加
            if (!$currentCharacter->isAlive()) {
                array_unshift($battleLog, ["message" => "{$currentCharacter->name}は倒れた！"]);
                $currentCharacterIndex++;
                $_SESSION['currentCharacterIndex'] = $currentCharacterIndex;

                // 全ての敵を倒した場合
                if ($currentCharacterIndex >= count($characters)) {
                    $_SESSION['battleLog'] = [];
                    header("Location: victory.php?final=true"); // 最後の勝利画面へ遷移
                } else {
                    $_SESSION['battleLog'] = [];
                    header("Location: victory.php"); // 通常の勝利画面へ遷移
                }
                exit();
            }

            // 現在の敵の攻撃
            $damage = $currentCharacter->attack($dora);
            array_unshift($battleLog, ["attacker" => $currentCharacter, "target" => $dora, "damage" => $damage]);  // ログを先頭に追加
            if (!$dora->isAlive()) {
                array_unshift($battleLog, ["message" => "{$dora->name}は倒れた！"]);
                $_SESSION = []; // セッションをクリアしてバトルをリセット
                header("Location: defeat.php"); // 敗北時にdefeat.phpへ移動
                exit();
            }
            break;

        case 'defend':
            $dora->isDefending = true;
            array_unshift($battleLog, ["message" => "{$dora->name}は防御している！"]);  // ログを先頭に追加
            // 現在の敵の攻撃
            $damage = $currentCharacter->attack($dora);
            array_unshift($battleLog, ["attacker" => $currentCharacter, "target" => $dora, "damage" => $damage]);  // ログを先頭に追加
            if (!$dora->isAlive()) {
                array_unshift($battleLog, ["message" => "{$dora->name}は倒れた！"]);
                $_SESSION = []; // セッションをクリアしてバトルをリセット
                header("Location: defeat.php"); // 敗北時にdefeat.phpへ移動
                exit();
            }
            break;

        case 'item':
            if (isset($_POST['selected_item'])) {
                $selectedItemIndex = $_POST['selected_item'];
                $selectedItem = $items[$selectedItemIndex];
                $selectedItem->use($dora);
                array_unshift($battleLog, ["message" => "{$dora->name}は{$selectedItem->name}を使い、効果を得た！"]);  // ログを先頭に追加
            }
            // 現在の敵の攻撃
            $damage = $currentCharacter->attack($dora);
            array_unshift($battleLog, ["attacker" => $currentCharacter, "target" => $dora, "damage" => $damage]);  // ログを先頭に追加
            if (!$dora->isAlive()) {
                array_unshift($battleLog, ["message" => "{$dora->name}は倒れた！"]);
                $_SESSION = []; // セッションをクリアしてバトルをリセット
                header("Location: defeat.php"); // 敗北時にdefeat.phpへ移動
                exit();
            }
            break;

        case 'run':
            array_unshift($battleLog, ["message" => "{$dora->name}は逃げ出した！"]);
            $_SESSION = []; // セッションをクリアしてバトルをリセット
            header("Location: defeat.php"); // 敗北時にdefeat.phpへ移動
            exit();
            break;
    }

    $_SESSION['dora'] = serialize($dora);
    $_SESSION['characters'] = serialize($characters);
    $_SESSION['items'] = serialize($items);
    $_SESSION['battleLog'] = $battleLog;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ドラクエ風 戦闘画面</title>
    <style>
        body {
            font-family: 'Mplus 1p', sans-serif;
            background-color: #000;
            color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 1200px; /* コンテナの幅を大きく設定 */
            padding: 40px;
            background-color: #002B6F;
            border: 5px solid #fff;
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.5);
        }
        h1 {
            text-align: center;
            font-family: 'PixelMplus12', sans-serif;
            font-size: 48px; /* 見出しのフォントサイズを大きく設定 */
            color: #FFD700;
        }
        .characters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .character {
            text-align: center;
        }
        .character img {
            width: 400px; 
            height: 400px;
            border: 3px solid #fff;
            border-radius: 10px;
            background-color: #000;
        }
        .status {
            font-family: 'PixelMplus12', sans-serif;
            font-size: 24px; /* ステータスのフォントサイズを大きく設定 */
            color: #fff;
            margin-top: 15px;
        }
        .battle-log {
            background-color: #000;
            padding: 25px;
            border: 3px solid #fff;
            height: 250px; /* バトルログの高さを大きく設定 */
            overflow-y: auto;
            font-family: 'PixelMplus12', sans-serif;
            font-size: 20px; /* バトルログのフォントサイズを大きく設定 */
            color: #fff;
        }
        .battle-log p {
            margin: 0;
            padding: 10px 0;
        }
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        .actions button {
            font-family: 'PixelMplus12', sans-serif;
            font-size: 24px; /* ボタンのフォントサイズを大きく設定 */
            background-color: #002B6F;
            color: #fff;
            border: 3px solid #fff;
            padding: 15px 30px;
            margin: 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .actions button:hover {
            background-color: #0040FF;
        }
        .actions button:disabled {
            background-color: #444;
            color: #ccc;
            cursor: not-allowed;
        }
        .item-select {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .item-select select {
            font-size: 24px; /* フォントサイズを大きく */
            padding: 10px; /* 内側の余白を追加 */
            background-color: #002B6F; /* 背景色をボタンと合わせる */
            color: #fff; /* 文字色を白に設定 */
            border: 3px solid #fff; /* ボーダーを太く */
            border-radius: 5px; /* ボーダーの角を丸める */
            width: 100%; /* ドロップダウンを幅いっぱいに広げる */
            box-sizing: border-box; /* パディングを含めた幅計算 */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>戦闘</h1>
        <div class="characters">
            <div class="character">
                <img src="<?php echo $dora->image; ?>" alt="どらえもん">
                <div class="status"><?php echo $dora->name; ?> (HP: <?php echo $dora->hp; ?>)</div>
            </div>
            <?php if ($currentCharacterIndex < count($characters)): ?>
            <div class="character">
                <img src="<?php echo $currentCharacter->image; ?>" alt="敵キャラクター">
                <div class="status"><?php echo $currentCharacter->name; ?> (HP: <?php echo $currentCharacter->hp; ?>)</div>
            </div>
            <?php endif; ?>
        </div>
        <div class="battle-log">
            <?php foreach ($battleLog as $log): ?>
                <?php if (isset($log['message'])): ?>
                    <p><?php echo $log['message']; ?></p>
                <?php else: ?>
                    <p><?php echo $log['attacker']->name; ?>は<?php echo $log['target']->name; ?>に<?php echo $log['damage']; ?>のダメージを与えた！</p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="actions">
            <?php if ($dora->isAlive() && $currentCharacterIndex < count($characters)): ?>
                <form method="post">
                    <button type="submit" name="action" value="attack">攻撃</button>
                    <button type="submit" name="action" value="defend">防御</button>
                    <div class="item-select">
                        <select name="selected_item">
                            <?php foreach ($items as $index => $item): ?>
                                <option value="<?php echo $index; ?>"><?php echo $item->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="action" value="item">道具を使う</button>
                    </div>
                    <button type="submit" name="action" value="run">逃げる</button>
                </form>
            <?php else: ?>
                <form method="post">
                    <button type="submit" name="reset" value="1">再戦</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- 音楽や効果音の追加 -->
    <audio id="battle-music" src="sound/battle-music.mp3" loop></audio>
    <audio id="victory-sound" src="sound/victory-sound.mp3"></audio>
    <audio id="defeat-sound" src="sound/defeat-sound.mp3"></audio>

 <script>
    // 戦闘音楽を再生し、音量を50%に設定
    var battleMusic = document.getElementById('battle-music');
    battleMusic.volume = 0.03;
    battleMusic.play();

    // 勝利時の効果音を再生し、音量を50%に設定
    function playVictorySound() {
        var victorySound = document.getElementById('victory-sound');
        victorySound.volume = 0.03; 
        victorySound.play();
    }

    // 敗北時の効果音を再生し、音量を50%に設定
    function playDefeatSound() {
        var defeatSound = document.getElementById('defeat-sound');
        defeatSound.volume = 0.03; 
        defeatSound.play();
    }
    </script>

</body>
</html>