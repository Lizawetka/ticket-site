<?php
// Установка кодировки и заголовка
header('Content-Type: text/html; charset=utf-8');

// Получение и фильтрация GET-параметров
$filter_type = isset($_GET['transport_type']) ? trim($_GET['transport_type']) : '';
$sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'id';
$sort_order = isset($_GET['order']) ? trim($_GET['order']) : 'ascending';

// Белый список для полей сортировки и порядка
allowed_sort:
$allowed_columns = ['id', 'transport_type', 'route', 'date', 'price'];
if (!in_array($sort_by, $allowed_columns)) {
    $sort_by = 'id';
}
if ($sort_order !== 'ascending' && $sort_order !== 'descending') {
    $sort_order = 'ascending';
}

// Проверка существования файлов XML и XSL
$xml_file = 'events.xml';
$xsl_file = 'events.xsl';

if (!file_exists($xml_file) || !file_exists($xsl_file)) {
    die('Ошибка: Файлы XML или XSL не найдены на сервере.');
}

// Загрузка XML
$dom = new DOMDocument();
$dom->load($xml_file);

// Фильтрация данных через XPath, если выбран конкретный тип транспорта
if ($filter_type !== '') {
    $xpath = new DOMXPath($dom);
    $query = "//trip[transport_type='" . $xpath->quote($filter_type) . "']";
    $nodes = $xpath->query($query);
    
    // Создаем новый документ для отфильтрованных данных
    $new_dom = new DOMDocument('1.0', 'UTF-8');
    $root = $new_dom->createElement('transport_catalog');
    $new_dom->appendChild($root);
    
    foreach ($nodes as $node) {
        $imported = $new_dom->importNode($node, true);
        $root->appendChild($imported);
    }
    $xml_to_process = $new_dom;
} else {
    $xml_to_process = $dom;
}

// Загрузка XSL и трансформация через XSLTProcessor
$xsl = new DOMDocument();
$xsl->load($xsl_file);

$processor = new XSLTProcessor();
$processor->importStyleSheet($xsl);

// Передача параметров сортировки в XSL-шаблон
$processor->setParameter('', 'sort_by', $sort_by);
$processor->setParameter('', 'sort_order', $sort_order);

$html_output = $processor->transformToXML($xml_to_process);
if ($html_output === false) {
    die('Ошибка трансформации XSL.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Реестр рейсов и расписание - Путешествуйте выгодно</title>
    <style>
        :root {
            --primary: #ff6b6b;
            --secondary: #4834d4;
            --accent: #00d2d3;
            --warning: #feca57;
            --dark: #222f3e;
            --light: #f1f2f6;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: var(--light); color: var(--dark); }
        
        .labs-top-bar { background: #2d3436; padding: 4px 15px; text-align: right; font-size: 11px; }
        .labs-top-bar span { color: #b2bec3; margin-right: 8px; }
        .labs-top-bar a { color: #dfe6e9; text-decoration: none; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.1); margin-left: 4px; }
        .labs-top-bar a:hover { background: rgba(255,255,255,0.25); color: white; }

        header { 
            background: linear-gradient(135deg, var(--secondary), var(--primary)); 
            color: white; 
            padding: 30px 20px 50px 20px; 
            text-align: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        header h1 { font-size: 32px; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 2px; }
        header p { font-size: 16px; margin: 0; opacity: 0.9; }

        .nav-container {
            max-width: 1100px;
            margin: -30px auto 30px auto;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            position: relative;
            z-index: 10;
        }
        .nav-card-btn {
            background: white;
            color: var(--dark);
            padding: 12px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, background 0.2s, color 0.2s;
        }
        .nav-card-btn:hover, .nav-card-btn.active { transform: translateY(-3px); background: var(--secondary); color: white; }

        .container { max-width: 1000px; margin: 20px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        h2 { color: var(--secondary); font-size: 22px; border-bottom: 3px solid var(--warning); padding-bottom: 8px; display: inline-block; margin-top: 0; }

        .filter-form {
            background: #f8f9fa;
            border: 1px solid #e4e7eb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 13px; font-weight: 600; color: #57606f; margin-bottom: 5px; }
        .filter-control { padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; background: white; }
        .btn-filter { background: var(--secondary); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-filter:hover { background: #3b2bc5; }

        /* Стили для таблицы, сгенерированной через XSL */
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        .schedule-table th, .schedule-table td { border: 1px solid #e4e7eb; padding: 12px; text-align: left; }
        .schedule-table th { background-color: var(--secondary); color: white; }
        .schedule-table tr:nth-child(even) { background-color: #f8f9fa; }

        footer { text-align: center; padding: 15px; background: var(--dark); color: white; margin-top: 40px; font-size: 13px; }
    </style>
</head>
<body>

<div class="labs-top-bar">
    <span>Лабораторные:</span>
    <a href="lab2.html">№2</a>
    <a href="lab3.html">№3</a>
    <a href="lab4.html">№4</a>
</div>

<header>
    <h1>Интерактивное расписание рейсов</h1>
    <p>Выборка и сортировка данных на основе XML и XSL</p>
</header>

<nav class="nav-container">
    <a href="index.html" class="nav-card-btn">🏠 Главная</a>
    <a href="transport.html" class="nav-card-btn">🚆 Транспорт</a>
    <a href="flights_plane.html" class="nav-card-btn">✈️ Рейсы</a>
    <a href="tickets.html" class="nav-card-btn">🎟️ Билеты</a>
    <a href="events.php" class="nav-card-btn active">📊 Реестр XML</a>
    <a href="customers.html" class="nav-card-btn">👤 Заказчики</a>
    <a href="search.html" class="nav-card-btn">🔍 Поиск</a>
    <a href="links.html" class="nav-card-btn">🔗 Ссылки</a>
    <a href="contacts.html" class="nav-card-btn">📞 Контакты</a>
</nav>

<div class="container">
    <h2>Фильтрация и сортировка данных XML</h2>
    
    <!-- Форма параметров выборки и сортировки -->
    <form method="GET" action="events.php" class="filter-form">
        <div class="filter-group">
            <label>Вид транспорта:</label>
            <select name="transport_type" class="filter-control">
                <option value="">-- Все виды --</option>
                <option value="Самолет" <?php if($filter_type=='Самолет') echo 'selected'; ?>>Самолет</option>
                <option value="Поезд" <?php if($filter_type=='Поезд') echo 'selected'; ?>>Поезд</option>
                <option value="Автобус" <?php if($filter_type=='Автобус') echo 'selected'; ?>>Автобус</option>
                <option value="Электричка" <?php if($filter_type=='Электричка') echo 'selected'; ?>>Электричка</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Сортировать по:</label>
            <select name="sort" class="filter-control">
                <option value="id" <?php if($sort_by=='id') echo 'selected'; ?>>По умолчанию (ID)</option>
                <option value="transport_type" <?php if($sort_by=='transport_type') echo 'selected'; ?>>Вид транспорта</option>
                <option value="route" <?php if($sort_by=='route') echo 'selected'; ?>>Маршрут</option>
                <option value="date" <?php if($sort_by=='date') echo 'selected'; ?>>Дата</option>
                <option value="price" <?php if($sort_by=='price') echo 'selected'; ?>>Цена</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Порядок:</label>
            <select name="order" class="filter-control">
                <option value="ascending" <?php if($sort_order=='ascending') echo 'selected'; ?>>По возрастанию</option>
                <option value="descending" <?php if($sort_order=='descending') echo 'selected'; ?>>По убыванию</option>
            </select>
        </div>

        <button type="submit" class="btn-filter">Применить</button>
    </form>

    <!-- Вывод сгенерированной через XSL таблицы -->
    <?php echo $html_output; ?>
</div>

<footer>
    <p>&copy; 2026 Система заказа билетов. Все права защищены.</p>
</footer>

</body>
</html>