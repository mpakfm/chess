<?php

/** @noinspection PhpInternalEntityUsedInspection */

namespace PhpCsFixer\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\TokenUtils;
use SplFileInfo;

/**
 * Проверяет и исправляет неправильные названия классов и интерфейсов:
 * - Для абстрактных классов добавляет суффикс Abstract (префикс Abstract убирает);
 * - Для классов исключений добавляет суффикс Exception (префикс Exception убирает);
 * - Для интерфейсов добавляет суффикс Interface (префикс Interface убирает);
 * - Для трейтов добавляет суффикс Trait (префикс Trait убирает).
 *
 * Было:  abstract class User {}
 * Стало: abstract class UserAbstract {}
 *
 * @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/2.12/doc/COOKBOOK-FIXERS.md
 */
class ClassNamesFixer extends AbstractFixer {
    public function getName() {
        return 'Dostavista/' . parent::getName();
    }

    public function getDefinition() {
        return new FixerDefinition(
            'Class and interface naming conventions fixer: suffix "Abstract" for abstract classes, "Exception" for exception classes, "Interface" for interfaces, "Trait" for traits',
            [
                new CodeSample('<?php abstract class User {}'),
                new CodeSample('<?php abstract class AbstractUser {}'),
                new CodeSample('<?php class AbstractClient extends UserAbstract {}'),
                new CodeSample('<?php class Unexpected extends Exception {}'),
                new CodeSample('<?php interface Editable {}'),
            ]
        );
    }

    public function isCandidate(Tokens $tokens) {
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds());
    }

    public function isRisky() {
        return true;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens) {
        // Проверяем и исправляем неправильные названия классов
        foreach (array_keys($tokens->findGivenKind(T_CLASS)) as $index) {
            $this->tryToApplyFixClassName($tokens, $index);
        }

        // Проверяем и исправляем неправильные названия интерфейсов
        foreach (array_keys($tokens->findGivenKind(T_INTERFACE)) as $index) {
            $this->tryToApplyFixInterfaceName($tokens, $index);
        }

        // Проверяем и исправляем неправильные названия трейтов
        foreach (array_keys($tokens->findGivenKind(T_TRAIT)) as $index) {
            $this->tryToApplyFixTraitName($tokens, $index);
        }
    }

    /**
     * Проверяет и исправляет неправильные названия классов:
     * - Для абстрактных классов добавляет суффикс Abstract (префикс Abstract убирает);
     * - Для классов исключений добавляет суффикс Exception (префикс Exception убирает).
     *
     * Было:  abstract class User {}
     * Стало: abstract class UserAbstract {}
     *
     * Было:  abstract class AbstractUser {}
     * Стало: abstract class UserAbstract {}
     *
     * Было:  class AbstractClient extends UserAbstract {}
     * Стало: class Client extends UserAbstract {}
     *
     * Было:  class Unexpected extends Exception {}
     * Стало: class UnexpectedException extends Exception {}
     */
    private function tryToApplyFixClassName(Tokens $tokens, int $classTokenIndex): void {
        // Токен из примеров: class
        TokenUtils::requireTokenIndexOfGivenKind($tokens, $classTokenIndex, T_CLASS);

        // Токен из примеров: User / AbstractUser / AbstractClient / Unexpected
        $classNameTokenIndex = $tokens->getNextMeaningfulToken($classTokenIndex);
        $classNameToken      = $tokens[$classNameTokenIndex] ?? null;
        if ($classNameTokenIndex === null || !$classNameToken->isGivenKind(T_STRING)) {
            return;
        }

        $oldClassName = $classNameToken->getContent();
        $newClassName = $classNameToken->getContent();

        if (self::isAbstractClass($tokens, $classTokenIndex)) {
            $newClassName = $this->addSuffix($newClassName, 'Abstract');
            $newClassName = $this->removePrefix($newClassName, 'Abstract');
        } else {
            $newClassName = $this->removeSuffix($newClassName, 'Abstract');
            $newClassName = $this->removePrefix($newClassName, 'Abstract');
        }

        if (self::isExceptionClass($tokens, $classTokenIndex)) {
            $newClassName = $this->addSuffix($newClassName, 'Exception');
            $newClassName = $this->removePrefix($newClassName, 'Exception');
        } elseif ($newClassName != 'MandrillBadResponseException') {
            $newClassName = $this->removeSuffix($newClassName, 'Exception');
            $newClassName = $this->removePrefix($newClassName, 'Exception');
        }

        if ($oldClassName != $newClassName && !empty($newClassName)) {
            $tokens[$classNameTokenIndex] = new Token([$classNameToken->getId(), $newClassName]);
        }
    }

    /**
     * Проверяет и исправляет неправильные названия интерфейсов:
     * - Для интерфейсов добавляет суффикс Interface (префикс Interface убирает).
     *
     * Было:  interface Editable {}
     * Стало: interface EditableInterface {}
     *
     * Было:  interface InterfaceEditable {}
     * Стало: interface EditableInterface {}
     */
    private function tryToApplyFixInterfaceName(Tokens $tokens, int $interfaceTokenIndex): void {
        // Токен из примеров: interface
        TokenUtils::requireTokenIndexOfGivenKind($tokens, $interfaceTokenIndex, T_INTERFACE);

        // Токен из примеров: Editable / InterfaceEditable
        $interfaceNameTokenIndex = $tokens->getNextMeaningfulToken($interfaceTokenIndex);
        $interfaceNameToken      = $tokens[$interfaceNameTokenIndex] ?? null;
        if ($interfaceNameTokenIndex === null || !$interfaceNameToken->isGivenKind(T_STRING)) {
            return;
        }

        $oldInterfaceName = $interfaceNameToken->getContent();
        $newInterfaceName = $interfaceNameToken->getContent();

        $newInterfaceName = $this->addSuffix($newInterfaceName, 'Interface');
        $newInterfaceName = $this->removePrefix($newInterfaceName, 'Interface');

        if ($oldInterfaceName != $newInterfaceName && !empty($newInterfaceName)) {
            $tokens[$interfaceNameTokenIndex] = new Token([$interfaceNameToken->getId(), $newInterfaceName]);
        }
    }

    /**
     * Проверяет и исправляет неправильные названия трейтов:
     * - Для трейтов добавляет суффикс Trait (префикс Trait убирает).
     *
     * Было:  trait Helper {}
     * Стало: trait HelperTrait {}
     *
     * Было:  trait TraitHelper {}
     * Стало: trait HelperTrait {}
     */
    private function tryToApplyFixTraitName(Tokens $tokens, int $traitTokenIndex): void {
        // Токен из примеров: trait
        TokenUtils::requireTokenIndexOfGivenKind($tokens, $traitTokenIndex, T_TRAIT);

        // Токен из примеров: Helper / TraitHelper
        $traitNameTokenIndex = $tokens->getNextMeaningfulToken($traitTokenIndex);
        $traitNameToken      = $tokens[$traitNameTokenIndex] ?? null;
        if ($traitNameTokenIndex === null || !$traitNameToken->isGivenKind(T_STRING)) {
            return;
        }

        $oldTraitName = $traitNameToken->getContent();
        $newTraitName = $traitNameToken->getContent();

        $newTraitName = $this->addSuffix($newTraitName, 'Trait');
        $newTraitName = $this->removePrefix($newTraitName, 'Trait');

        if ($oldTraitName != $newTraitName && !empty($newTraitName)) {
            $tokens[$traitNameTokenIndex] = new Token([$traitNameToken->getId(), $newTraitName]);
        }
    }

    private static function isAbstractClass(Tokens $tokens, int $classTokenIndex): bool {
        // Токен из примеров: class
        TokenUtils::requireTokenIndexOfGivenKind($tokens, $classTokenIndex, T_CLASS);

        // Предыдущий токен
        $classPerviousTokenIndex = $tokens->getPrevMeaningfulToken($classTokenIndex);
        $classPerviousToken      = $tokens[$classPerviousTokenIndex] ?? null;
        return ($classPerviousToken && $classPerviousToken->isGivenKind(T_ABSTRACT));
    }

    private static function isExceptionClass(Tokens $tokens, int $classTokenIndex): bool {
        // Токен из примеров: class
        TokenUtils::requireTokenIndexOfGivenKind($tokens, $classTokenIndex, T_CLASS);

        // Токен из примеров: User / AbstractUser / AbstractClient / Unexpected
        $classNameTokenIndex = $tokens->getNextMeaningfulToken($classTokenIndex);
        $classNameToken      = $tokens[$classNameTokenIndex] ?? null;
        if ($classNameToken === null || !$classNameToken->isGivenKind(T_STRING)) {
            return false;
        }

        // Токен из примеров: extends
        $extendsTokenIndex = $tokens->getNextMeaningfulToken($classNameTokenIndex);
        $extendsToken      = $tokens[$extendsTokenIndex] ?? null;
        if ($extendsToken === null || !$extendsToken->isGivenKind(T_EXTENDS)) {
            return false;
        }

        // Токен из примеров: UserAbstract / Exception
        $parentClassNameToken = null;
        $nextTokenIndex       = $extendsTokenIndex;

        do {
            $nextTokenIndex = $tokens->getNextMeaningfulToken($nextTokenIndex);
            $nextToken      = $tokens[$nextTokenIndex] ?? null;
            if ($nextToken !== null && $nextToken->isGivenKind(T_STRING)) {
                $parentClassNameToken = $nextToken;
            }
            if ($nextToken !== null && $nextToken->isGivenKind(T_NS_SEPARATOR)) {
                $parentClassNameToken = null;
            }
        } while ($nextToken !== null && $nextToken->isGivenKind([T_NS_SEPARATOR, T_STRING]));

        if ($parentClassNameToken === null) {
            return false;
        }

        // Классами исключений считаем только классы, у которых в родительском классе встречается слово Exception.
        // Для классов исключений, где это не выполняется, метод отработает неверно.
        return (strpos($parentClassNameToken->getContent(), 'Exception') !== false);
    }

    /**
     * Добавляет суффикс.
     */
    private function addSuffix(string $string, string $suffix): string {
        if (preg_match('/' . preg_quote($suffix) . '$/', $string)) {
            return $string;
        }
        return $string . $suffix;
    }

    /**
     * Удаляет суффикс.
     */
    private function removeSuffix(string $string, string $suffix): string {
        if (preg_match('/^(.+)' . preg_quote($suffix) . '$/', $string, $matches)) {
            return $matches[1];
        }
        return $string;
    }

    /**
     * Удаляет префикс.
     */
    private function removePrefix(string $string, string $prefix): string {
        if (preg_match('/^' . preg_quote($prefix) . '([^a-z].*)$/', $string, $matches)) {
            return $matches[1];
        }
        return $string;
    }
}
