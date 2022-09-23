<?php

namespace EvoSC\Modules\OpenplanetControl\Lib;

use EvoSC\Modules\OpenplanetControl\Exceptions\CouldNotParseToolInfoException;

/**
 * @property string $version
 * @property string $game
 * @property string $branch
 * @property string $signatureMode
 * @property string $build
 */
class OpenplanetInfo
{
    const MODE_REGULAR = 'REGULAR'; //Default: all signed plugins
    const MODE_DEV = 'DEVMODE'; //All plugins, including unsigned ones
    const MODE_OFFICIAL = 'OFFICIAL'; //Plugins shipped with Openplanet
    const MODE_COMPETITION = 'COMPETITION'; //Plugins approved for use in TMGL
    const MODE_UNKNOWN = 'UNKNOWN';

    public string $version;
    public string $game;
    public string $branch;
    public string $build;
    public string $signatureMode;

    public function __construct(string $version, string $game, string $branch, string $build, string $signatureMode)
    {
        $this->version = $version;
        $this->game = $game;
        $this->branch = $branch;
        $this->build = $build;
        $this->signatureMode = $signatureMode;
    }

    /**
     * @return bool
     */
    public function isDevMode(): bool
    {
        return $this->signatureMode == self::MODE_DEV;
    }
}

class Openplanet
{
    /**
     * Matching pattern for tool info string
     */
    const PATTERN = '/^Openplanet ([\d.]+) \((\w+), ([A-Z]\w+), (\d{4}-\d{2}-\d{2})\)(?:\s(?:\[([A-Z]+)\]))*$/';

    /**
     * @param string $toolInfo
     *
     * @return \EvoSC\Modules\OpenplanetControl\Lib\OpenplanetInfo
     * @throws \EvoSC\Modules\OpenplanetControl\Exceptions\CouldNotParseToolInfoException
     */
    public static function parseToolInfo(string $toolInfo)
    {
        if (preg_match(self::PATTERN, $toolInfo, $matches)) {
            $openplanetVersion = $matches[1];
            $game = $matches[2];
            $branch = $matches[3];
            $build = $matches[4];
            $signatureMode = empty($matches[5]) ? OpenplanetInfo::MODE_REGULAR : $matches[5];

            return new OpenplanetInfo($openplanetVersion, $game, $branch, $build, $signatureMode);
        } else {
            throw new CouldNotParseToolInfoException("Failed to parse: $toolInfo");
        }
    }
}
