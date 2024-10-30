<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>敗北...</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <h1>敗北...</h1>
    <a href="home.html">タイトルに戻る</a>
    <audio id="defeat-sound" src="sound/defeat-sound.mp3"></audio>
    
 <script>
    // 敗北時の効果音を再生し、音量を50%に設定
    var defeatSound = document.getElementById('defeat-sound');
    defeatSound.volume = 0.03; 
    defeatSound.play();
</script>
</body>
</html>
