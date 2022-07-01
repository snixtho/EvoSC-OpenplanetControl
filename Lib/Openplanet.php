<?php

namespace EvoSC\Modules\OpenplanetControl\Lib;

class OpenplanetInfo {
    public string $version;
    public string $game;
    public string $branch;
    public string $build;
    public bool $devMode;

    public function __construct(string $version, string $game, string $branch, string $build, bool $isDev)
    {
        $this->version = $version;
        $this->game = $game;
        $this->branch = $branch;
        $this->build = $build;
        $this->devMode = $isDev;
    }
}

class Openplanet {
    public static function parseToolInfo(string $toolInfo) {
        $match = preg_match('/(Openplanet)\s([\d\.]+)\s\(([^,]+,\s[^\)]+)\)\s?\[?([^\]]*)/', $toolInfo, $matches, PREG_UNMATCHED_AS_NULL);

        if ($match == false)
            return false;

        $details = explode(", ", $matches[3]);
        $version = $matches[2];
        $game = $details[0];
        $branch = $details[1];
        $build = $details[2];
        $devMode = $matches[4] != null && $matches[4] == "DEVMODE";

        return new OpenplanetInfo($version, $game, $branch, $build, $devMode);
    }
}
