<?php

namespace Tests\WidgetEditor;

use Nails\Cms\WidgetEditor\Service\Version;
use Nails\Common\Service\Session;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testNormalise(): void
    {
        self::assertSame(['v1', 'v2'], Version::normalise(null));
        self::assertSame(['v1', 'v2'], Version::normalise([]));
        self::assertSame(['v1', 'v2'], Version::normalise(['nope']));
        self::assertSame(['v1'], Version::normalise('v1'));
        self::assertSame(['v2', 'v1'], Version::normalise(['v2', 'v1', 'v2']));
    }

    public function testMissingSessionChoiceStoresTheFirstEnabledVersion(): void
    {
        $session = $this->session(null);
        $version = new Version($session, Version::normalise(null));

        self::assertSame('v1', $version->getDefault());
        self::assertSame('v1', $session->stored[Version::SESSION_KEY]);
    }

    public function testStoredChoiceIsKeptWhenItIsStillEnabled(): void
    {
        $session = $this->session('v2');
        $version = new Version($session, Version::normalise(null));

        self::assertSame('v2', $version->getDefault());
        self::assertArrayNotHasKey(Version::SESSION_KEY, $session->written);
    }

    public function testStoredChoiceIsReplacedWhenThatVersionIsDisabled(): void
    {
        $session = $this->session('v2');
        $version = new Version($session, Version::normalise(['v1']));

        self::assertSame('v1', $version->getDefault());
        self::assertSame('v1', $session->stored[Version::SESSION_KEY]);
    }

    public function testSetDefaultRefusesAVersionThatIsNotEnabled(): void
    {
        $version = new Version($this->session(null), Version::normalise(['v1']));

        $this->expectException(\InvalidArgumentException::class);
        $version->setDefault('v2');
    }

    public function testSetDefaultStoresTheChoice(): void
    {
        $session = $this->session(null);
        $version = new Version($session, Version::normalise(null));

        $version->setDefault('v2');

        self::assertSame('v2', $version->getDefault());
        self::assertSame('v2', $session->stored[Version::SESSION_KEY]);
    }

    private function session(?string $stored): Session
    {
        return new class($stored) extends Session {
            public array $stored = [];

            public array $written = [];

            public function __construct(?string $stored)
            {
                if ($stored !== null) {
                    $this->stored[Version::SESSION_KEY] = $stored;
                }
            }

            public function getUserData(?string $sKey = null)
            {
                return $this->stored[$sKey] ?? null;
            }

            public function setUserData($mKey, $mValue = null): Session
            {
                $this->stored[$mKey] = $mValue;
                $this->written[$mKey] = $mValue;

                return $this;
            }
        };
    }
}
