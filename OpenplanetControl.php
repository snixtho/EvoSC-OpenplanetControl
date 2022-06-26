<?php


namespace EvoSC\Modules\OpenplanetControl;

use EvoSC\Classes\Hook;
use EvoSC\Classes\Log;
use EvoSC\Classes\Module;
use EvoSC\Classes\Template;
use EvoSC\Interfaces\ModuleInterface;
use EvoSC\Models\Player;
use EvoSC\Classes\ManiaLinkEvent;
use EvoSC\Classes\Server;

class OpenplanetControl extends Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public static function start(string $mode, bool $isBoot = false)
    {
        Hook::add('PlayerConnect', [self::class, 'playerConnect']);
        ManiaLinkEvent::add("opcontrol.toolinfo", [self::class, 'onToolInfo']);
    }

    public static function playerConnect(Player $player) {
        Template::show($player, 'OpenplanetControl.detect-openplanet');
    }

    public static function onToolInfo(Player $player, string $rawInfo)
    {
        $info = urldecode($rawInfo);

        if (str_starts_with($info, "Openplanet")) {
            $msg = "Player " . $player->NickName . " (Login: " . $player->Login . ") is using Openplanet";
            Log::warning($msg);

            if (config("openplanet-control.auto-kick")) {
                Server::kick($player->Login, "Openplanet not allowed on this server.");
                warningMessage($player->NickName . " was kicked for joining with Openplanet")->sendAdmin();
            } else {
                warningMessage($msg)->sendAdmin();
            }
        }
    }
}