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

namespace Jose\Component\Encryption\Tests\RFC7520;

use Base64Url\Base64Url;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Tests\EncryptionTest;

/**
 * @see https://tools.ietf.org/html/rfc7520#section-5.8
 *
 * @group RFC7520
 */
final class A128KWAndA128GCMEncryptionTest extends EncryptionTest
{
    /**
     * Please note that we cannot the encryption and get the same result as the example (IV, TAG and other data are always different).
     * The output given in the RFC is used and only decrypted.
     */
    public function testA128KWAndA128GCMEncryption()
    {
        $expected_payload = "You can trust us to stick with you through thick and thin\xe2\x80\x93to the bitter end. And you can trust us to keep any secret of yours\xe2\x80\x93closer than you keep it yourself. But you cannot trust us to let you face trouble alone, and go off without a word. We are your friends, Frodo.";

        $private_key = JWK::create([
            'kty' => 'oct',
            'kid' => '81b20965-8332-43d9-a468-82160ad91ac8',
            'use' => 'enc',
            'alg' => 'A128KW',
            'k' => 'GZy6sIZ6wl9NJOKB-jnmVQ',
        ]);

        $protectedHeader = [
            'alg' => 'A128KW',
            'kid' => '81b20965-8332-43d9-a468-82160ad91ac8',
            'enc' => 'A128GCM',
        ];

        $expected_compact_json = 'eyJhbGciOiJBMTI4S1ciLCJraWQiOiI4MWIyMDk2NS04MzMyLTQzZDktYTQ2OC04MjE2MGFkOTFhYzgiLCJlbmMiOiJBMTI4R0NNIn0.CBI6oDw8MydIx1IBntf_lQcw2MmJKIQx.Qx0pmsDa8KnJc9Jo.AwliP-KmWgsZ37BvzCefNen6VTbRK3QMA4TkvRkH0tP1bTdhtFJgJxeVmJkLD61A1hnWGetdg11c9ADsnWgL56NyxwSYjU1ZEHcGkd3EkU0vjHi9gTlb90qSYFfeF0LwkcTtjbYKCsiNJQkcIp1yeM03OmuiYSoYJVSpf7ej6zaYcMv3WwdxDFl8REwOhNImk2Xld2JXq6BR53TSFkyT7PwVLuq-1GwtGHlQeg7gDT6xW0JqHDPn_H-puQsmthc9Zg0ojmJfqqFvETUxLAF-KjcBTS5dNy6egwkYtOt8EIHK-oEsKYtZRaa8Z7MOZ7UGxGIMvEmxrGCPeJa14slv2-gaqK0kEThkaSqdYw0FkQZF.ER7MWJZ1FBI_NKvn7Zb1Lw';
        $expected_flattened_json = '{"protected":"eyJhbGciOiJBMTI4S1ciLCJraWQiOiI4MWIyMDk2NS04MzMyLTQzZDktYTQ2OC04MjE2MGFkOTFhYzgiLCJlbmMiOiJBMTI4R0NNIn0","encrypted_key":"CBI6oDw8MydIx1IBntf_lQcw2MmJKIQx","iv":"Qx0pmsDa8KnJc9Jo","ciphertext":"AwliP-KmWgsZ37BvzCefNen6VTbRK3QMA4TkvRkH0tP1bTdhtFJgJxeVmJkLD61A1hnWGetdg11c9ADsnWgL56NyxwSYjU1ZEHcGkd3EkU0vjHi9gTlb90qSYFfeF0LwkcTtjbYKCsiNJQkcIp1yeM03OmuiYSoYJVSpf7ej6zaYcMv3WwdxDFl8REwOhNImk2Xld2JXq6BR53TSFkyT7PwVLuq-1GwtGHlQeg7gDT6xW0JqHDPn_H-puQsmthc9Zg0ojmJfqqFvETUxLAF-KjcBTS5dNy6egwkYtOt8EIHK-oEsKYtZRaa8Z7MOZ7UGxGIMvEmxrGCPeJa14slv2-gaqK0kEThkaSqdYw0FkQZF","tag":"ER7MWJZ1FBI_NKvn7Zb1Lw"}';
        $expected_json = '{"recipients":[{"encrypted_key":"CBI6oDw8MydIx1IBntf_lQcw2MmJKIQx"}],"protected":"eyJhbGciOiJBMTI4S1ciLCJraWQiOiI4MWIyMDk2NS04MzMyLTQzZDktYTQ2OC04MjE2MGFkOTFhYzgiLCJlbmMiOiJBMTI4R0NNIn0","iv":"Qx0pmsDa8KnJc9Jo","ciphertext":"AwliP-KmWgsZ37BvzCefNen6VTbRK3QMA4TkvRkH0tP1bTdhtFJgJxeVmJkLD61A1hnWGetdg11c9ADsnWgL56NyxwSYjU1ZEHcGkd3EkU0vjHi9gTlb90qSYFfeF0LwkcTtjbYKCsiNJQkcIp1yeM03OmuiYSoYJVSpf7ej6zaYcMv3WwdxDFl8REwOhNImk2Xld2JXq6BR53TSFkyT7PwVLuq-1GwtGHlQeg7gDT6xW0JqHDPn_H-puQsmthc9Zg0ojmJfqqFvETUxLAF-KjcBTS5dNy6egwkYtOt8EIHK-oEsKYtZRaa8Z7MOZ7UGxGIMvEmxrGCPeJa14slv2-gaqK0kEThkaSqdYw0FkQZF","tag":"ER7MWJZ1FBI_NKvn7Zb1Lw"}';
        $expected_iv = 'Qx0pmsDa8KnJc9Jo';
        $expected_encrypted_key = 'CBI6oDw8MydIx1IBntf_lQcw2MmJKIQx';
        $expected_ciphertext = 'AwliP-KmWgsZ37BvzCefNen6VTbRK3QMA4TkvRkH0tP1bTdhtFJgJxeVmJkLD61A1hnWGetdg11c9ADsnWgL56NyxwSYjU1ZEHcGkd3EkU0vjHi9gTlb90qSYFfeF0LwkcTtjbYKCsiNJQkcIp1yeM03OmuiYSoYJVSpf7ej6zaYcMv3WwdxDFl8REwOhNImk2Xld2JXq6BR53TSFkyT7PwVLuq-1GwtGHlQeg7gDT6xW0JqHDPn_H-puQsmthc9Zg0ojmJfqqFvETUxLAF-KjcBTS5dNy6egwkYtOt8EIHK-oEsKYtZRaa8Z7MOZ7UGxGIMvEmxrGCPeJa14slv2-gaqK0kEThkaSqdYw0FkQZF';
        $expected_tag = 'ER7MWJZ1FBI_NKvn7Zb1Lw';

        $jweDecrypter = $this->getJWEDecrypterFactory()->create(['A128KW'], ['A128GCM'], ['DEF']);

        $loaded_compact_json = $this->getJWESerializerManager()->unserialize($expected_compact_json);
        self::assertTrue($jweDecrypter->decryptUsingKey($loaded_compact_json, $private_key, 0));

        $loaded_flattened_json = $this->getJWESerializerManager()->unserialize($expected_flattened_json);
        self::assertTrue($jweDecrypter->decryptUsingKey($loaded_flattened_json, $private_key, 0));

        $loaded_json = $this->getJWESerializerManager()->unserialize($expected_json);
        self::assertTrue($jweDecrypter->decryptUsingKey($loaded_json, $private_key, 0));

        self::assertEquals($expected_ciphertext, Base64Url::encode($loaded_compact_json->getCiphertext()));
        self::assertEquals($protectedHeader, $loaded_compact_json->getSharedProtectedHeader());
        self::assertEquals($expected_iv, Base64Url::encode($loaded_compact_json->getIV()));
        self::assertEquals($expected_encrypted_key, Base64Url::encode($loaded_compact_json->getRecipient(0)->getEncryptedKey()));
        self::assertEquals($expected_tag, Base64Url::encode($loaded_compact_json->getTag()));

        self::assertEquals($expected_ciphertext, Base64Url::encode($loaded_flattened_json->getCiphertext()));
        self::assertEquals($protectedHeader, $loaded_flattened_json->getSharedProtectedHeader());
        self::assertEquals($expected_iv, Base64Url::encode($loaded_flattened_json->getIV()));
        self::assertEquals($expected_encrypted_key, Base64Url::encode($loaded_flattened_json->getRecipient(0)->getEncryptedKey()));
        self::assertEquals($expected_tag, Base64Url::encode($loaded_flattened_json->getTag()));

        self::assertEquals($expected_ciphertext, Base64Url::encode($loaded_json->getCiphertext()));
        self::assertEquals($protectedHeader, $loaded_json->getSharedProtectedHeader());
        self::assertEquals($expected_iv, Base64Url::encode($loaded_json->getIV()));
        self::assertEquals($expected_encrypted_key, Base64Url::encode($loaded_json->getRecipient(0)->getEncryptedKey()));
        self::assertEquals($expected_tag, Base64Url::encode($loaded_json->getTag()));

        self::assertEquals($expected_payload, $loaded_compact_json->getPayload());
        self::assertEquals($expected_payload, $loaded_flattened_json->getPayload());
        self::assertEquals($expected_payload, $loaded_json->getPayload());
    }

    /**
     * Same input as before, but we perform the encryption first.
     */
    public function testA128KWAndA128GCMEncryptionBis()
    {
        $expected_payload = "You can trust us to stick with you through thick and thin\xe2\x80\x93to the bitter end. And you can trust us to keep any secret of yours\xe2\x80\x93closer than you keep it yourself. But you cannot trust us to let you face trouble alone, and go off without a word. We are your friends, Frodo.";

        $private_key = JWK::create([
            'kty' => 'oct',
            'kid' => '81b20965-8332-43d9-a468-82160ad91ac8',
            'use' => 'enc',
            'alg' => 'A128KW',
            'k' => 'GZy6sIZ6wl9NJOKB-jnmVQ',
        ]);

        $protectedHeader = [
            'alg' => 'A128KW',
            'kid' => '81b20965-8332-43d9-a468-82160ad91ac8',
            'enc' => 'A128GCM',
        ];

        $jweBuilder = $this->getJWEBuilderFactory()->create(['A128KW'], ['A128GCM'], ['DEF']);
        $jweDecrypter = $this->getJWEDecrypterFactory()->create(['A128KW'], ['A128GCM'], ['DEF']);

        $jwe = $jweBuilder
            ->create()->withPayload($expected_payload)
            ->withSharedProtectedHeader($protectedHeader)
            ->addRecipient($private_key)
            ->build();

        $loaded_compact_json = $this->getJWESerializerManager()->unserialize($this->getJWESerializerManager()->serialize('jwe_compact', $jwe, 0));
        self::assertTrue($jweDecrypter->decryptUsingKey($loaded_compact_json, $private_key, 0));

        $loaded_flattened_json = $this->getJWESerializerManager()->unserialize($this->getJWESerializerManager()->serialize('jwe_json_flattened', $jwe, 0));
        self::assertTrue($jweDecrypter->decryptUsingKey($loaded_flattened_json, $private_key, 0));

        $loaded_json = $this->getJWESerializerManager()->unserialize($this->getJWESerializerManager()->serialize('jwe_json_general', $jwe));
        self::assertTrue($jweDecrypter->decryptUsingKey($loaded_json, $private_key, 0));

        self::assertEquals($protectedHeader, $loaded_compact_json->getSharedProtectedHeader());

        self::assertEquals($protectedHeader, $loaded_flattened_json->getSharedProtectedHeader());

        self::assertEquals($protectedHeader, $loaded_json->getSharedProtectedHeader());

        self::assertEquals($expected_payload, $loaded_compact_json->getPayload());
        self::assertEquals($expected_payload, $loaded_flattened_json->getPayload());
        self::assertEquals($expected_payload, $loaded_json->getPayload());
    }
}
