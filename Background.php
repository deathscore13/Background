<?php

/**
 * Background
 * 
 * Выполнение кода после отправки ответа для PHP 8.0.0+
 * https://github.com/deathscore13/Background
 */

class Background
{
    public const EXIT = 'Background::exit()';           /**< Сообщение исключения которое будет считаться за выход */
    public const EXIT_ALL = 'Background::exitAll()';    /**< Сообщение исключения которое будет считаться за выход из всех файлов/функций */

    private string|false $cwd;
    private string $includes;

    private bool $stop = true;
    private bool $stopped = false;

    private array $methods = [];
    private array $argb = [];

    private int $count = 0;

    /**
     * Конструктор
     * 
     * @param ?string $cwd              Рабочая директория или null чтобы использовать текущую
     * @param ?string $includes         Значение для include_path или null чтобы использовать текущее
     */
    public function __construct(?string $cwd = null, ?string $includes = null)
    {
        if ($cwd && !is_dir($cwd))
            throw new Exception('No such directory');
        
        $this->cwd = $cwd ?? getcwd();
        $this->includes = $includes ?? get_include_path();
    }

    /**
     * Аналог exit(), при вызове которого register_shutdown_function не будет завершён
     */
    public static function exit(): never
    {
        throw new Exception(self::EXIT);
    }

    /**
     * Работает как exit(), но не выполняет оставшиеся добавленные файлы и функции
     */
    public static function exitAll(): never
    {
        throw new Exception(self::EXIT_ALL);
    }

    /**
     * Поведение при обработке исключения
     * 
     * @param ?bool $status             true чтобы остановить выполнение следующих файлов и callback функций,
     *                                  false чтобы не останавливать или null для получения текущего значения
     * 
     * @return bool                     Текущее значение
     */
    public function stop(?bool $status = null): bool
    {
        if ($status !== null)
            $this->stop = $status;
        
        return $this->status;
    }

    /**
     * Регистрация callback функции или файла для выполнения после отправки ответа
     * 
     * @param string|callable $method   Путь к подключаемому файлу или callback функция
     * @param mixed &...$argb           Передаваемые аргументы. Если первый параметр - путь к файлу,
     *                                  то аргументы будут храниться по ссылке в переменной $argb
     */
    public function register(string|callable $method, mixed &...$argb): void
    {
        if (is_callable($method))
        {
            if (is_string($method) && !function_exists($method))
                throw new Exception('Argument #1 ($method) must be a valid callback, function "'.$method.
                    '" not found or invalid function name');
        }
        else if (!is_file($method))
        {
            throw new Exception('Failed opening required \''.$method.'\' (include_path=\''.get_include_path().'\')');
        }

        $this->methods[$this->count] = $method;
        $this->argb[$this->count] = $argb;

        register_shutdown_function([$this, '__callback'], $this->count++);
    }

    public function __callback(int $key): void
    {
        if ($this->stop && $this->stopped)
            return;

        if ($this->cwd)
            chdir($this->cwd);
        
        set_include_path($this->includes);

        try
        {
            if (is_callable($this->methods[$key]))
            {
                $this->methods[$key](...$this->argb[$key]);
            }
            else
            {
                $argb = $this->argb[$key];
                require($this->methods[$key]);
            }
        }
        catch (Exception $e)
        {
            $msg = $e->getMessage();
            if ($msg === self::EXIT)
            {
                $this->stopped = true;
            }
            else if ($msg === self::EXIT_ALL)
            {
                $this->stop = true;
                $this->stopped = true;
            }
            else
            {
                error_log($e);
            }
        }
    }
}
