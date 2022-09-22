<?php

namespace EvoSC\Modules\OpenplanetControl\Lib;

use EvoSC\Modules\OpenplanetControl\Exceptions\CouldNotParseToolInfoException;

class OpenplanetInfo
{
    const MODE_DEV = 'DEVMODE';
    const MODE_OFFICIAL = 'OFFICIAL';
    const MODE_COMPETITION = 'COMPETITION';
    const MODE_UNKNOWN = 'UNKNOWN';

    public string $version;
    public string $game;
    public string $branch;
    public string $build;
    public string $mode;

    public function __construct(string $version, string $game, string $branch, string $build, bool $isDev)
    {
        $this->version = $version;
        $this->game = $game;
        $this->branch = $branch;
        $this->build = $build;
        $this->mode = $isDev;
    }

    /**
     * @return bool
     */
    public function isDevMode(): bool
    {
        return $this->mode == self::MODE_DEV;
    }
}

class Openplanet
{
    /**
     * Matching pattern for tool info string
     */
    const PATTERN = '/(Openplanet)\s([\d\.]+)\s\(([^,]+,\s[^\)]+)\)\s?\[?([^\]]*)/';

    /**
     * @param string $toolInfo
     *
     * @return \EvoSC\Modules\OpenplanetControl\Lib\OpenplanetInfo
     * @throws \EvoSC\Modules\OpenplanetControl\Exceptions\CouldNotParseToolInfoException
     */
    public static function parseToolInfo(string $toolInfo)
    {
        if (preg_match(self::PATTERN, $toolInfo, $matches, PREG_UNMATCHED_AS_NULL)) {
            $details = explode(", ", $matches[3]);
            $version = $matches[2];

            $game = $details[0];
            $branch = $details[1];
            $build = $details[2];
            $mode = $matches[4];

            return new OpenplanetInfo($version, $game, $branch, $build, $mode);
        } else {
            throw new CouldNotParseToolInfoException("Failed to parse: $toolInfo");
        }
    }
}
