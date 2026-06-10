<?php
session_start();

// Inicializa histórico
if (!isset($_SESSION['calculator_history'])) {
    $_SESSION['calculator_history'] = [];
}

// Função de cálculo
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
            }
            return null;
        default:
            return $curr;
    }
}

// Processa requisição AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Limpa qualquer saída que possa existir
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $action = $_POST['action'] ?? '';
    $display = $_POST['display'] ?? '0';
    $previous = $_POST['previous'] ?? '';
    $operation = $_POST['operation'] ?? '';
    $newNumber = ($_POST['new_number'] ?? 'true') === 'true';
    
    $result = [
        'display' => $display,
        'previous' => $previous,
        'operation' => $operation,
        'new_number' => $newNumber,
        'history' => $_SESSION['calculator_history']
    ];
    
    switch($action) {
        case 'clear':
            $result['display'] = '0';
            $result['previous'] = '';
            $result['operation'] = '';
            $result['new_number'] = true;
            break;
            
        case 'delete':
            if (!$newNumber && strlen($display) > 1) {
                $result['display'] = substr($display, 0, -1);
                if ($result['display'] === '') $result['display'] = '0';
            } elseif (!$newNumber && strlen($display) == 1) {
                $result['display'] = '0';
            }
            break;
            
        case 'sign':
            if ($display !== '0') {
                if ($display[0] === '-') {
                    $result['display'] = substr($display, 1);
                } else {
                    $result['display'] = '-' . $display;
                }
            }
            break;
            
        case 'percentage':
            $value = floatval($display);
            $val = $value / 100;
            if ($val == intval($val)) {
                $result['display'] = intval($val);
            } else {
                $result['display'] = $val;
            }
            break;
            
        case 'number':
            $num = $_POST['number'] ?? '0';
            if ($newNumber) {
                $result['display'] = $num;
                $result['new_number'] = false;
            } else {
                if ($display === '0') {
                    $result['display'] = $num;
                } else {
                    $result['display'] = $display . $num;
                }
            }
            if (strlen($result['display']) > 15) {
                $result['display'] = substr($result['display'], 0, 15);
            }
            break;
            
        case 'decimal':
            if ($newNumber) {
                $result['display'] = '0.';
                $result['new_number'] = false;
            } else {
                if (strpos($display, '.') === false) {
                    $result['display'] = $display . '.';
                }
            }
            break;
            
        case 'operator':
            $op = $_POST['operator'] ?? '';
            if ($operation !== '' && $previous !== '' && !$newNumber) {
                $calcResult = calculate($previous, $operation, $display);
                if ($calcResult === null) {
                    $result['display'] = 'Erro';
                    $result['previous'] = '';
                    $result['operation'] = '';
                    $result['new_number'] = true;
                } else {
                    $result['display'] = $calcResult;
                    $result['previous'] = $calcResult;
                    $result['operation'] = $op;
                    $result['new_number'] = true;
                }
            } else {
                if (!$newNumber) {
                    $result['previous'] = $display;
                }
                $result['operation'] = $op;
                $result['new_number'] = true;
                $result['display'] = '0';
            }
            break;
            
        case 'equals':
            if ($operation !== '' && $previous !== '' && !$newNumber) {
                $calcResult = calculate($previous, $operation, $display);
                if ($calcResult === null) {
                    $result['display'] = 'Erro';
                    $result['previous'] = '';
                    $result['operation'] = '';
                    $result['new_number'] = true;
                } else {
                    $historyItem = "$previous $operation $display = $calcResult";
                    array_unshift($_SESSION['calculator_history'], $historyItem);
                    if (count($_SESSION['calculator_history']) > 10) {
                        array_pop($_SESSION['calculator_history']);
                    }
                    
                    $result['display'] = $calcResult;
                    $result['previous'] = '';
                    $result['operation'] = '';
                    $result['new_number'] = true;
                    $result['history'] = $_SESSION['calculator_history'];
                }
            }
            break;
            
        case 'clear_history':
            $_SESSION['calculator_history'] = [];
            $result['history'] = [];
            break;
    }
    
    // Formata o display
    if (is_numeric($result['display']) && $result['display'] !== 'Erro') {
        if (strpos($result['display'], '.') !== false) {
            $result['display'] = rtrim(rtrim($result['display'], '0'), '.');
        }
    }
    
    // Limpa qualquer saída anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Define o header correto
    header('Content-Type: application/json; charset=utf-8');
    
    // Envia o JSON
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora Profissional | PHP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 60%);
            pointer-events: none;
        }

        /* ANIMAÇÃO OTIMIZADA - SEM AVISOS DE PERFORMANCE */
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .calculator-container {
            /* Dica de performance para o navegador */
            will-change: transform, opacity;
            
            /* Estilos visuais */
            background: rgba(15, 23, 42, 0.95);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
            width: 100%;
            max-width: 520px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            
            /* Animação suave */
            animation: fadeInUp 0.35s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
        }
        
        /* Fallback para navegadores sem suporte a backdrop-filter */
        @supports not (backdrop-filter: blur(10px)) {
            .calculator-container {
                background: #0f172a;
            }
        }

        /* Respeita configuração de acessibilidade do usuário (reduz movimento) */
        @media (prefers-reduced-motion: reduce) {
            .calculator-container {
                animation: none;
                opacity: 1;
                transform: none;
            }
            
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        .calculator-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
        }

        .header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(168, 85, 247, 0.2) 100%);
            padding: 24px 28px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header h1 {
            font-size: 22px;
            font-weight: 600;
            background: linear-gradient(135deg, #a5b4fc 0%, #c084fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
            font-weight: 500;
        }

        .display {
            background: #0f172a;
            padding: 32px 28px;
            text-align: right;
            min-height: 160px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .previous-operand {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            min-height: 22px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            letter-spacing: 0.5px;
        }

        .current-operand {
            color: #f1f5f9;
            font-size: 52px;
            font-weight: 700;
            margin-top: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            letter-spacing: -1px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .current-operand::-webkit-scrollbar {
            height: 4px;
        }
        
        .current-operand::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 10px;
        }
        
        .current-operand::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 10px;
        }

        .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: #1e293b;
            padding: 1px;
        }

        button {
            border: none;
            background: #0f172a;
            font-size: 22px;
            padding: 24px 16px;
            cursor: pointer;
            transition: all 0.1s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            color: #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        button:active::before {
            width: 200px;
            height: 200px;
        }

        button:active {
            transform: scale(0.96);
            background: #1e293b;
        }

        .operator {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            font-weight: 700;
            font-size: 24px;
        }

        .operator:active {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }

        .equals {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-weight: 700;
            font-size: 24px;
            grid-column: span 2;
        }

        .equals:active {
            background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        }

        .clear {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-weight: 700;
        }

        .clear:active {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        }

        .delete {
            background: #334155;
            color: #cbd5e1;
            font-weight: 600;
        }

        .delete:active {
            background: #475569;
        }

        .zero {
            grid-column: span 2;
        }

        .history {
            background: #0f172a;
            padding: 20px;
            max-height: 220px;
            overflow-y: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .history::-webkit-scrollbar {
            width: 4px;
        }
        
        .history::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 10px;
        }
        
        .history::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 10px;
        }

        .history-title {
            font-size: 13px;
            font-weight: 600;
            color: #818cf8;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .history-list {
            list-style: none;
        }

        .history-list li {
            padding: 10px 12px;
            margin-bottom: 6px;
            background: #1e293b;
            border-radius: 12px;
            font-size: 13px;
            color: #cbd5e1;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
            cursor: default;
        }

        .history-list li:hover {
            background: #334155;
            transform: translateX(4px);
            border-color: rgba(129, 140, 248, 0.3);
        }

        .clear-history {
            background: rgba(129, 140, 248, 0.1);
            border: 1px solid rgba(129, 140, 248, 0.3);
            color: #a5b4fc;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .clear-history:active {
            background: rgba(129, 140, 248, 0.2);
            transform: scale(0.98);
        }

        .empty-history {
            text-align: center;
            padding: 24px;
            color: #475569;
            font-size: 13px;
            font-weight: 500;
        }

        /* Responsividade para tablets */
        @media (max-width: 768px) {
            .calculator-container {
                max-width: 480px;
            }
            
            button {
                padding: 20px 14px;
                font-size: 20px;
            }
            
            .current-operand {
                font-size: 44px;
            }
        }

        /* Responsividade para celulares */
        @media (max-width: 480px) {
            .calculator-container {
                border-radius: 32px;
            }
            
            button {
                padding: 18px 12px;
                font-size: 20px;
            }
            
            .current-operand {
                font-size: 40px;
            }
            
            .header h1 {
                font-size: 18px;
            }
            
            .operator, .equals {
                font-size: 22px;
            }
            
            .header {
                padding: 18px 20px;
            }
            
            .display {
                padding: 24px 20px;
                min-height: 130px;
            }
        }

        /* Responsividade para celulares muito pequenos */
        @media (max-width: 380px) {
            button {
                padding: 14px 10px;
                font-size: 18px;
            }
            
            .current-operand {
                font-size: 32px;
            }
            
            .operator, .equals {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="calculator-container">
        <div class="header">
            <h1>⚡ Calculadora Profissional</h1>
            <p>Operações precisas | Design moderno</p>
        </div>
        
        <div class="display">
            <div class="previous-operand" id="previousOperand"></div>
            <div class="current-operand" id="currentOperand">0</div>
        </div>
        
        <div class="buttons" id="buttons">
            <button data-action="clear" class="clear">AC</button>
            <button data-action="sign" class="operator">±</button>
            <button data-action="percentage" class="operator">%</button>
            <button data-action="operator" data-value="÷" class="operator">÷</button>
            
            <button data-action="number" data-value="7">7</button>
            <button data-action="number" data-value="8">8</button>
            <button data-action="number" data-value="9">9</button>
            <button data-action="operator" data-value="×" class="operator">×</button>
            
            <button data-action="number" data-value="4">4</button>
            <button data-action="number" data-value="5">5</button>
            <button data-action="number" data-value="6">6</button>
            <button data-action="operator" data-value="-" class="operator">-</button>
            
            <button data-action="number" data-value="1">1</button>
            <button data-action="number" data-value="2">2</button>
            <button data-action="number" data-value="3">3</button>
            <button data-action="operator" data-value="+" class="operator">+</button>
            
            <button data-action="number" data-value="0" class="zero">0</button>
            <button data-action="decimal" data-value=".">.</button>
            <button data-action="delete" class="delete">⌫</button>
            <button data-action="equals" class="equals">=</button>
        </div>
        
        <div class="history">
            <div class="history-title">
                <span>📋 Histórico de Cálculos</span>
                <button data-action="clear_history" class="clear-history" id="clearHistoryBtn">
                    Limpar tudo
                </button>
            </div>
            <div id="historyList">
                <div class="empty-history">Nenhum cálculo realizado ainda</div>
            </div>
        </div>
    </div>
    
    <script>
        const state = {
            display: '0',
            previous: '',
            operation: '',
            newNumber: true
        };
        
        async function sendAction(action, value = null) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('display', state.display);
            formData.append('previous', state.previous);
            formData.append('operation', state.operation);
            formData.append('new_number', state.newNumber);
            
            if (value !== null && (action === 'number' || action === 'operator')) {
                formData.append(action, value);
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                state.display = data.display;
                state.previous = data.previous;
                state.operation = data.operation;
                state.newNumber = data.new_number;
                
                updateDisplay();
                updateHistory(data.history);
                
                const displayElement = document.getElementById('currentOperand');
                if (displayElement) {
                    displayElement.scrollLeft = displayElement.scrollWidth;
                }
                
            } catch (error) {
                console.error('Erro:', error);
            }
        }
        
        function updateDisplay() {
            const currentElement = document.getElementById('currentOperand');
            const previousElement = document.getElementById('previousOperand');
            
            if (currentElement) currentElement.textContent = state.display;
            
            if (previousElement) {
                if (state.previous && state.operation) {
                    previousElement.textContent = `${state.previous} ${state.operation}`;
                } else {
                    previousElement.textContent = '';
                }
            }
        }
        
        function updateHistory(history) {
            const historyContainer = document.getElementById('historyList');
            if (!historyContainer) return;
            
            if (!history || history.length === 0) {
                historyContainer.innerHTML = '<div class="empty-history">Nenhum cálculo realizado ainda</div>';
                return;
            }
            
            let html = '<ul class="history-list">';
            history.forEach(item => {
                html += `<li>${escapeHtml(item)}</li>`;
            });
            html += '</ul>';
            historyContainer.innerHTML = html;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function setupButtons() {
            const buttons = document.querySelectorAll('#buttons button');
            
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 100);
                    
                    const action = this.dataset.action;
                    const value = this.dataset.value;
                    sendAction(action, value);
                });
            });
            
            const clearHistoryBtn = document.getElementById('clearHistoryBtn');
            if (clearHistoryBtn) {
                clearHistoryBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    sendAction('clear_history');
                });
            }
        }
        
        function setupKeyboard() {
            document.addEventListener('keydown', function(e) {
                const key = e.key;
                
                if (/[0-9]/.test(key)) {
                    e.preventDefault();
                    sendAction('number', key);
                }
                
                if (key === '.') {
                    e.preventDefault();
                    sendAction('decimal');
                }
                
                if (key === '+') {
                    e.preventDefault();
                    sendAction('operator', '+');
                }
                if (key === '-') {
                    e.preventDefault();
                    sendAction('operator', '-');
                }
                if (key === '*') {
                    e.preventDefault();
                    sendAction('operator', '×');
                }
                if (key === '/') {
                    e.preventDefault();
                    sendAction('operator', '÷');
                }
                
                if (key === 'Enter') {
                    e.preventDefault();
                    sendAction('equals');
                }
                
                if (key === 'Escape') {
                    e.preventDefault();
                    sendAction('clear');
                }
                
                if (key === 'Backspace' || key === 'Delete') {
                    e.preventDefault();
                    sendAction('delete');
                }
                
                if (key === '%') {
                    e.preventDefault();
                    sendAction('percentage');
                }
            });
        }
        
        function init() {
            setupButtons();
            setupKeyboard();
            updateDisplay();
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    </script>
</body>
</html>
