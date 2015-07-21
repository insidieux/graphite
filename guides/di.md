#Dependency Injection

Вольная реализация паттерна Service Locator. Помогает управлять зависимостями, и уменьшить связанность.
Основная функция - контейнер для сервисов, умеющий создавать их по требованию. Сервисом является **объект** некоторого класса.

##Регистрация сервисов

Сервисы в Di регистрируются с помощью метода set
```php
$di = new \Graphite\Di\Container();
$di->set('MyService', new \stdClass());
 
// Для получения сервиса из контейнера - метод get, с указанием имени сервиса
$obj = $di->get('MyService'); // $obj instance of stdClass
```
В данном примере мы зарегистрировали сервис (объект stdClass) с именем "MyService". Это самый простой случай, когда сервис регистрируется как готовый объект. Но Container поддерживает возможность определить для сервиса не только готовую сущность, а фабрику/конструктор для его создания. В этом случае объект сервиа будет создан при первом запросе его из контейнера.

###По имени класса
Если вторым параметром методу set передать строку, то Container будет использовать эту строку как имя класса для сервиса
```php
$di->set('MyService', '\\Base\\Components\\Logger');
 
// Logger будет объектом \\Base\\Components\\Logger
$logger = $di->get('MyService');
```
Основным достоинством такого подходя является то, что сам объект будет создан только при первом обращении

###С использованием анонимной функции
Этот способ будет удобен, если объект сервиса надо до настроить. В данном случае вторым параметром можно передать анонимную функцию. Первым параметром в эту функцию будет передан объект контейнера. Функция должна вернуть объект сервиса
```php
$di->set('MyLogger', function (\Graphite\Di\Container $di) {
    $config = $di->get('AppConfig');
     
    $logger = new Base\Components\Logger();
    $logger->setPath($config->varPath);
    $logger->setPrefix('MyLog');
     
    return $logger;
});
```

###Singleton / Factory
Во всех примерах выше сервисы создавались как singleton: создаются один раз, и при повторном обращении вернется уже созданная ранее сущность. Иногда требуется получить новый объект при каждом обращении, но тем не менее уже некоторым образом преднастроенный.
Чтобы задать сервис, который будет создаваться при каждом обращении, нужно передать в метод set третий параметр - тип сервиса:
* Container::TYPE_SINGLETON - сервис будет создан лишь единожды (значение по умолчанию)
* Container::TYPE_FACTORY - сервис будет создаваться при каждом обращении

К примеру мы хотим как и в предыдущем примере получить логгер, с указанным путем, но префикс уже будет задаваться в момент использования.

```php
$di->set('MyLogger', function (\Graphite\Di\Container $di) {
    $config = $di->get('AppConfig');
    $logger = new Base\Components\Logger();
    $logger->setPath($config->varPath);
    return $logger;
}, $di::TYPE_FACTORY);
 
// ...
 
$logger = $di->get('MyLogger');
$logger->setPrefix('ContextPrefix');
```
Также есть два shorthand метода:
* `\Graphite\Di\Container::setSingleton($name, $factory)` - регистрирует сервис как синглтон
* `\Graphite\Di\Container::setFactory($name, $factory)` - регистрирует сервис как фабрику

А так же 2 метода для мультисетов
* `\Graphite\Di\Container::mSetSingleton(array $config)`
* `\Graphite\Di\Container::mSetFactory(array $config)`

```php
$service = new \\Namespace\\Service;
$array = [
    'service' => \\Namespace\\Service::class,
    'service2' => $service
];
$this->getDi()->mSetSingleton(array);
$this->getDi()->mSetFactory(array);
```

###Провайдеры
Провайдеры - это классы, выступающие в роли фабрик для сервисов. Использование провайдеров не дает особых преимуществ, лишь больше удобства в организации кода. Логику по созданию сервисов удобно вынести в провайдеры, а в контейнер регистрировать сами провайдеры
Провайдеры - это классы, унаследованные от \Graphite\Di\Provider, и реализующие в общем случае лишь метод get, который и должен вернуть объект сервиса. Метод get первым аргументом принимает Container

```php
// Module/Service/Providers/LoggerProvider.php
class LoggerProvider extends \Graphite\Di\Provider 
{
    public function get(Container $di)
    {
        return new Base\Logger('path/to/logs');
    }
}
 
// Module/Service/Providers/ImMessengerProvider.php
class ImMessengerProvider extends \Graphite\Di\Provider
{
    public function get(Container $di)
    {
        $cfg = $di->get('ImConfig');
        $messenger = new Base\Messenger($cfg->username, $cfg->auth);
        return $messenger;
    }
}
 
//...
 
// При инициализации модуля - регистрируем сервисы
$di->set('Logger', 'Module/Service/Providers/LoggerProvider')
   ->set('Messenger', 'Module/Service/Providers/ImMessengerProvider'); 
```
