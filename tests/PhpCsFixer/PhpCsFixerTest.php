<?php

namespace tests\PhpCsFixer;

use PhpCsFixer\Console\Command\FixCommandExitStatusCalculator;
use PhpCsFixer\PhpCsFixerConfig;
use PhpCsFixer\PhpCsFixerFilesAnalyzer;
use PHPUnit\Framework\TestCase;
use ProcessExecutor\ExecutionResult;
use ProcessExecutor\ProcessExecutor;

class PhpCsFixerTest extends TestCase {
    private $process;

    /**
     * Проверяет code style в php файлах.
     */
    public function testPhpFilesCodeStyle() {
        $this->assertNoCodeStyleProblems('.php_cs.php.dist');
    }

    /**
     * @param string $configFilename
     */
    private function assertNoCodeStyleProblems($configFilename) {
        $this->process = new ProcessExecutor();

        $changedFiles = $this->getChangedFiles();

        // Если измененных файлов нет, то проверяем весь проект
        if (!$changedFiles) {
            $changedFiles = ['.'];
        }

        $executionResult = $this->process->exec(sprintf(
            'cd %s; vendor/bin/php-cs-fixer fix --config=%s --dry-run --verbose --path-mode=intersection -- %s',
            escapeshellarg($_SERVER['DOCUMENT_ROOT']),
            escapeshellarg($configFilename),
            implode(' ', array_map('escapeshellarg', $changedFiles))
        ));

        $message = $this->getPhpCsFixerCommandResultMessage($executionResult, $configFilename);
        $this->assertSame(0, $executionResult->getExitCode(), $message);
    }

    /**
     * @return string[]
     */
    private function getChangedFiles() {
        // Поиск ближайшего общего предка между текущим коммитом и origin/master
        $executionResult = exec('git merge-base origin/master HEAD');
        $executionResult = $this->process->exec(
            'git merge-base origin/master HEAD'
        );
        $this->assertSame(0, $executionResult->getExitCode(), $executionResult->getErrorOutput());
        $mergeBase = $executionResult->getStandardOutputLines()[0] ?? '';
        $this->assertNotEmpty($mergeBase, 'Merge base between origin/master and HEAD not found');

        // Получим список измененных файлов (в сделанных коммитах и рабочей директории)
        $executionResult = $this->process->exec(sprintf(
            'git diff --name-only --diff-filter=ACMRTUXB %s',
            escapeshellarg($mergeBase)
        ));
        $this->assertSame(0, $executionResult->getExitCode(), $executionResult->getErrorOutput());

        // Исключим пустые строки, обычно это последняя строка, из-за переноса
        $changedFiles = array_filter($executionResult->getStandardOutputLines(), function ($line) {
            return $line != '';
        });

        return $changedFiles;
    }

    /**
     * @param ExecutionResult $consoleCommand
     * @param string          $configFilename
     * @return string
     */
    private function getPhpCsFixerCommandResultMessage(ExecutionResult $consoleCommand, $configFilename) {
        //var_dump($output);
        //var_dump($exitCode);
        //return "";
        $exitCode = $consoleCommand->getExitCode();
        $stdErr   = $consoleCommand->getErrorOutput();
        $messages = [];

        // @see https://github.com/FriendsOfPHP/PHP-CS-Fixer#exit-codes
        if ($exitCode === 1) {
            return "General error (or PHP minimal requirement not matched).\n{$stdErr}\n";
        }

        if ($exitCode & FixCommandExitStatusCalculator::EXIT_STATUS_FLAG_HAS_INVALID_FILES) {
            $messages[] = $stdErr;
        }

        if ($exitCode & FixCommandExitStatusCalculator::EXIT_STATUS_FLAG_HAS_CHANGED_FILES) {
            $analyzer = new PhpCsFixerFilesAnalyzer($_SERVER['DOCUMENT_ROOT'] . '/' . $configFilename);

            $messages[] = 'Some files need fixing.';
            $messages[] = $this->reformatPhpCsFixerStdOut($analyzer, $consoleCommand->getStandardOutputLines());
            $messages[] = 'Run command below to automatically fix them:';
            $messages[] = "vendor/bin/php-cs-fixer fix --config={$configFilename}";
        }

        if ($exitCode & FixCommandExitStatusCalculator::EXIT_STATUS_FLAG_HAS_INVALID_CONFIG) {
            return "Configuration error of the application.\n{$stdErr}\n";
        }

        if ($exitCode & FixCommandExitStatusCalculator::EXIT_STATUS_FLAG_HAS_INVALID_FIXER_CONFIG) {
            return "Configuration error of a Fixer.\n{$stdErr}\n";
        }

        if ($exitCode & FixCommandExitStatusCalculator::EXIT_STATUS_FLAG_EXCEPTION_IN_APP) {
            return "Exception raised within the application.\n{$stdErr}\n";
        }

        if ($exitCode === 255) {
            return "PHP error.\n{$stdErr}\n";
        }

        return implode("\n", $messages) . "\n";
    }

    /**
     * @param PhpCsFixerFilesAnalyzer $analyzer
     * @param string[]                $standardOutputLines
     * @return string
     */
    private function reformatPhpCsFixerStdOut(PhpCsFixerFilesAnalyzer $analyzer, array $standardOutputLines) {
        $result = '';

        // Переформатируем строки с файлами.
        //
        // Было:
        // 1) /path/to/file.php (indentation_type, array_syntax)
        //
        // Стало:
        // 1) Code must use configured indentation type (4 spaces):
        //  /path/to/file.php:74
        // 2) PHP arrays should be declared using short syntax:
        //  /path/to/file.php:95

        $i = 1;
        foreach ($standardOutputLines as $line) {
            if (preg_match('/^\s*\d+\)\s(.+)\s\((.+)\)/', $line, $matches)) {
                $filePath  = $matches[1];
                $ruleNames = explode(',', $matches[2]);
                $ruleNames = array_map('trim', $ruleNames);

                foreach ($ruleNames as $ruleName) {
                    $errorMessage = PhpCsFixerConfig::getRuleDescription($ruleName);

                    // Чтобы в консоли PhpStorm ссылка на файл была кликабельной,
                    // после пути к файлу не должно идти других символов, а впереди нужен один пробел.
                    $result .= "{$i}) {$errorMessage}:\n {$filePath}";

                    // Всего показываем не больше 10 ошибок на каждое правило в каждом файле
                    $diffGroups = $analyzer->getFileDiffForRule($filePath, $ruleName, 10);

                    // Находим строку с первой ошибкой
                    if ($diffGroups) {
                        /** @var DiffLine $diffLine */
                        foreach ($diffGroups[0] as $diffLine) {
                            if ($diffLine->isModified() && $diffLine->oldLineNumber) {
                                $result .= ":{$diffLine->oldLineNumber}";
                                break;
                            }
                        }
                    }

                    $result .= "\n";
                    $result .= PhpCsFixerFilesAnalyzer::convertDiffToString($diffGroups);

                    $i++;
                }
            } else {
                $result .= "{$line}\n";
            }
        }

        return $result;
    }
}
