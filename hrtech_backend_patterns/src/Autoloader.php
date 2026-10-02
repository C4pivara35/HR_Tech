<?php

declare(strict_types=1);

namespace HrTech;

/**
 * Classe Autoloader
 *
 * Autoloader em total conformidade com a PSR-4 com mapeamento de prefixos multi-diretório,
 * diretórios de fallback, filtros de segurança e bootstrap estático zero-config.
 */
class Autoloader
{
    /**
     * Array associativo onde a chave é o prefixo de namespace e o valor
 * é um array de diretórios base para classes naquele namespace.
     *
     * @var array<string, array<int, string>>
     */
    private array $prefixes = [];

    /**
     * Diretórios de fallback consultados caso nenhum prefixo mapeado resolva o arquivo.
     *
     * @var array<int, string>
     */
    private array $fallbackDirs = [];

    /**
     * Array de rastreamento de todos os arquivos carregados com sucesso por este autoloader.
     *
     * @var array<string, string>
     */
    private array $loadedFiles = [];

    /**
     * Sinalizador indicando se este autoloader está registrado na pilha SPL.
     */
    private bool $isRegistered = false;

    /**
     * Mapeamento direto de nomes de classes para caminhos de arquivos ou callables de resolução.
     *
     * @var array<string, string|callable>
     */
    private array $classMap = [];

    /**
     * Mapa de classes estático aplicado globalmente entre instâncias do autoloader.
     *
     * @var array<string, string|callable>
     */
    private static array $staticClassMap = [];

    /**
     * Instância Singleton para bootstrap padrão.
     */
    private static ?self $instance = null;

    /**
     * Referência ao autoloader instanciado mais recentemente.
     */
    private static ?self $lastInstantiated = null;

    public function __construct()
    {
        self::$lastInstantiated = $this;
    }

    /**
     * Registra o loader na pilha de autoloading da SPL.
     *
     * @param bool $prepend Verdadeiro para incluir no início da pilha do loader, falso para anexar ao fim.
     * @return $this
     */
    public function registerLoader(bool $prepend = false): self
    {
        if (!$this->isRegistered) {
            spl_autoload_register([$this, 'loadClass'], true, $prepend);
            $this->isRegistered = true;
        }

        return $this;
    }

    /**
     * Trata dinamicamente chamadas de métodos como \$autoloader->register().
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if ($name === 'register') {
            $prepend = (bool)($arguments[0] ?? false);
            return $this->registerLoader($prepend);
        }

        throw new \BadMethodCallException("Method {$name} does not exist on " . static::class);
    }

    /**
     * Helper to register default autoloader instance or custom project root.
     * Intelligently detects if called on an instance via $loader->register().
     *
     * @param string|null $projectRoot
     * @return self
     */
    public static function register(?string $projectRoot = null): self
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $trace[0]['file'] ?? '';
        $line = $trace[0]['line'] ?? 0;
        $isInstanceCall = false;

        if ($file !== '' && $line > 0 && file_exists($file)) {
            $lines = file($file);
            $callingLine = $lines[$line - 1] ?? '';
            if (preg_match('/->\s*register\s*\(/', $callingLine)) {
                $isInstanceCall = true;
            }
        } elseif (!file_exists($file) && self::$lastInstantiated !== null && !self::$lastInstantiated->isRegistered()) {
            $isInstanceCall = true;
        }

        if ($isInstanceCall && self::$lastInstantiated !== null) {
            return self::$lastInstantiated->registerLoader();
        }

        return self::registerDefault($projectRoot);
    }

    /**
     * Adds an array of class mappings or a resolver closure to this instance.
     *
     * @param array<string, string|callable>|callable $classMap
     * @return $this
     */
    public function addClassMap(array|callable $classMap): self
    {
        if (is_callable($classMap)) {
            $this->classMap['__closure__' . spl_object_id((object)$classMap)] = $classMap;
        } else {
            foreach ($classMap as $class => $target) {
                $this->classMap[ltrim($class, '\\')] = $target;
            }
        }
        return $this;
    }

    /**
     * Adds an array of class mappings or a resolver closure statically.
     *
     * @param array<string, string|callable>|callable $classMap
     */
    public static function addStaticClassMap(array|callable $classMap): void
    {
        if (is_callable($classMap)) {
            self::$staticClassMap['__closure__' . spl_object_id((object)$classMap)] = $classMap;
        } else {
            foreach ($classMap as $class => $target) {
                self::$staticClassMap[ltrim($class, '\\')] = $target;
            }
        }
    }

    /**
     * Maps a single class name to a file path or resolver callable.
     *
     * @param string $class
     * @param string|callable $target
     * @return $this
     */
    public function addClass(string $class, string|callable $target): self
    {
        $this->classMap[ltrim($class, '\\')] = $target;
        return $this;
    }

    /**
     * Retorna o mapa de classes da instância.
     *
     * @return array<string, string|callable>
     */
    public function getClassMap(): array
    {
        return $this->classMap;
    }

    /**
     * Maps a single class name to a file path or resolver callable statically.
     *
     * @param string $class
     * @param string|callable $target
     */
    public static function addStaticClass(string $class, string|callable $target): void
    {
        self::$staticClassMap[ltrim($class, '\\')] = $target;
    }

    /**
     * Retorna o mapa de classes estático.
     *
     * @return array<string, string|callable>
     */
    public static function getStaticClassMap(): array
    {
        return self::$staticClassMap;
    }

    /**
     * Desregistra o loader da pilha SPL.
     *
     * @return $this
     */
    public function unregister(): self
    {
        if ($this->isRegistered) {
            spl_autoload_unregister([$this, 'loadClass']);
            $this->isRegistered = false;
        }

        return $this;
    }

    /**
     * Retorna se o autoloader está registrado no momento.
     */
    public function isRegistered(): bool
    {
        return $this->isRegistered;
    }

    /**
     * Adiciona um diretório base para um prefixo de namespace.
     *
     * @param string $prefix The namespace prefix (e.g. "HrTech\\" or "HrTech").
     * @param string $baseDir Um diretório base para arquivos de classe no namespace.
     * @param bool $prepend If true, prepend the base directory to the stack.
     * @return $this
     */
    public function addNamespace(string $prefix, string $baseDir, bool $prepend = false): self
    {
        // Normalize namespace prefix: trim slashes and ensure trailing backslash
        $prefix = trim($prefix, '\\') . '\\';

        // Normaliza o diretório base: substitui barras por DIRECTORY_SEPARATOR e garante separador final
        $baseDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            $this->prefixes[$prefix][] = $baseDir;
        }

        return $this;
    }

    /**
     * Adiciona um diretório de fallback para busca quando a correspondência por prefixo falhar.
     *
     * @param string $baseDir
     * @return $this
     */
    public function addFallbackDir(string $baseDir): self
    {
        $baseDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!in_array($baseDir, $this->fallbackDirs, true)) {
            $this->fallbackDirs[] = $baseDir;
        }

        return $this;
    }

    /**
     * Carrega o arquivo de classe para um determinado nome de classe.
     *
     * @param string $class O nome totalmente qualificado da classe (FQCN).
     * @return string|false The mapped file name on success, or false on failure.
     */
    public function loadClass(string $class): string|false
    {
        // Trim leading backslash
        $class = ltrim($class, '\\');

        // Security check: reject potential directory traversal or null byte injection
        if (str_contains($class, '..') || str_contains($class, "\0")) {
            return false;
        }

        // 1. Instance Class Map Resolution (direct mapping or closure)
        if (isset($this->classMap[$class])) {
            $target = $this->classMap[$class];
            if (is_callable($target)) {
                $resolved = $target($class);
                if (is_string($resolved) && $this->requireFile($resolved)) {
                    $this->loadedFiles[$class] = $resolved;
                    return $resolved;
                }
            } elseif (is_string($target) && $this->requireFile($target)) {
                $this->loadedFiles[$class] = $target;
                return $target;
            }
        }

        // Check closure resolvers in instance class map
        foreach ($this->classMap as $key => $target) {
            if (str_starts_with((string)$key, '__closure__') && is_callable($target)) {
                $resolved = $target($class);
                if (is_string($resolved) && $this->requireFile($resolved)) {
                    $this->loadedFiles[$class] = $resolved;
                    return $resolved;
                }
            }
        }

        // 2. Static Class Map Resolution (global static mapping or closure)
        if (isset(self::$staticClassMap[$class])) {
            $target = self::$staticClassMap[$class];
            if (is_callable($target)) {
                $resolved = $target($class);
                if (is_string($resolved) && $this->requireFile($resolved)) {
                    $this->loadedFiles[$class] = $resolved;
                    return $resolved;
                }
            } elseif (is_string($target) && $this->requireFile($target)) {
                $this->loadedFiles[$class] = $target;
                return $target;
            }
        }

        // Check closure resolvers in static class map
        foreach (self::$staticClassMap as $key => $target) {
            if (str_starts_with((string)$key, '__closure__') && is_callable($target)) {
                $resolved = $target($class);
                if (is_string($resolved) && $this->requireFile($resolved)) {
                    $this->loadedFiles[$class] = $resolved;
                    return $resolved;
                }
            }
        }

        // Backward-walking prefix resolution:
        // Inicia a partir do nome completo da classe e remove segmentos de namespace da direita para a esquerda
        $prefix = $class;
        while (false !== ($pos = strrpos($prefix, '\\'))) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile !== false) {
                return $mappedFile;
            }

            $prefix = rtrim($prefix, '\\');
        }

        // Attempt fallback directories
        foreach ($this->fallbackDirs as $fallbackDir) {
            $file = $fallbackDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            if ($this->requireFile($file)) {
                $this->loadedFiles[$class] = $file;
                return $file;
            }
        }

        // Class not resolved by this loader; allow next loader in SPL stack to attempt
        return false;
    }

    /**
     * Carrega o arquivo mapeado para o prefixo de namespace e classe relativa.
     *
     * @param string $prefix O prefixo de namespace.
     * @param string $relativeClass O nome relativo da classe.
     * @return string|false The mapped file name on success, or false on failure.
     */
    protected function loadMappedFile(string $prefix, string $relativeClass): string|false
    {
        if (!isset($this->prefixes[$prefix])) {
            return false;
        }

        $relativeFile = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . $relativeFile;

            if ($this->requireFile($file)) {
                $this->loadedFiles[$prefix . $relativeClass] = $file;
                return $file;
            }
        }

        return false;
    }

    /**
     * Se o arquivo existir, inclui via require do sistema de arquivos.
     *
     * @param string $file The path to the file to require.
     * @return bool True if file exists and was required, false otherwise.
     */
    protected function requireFile(string $file): bool
    {
        if (is_file($file)) {
            require_once $file;
            return true;
        }

        return false;
    }

    /**
     * Retorna o array de prefixos de namespace registrados e seus respectivos diretórios.
     *
     * @return array<string, array<int, string>>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * Retorna todos os arquivos carregados por esta instância.
     *
     * @return array<string, string>
     */
    public function getLoadedFiles(): array
    {
        return $this->loadedFiles;
    }

    /**
     * Convenience method to register the default application namespaces.
     * Detecta automaticamente a raiz do projeto a partir da localização deste arquivo Autoloader.
     *
     * @param string|null $projectRoot Optional project root directory.
     * @return self
     */
    public static function registerDefault(?string $projectRoot = null): self
    {
        if (self::$instance !== null && self::$instance->isRegistered()) {
            return self::$instance;
        }

        $root = $projectRoot !== null
            ? rtrim($projectRoot, '/\\')
            : dirname(__DIR__); // Assuming Autoloader.php is in <root>/src/

        $autoloader = new self();
        // Register more specific prefixes first or ensure both are present
        $autoloader->addNamespace('HrTech\\Tests\\', $root . DIRECTORY_SEPARATOR . 'tests');
        $autoloader->addNamespace('HrTech\\', $root . DIRECTORY_SEPARATOR . 'src');
        $autoloader->addFallbackDir($root . DIRECTORY_SEPARATOR . 'src');
        $autoloader->registerLoader(true);

        self::$instance = $autoloader;
        return $autoloader;
    }

    /**
     * Reset the static default instance (useful in testing suites).
     */
    public static function reset(): void
    {
        if (self::$instance !== null) {
            self::$instance->unregister();
            self::$instance = null;
        }
    }
}
