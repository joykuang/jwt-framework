<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Jose\Component\Encryption;

use Base64Url\Base64Url;
use Jose\Component\Core\Converter\JsonConverterInterface;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\KeyChecker;
use Jose\Component\Encryption\Algorithm\ContentEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryption\DirectEncryptionInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryption\KeyAgreementInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryption\KeyAgreementWrappingInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryption\KeyEncryptionInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryption\KeyWrappingInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Compression\CompressionMethodInterface;
use Jose\Component\Encryption\Compression\CompressionMethodManager;

/**
 * Class JWEBuilder.
 */
final class JWEBuilder
{
    /**
     * @var JsonConverterInterface
     */
    private $jsonConverter;

    /**
     * @var string
     */
    private $payload;

    /**
     * @var string|null
     */
    private $aad;

    /**
     * @var array
     */
    private $recipients = [];

    /**
     * @var AlgorithmManager
     */
    private $keyEncryptionAlgorithmManager;

    /**
     * @var AlgorithmManager
     */
    private $contentEncryptionAlgorithmManager;

    /**
     * @var CompressionMethodManager
     */
    private $compressionManager;

    /**
     * @var array
     */
    private $sharedProtectedHeaders = [];

    /**
     * @var array
     */
    private $sharedHeaders = [];

    /**
     * @var null|CompressionMethodInterface
     */
    private $compressionMethod = null;

    /**
     * @var null|ContentEncryptionAlgorithmInterface
     */
    private $contentEncryptionAlgorithm = null;

    /**
     * @var null|string
     */
    private $keyManagementMode = null;

    /**
     * JWEBuilder constructor.
     *
     * @param JsonConverterInterface   $jsonConverter
     * @param AlgorithmManager         $keyEncryptionAlgorithmManager
     * @param AlgorithmManager         $contentEncryptionAlgorithmManager
     * @param CompressionMethodManager $compressionManager
     */
    public function __construct(JsonConverterInterface $jsonConverter, AlgorithmManager $keyEncryptionAlgorithmManager, AlgorithmManager $contentEncryptionAlgorithmManager, CompressionMethodManager $compressionManager)
    {
        $this->jsonConverter = $jsonConverter;
        $this->keyEncryptionAlgorithmManager = $keyEncryptionAlgorithmManager;
        $this->contentEncryptionAlgorithmManager = $contentEncryptionAlgorithmManager;
        $this->compressionManager = $compressionManager;
    }

    /**
     * Reset the current data.
     *
     * @return JWEBuilder
     */
    public function create(): JWEBuilder
    {
        $this->payload = null;
        $this->aad = null;
        $this->recipients = [];
        $this->sharedProtectedHeaders = [];
        $this->sharedHeaders = [];
        $this->compressionMethod = null;
        $this->contentEncryptionAlgorithm = null;
        $this->keyManagementMode = null;

        return $this;
    }

    /**
     * @return AlgorithmManager
     */
    public function getKeyEncryptionAlgorithmManager(): AlgorithmManager
    {
        return $this->keyEncryptionAlgorithmManager;
    }

    /**
     * @return AlgorithmManager
     */
    public function getContentEncryptionAlgorithmManager(): AlgorithmManager
    {
        return $this->contentEncryptionAlgorithmManager;
    }

    /**
     * @return CompressionMethodManager
     */
    public function getCompressionManager(): CompressionMethodManager
    {
        return $this->compressionManager;
    }

    /**
     * @param mixed $payload
     *
     * @return JWEBuilder
     */
    public function withPayload($payload): JWEBuilder
    {
        $payload = is_string($payload) ? $payload : $this->jsonConverter->encode($payload);
        if (false === mb_detect_encoding($payload, 'UTF-8', true)) {
            throw new \InvalidArgumentException('The payload must be encoded in UTF-8');
        }
        $clone = clone $this;
        $clone->payload = $payload;

        return $clone;
    }

    /**
     * @param string|null $aad
     *
     * @return JWEBuilder
     */
    public function withAAD(?string $aad): JWEBuilder
    {
        $clone = clone $this;
        $clone->aad = $aad;

        return $clone;
    }

    /**
     * @param array $sharedProtectedHeaders
     *
     * @return JWEBuilder
     */
    public function withSharedProtectedHeaders(array $sharedProtectedHeaders): JWEBuilder
    {
        $this->checkDuplicatedHeaderParameters($sharedProtectedHeaders, $this->sharedHeaders);
        foreach ($this->recipients as $recipient) {
            $this->checkDuplicatedHeaderParameters($sharedProtectedHeaders, $recipient->getHeaders());
        }
        $clone = clone $this;
        $clone->sharedProtectedHeaders = $sharedProtectedHeaders;

        return $clone;
    }

    /**
     * @param array $sharedHeaders
     *
     * @return JWEBuilder
     */
    public function withSharedHeaders(array $sharedHeaders): JWEBuilder
    {
        $this->checkDuplicatedHeaderParameters($this->sharedProtectedHeaders, $sharedHeaders);
        foreach ($this->recipients as $recipient) {
            $this->checkDuplicatedHeaderParameters($sharedHeaders, $recipient->getHeaders());
        }
        $clone = clone $this;
        $clone->sharedHeaders = $sharedHeaders;

        return $clone;
    }

    /**
     * @param JWK   $recipientKey
     * @param array $recipientHeaders
     *
     * @return JWEBuilder
     */
    public function addRecipient(JWK $recipientKey, array $recipientHeaders = []): JWEBuilder
    {
        $this->checkDuplicatedHeaderParameters($this->sharedProtectedHeaders, $recipientHeaders);
        $this->checkDuplicatedHeaderParameters($this->sharedHeaders, $recipientHeaders);
        $clone = clone $this;
        $completeHeaders = array_merge($clone->sharedHeaders, $recipientHeaders, $clone->sharedProtectedHeaders);
        $clone->checkAndSetContentEncryptionAlgorithm($completeHeaders);
        $keyEncryptionAlgorithm = $clone->getKeyEncryptionAlgorithm($completeHeaders);
        if (null === $clone->keyManagementMode) {
            $clone->keyManagementMode = $keyEncryptionAlgorithm->getKeyManagementMode();
        } else {
            if (!$clone->areKeyManagementModesCompatible($clone->keyManagementMode, $keyEncryptionAlgorithm->getKeyManagementMode())) {
                throw new \InvalidArgumentException('Foreign key management mode forbidden.');
            }
        }

        $compressionMethod = $clone->getCompressionMethod($completeHeaders);
        if (null !== $compressionMethod) {
            if (null === $clone->compressionMethod) {
                $clone->compressionMethod = $compressionMethod;
            } elseif ($clone->compressionMethod->name() !== $compressionMethod->name()) {
                throw new \InvalidArgumentException('Incompatible compression method.');
            }
        }
        if (null === $compressionMethod && null !== $clone->compressionMethod) {
            throw new \InvalidArgumentException('Inconsistent compression method.');
        }
        $clone->checkKey($keyEncryptionAlgorithm, $recipientKey);
        $clone->recipients[] = [
            'key' => $recipientKey,
            'headers' => $recipientHeaders,
            'key_encryption_algorithm' => $keyEncryptionAlgorithm,
        ];

        return $clone;
    }

    /**
     * @return JWE
     */
    public function build(): JWE
    {
        if (0 === count($this->recipients)) {
            throw new \LogicException('No recipient.');
        }

        $additionalHeaders = [];
        $cek = $this->determineCEK($additionalHeaders);

        $recipients = [];
        foreach ($this->recipients as $recipient) {
            $recipient = $this->processRecipient($recipient, $cek, $additionalHeaders);
            $recipients[] = $recipient;
        }

        if (!empty($additionalHeaders) && 1 === count($this->recipients)) {
            $sharedProtectedHeaders = array_merge($additionalHeaders, $this->sharedProtectedHeaders);
        } else {
            $sharedProtectedHeaders = $this->sharedProtectedHeaders;
        }
        $encodedSharedProtectedHeaders = empty($sharedProtectedHeaders) ? '' : Base64Url::encode($this->jsonConverter->encode($sharedProtectedHeaders));

        list($ciphertext, $iv, $tag) = $this->encryptJWE($cek, $encodedSharedProtectedHeaders);

        return JWE::create($ciphertext, $iv, $tag, $this->aad, $this->sharedHeaders, $sharedProtectedHeaders, $encodedSharedProtectedHeaders, $recipients);
    }

    /**
     * @param array $completeHeaders
     */
    protected function checkAndSetContentEncryptionAlgorithm(array $completeHeaders): void
    {
        $contentEncryptionAlgorithm = $this->getContentEncryptionAlgorithm($completeHeaders);
        if (null === $this->contentEncryptionAlgorithm) {
            $this->contentEncryptionAlgorithm = $contentEncryptionAlgorithm;
        } elseif ($contentEncryptionAlgorithm->name() !== $this->contentEncryptionAlgorithm->name()) {
            throw new \InvalidArgumentException('Inconsistent content encryption algorithm');
        }
    }

    /**
     * @param array  $recipient
     * @param string $cek
     * @param array  $additionalHeaders
     *
     * @return Recipient
     */
    private function processRecipient(array $recipient, string $cek, array &$additionalHeaders): Recipient
    {
        $completeHeaders = array_merge($this->sharedHeaders, $recipient['headers'], $this->sharedProtectedHeaders);
        /** @var KeyEncryptionAlgorithmInterface $keyEncryptionAlgorithm */
        $keyEncryptionAlgorithm = $recipient['key_encryption_algorithm'];
        $encryptedContentEncryptionKey = $this->getEncryptedKey($completeHeaders, $cek, $keyEncryptionAlgorithm, $additionalHeaders, $recipient['key']);
        $recipientHeaders = $recipient['headers'];
        if (!empty($additionalHeaders) && 1 !== count($this->recipients)) {
            $recipientHeaders = array_merge($recipientHeaders, $additionalHeaders);
            $additionalHeaders = [];
        }

        return Recipient::create($recipientHeaders, $encryptedContentEncryptionKey);
    }

    /**
     * @param string $cek
     * @param string $encodedSharedProtectedHeaders
     *
     * @return array
     */
    private function encryptJWE(string $cek, string $encodedSharedProtectedHeaders): array
    {
        $tag = null;
        $iv_size = $this->contentEncryptionAlgorithm->getIVSize();
        $iv = $this->createIV($iv_size);
        $payload = $this->preparePayload();
        $aad = $this->aad ? Base64Url::encode($this->aad) : null;
        $ciphertext = $this->contentEncryptionAlgorithm->encryptContent($payload, $cek, $iv, $aad, $encodedSharedProtectedHeaders, $tag);

        return [$ciphertext, $iv, $tag];
    }

    /**
     * @return string
     */
    private function preparePayload(): ?string
    {
        $prepared = $this->payload;

        if (null === $this->compressionMethod) {
            return $prepared;
        }
        $compressedPayload = $this->compressionMethod->compress($prepared);
        if (null === $compressedPayload) {
            throw new \RuntimeException('The payload cannot be compressed.');
        }

        return $compressedPayload;
    }

    /**
     * @param array                           $completeHeaders
     * @param string                          $cek
     * @param KeyEncryptionAlgorithmInterface $keyEncryptionAlgorithm
     * @param JWK                             $recipientKey
     * @param array                           $additionalHeaders
     *
     * @return string|null
     */
    private function getEncryptedKey(array $completeHeaders, string $cek, KeyEncryptionAlgorithmInterface $keyEncryptionAlgorithm, array &$additionalHeaders, JWK $recipientKey): ?string
    {
        if ($keyEncryptionAlgorithm instanceof KeyEncryptionInterface) {
            return $this->getEncryptedKeyFromKeyEncryptionAlgorithm($completeHeaders, $cek, $keyEncryptionAlgorithm, $recipientKey, $additionalHeaders);
        } elseif ($keyEncryptionAlgorithm instanceof KeyWrappingInterface) {
            return $this->getEncryptedKeyFromKeyWrappingAlgorithm($completeHeaders, $cek, $keyEncryptionAlgorithm, $recipientKey, $additionalHeaders);
        } elseif ($keyEncryptionAlgorithm instanceof KeyAgreementWrappingInterface) {
            return $this->getEncryptedKeyFromKeyAgreementAndKeyWrappingAlgorithm($completeHeaders, $cek, $keyEncryptionAlgorithm, $additionalHeaders, $recipientKey);
        } elseif ($keyEncryptionAlgorithm instanceof KeyAgreementInterface) {
            return null;
        } elseif ($keyEncryptionAlgorithm instanceof DirectEncryptionInterface) {
            return null;
        }

        throw new \InvalidArgumentException('Unsupported key encryption algorithm.');
    }

    /**
     * @param array                         $completeHeaders
     * @param string                        $cek
     * @param KeyAgreementWrappingInterface $keyEncryptionAlgorithm
     * @param array                         $additionalHeaders
     * @param JWK                           $recipientKey
     *
     * @return string
     */
    private function getEncryptedKeyFromKeyAgreementAndKeyWrappingAlgorithm(array $completeHeaders, string $cek, KeyAgreementWrappingInterface $keyEncryptionAlgorithm, array &$additionalHeaders, JWK $recipientKey): string
    {
        return $keyEncryptionAlgorithm->wrapAgreementKey($recipientKey, $cek, $this->contentEncryptionAlgorithm->getCEKSize(), $completeHeaders, $additionalHeaders);
    }

    /**
     * @param array                  $completeHeaders
     * @param string                 $cek
     * @param KeyEncryptionInterface $keyEncryptionAlgorithm
     * @param JWK                    $recipientKey
     * @param array                  $additionalHeaders
     *
     * @return string
     */
    private function getEncryptedKeyFromKeyEncryptionAlgorithm(array $completeHeaders, string $cek, KeyEncryptionInterface $keyEncryptionAlgorithm, JWK $recipientKey, array &$additionalHeaders): string
    {
        return $keyEncryptionAlgorithm->encryptKey($recipientKey, $cek, $completeHeaders, $additionalHeaders);
    }

    /**
     * @param array                $completeHeaders
     * @param string               $cek
     * @param KeyWrappingInterface $keyEncryptionAlgorithm
     * @param JWK                  $recipientKey
     * @param array                $additionalHeaders
     *
     * @return string
     */
    private function getEncryptedKeyFromKeyWrappingAlgorithm(array $completeHeaders, string $cek, KeyWrappingInterface $keyEncryptionAlgorithm, JWK $recipientKey, array &$additionalHeaders): string
    {
        return $keyEncryptionAlgorithm->wrapKey($recipientKey, $cek, $completeHeaders, $additionalHeaders);
    }

    /**
     * @param KeyEncryptionAlgorithmInterface $keyEncryptionAlgorithm
     * @param JWK                             $recipientKey
     */
    protected function checkKey(KeyEncryptionAlgorithmInterface $keyEncryptionAlgorithm, JWK $recipientKey)
    {
        KeyChecker::checkKeyUsage($recipientKey, 'encryption');
        if ('dir' !== $keyEncryptionAlgorithm->name()) {
            KeyChecker::checkKeyAlgorithm($recipientKey, $keyEncryptionAlgorithm->name());
        } else {
            KeyChecker::checkKeyAlgorithm($recipientKey, $this->contentEncryptionAlgorithm->name());
        }
    }

    /**
     * @param array $additionalHeaders
     *
     * @return string
     */
    private function determineCEK(array &$additionalHeaders): string
    {
        switch ($this->keyManagementMode) {
            case KeyEncryptionInterface::MODE_ENCRYPT:
            case KeyEncryptionInterface::MODE_WRAP:
                return $this->createCEK($this->contentEncryptionAlgorithm->getCEKSize());
            case KeyEncryptionInterface::MODE_AGREEMENT:
                if (1 !== count($this->recipients)) {
                    throw new \LogicException('Unable to encrypt for multiple recipients using key agreement algorithms.');
                }
                /** @var JWK $key */
                $key = $this->recipients[0]['key'];
                /** @var KeyAgreementInterface $algorithm */
                $algorithm = $this->recipients[0]['key_encryption_algorithm'];
                $completeHeaders = array_merge($this->sharedHeaders, $this->recipients[0]['headers'], $this->sharedProtectedHeaders);

                return $algorithm->getAgreementKey($this->contentEncryptionAlgorithm->getCEKSize(), $this->contentEncryptionAlgorithm->name(), $key, $completeHeaders, $additionalHeaders);
            case KeyEncryptionInterface::MODE_DIRECT:
                if (1 !== count($this->recipients)) {
                    throw new \LogicException('Unable to encrypt for multiple recipients using key agreement algorithms.');
                }
                /** @var JWK $key */
                $key = $this->recipients[0]['key'];
                if ('oct' !== $key->get('kty')) {
                    throw new \RuntimeException('Wrong key type.');
                }

                return Base64Url::decode($key->get('k'));
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported key management mode "%s".', $this->keyManagementMode));
        }
    }

    /**
     * @param array $completeHeaders
     *
     * @return CompressionMethodInterface|null
     */
    protected function getCompressionMethod(array $completeHeaders): ?CompressionMethodInterface
    {
        if (!array_key_exists('zip', $completeHeaders)) {
            return null;
        }

        return $this->compressionManager->get($completeHeaders['zip']);
    }

    /**
     * @param string $current
     * @param string $new
     *
     * @return bool
     */
    protected function areKeyManagementModesCompatible(string $current, string $new): bool
    {
        $agree = KeyEncryptionAlgorithmInterface::MODE_AGREEMENT;
        $dir = KeyEncryptionAlgorithmInterface::MODE_DIRECT;
        $enc = KeyEncryptionAlgorithmInterface::MODE_ENCRYPT;
        $wrap = KeyEncryptionAlgorithmInterface::MODE_WRAP;
        $supportedKeyManagementModeCombinations = [$enc.$enc => true, $enc.$wrap => true, $wrap.$enc => true, $wrap.$wrap => true, $agree.$agree => false, $agree.$dir => false, $agree.$enc => false, $agree.$wrap => false, $dir.$agree => false, $dir.$dir => false, $dir.$enc => false, $dir.$wrap => false, $enc.$agree => false, $enc.$dir => false, $wrap.$agree => false, $wrap.$dir => false];

        if (array_key_exists($current.$new, $supportedKeyManagementModeCombinations)) {
            return $supportedKeyManagementModeCombinations[$current.$new];
        }

        return false;
    }

    /**
     * @param int $size
     *
     * @return string
     */
    private function createCEK(int $size): string
    {
        return random_bytes($size / 8);
    }

    /**
     * @param int $size
     *
     * @return string
     */
    private function createIV(int $size): string
    {
        return random_bytes($size / 8);
    }

    /**
     * @param array $completeHeaders
     *
     * @return KeyEncryptionAlgorithmInterface
     */
    protected function getKeyEncryptionAlgorithm(array $completeHeaders): KeyEncryptionAlgorithmInterface
    {
        if (!array_key_exists('alg', $completeHeaders)) {
            throw new \InvalidArgumentException('Parameter "alg" is missing.');
        }
        $keyEncryptionAlgorithm = $this->keyEncryptionAlgorithmManager->get($completeHeaders['alg']);
        if (!$keyEncryptionAlgorithm instanceof KeyEncryptionAlgorithmInterface) {
            throw new \InvalidArgumentException(sprintf('The key encryption algorithm "%s" is not supported or not a key encryption algorithm instance.', $completeHeaders['alg']));
        }

        return $keyEncryptionAlgorithm;
    }

    /**
     * @param array $completeHeaders
     *
     * @return ContentEncryptionAlgorithmInterface
     */
    private function getContentEncryptionAlgorithm(array $completeHeaders): ContentEncryptionAlgorithmInterface
    {
        if (!array_key_exists('enc', $completeHeaders)) {
            throw new \InvalidArgumentException('Parameter "enc" is missing.');
        }
        $contentEncryptionAlgorithm = $this->contentEncryptionAlgorithmManager->get($completeHeaders['enc']);
        if (!$contentEncryptionAlgorithm instanceof ContentEncryptionAlgorithmInterface) {
            throw new \InvalidArgumentException(sprintf('The content encryption algorithm "%s" is not supported or not a content encryption algorithm instance.', $completeHeaders['alg']));
        }

        return $contentEncryptionAlgorithm;
    }

    /**
     * @param array $header1
     * @param array $header2
     */
    private function checkDuplicatedHeaderParameters(array $header1, array $header2)
    {
        $inter = array_intersect_key($header1, $header2);
        if (!empty($inter)) {
            throw new \InvalidArgumentException(sprintf('The header contains duplicated entries: %s.', implode(', ', array_keys($inter))));
        }
    }
}