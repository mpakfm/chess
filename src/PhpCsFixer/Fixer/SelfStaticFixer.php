<?php

/** @noinspection PhpInternalEntityUsedInspection */
/** @noinspection PhpDeprecationInspection */

namespace PhpCsFixer\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class SelfStaticFixer extends AbstractFixer {
    public function getName() {
        return 'Dostavista/self_static';
    }

    public function getDefinition() {
        return new FixerDefinition('Proper usage of self:: and static::', []);
    }

    public function isCandidate(Tokens $tokens) {
        return $tokens->isTokenKindFound(T_CLASS);
    }

    public function getPriority() {
        return 0;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens) {
        // Нельзя же просто сделать массив, надо выпендриваться обязательно.
        // Итерация по объекту Tokens в условиях вложенных циклов тут же сломается,
        // так что лучше мы его преобразуем сразу.

        /** @var Token[] $tokenArray */
        $tokenArray = [];
        for ($i = 0; $i < count($tokens); $i++) {
            $tokenArray[$i] = $tokens[$i];
        }

        $members = null;

        foreach ($tokenArray as $index => $token) {
            if ($token->isGivenKind(T_STATIC)) {
                if ($members === null) {
                    $members = $this->getMembersWithVisibility($tokenArray);
                }
                $properKeyword = $this->getProperKeyword($tokens, $index, $members);
                if ($properKeyword === 'self') {
                    $tokens[$index] = new Token([T_STRING, 'self']);
                }
                continue;
            }
            if ($token->isGivenKind(T_STRING) && $token->getContent() === 'self') {
                if ($members === null) {
                    $members = $this->getMembersWithVisibility($tokenArray);
                }
                $properKeyword = $this->getProperKeyword($tokens, $index, $members);
                if ($properKeyword === 'static') {
                    $tokens[$index] = new Token([T_STATIC, 'static']);
                }
                continue;
            }
        }
    }

    private function getProperKeyword(Tokens $tokens, int $keywordTokenIndex, array $membersWithVisibility): string {
        // $keywordTokenIndex - это self или static
        $index = $keywordTokenIndex;

        // Следующий токен мы хотим увидеть ::
        $index++;
        if ($tokens[$index]->isGivenKind(T_WHITESPACE)) {
            $index++;
        }
        if (!$tokens[$index]->isGivenKind(T_DOUBLE_COLON)) {
            return null;
        }

        // Дальше будет либо метод, либо константа, либо статическое свойство.
        $index++;
        if ($tokens[$index]->isGivenKind(T_WHITESPACE)) {
            $index++;
        }
        $identifierToken = $tokens[$index];
        if ($identifierToken->isGivenKind(T_VARIABLE)) {
            // Если у нас статическое свойство.
            $propertyName = $identifierToken->getContent();

            $visibility = $membersWithVisibility['properties'][$propertyName] ?? null;

            return $visibility === 'private' ? 'self' : 'static';
        }

        // Остались либо константы, либо методы.
        if (!$identifierToken->isGivenKind(T_STRING)) {
            return null;
        }

        // Теперь надо понять, константа у нас тут или метод
        $index++;
        if ($tokens[$index]->isGivenKind(T_WHITESPACE)) {
            $index++;
        }
        if ($tokens[$index]->getContent() === '(') {
            // У нас вызов метода.
            $methodName = $identifierToken->getContent();

            $visibility = $membersWithVisibility['methods'][$methodName] ?? null;

            return $visibility === 'private' ? 'self' : 'static';
        }

        // У нас константа.
        $constantName = $identifierToken->getContent();

        if ($constantName === 'class') {
            return null;
        }

        $visibility = $membersWithVisibility['constants'][$constantName] ?? null;

        if ($visibility === 'private') {
            return 'self';
        }

        // Видимости не было = константы нет в текущем классе.

        // По константам возможен нехороший вариант:
        // const A = [self::B];
        // Здесь нельзя менять на static::, потому что parse error будет.

        // Второй нехороший вариант:
        // function x($a = self::B)

        // Пойдём искать, в функции мы или нет.
        $isStaticKeywordAllowed  = false;
        $isCurlyBraceEncountered = false;

        $index = $keywordTokenIndex;
        while ($index > 0) {
            $index--;
            if ($tokens[$index]->getContent() === '{') {
                $isCurlyBraceEncountered = true;
                continue;
            }
            if ($tokens[$index]->isGivenKind(T_FUNCTION)) {
                $isStaticKeywordAllowed = $isCurlyBraceEncountered;
                break;
            }
            if ($tokens[$index]->isGivenKind([T_CLASS, T_CONST, T_PRIVATE, T_PROTECTED, T_PUBLIC])) {
                // Дошли до const/class/модификатора раньше, чем до function (мы идём снизу вверх).
                // Значит, мы не внутри метода.
                break;
            }
        }

        if ($isStaticKeywordAllowed) {
            return 'static';
        }
        return null;
    }

    /**
     * Ищем среди токенов определения констант и методов с областью видимости
     * @param Token[] $tokens
     * @return array
     */
    private function getMembersWithVisibility(array $tokens): array {
        $members = [];

        $isClassFound = false;
        foreach ($tokens as $index => $token) {
            if (!$isClassFound) {
                if ($token->isGivenKind(T_CLASS)) {
                    $isClassFound = true;
                }
                continue;
            }

            if ($token->isGivenKind(T_CLASS)) {
                // В списке токенов нашли два класса, пока что такие случаи обрабатывать не будем вообще.
                $members = [];
                break;
            }

            if ($token->isGivenKind(T_CONST)) {
                // Нащупали константу. Надо определить название и область видимости.

                $nameOffset = 1;
                if ($tokens[$index + $nameOffset]->isGivenKind(T_WHITESPACE)) {
                    $nameOffset++;
                }
                if (!$tokens[$index + $nameOffset]->isGivenKind(T_STRING)) {
                    continue;
                }
                $constantName = $tokens[$index + $nameOffset]->getContent();

                $visibilityOffset = -1;
                if ($tokens[$index + $visibilityOffset]->isGivenKind(T_WHITESPACE)) {
                    $visibilityOffset--;
                }
                $potentialVisibility = $tokens[$index + $visibilityOffset]->getContent();

                $visibility                          = in_array($potentialVisibility, ['private', 'protected', 'public']) ? $potentialVisibility : 'public';
                $members['constants'][$constantName] = $visibility;
            }

            if ($token->isGivenKind(T_STATIC)) {
                // Нащупали статическое свойство.
                $propertyName = $visibility = null;

                // Если свойство написано в виде 'private static $a'
                if ($tokens[$index - 1]->isGivenKind(T_WHITESPACE) &&
                    $tokens[$index - 2]->isGivenKind([T_PRIVATE, T_PROTECTED, T_PUBLIC]) &&
                    $tokens[$index + 1]->isGivenKind(T_WHITESPACE) &&
                    $tokens[$index + 2]->isGivenKind(T_VARIABLE)) {
                    $propertyName = $tokens[$index + 2]->getContent();
                    $visibility   = $tokens[$index - 2]->getContent();
                }
                // Если свойство написано в виде 'static private $a'
                elseif ($tokens[$index + 1]->isGivenKind(T_WHITESPACE) &&
                    $tokens[$index + 2]->isGivenKind([T_PRIVATE, T_PROTECTED, T_PUBLIC]) &&
                    $tokens[$index + 3]->isGivenKind(T_WHITESPACE) &&
                    $tokens[$index + 4]->isGivenKind(T_VARIABLE)) {
                    $propertyName = $tokens[$index + 4]->getContent();
                    $visibility   = $tokens[$index + 2]->getContent();
                }
                // Если свойство написано в виде 'static $a'
                elseif ($tokens[$index + 1]->isGivenKind(T_WHITESPACE) &&
                    $tokens[$index + 2]->isGivenKind(T_VARIABLE)) {
                    $propertyName = $tokens[$index + 2]->getContent();
                    $visibility   = 'public';
                }

                if (!empty($propertyName)) {
                    $members['properties'][$propertyName] = $visibility;
                }
            }

            if ($token->isGivenKind(T_FUNCTION)) {
                // Нащупали метод. Надо определить название и область видимости.
                // Также надо отличить лямбду от метода.

                $nameOffset = 1;
                if ($tokens[$index + $nameOffset]->isGivenKind(T_WHITESPACE)) {
                    $nameOffset++;
                }
                if (!$tokens[$index + $nameOffset]->isGivenKind(T_STRING)) {
                    continue;
                }

                $methodName = $tokens[$index + $nameOffset]->getContent();

                $visibility       = 'public';
                $visibilityOffset = -1;
                while ($tokens[$index + $visibilityOffset]->isGivenKind([T_PRIVATE, T_PROTECTED, T_PUBLIC, T_WHITESPACE, T_FINAL, T_ABSTRACT, T_STATIC])) {
                    if ($tokens[$index + $visibilityOffset]->isGivenKind([T_PRIVATE, T_PROTECTED, T_PUBLIC])) {
                        $visibility = $tokens[$index + $visibilityOffset]->getContent();
                        break;
                    }
                    $visibilityOffset--;
                }
                $members['methods'][$methodName] = $visibility;
            }
        }

        return $members;
    }
}
