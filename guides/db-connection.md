Создание соединения
-------------------

Для создания соединения нужно создать объест Connection, и указать ему параметры соединения:

```php
$connOptions = [
    'db_host'  => '', // обязательно
    'db_port'  => '',
    'db_name'  => '', // обязательно
    'username' => '', // обязательно
    'password' => '',
    'charset'  => 'UTF8',
];
 
$connection = new \Graphite\Db\Connection($connOptions);
```

При этом, соединение с mysql будет установлено при первом запросе. Проверить, установлено ли соединение с mysql можно методом `isConnected`

Если есть необходимость получить созданный объект \PDO, можно воспользоваться методом `getPdoInstance()`

```php
$pdo = $connection->getPdoInstance();
print_r($pdo->getAvailableDrivers());
```

Экранирование
-------------
```php
// экранируем названия колонок или имен таблиц
$table  = $connection->quoteName('table_name'); // `table_name`
$column = $connection->quoteName('count');      // `count`
 
// массив имен
$connection->quoteNames(['lorem', 'ipsum', 'dolar']) // array ( 0 => '`lorem`', 1 => '`ipsum`', 2 => '`dolar`', )
```

Общие моменты при экранировании значений:
* `int|float` - остаются числами
* `array` - каждое значение экранируется индивидуально,  * и весь массив соединяется ч/з "," (удобно для использования в IN(?))
* `string` - экранируется ч/з \PDO::quote()
* `null` - также как и строка, тоесть приводится к пустой строке

```php
// экранируем значение
$val = $connection->quoteValue($param);
 
// экранируем массив значений
$valArr = $connection->quoteValues([$param1, $param2, $param3]);
 
// экранируем в строке
$sql = "SELECT * FROM table WHERE id IN(?) AND age > ? OR name = ?";
$sql = $connection->quoteInString($sql, [[1, 2, 3], 18, "D'art Anian"]);
// SELECT * FROM table WHERE id IN(1, 2, 3) AND age > 18 OR name = 'D\'art Anian'
```

Выполнение запросов
-------------------
Connection содержит два метода, для выполнения запросов к mysql:

* `Connection::query()` - для выполнения подготавливаемых запросов, которые возвращают результат в виде набора строк (SELECT, SHOW и т.п.)
* `Connection::execute()` - для выполнения запросов не возвращающих наборы строк, а лишь отдающие кол-во задействованных строк (ALTER, UPDATE ...)

Оба метода принимают в качестве параметра строку с запросом. Запрос может быть как "чистым", полностью подготовленным (тогда задача по экранированию значений полностью на совести разработчика), так и содержать метки для экранирования и подстановки значений в запрос. 

```php
$resultSet = $db->query('SELECT * FROM users WHERE age > 16');
$resultSet = $db->query('SELECT * FROM users WHERE age > ? AND active = ?', [16, true]);
```

query(), в качестве результата вернет объект Graphite\Db\ResultSet, являющийся по сути оберткой над \PDOStatement. ResultSet предоставляет набор методов для получения результата:

```php
$resultSet = $db->query('SELECT * FROM users WHERE age > 16');
 
// Вернет все строки в виде ассоциативного массива. Массив будет проиндексирован в порядке добавления
$resultSet->fetchAll();
 
// Вернет все строки в виде ассоциативного массива, проиндексированного по значению указанной колонки
$resultSet->fetchAllIndexed($indexBy);
 
// Вернет все строки в виде ассоциативного массива, сгруппированные по указанной колонке. 
$resultSet->fetchAllGrouped($groupBy, $indexBy);
 
// Вернет одну строку из результата
$resultSet->fetchRow();
 
// Вернет все строки из результата, содержащие только 1 колонку
$resultSet->fetchColumn();
 
// Вернет все строки в виде ассоциативного массива, где key - значение первой колонки, value - значение второй
$resultSet->fetchPairs();
 
// Вернет значение первой колонки первой строки. Для выборки скалярных/одиночных значений
$resultSet->fetchOne();
 
// Вернет все строки результата как массив объектов указанного класса.
// Если класс не указан - объекты будут \StdClass 
$resultSet->fetchClass($className);
 
// Вернет кол-во строк затронутых запросом
$resultSet->getRowCount();
```

Server info & Attributes
------------------------
В Connecion есть методы для получения атрибутов текущего соединения. Указать необходимый атрибут нужно передать одну из PDO::ATTR_* констант (PDO::getAttribute)
```php
// получение database connection attribute
$attrValue = $connection->getAttribute($attr);
 
// установка атрибута (о возможных атрибутах и значениях смотрим в http://php.net/manual/en/pdo.setattribute.php)
if ($connection->setAttribute($attr)) {
    // атрибут успешно установлен...
} else {
    // что-то пошло не так...
}
```

В дополнении есть пара методов, упрощающих получение информации о сервере и клиенте
```php
$info = $connection->getServerInfo();
// Uptime: 19784486  Threads: 203  Questions: 8093703722  Slow queries: 31143  Opens: 3922822  Flush tables: 3  Open tables: 2048  Queries per second avg: 409.93
 
$version = $connection->getServerVersion()
// 5.1.61-log
 
 
$clientVersion = $connection->getClientVersion();
// mysqlnd 5.0.11-dev - 20120503 - $Id: bf9ad53b11c9a57efdb1057292d73b928b8c5c77 $
```
