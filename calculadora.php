<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Calculadora PHP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .calculator-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .display {
            background: #1e1e1e;
            color: #fff;
            padding: 25px;
            text-align: right;
        }

        .previous-operand {
            color: #888;
            font-size: 18px;
            min-height: 27px;
            word-wrap: break-word;
            word-break: break-all;
        }

        .current-operand {
            color: #fff;
            font-size: 48px;
            font-weight: bold;
            margin-top: 10px;
            word-wrap: break-word;
            word-break: break-all;
        }

        .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: #e0e0e0;
        }

        button {
            border: none;
            background: #f8f9fa;
            font-size: 20px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        button:hover {
            background: #e9ecef;
            transform: scale(0.98);
        }

        button:active {
            transform: scale(0.95);
        }

        .operator {
            background: #ff9800;
            color: white;
        }

        .operator:hover {
            background: #fb8c00;
        }

        .equals {
            background: #4caf50;
            color: white;
            grid-column: span 2;
        }

        .equals:hover {
            background: #45a049;
        }

        .clear {
            background: #f44336;
            color: white;
        }

        .clear:hover {
            background: #da190b;
        }

        .delete {
            background: #9e9e9e;
            color: white;
        }

        .delete:hover {
            background: #757575;
        }

        .zero {
            grid-column: span 2;
        }

        .history {
            background: #f8f9fa;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
            border-top: 1px solid #e0e0e0;
        }

        .history-title {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .history-list {
            list-style: none;
        }

        .history-list li {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
            color: #555;
            font-family: monospace;
        }

        .clear-history {
            background: transparent;
            border: 1px solid #667eea;
            color: #667eea;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
        }

        .clear-history:hover {
            background: #667eea;
            color: white;
            transform: none;
        }

        @media (max-width: 480px) {
            button {
                padding: 15px;
                font-size: 18px;
            }
            
            .current-operand {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <?php
    // variáveis
    $result = '';
    $previous = '';
    $current = '';
    $operation = '';
    $history = [];
    
    // histórico 
    session_start();
    if (!isset($_SESSION['calculator_history'])) {
        $_SESSION['calculator_history'] = [];
    }
    $history = $_SESSION['calculator_history'];
    
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['number'])) {
            $current = $_POST['current'] . $_POST['number'];
        }
        elseif (isset($_POST['operator'])) {
            $op = $_POST['operator'];
            $prev = $_POST['current'];
            
            if ($prev != '') {
                if ($_POST['previous'] != '' && $_POST['operation'] != '') {
                   
                    $calc = calculate($_POST['previous'], $_POST['operation'], $prev);
                    $previous = $calc;
                    $current = '';
                } else {
                    $previous = $prev;
                    $current = '';
                }
                $operation = $op;
            } else {
                if ($_POST['previous'] != '') {
                    $previous = $_POST['previous'];
                    $operation = $op;
                }
            }
        }
        elseif (isset($_POST['equals'])) {
            $prev = $_POST['previous'];
            $op = $_POST['operation'];
            $curr = $_POST['current'];
            
            if ($prev != '' && $op != '' && $curr != '') {
                $result = calculate($prev, $op, $curr);
                
                // Salvar 
                $historyItem = "$prev $op $curr = $result";
                array_unshift($_SESSION['calculator_history'], $historyItem);
                if (count($_SESSION['calculator_history']) > 10) {
                    array_pop($_SESSION['calculator_history']);
                }
                $history = $_SESSION['calculator_history'];
                
                $previous = $result;
                $current = '';
                $operation = '';
            }
        }
        elseif (isset($_POST['clear'])) {
            $previous = '';
            $current = '';
            $operation = '';
        }
        elseif (isset($_POST['delete'])) {
            $current = substr($_POST['current'], 0, -1);
        }
        elseif (isset($_POST['clear_history'])) {
            $_SESSION['calculator_history'] = [];
            $history = [];
        }
        elseif (isset($_POST['decimal'])) {
            if (strpos($_POST['current'], '.') === false) {
                $current = $_POST['current'] . '.';
            } else {
                $current = $_POST['current'];
            }
        }
        elseif (isset($_POST['percentage'])) {
            $current = $_POST['current'];
            if ($current != '') {
                $current = $current / 100;
            }
        }
        elseif (isset($_POST['sign'])) {
            $current = $_POST['current'];
            if ($current != '') {
                $current = -$current;
            }
        }
        else {
            $previous = isset($_POST['previous']) ? $_POST['previous'] : '';
            $current = isset($_POST['current']) ? $_POST['current'] : '';
            $operation = isset($_POST['operation']) ? $_POST['operation'] : '';
        }
    }
    
    // Função 
    function calculate($prev, $op, $curr) {
        $prev = floatval($prev);
        $curr = floatval($curr);
        
        switch($op) {
            case '+':
                return $prev + $curr;
            case '-':
                return $prev - $curr;
            case '×':
                return $prev * $curr;
            case '÷':
                if ($curr != 0) {
                    return $prev / $curr;
                } else {
                    return 'Erro';
                }
            default:
                return $curr;
        }
    }
    
    
    $displayCurrent = $current != '' ? $current : '0';
    if (is_numeric($displayCurrent)) {
        if (strpos($displayCurrent, '.') !== false) {
            $displayCurrent = rtrim(rtrim($displayCurrent, '0'), '.');
        }
    }
    
    $displayPrevious = '';
    if ($previous != '') {
        $displayPrevious = $previous . ' ' . $operation;
    }
    ?>
    
    <div class="calculator-container">
        <div class="header">
            <h1>Calculadora PHP</h1>
            <p>Operações matemáticas</p>
        </div>
        
        <form method="POST" action="" id="calculatorForm">
            <div class="display">
                <div class="previous-operand"><?php echo htmlspecialchars($displayPrevious); ?></div>
                <div class="current-operand"><?php echo htmlspecialchars($displayCurrent); ?></div>
            </div>
            
            <input type="hidden" name="previous" value="<?php echo htmlspecialchars($previous); ?>">
            <input type="hidden" name="current" value="<?php echo htmlspecialchars($current); ?>">
            <input type="hidden" name="operation" value="<?php echo htmlspecialchars($operation); ?>">
            
            <div class="buttons">
                <button type="submit" name="clear" class="clear">AC</button>
                <button type="submit" name="sign" class="operator">±</button>
                <button type="submit" name="percentage" class="operator">%</button>
                <button type="submit" name="operator" value="÷" class="operator">÷</button>
                
                <button type="submit" name="number" value="7">7</button>
                <button type="submit" name="number" value="8">8</button>
                <button type="submit" name="number" value="9">9</button>
                <button type="submit" name="operator" value="×" class="operator">×</button>
                
                <button type="submit" name="number" value="4">4</button>
                <button type="submit" name="number" value="5">5</button>
                <button type="submit" name="number" value="6">6</button>
                <button type="submit" name="operator" value="-" class="operator">-</button>
                
                <button type="submit" name="number" value="1">1</button>
                <button type="submit" name="number" value="2">2</button>
                <button type="submit" name="number" value="3">3</button>
                <button type="submit" name="operator" value="+" class="operator">+</button>
                
                <button type="submit" name="number" value="0" class="zero">0</button>
                <button type="submit" name="decimal" value=".">.</button>
                <button type="submit" name="delete" class="delete">⌫</button>
                <button type="submit" name="equals" class="equals">=</button>
            </div>
        </form>
        
        <div class="history">
            <div class="history-title">
                <span> Histórico de Cálculos</span>
                <?php if (!empty($history)): ?>
                    <button type="submit" name="clear_history" form="calculatorForm" class="clear-history">Limpar</button>
                <?php endif; ?>
            </div>
            <?php if (empty($history)): ?>
                <p style="color: #999; text-align: center; font-size: 14px;">Nenhum cálculo realizado ainda</p>
            <?php else: ?>
                <ul class="history-list">
                    <?php foreach($history as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
       
        const form = document.getElementById('calculatorForm');
        const buttons = form.querySelectorAll('button');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html>