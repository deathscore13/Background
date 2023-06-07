# Background
### Выполнение кода после отправки ответа для PHP 8.0.0+<br><br>

Советую открыть **`Background.php`** и почитать описания методов

<br><br>
## Ограничения PHP
1. Вам придётся использовать `Background::exit()` вместо `exit()` и `die()`, иначе функции добавленные в [register_shutdown_function](https://www.php.net/manual/ru/function.register-shutdown-function.php), а также файлы и функции добавленные в `Background::register()` не будут выполняться

<br><br>
## Пример
**bg.php**:
```php
if ($argb[0] !== 123)
    exit(); // неправильное завершение, функции добавленные в register_shutdown_function не выполнятся

if (isset($argb[1]))
    Background::exit(); // правильное завершение

// вывод значений $argb
file_put_contents('bg.txt', print_r($argb, true).PHP_EOL, FILE_APPEND);

// смена значения по ссылке
$argb[0] = 321;
```
**main.php**:
```php
// подключение Background
require('Background/Background.php');

// создание объекта Background
$bg = new Background();

$var1 = 123;

// регистрация выполнения bg.php в фоне
$bg->register('bg.php', $var1);

// повторное выполнение bg.php в фоне
$bg->register('bg.php', $var1);
```
