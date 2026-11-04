<?php

/**
 * Which WidgetEditor version opens, for this browser session
 *
 * `CMS_WIDGET_EDITOR_ENABLE` lists the enabled versions, the same way `CDN_MEDIA_MANAGER_ENABLE` does.
 * An empty or missing value enables both. With no session choice the first enabled version opens, and
 * that choice is stored for the session. v1 is listed first, so both-on opens v1 until someone switches.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

use Nails\Common\Service\Session;
use Nails\Config;

class Version
{
    public const V1 = 'v1';

    public const V2 = 'v2';

    public const SESSION_KEY = 'CMS_WIDGET_EDITOR_DEFAULT';

    public const CONFIG_KEY = 'CMS_WIDGET_EDITOR_ENABLE';

    /** @var string[] */
    public const VERSIONS = [self::V1, self::V2];

    /**
     * @param string[] $enabled Already passed through normalise()
     */
    public function __construct(
        private Session $session,
        private array $enabled,
    ) {
    }

    // --------------------------------------------------------------------------

    public static function fromConfig(Session $session): static
    {
        return new static($session, static::normalise(Config::get(static::CONFIG_KEY)));
    }

    // --------------------------------------------------------------------------

    /**
     * @return string[]
     */
    public static function normalise(mixed $configured): array
    {
        if (is_string($configured) && $configured !== '') {
            $configured = [$configured];
        }

        if (!is_array($configured)) {
            $configured = [];
        }

        $enabled = [];
        foreach ($configured as $version) {
            if (!is_string($version) || !in_array($version, static::VERSIONS, true) || in_array($version, $enabled, true)) {
                continue;
            }
            $enabled[] = $version;
        }

        return $enabled === [] ? static::VERSIONS : $enabled;
    }

    // --------------------------------------------------------------------------

    /**
     * @return string[]
     */
    public function getEnabledVersions(): array
    {
        return $this->enabled;
    }

    // --------------------------------------------------------------------------

    public function isVersionEnabled(string $version): bool
    {
        return in_array($version, $this->enabled, true);
    }

    // --------------------------------------------------------------------------

    /**
     * The version that opens. A stored choice that is no longer enabled is replaced.
     */
    public function getDefault(): string
    {
        $stored = $this->session->getUserData(self::SESSION_KEY);
        if (is_string($stored) && $this->isVersionEnabled($stored)) {
            return $stored;
        }

        $default = $this->enabled[0];
        $this->session->setUserData(self::SESSION_KEY, $default);

        return $default;
    }

    // --------------------------------------------------------------------------

    /**
     * @throws \InvalidArgumentException
     */
    public function setDefault(string $version): static
    {
        if (!in_array($version, self::VERSIONS, true)) {
            throw new \InvalidArgumentException('Invalid editor version');
        }

        if (!$this->isVersionEnabled($version)) {
            throw new \InvalidArgumentException(sprintf('Editor version "%s" is not enabled', $version));
        }

        $this->session->setUserData(self::SESSION_KEY, $version);

        return $this;
    }
}
