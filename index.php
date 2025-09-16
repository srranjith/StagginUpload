<?php
$output = "";
$downloadFile = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $master_id = $_POST['master_id'];
    $file = $_FILES['excel_file']['tmp_name'];
    $table_name = pathinfo($_FILES['excel_file']['name'], PATHINFO_FILENAME);
    $headers_row = $_POST['headers_row'];

    // Move uploaded file to tmp directory
    $uploadPath = sys_get_temp_dir() . "/" . basename($_FILES['excel_file']['name']);
    move_uploaded_file($file, $uploadPath);
    $createrFilePath = __DIR__ . '/src/stagingCreator.py';

    $command = "python " . escapeshellarg($createrFilePath)
    . " --file " . escapeshellarg($uploadPath)
    . " --master_id " . escapeshellarg($master_id)
    . " --table_name " . escapeshellarg($table_name)
    . " --headers_row " . escapeshellarg($headers_row)
    . " --output " . escapeshellarg(sys_get_temp_dir() . "/output.sql");
    //. " 2>&1"; // Capture stderr
   
    $output = [];
    $return_var = 0;

exec($command, $output, $return_var);

  //Check if SQL file exists
  $downloadFile = sys_get_temp_dir() . "/output.sql";
  if (!file_exists($downloadFile)) {
      $downloadFile = "";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Excel → SQL Generator</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f7fa;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .container {
      background: #fff;
      max-width: 800px;
      width: 100%;
      margin: 40px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0px 8px 20px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    label {
      font-weight: 600;
      color: #444;
    }

    input[type="file"],
    input[type="number"],
    button {
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
    }

    input[type="file"],
    input[type="number"] {
      width: 100%;
    }

    button {
      background: #27a2e4ff;
      color: white;
      border: none;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #228be7ff;
    }

    .output-section {
      margin-top: 30px;
    }

    textarea {
      width: 100%;
      height: 350px;
      border-radius: 10px;
      border: 1px solid #ccc;
      padding: 15px;
      font-family: monospace;
      font-size: 14px;
      resize: vertical;
      background: #f9f9f9;
      color: #333;
    }

    .btn-group {
      margin-top: 15px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-secondary {
      background: #007BFF;
    }

    .btn-secondary:hover {
      background: #0069d9;
    }

    @media (max-width: 600px) {
      .container {
        margin: 20px;
        padding: 20px;
      }
      textarea {
        height: 250px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Excel → SQL Generator</h2>

    <form method="POST" enctype="multipart/form-data">
      <div>
        <label>Choose Excel File:</label>
        <input type="file" name="excel_file" accept=".xlsx" required>
      </div>

      <div>
        <label>Enter Master ID:</label>
        <input type="number" name="master_id" required>
      </div>

      <div>
        <label>Enter Headers Row:</label>
        <input type="number" name="headers_row" required>
      </div>

      <button type="submit">Generate SQL</button>
    </form>

      <?php if (!empty($output)): ?>
    <div class="output-section">
      <h3>Generated SQL:</h3>
      <textarea id="sqlOutput"><?= htmlspecialchars(is_array($output) ? implode("\n", $output) : $output) ?></textarea>
      <div class="btn-group">
        <button onclick="copySQL()">Copy SQL</button>
        <?php if (!empty($downloadFile)): ?>
          <form method="post" action="download.php" style="margin:0;">
            <input type="hidden" name="path" value="<?= htmlspecialchars($downloadFile) ?>">
            <button type="submit" class="btn-secondary">Download .sql</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

    </div>

  <script>
    function copySQL() {
      var textarea = document.getElementById("sqlOutput");
      textarea.select();
      textarea.setSelectionRange(0, 99999); // For mobile
      document.execCommand("copy");
      alert("SQL copied to clipboard!");
    }
  </script>
</body>
</html>
