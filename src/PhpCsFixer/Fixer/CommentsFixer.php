<?php

/** @noinspection PhpInternalEntityUsedInspection */
/** @noinspection PhpDeprecationInspection */

namespace PhpCsFixer\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\Comment\SingleLineCommentStyleFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\TokenUtils;
use SplFileInfo;

/**
 * Форматирует комментарии:
 * - Комментарии пишем с заглавной буквы, если это не продолжение предложения;
 * - После // ставим один пробел;
 * - Удаляем лишние символы.
 *
 * Было:  //комментарий
 * Стало: // Комментарий
 *
 * Было:  //   комментарий
 * Стало: // Комментарий
 *
 * Было:  ///// комментарий
 * Стало: // Комментарий
 *
 * @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/2.12/doc/COOKBOOK-FIXERS.md
 */
class CommentsFixer extends AbstractFixer {
    const COMMENT_HASH             = '#';   // # Комментарий
    const COMMENT_SINGLE_LINE      = '//';  // // Комментарий
    const COMMENT_MULTI_LINE_BEGIN = '/*';  // /* Комментарий */
    const COMMENT_MULTI_LINE_END   = '*/';  // /* Комментарий */

    public function getName() {
        return 'Dostavista/' . parent::getName();
    }

    public function getDefinition() {
        return new FixerDefinition(
            'Start comments with a capital letter. There must be only one space before the comment.',
            [
                new CodeSample('//комментарий'),
                new CodeSample('//   комментарий'),
            ]
        );
    }

    public function isCandidate(Tokens $tokens) {
        return $tokens->isTokenKindFound(T_COMMENT);
    }

    public function getPriority() {
        return (int) (1 + max(
            (new PhpdocToCommentFixer())->getPriority(),
            (new SingleLineCommentStyleFixer())->getPriority()
        ));
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens) {
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_COMMENT)) {
                $this->tryToApplyFix($tokens, $index);
            }
        }
    }

    /**
     * Исправляем комментарии:
     *
     * Было:  //комментарий 1
     * Стало: // Комментарий 1
     *
     * Было:  //   комментарий 1
     * Стало: // Комментарий 1
     *
     * Было:  ///// комментарий 1
     * Стало: // Комментарий 1
     *
     * Если комментарий является продолжением предложения, то не делаем первую букву заглавной:
     *
     * Было:
     * //комментарий 1
     * //комментарий 2
     * Стало:
     * // Комментарий 1
     * // комментарий 2
     *
     * Было:
     * //комментарий 1.
     * //комментарий 2.
     * Стало:
     * // Комментарий 1.
     * // Комментарий 2.
     *
     * Тут ничего не меняем:
     * // Комментарий 1
     * // Комментарий 2
     */
    private function tryToApplyFix(Tokens $tokens, int $commentTokenIndex) {
        $commentToken = TokenUtils::requireTokenIndexOfGivenKind($tokens, $commentTokenIndex, T_COMMENT);

        // Достаем текст комментария
        $commentInfo  = self::getCommentInfo($commentToken);
        $commentBegin = $commentInfo['begin'];
        $commentSpace = $commentInfo['space'];
        $commentText  = $commentInfo['text'];
        $commentEnd   = $commentInfo['end'];

        if ($commentBegin === null || $commentText === null) {
            return;
        }

        // Предыдущий токен
        $prevTokenIndex = $tokens->getPrevNonWhitespace($commentTokenIndex);
        $prevToken      = $tokens[$prevTokenIndex] ?? null;

        // Предыдущий комментарий
        $prevCommentToken = null;
        $prevCommentText  = null;
        $prevCommentEnd   = null;
        if ($prevToken && $prevToken->isGivenKind(T_COMMENT)) {
            $prevCommentToken = $prevToken;

            // Достаем текст предыдущего комментария
            $prevCommentInfo = self::getCommentInfo($prevCommentToken);
            $prevCommentText = $prevCommentInfo['text'];
            $prevCommentEnd  = $prevCommentInfo['end'];
        }

        // Всегда должен быть хотя бы один пробел
        $newCommentSpace = $commentSpace;
        if ($newCommentSpace === '') {
            $newCommentSpace = ' ';
        }

        // Пишем комментарий с заглавной буквы, если надо
        $newCommentText = $commentText;
        if ($prevCommentText === null || in_array(mb_substr($prevCommentText, -1), ['.', '!', '?']) || $prevCommentEnd !== null) {
            // Возможно, что комментарий начинается с названия функции или содержит что-то вроде beanstalk://host:port/tube_name,
            // поэтому меняем только комментарии, начинающиеся с русской буквы
            if (preg_match('/^[а-яё]/iu', $newCommentText)) {
                $newCommentText  = mb_ucfirst($newCommentText);
                $newCommentSpace = ' ';
            }
        }

        if ($commentEnd === null) {
            $newCommentContent = $commentBegin . $newCommentSpace . $newCommentText;
        } else {
            $newCommentContent = $commentBegin . $newCommentSpace . $newCommentText . ' ' . $commentEnd;
        }

        $oldCommentContent = $commentToken->getContent();
        if ($oldCommentContent !== $newCommentContent) {
            $tokens[$commentTokenIndex] = new Token([$commentToken->getId(), $newCommentContent]);
        }
    }

    /**
     * Разбивает комментарий на части: начало, текст и конец.
     */
    private static function getCommentInfo(Token $commentToken): array {
        TokenUtils::requireTokenOfGivenKind($commentToken, T_COMMENT);

        $commentContent = $commentToken->getContent();
        $commentBegin   = null;
        $commentSpace   = null;
        $commentText    = null;
        $commentEnd     = null;

        $commentTextRegexp = '[a-z0-9а-яё].*';

        // Избегаем лишних проверок для ускорения работы метода
        if (mb_substr($commentContent, 0, 1) === self::COMMENT_HASH) {
            if (preg_match('/^#+(\s*)(' . $commentTextRegexp . ')$/iu', $commentContent, $matches)) {
                $commentBegin = self::COMMENT_HASH;
                $commentSpace = $matches[1];
                $commentText  = $matches[2];
            }
        } elseif (mb_substr($commentContent, 0, 2) === self::COMMENT_SINGLE_LINE) {
            if (preg_match('/^\/{2,}(\s*)(' . $commentTextRegexp . ')$/iu', $commentContent, $matches)) {
                $commentBegin = self::COMMENT_SINGLE_LINE;
                $commentSpace = $matches[1];
                $commentText  = $matches[2];
            }
        } elseif (
            mb_substr($commentContent, 0, 2) === self::COMMENT_MULTI_LINE_BEGIN
            && mb_substr($commentContent, -2) === self::COMMENT_MULTI_LINE_END
        ) {
            if (preg_match('/^\/\*(\s*)(' . $commentTextRegexp . ')\s*\*\/$/iu', $commentContent, $matches)) {
                $commentBegin = self::COMMENT_MULTI_LINE_BEGIN;
                $commentSpace = $matches[1];
                $commentText  = $matches[2];
                $commentEnd   = self::COMMENT_MULTI_LINE_END;
            }
        }

        $commentText = trim($commentText);
        if ($commentText === '') {
            $commentText = null;
        }

        return [
            'begin' => $commentBegin,
            'space' => $commentSpace,
            'text'  => $commentText,
            'end'   => $commentEnd,
        ];
    }
}
