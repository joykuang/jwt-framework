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

namespace Jose\Component\Console\Tests;

use Jose\Component\Console;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @group Console
 * @group KeyConversionCommand
 */
final class KeyConversionCommandTest extends TestCase
{
    /**
     * @test
     */
    public function iCanLoadAKeyFile()
    {
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'file' => __DIR__.'/Sample/2048b-rsa-example-cert.pem',
        ]);
        $output = new BufferedOutput();
        $command = new Console\KeyFileLoaderCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        $jwk = JWK::createFromJson($content);
        self::assertInstanceOf(JWK::class, $jwk);
    }

    /**
     * @test
     */
    public function iCanLoadAnEncryptedKeyFile()
    {
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'file' => __DIR__.'/Sample/private.es512.encrypted.key',
            '--secret' => 'test',
        ]);
        $output = new BufferedOutput();
        $command = new Console\KeyFileLoaderCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        $jwk = JWK::createFromJson($content);
        self::assertInstanceOf(JWK::class, $jwk);
    }

    /**
     * @test
     */
    public function iCanLoadAPKCS12CertificateFile()
    {
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'file' => __DIR__.'/Sample/CertRSA.p12',
            '--secret' => 'certRSA',
        ]);
        $output = new BufferedOutput();
        $command = new Console\P12CertificateLoaderCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        $jwk = JWK::createFromJson($content);
        self::assertInstanceOf(JWK::class, $jwk);
    }

    /**
     * @test
     */
    public function iCanLoadAX509CertificateFile()
    {
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'file' => __DIR__.'/Sample/google.crt',
        ]);
        $output = new BufferedOutput();
        $command = new Console\X509CertificateLoaderCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        $jwk = JWK::createFromJson($content);
        self::assertInstanceOf(JWK::class, $jwk);
    }

    /**
     * @test
     */
    public function iCanOptimizeARsaKey()
    {
        $jwk = JWK::create([
            'kty' => 'RSA',
            'n' => 'gVf-iyhwLn2J2Up4EKjwdLYmk5n24gjGk4oQkCHVcE7j8wkS1iSzcu0ApVcMPLklEp_PWycZE12vL90gPeVjF2IPL_MKFL0b6Wy7A1f4kCDkKv7TDDjt1IIwbS-Jdp-2pG7bPb3tWjJUu6QZBLoXfRtW3cMDkQjXaVGixENORLAZs6qdu2MMKV94jetCiFd0JYCjxGVC0HW2OKnM21B_2R1NubOvMlWA7gypdpvmBYDGpkw4mjV3walWlCZObG7IH84Ovl7wOP8XLzqi2un4e6fNzy3rdp4OUSPYItF4ZX5qThWYY2R47Z5sbrZxHjNeDECKUeio0KPQNrgr6FSKSw',
            'e' => 'AQAB',
            'd' => 'JSqz6ijkk3dfdSEA_0iMT_1HeIJ1ft4msZ6qw7_1JSCGQAALeZ1yM0QHO3uX-Jr7HC7v1rGVcwsonAhei2qu3rk-w_iCnRL6QkkMNBnDQycwaWpwGsMBFF-UqstOJNggE4AHX-aDnbd4wbKVvdX7ieehPngbPkHcJFdg_iSZCQNoajz6XfEruyIi7_IFXYEGmH_UyEbQkgNtriZysutgYdolUjo9flUlh20HbuV3NwsPjGyDG4dUMpNpdBpSuRHYKLX6h3FjeLhItBmhBfuL7d-G3EXwKlwfNXXYivqY5NQAkFNrRbvFlc_ARIws3zAfykPDIWGWFiPiN3H-hXMgAQ',
        ]);
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'jwk' => $converter->encode($jwk),
        ]);
        $output = new BufferedOutput();
        $command = new Console\OptimizeRsaKeyCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        $jwk = JWK::createFromJson($content);
        self::assertInstanceOf(JWK::class, $jwk);
        self::assertTrue($jwk->has('p'));
        self::assertTrue($jwk->has('q'));
        self::assertTrue($jwk->has('dp'));
        self::assertTrue($jwk->has('dq'));
        self::assertTrue($jwk->has('qi'));
    }

    /**
     * @test
     */
    public function iCanConvertARsaKeyIntoPKCS1()
    {
        $jwk = JWK::create([
            'kty' => 'RSA',
            'n' => 'gVf-iyhwLn2J2Up4EKjwdLYmk5n24gjGk4oQkCHVcE7j8wkS1iSzcu0ApVcMPLklEp_PWycZE12vL90gPeVjF2IPL_MKFL0b6Wy7A1f4kCDkKv7TDDjt1IIwbS-Jdp-2pG7bPb3tWjJUu6QZBLoXfRtW3cMDkQjXaVGixENORLAZs6qdu2MMKV94jetCiFd0JYCjxGVC0HW2OKnM21B_2R1NubOvMlWA7gypdpvmBYDGpkw4mjV3walWlCZObG7IH84Ovl7wOP8XLzqi2un4e6fNzy3rdp4OUSPYItF4ZX5qThWYY2R47Z5sbrZxHjNeDECKUeio0KPQNrgr6FSKSw',
            'e' => 'AQAB',
            'p' => 'pxyF-Ao17wl4ADI0YSsNYm9OzZz6AZD9cUxbxvX-z3yR_vH2GExdcOht5UD9Ij9r0ZyHKkmWGKCtrYzr-Qi2ia2vyiZU0wGmxR_fadHnkxfIqW78ME5C-xGoWLBtHlTaPCWSEmv3p5vM2fqZeUdqTxzb0bQABt0fI6HPjvBlI0s',
            'd' => 'JSqz6ijkk3dfdSEA_0iMT_1HeIJ1ft4msZ6qw7_1JSCGQAALeZ1yM0QHO3uX-Jr7HC7v1rGVcwsonAhei2qu3rk-w_iCnRL6QkkMNBnDQycwaWpwGsMBFF-UqstOJNggE4AHX-aDnbd4wbKVvdX7ieehPngbPkHcJFdg_iSZCQNoajz6XfEruyIi7_IFXYEGmH_UyEbQkgNtriZysutgYdolUjo9flUlh20HbuV3NwsPjGyDG4dUMpNpdBpSuRHYKLX6h3FjeLhItBmhBfuL7d-G3EXwKlwfNXXYivqY5NQAkFNrRbvFlc_ARIws3zAfykPDIWGWFiPiN3H-hXMgAQ',
            'q' => 'xiSp6dbdYGINxtklTJlzVr91u_GJzWqyyA4t0jhuWrQN7dLW0s_3I9x6Pdk5U19j0iLWBwcutY9e5SyWPoF0lYVIowZeW0jNiOtv0NthayJ3HJpPk8kj6sVlH0y4sKN_WWHhU5leTwOpr8IG-yohKRyV6Xwhu_JLkzKKWod21QE',
            'dp' => 'pYUyCNGMRDx7uK4BhbEP68zWIAB4_K4w6lS4nuQvRDJdpUjh-YVCFECUATwSviZVU-QXWUJTwgb8n-byH9OKgeogMTkwUWPUXHHKZ1T6a45mObRtZCdQXsBJn7b4Dc_77RFFkquQPFqsV8fI1gBvgvbRn-8LC8FfQ3rVS_4-Hus',
            'dq' => 'rNTcNPFLhj_hPnq4UzliZt94RaipB7mzGldr1nuMnqeBotmOsrHeI7S0F_C7VSLWgjwKrnSwZIQbRRGAOCNZWva4ZiMu-LbnOTAMB4TkU7vrY9Kh6QnAv47Q5t1YGBN1CLUdA3u6zHcocvtudXTJGgAqL1AsaLEvBMVH8zFIEQE',
            'qi' => 'bbFp1zSfnmmOUYUtbaKhmFofn0muf1PrnMGq6zeu8zruf3gK9Y1oDsUk54FlV0mNBO3_t3Zbw2752CLklt73zesVeF-Nsc1kDnx_WGf4YrQpLh5PvkEfT_wPbveKTTcVXiVxMPHHZ-n2kOe3oyShycSLP5_I_SYN-loZHu7QC_I',
        ]);
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'jwk' => $converter->encode($jwk),
        ]);
        $output = new BufferedOutput();
        $command = new Console\PemConverterCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        self::assertContains('-----BEGIN RSA PRIVATE KEY-----', $content);
    }

    /**
     * @test
     */
    public function iCanConvertAnEcKeyIntoPKCS1()
    {
        $jwk = JWK::create([
            'kty' => 'EC',
            'crv' => 'P-256',
            'd' => '13n3isfsEktzl-CtH5ECpRrKk-40prVuCbldkP77gak',
            'x' => 'YcIMUkalwbeeAVkUF6FP3aBVlCzlqxEd7i0uN_4roA0',
            'y' => 'bU8wOWJBkTNZ61gB1_4xp-r8-uVsQB8D6Xsl-aKMCy8',
        ]);
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'jwk' => $converter->encode($jwk),
        ]);
        $output = new BufferedOutput();
        $command = new Console\PemConverterCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        self::assertContains('-----BEGIN EC PRIVATE KEY-----', $content);
    }

    /**
     * @test
     */
    public function iCanConvertAPrivateKeyIntoPublicKey()
    {
        $jwk = JWK::create([
            'kty' => 'EC',
            'crv' => 'P-256',
            'd' => '13n3isfsEktzl-CtH5ECpRrKk-40prVuCbldkP77gak',
            'x' => 'YcIMUkalwbeeAVkUF6FP3aBVlCzlqxEd7i0uN_4roA0',
            'y' => 'bU8wOWJBkTNZ61gB1_4xp-r8-uVsQB8D6Xsl-aKMCy8',
        ]);
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'jwk' => $converter->encode($jwk),
        ]);
        $output = new BufferedOutput();
        $command = new Console\PublicKeyCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        self::assertContains('{"kty":"EC","crv":"P-256","x":"YcIMUkalwbeeAVkUF6FP3aBVlCzlqxEd7i0uN_4roA0","y":"bU8wOWJBkTNZ61gB1_4xp-r8-uVsQB8D6Xsl-aKMCy8"}', $content);
    }

    /**
     * @test
     */
    public function iCanConvertPrivateKeysIntoPublicKeys()
    {
        $keyset = JWKSet::createFromKeyData(['keys' => [
            [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => 'f83OJ3D2xF1Bg8vub9tLe1gHMzV76e8Tus9uPHvRVEU',
                'y' => 'x_FEzRu9m36HLN_tue659LNpXW6pCyStikYjKIWI5a0',
            ],
            [
                'kty' => 'EC',
                'crv' => 'P-521',
                'x' => 'AekpBQ8ST8a8VcfVOTNl353vSrDCLLJXmPk06wTjxrrjcBpXp5EOnYG_NjFZ6OvLFV1jSfS9tsz4qUxcWceqwQGk',
                'y' => 'ADSmRA43Z1DSNx_RvcLI87cdL07l6jQyyBXMoxVg_l2Th-x3S1WDhjDly79ajL4Kkd0AZMaZmh9ubmf63e3kyMj2',
            ],
        ]]);
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'jwkset' => $converter->encode($keyset),
        ]);
        $output = new BufferedOutput();
        $command = new Console\PublicKeysetCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        self::assertContains('{"keys":[{"kty":"EC","crv":"P-256","x":"f83OJ3D2xF1Bg8vub9tLe1gHMzV76e8Tus9uPHvRVEU","y":"x_FEzRu9m36HLN_tue659LNpXW6pCyStikYjKIWI5a0"},{"kty":"EC","crv":"P-521","x":"AekpBQ8ST8a8VcfVOTNl353vSrDCLLJXmPk06wTjxrrjcBpXp5EOnYG_NjFZ6OvLFV1jSfS9tsz4qUxcWceqwQGk","y":"ADSmRA43Z1DSNx_RvcLI87cdL07l6jQyyBXMoxVg_l2Th-x3S1WDhjDly79ajL4Kkd0AZMaZmh9ubmf63e3kyMj2"}]}', $content);
    }

    /**
     * @test
     */
    public function iCanGetTheThumbprintOfAKey()
    {
        $jwk = JWK::create([
            'kty' => 'RSA',
            'n' => '0vx7agoebGcQSuuPiLJXZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tSoc_BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ_2W-5JsGY4Hc5n9yBXArwl93lqt7_RN5w6Cf0h4QyQ5v-65YGjQR0_FDW2QvzqY368QQMicAtaSqzs8KJZgnYb9c7d0zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt-bFTWhAI4vMQFh6WeZu0fM4lFd2NcRwr3XPksINHaQ-G_xBniIqbw0Ls1jF44-csFCur-kEgU8awapJzKnqDKgw',
            'e' => 'AQAB',
            'alg' => 'RS256',
            'kid' => '2011-04-29',
        ]);
        $converter = new StandardConverter();
        $input = new ArrayInput([
            'jwk' => $converter->encode($jwk),
        ]);
        $output = new BufferedOutput();
        $command = new Console\GetThumbprintCommand($converter);
        $command->run($input, $output);
        $content = $output->fetch();
        self::assertEquals('NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs', $content);
    }
}
