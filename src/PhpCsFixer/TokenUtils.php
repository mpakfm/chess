<?php

namespace PhpCsFixer;

use LogicException;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

class TokenUtils {
    /**
     * Проверяет, что по указанному индексу находится токен с заданным типом.
     */
    public static function requireTokenIndexOfGivenKind(Tokens $tokens, int $index, int $kind): Token {
        return static::requireTokenOfGivenKind($tokens[$index] ?? null, $kind);
    }

    /**
     * Проверяет тип токена.
     */
    public static function requireTokenOfGivenKind(Token $token, int $kind): Token {
        if ($token === null || !$token->isGivenKind($kind)) {
            throw new LogicException(Token::getNameForId($kind) . ' token expected');
        }
        return $token;
    }
}
