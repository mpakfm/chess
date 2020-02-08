<?php

/** @noinspection PhpInternalEntityUsedInspection */
/** @noinspection PhpDeprecationInspection */

namespace PhpCsFixer\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Экранирует вывод любых значений в шаблонах.
 *
 * Было:  <?= $var ?>
 * Стало: <?= htmlspecialchars($var) ?>
 *
 * Было:  <?= $this->obj['key']->param ?>
 * Стало: <?= htmlspecialchars($this->obj['key']->param) ?>
 *
 * @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/2.12/doc/COOKBOOK-FIXERS.md
 */
class EscapeHtmlSpecialCharactersFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface {
    /** @var string[] */
    private $foundNames = [];

    public function getName() {
        return 'Dostavista/' . parent::getName();
    }

    public function getDefinition() {
        $xss  = "<script>alert('xss');</script>";
        $code = "<?php\n\n\$var = \"{$xss}\";\n\n?>\n<div><?= \$var ?></div>\n";

        return new FixerDefinition(
            'Add htmlspecialchars() to convert special characters to HTML entities in PHP/HTML templates.',
            [
                new CodeSample($code),
                new CodeSample($code, ['escape_function' => 'html']),
            ]
        );
    }

    public function isCandidate(Tokens $tokens) {
        return $tokens->isTokenKindFound(T_OPEN_TAG_WITH_ECHO);
    }

    protected function createConfigurationDefinition() {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('escape_function', 'Функция, которая будет использоваться для экранирования.'))
                ->setAllowedTypes(['string'])
                ->setDefault('htmlspecialchars')
                ->getOption(),
        ]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens) {
        // Обходим все токены от последнего к первому, чтобы при добавлении новых токенов
        // не надо было учитывать изменение индексов
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $this->tryToApplyFix($tokens, $index);
        }
    }

    /**
     * Пытаемся добавить экранирование HTML символов в шаблон, начиная с токена $index (открывающего тега <?=).
     *
     * Было:  <?= $begin ?>
     * Стало: <?= htmlspecialchars($begin) ?>
     *
     * Было:  <?= $begin; ?>
     * Стало: <?= htmlspecialchars($begin); ?>
     *
     * Было:  <?= $begin['key'] ?>
     * Стало: <?= htmlspecialchars($begin['key']) ?>
     *
     * Было:  <?= $begin->middle->end ?>
     * Стало: <?= htmlspecialchars($begin->middle->end) ?>
     *
     * Было:  <?= $begin['key']->middle['key']->end[3]['key'] ?>
     * Стало: <?= htmlspecialchars($begin['key']->middle['key']->end[3]['key']) ?>
     *
     * Если в названии переменной или свойства встречается слово HTML или JSON, то ничего не экранируем:
     *
     * Было:  <?= $beginHtml ?>
     * Стало: <?= $beginHtml ?>
     *
     * Было:  <?= $begin['key_html'] ?>
     * Стало: <?= $begin['key_html'] ?>
     *
     * Было:  <?= $begin->middle->endHtml ?>
     * Стало: <?= $begin->middle->endHtml ?>
     *
     * Было:  <?= $begin->middle->endJson ?>
     * Стало: <?= $begin->middle->endJson ?>
     */
    private function tryToApplyFix(Tokens $tokens, int $index) {
        /* Пока парсим очередной блок <?= ... ?> складываем все найденные имена */
        /* Например из <?= $this->obj['key']->param ?> получим массив имен ['this', 'obj', 'key', 'param'] */
        $this->foundNames = [];

        // Короткий открывающий PHP тег
        // Токен из примеров: <?=
        $openTagTokenIndex = $index;
        $openTagToken      = $tokens[$openTagTokenIndex] ?? null;
        if ($openTagToken === null || !$openTagToken->equals([T_OPEN_TAG_WITH_ECHO])) {
            return;
        }

        // Первый токен внутри PHP тегов, начиная с которого нужно экранировать
        // Токен из примеров: $begin / $beginHtml
        $unescapedBeginTokenIndex = $tokens->getNextNonWhitespace($openTagTokenIndex);
        $unescapedBeginToken      = $tokens[$unescapedBeginTokenIndex] ?? null;
        if ($unescapedBeginToken === null || !$unescapedBeginToken->isGivenKind(T_VARIABLE)) {
            return;
        }

        $nextTokenIndex = $this->skipVariable($tokens, $unescapedBeginTokenIndex);
        if ($nextTokenIndex === null || $nextTokenIndex == $unescapedBeginTokenIndex) {
            return;
        }

        // Оператор обращения к свойству объекта (объектный оператор)
        // Токен из примеров: ->
        $objectOperatorTokenIndex = $nextTokenIndex;
        $objectOperatorToken      = $tokens[$objectOperatorTokenIndex] ?? null;
        if ($objectOperatorToken && $objectOperatorToken->isGivenKind(T_OBJECT_OPERATOR)) {
            $nextTokenIndex = $this->skipAllAccessObjectProperties($tokens, $nextTokenIndex);
        }

        if ($nextTokenIndex === null) {
            return;
        }

        // Последний токен внутри PHP тегов, заканчивая которым нужно экранировать
        // Токен из примеров: $begin / $beginHtml / end / endHtml / endJson / ] / ;
        $unescapedEndTokenIndex = $tokens->getPrevNonWhitespace($nextTokenIndex);
        $unescapedEndToken      = $tokens[$unescapedEndTokenIndex] ?? null;
        if ($unescapedEndToken === null) {
            return;
        }

        // Точки с запятой
        // Токен из примеров: ;
        $semicolonTokenIndex = $nextTokenIndex;
        $semicolonToken      = $tokens[$semicolonTokenIndex] ?? null;
        if ($semicolonToken && $semicolonToken->equals(';')) {
            $nextTokenIndex = $tokens->getTokenNotOfKindSibling($semicolonTokenIndex, 1, [[T_WHITESPACE], ';']);
        }

        // Закрывающий PHP тег
        /* Токен из примеров: ?> */
        $closeTagTokenIndex = $nextTokenIndex;
        $closeTagToken      = $tokens[$closeTagTokenIndex] ?? null;
        if ($closeTagToken === null || !$closeTagToken->isGivenKind(T_CLOSE_TAG)) {
            return;
        }

        // Если в названии переменной или свойства встречается слово HTML или JSON, то ничего не экранируем
        // Токен из примеров: beginHtml / key_html / endHtml / endJson
        foreach ($this->foundNames as $name) {
            if (mb_stripos($name, 'html') !== false || mb_stripos($name, 'json') !== false) {
                return;
            }
        }

        $this->wrapEscapeHtmlSpecialCharactersFunction($tokens, $unescapedBeginTokenIndex, $unescapedEndTokenIndex);
    }

    /**
     * Пропускает обращение к переменной и возвращает индекс следующего токена,
     * либо null, если следующего токена нет.
     *
     * Пример 1: $this
     * Пример 2: $var
     * Пример 3: $var['key']
     * Пример 4: $var['key'][0]["key"]
     */
    private function skipVariable(Tokens $tokens, int $index): int {
        // Переменная
        // Токен из примеров: $this / $var
        $varTokenIndex = $index;
        $varToken      = $tokens[$varTokenIndex] ?? null;
        if ($varToken === null || !$varToken->isGivenKind(T_VARIABLE)) {
            return $index;
        }

        $this->foundNames[] = $varToken->getContent();

        // Пробелы вконце тоже пропускаем, они нам не нужны
        $nextTokenIndex = $tokens->getNextNonWhitespace($varTokenIndex);
        if ($nextTokenIndex === null) {
            return null;
        }

        // Открывающая квадратная скобка
        // Токен из примеров: [
        $braceOpenTokenIndex = $nextTokenIndex;
        $braceOpenToken      = $tokens[$braceOpenTokenIndex] ?? null;
        if ($braceOpenToken && $braceOpenToken->equals('[')) {
            return $this->skipAllAccessArrayElements($tokens, $braceOpenTokenIndex);
        }

        return $nextTokenIndex;
    }

    /**
     * Пропускает все обращения к свойству объекта и возвращает индекс следующего токена,
     * либо null, если следующего токена нет.
     *
     * Пример 1: ->property->property->property
     * Пример 2: ->property['key']->property['key']->property['key']
     * Пример 2: ->property['key'][0]["key"]->property['key'][0]["key"]->property['key'][0]["key"]
     */
    private function skipAllAccessObjectProperties(Tokens $tokens, int $index): int {
        $nextTokenIndex = $this->skipAccessObjectProperty($tokens, $index);
        if ($nextTokenIndex == $index || $nextTokenIndex === null) {
            return $nextTokenIndex;
        }

        return $this->skipAllAccessObjectProperties($tokens, $nextTokenIndex);
    }

    /**
     * Пропускает обращение к свойству объекта и возвращает индекс следующего токена,
     * либо null, если следующего токена нет.
     *
     * Пример 1: ->property
     * Пример 2: ->property['key']
     * Пример 3: ->property['key'][0]["key"]
     */
    private function skipAccessObjectProperty(Tokens $tokens, int $index): int {
        // Оператор обращения к свойству объекта (объектный оператор)
        // Токен из примеров: ->
        $objectOperatorTokenIndex = $index;
        $objectOperatorToken      = $tokens[$objectOperatorTokenIndex] ?? null;
        if ($objectOperatorToken === null || !$objectOperatorToken->isGivenKind(T_OBJECT_OPERATOR)) {
            return $index;
        }

        // Свойство
        // Токен из примеров: property
        $propertyTokenIndex = $tokens->getNextNonWhitespace($objectOperatorTokenIndex);
        $propertyToken      = $tokens[$propertyTokenIndex] ?? null;
        if ($propertyToken === null || !$propertyToken->isGivenKind([T_STRING])) {
            return $index;
        }

        $this->foundNames[] = $propertyToken->getContent();

        // Пробелы вконце тоже пропускаем, они нам не нужны
        $nextTokenIndex = $tokens->getNextNonWhitespace($propertyTokenIndex);
        if ($nextTokenIndex === null) {
            return null;
        }

        // Открывающая квадратная скобка
        // Токен из примеров: [
        $braceOpenTokenIndex = $nextTokenIndex;
        $braceOpenToken      = $tokens[$braceOpenTokenIndex] ?? null;
        if ($braceOpenToken && $braceOpenToken->equals('[')) {
            return $this->skipAllAccessArrayElements($tokens, $braceOpenTokenIndex);
        }

        return $nextTokenIndex;
    }

    /**
     * Пропускает все обращения к элементу многомерного массива и возвращает индекс следующего токена,
     * либо null, если следующего токена нет.
     *
     * Пример: ['key'][0]["key"]
     */
    private function skipAllAccessArrayElements(Tokens $tokens, int $index): int {
        $nextTokenIndex = $this->skipAccessArrayElement($tokens, $index);
        if ($nextTokenIndex == $index || $nextTokenIndex === null) {
            return $nextTokenIndex;
        }

        return $this->skipAllAccessArrayElements($tokens, $nextTokenIndex);
    }

    /**
     * Пропускает обращение к элементу массива и возвращает индекс следующего токена,
     * либо null, если следующего токена нет.
     *
     * Пример 1: [0]
     * Пример 2: ['key']
     * Пример 3: ["key"]
     */
    private function skipAccessArrayElement(Tokens $tokens, int $index): int {
        // Открывающая квадратная скобка
        // Токен из примеров: [
        $braceOpenTokenIndex = $index;
        $braceOpenToken      = $tokens[$braceOpenTokenIndex] ?? null;
        if ($braceOpenToken === null || !$braceOpenToken->equals('[')) {
            return $index;
        }

        // Индекс массива
        // Токен из примеров: 0 / 'key' / "key"
        $keyTokenIndex = $tokens->getNextNonWhitespace($braceOpenTokenIndex);
        $keyToken      = $tokens[$keyTokenIndex] ?? null;
        if ($keyToken === null || !$keyToken->isGivenKind([T_LNUMBER, T_CONSTANT_ENCAPSED_STRING])) {
            return $index;
        }

        // Закрывающая квадратная скобка
        // Токен из примеров: ]
        $braceCloseTokenIndex = $tokens->getNextNonWhitespace($keyTokenIndex);
        $braceCloseToken      = $tokens[$braceCloseTokenIndex] ?? null;
        if ($braceCloseToken === null || !$braceCloseToken->equals(']')) {
            return $index;
        }

        $this->foundNames[] = $keyToken->getContent();

        // Пробелы вконце тоже пропускаем, они нам не нужны
        return $tokens->getNextNonWhitespace($braceCloseTokenIndex);
    }

    /**
     * Оборачивает токены между $beginIndex и $endIndex в вызов функции htmlspecialchars().
     *
     * Было:  <?= $begin->middle->end ?>
     * Стало: <?= htmlspecialchars($begin->middle->end) ?>
     */
    private function wrapEscapeHtmlSpecialCharactersFunction(Tokens $tokens, int $beginIndex, int $endIndex) {
        if ($beginIndex > $endIndex) {
            return;
        }

        /* Было: <?= $begin->middle->end ?> */

        // Добавляем название функции для экранирования
        // Значения $beginIndex и $endIndex сместились на единицу
        /* Стало: <?= htmlspecialchars$begin->middle->end ?> */
        $tokens->insertAt($beginIndex, new Token([T_STRING, $this->configuration['escape_function']]));
        $beginIndex++;
        $endIndex++;

        // Добавляем открывающую круглую скобку
        // Значения $beginIndex и $endIndex сместились на единицу
        /* Стало: <?= htmlspecialchars($begin->middle->end ?> */
        $tokens->insertAt($beginIndex, new Token('('));
        /** @noinspection PhpUnusedLocalVariableInspection */
        $beginIndex++;
        $endIndex++;

        // Добавляем закрывающую круглую скобку
        /* Стало: <?= htmlspecialchars($begin->middle->end) ?> */
        $tokens->insertAt($endIndex + 1, new Token(')'));
    }
}
