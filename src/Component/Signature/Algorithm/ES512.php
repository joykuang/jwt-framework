<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Jose\Component\Signature\Algorithm;

/**
 * Class ES512.
 */
final class ES512 extends ECDSA
{
    /**
     * @return string
     */
    protected function getHashAlgorithm(): string
    {
        return 'sha512';
    }

    /**
     * @return int
     */
    protected function getSignaturePartLength(): int
    {
        return 132;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'ES512';
    }
}
