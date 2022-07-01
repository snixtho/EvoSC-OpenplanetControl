<?php


namespace EvoSC\Modules\OpenplanetControl;

use EvoSC\Classes\ChatCommand;
use EvoSC\Classes\Hook;
use EvoSC\Classes\Log;
use EvoSC\Classes\Module;
use EvoSC\Classes\Template;
use EvoSC\Interfaces\ModuleInterface;
use EvoSC\Models\Player;
use EvoSC\Classes\ManiaLinkEvent;
use EvoSC\Classes\Server;
use EvoSC\Classes\Timer;
use EvoSC\Controllers\ConfigController;
use EvoSC\Controllers\ModuleController;
use EvoSC\Models\AccessRight;
use EvoSC\Modules\OpenplanetControl\Lib\Openplanet;
use EvoSC\Modules\OpenplanetControl\Lib\OpenplanetInfo;
use PhpParser\Node\Expr\AssignOp\Plus;

class OpenplanetControl extends Module implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public static function start(string $mode, bool $isBoot = false)
    {
        Hook::add('PlayerConnect', [self::class, 'playerConnect']);
        ManiaLinkEvent::add('opcontrol.toolinfo', [self::class, 'onToolInfo']);
        ManiaLinkEvent::add('opcontrol.disconnect', [self::class, 'onDisconnect']);

        AccessRight::add('opcontrol_manage', 'Manage Openplanet Control module.');
        AccessRight::add('opcontrol_ignore', 'Ignore handling players with this permission.');

        ChatCommand::add('//opcontrol_enable', [self::class, 'cmdEnable'], 'Enable openplanet control.', 'opcontrol_manage');
        ChatCommand::add('//opcontrol_disable', [self::class, 'cmdDisable'], 'Disable openplanet control.', 'opcontrol_manage');
    }

    public static function playerConnect(Player $player) {
        if (config('opcontrol.enabled')) {
            Template::show($player, 'OpenplanetControl.detect-extratool');
        }
    }

    public static function onToolInfo(Player $player, string $rawInfo)
    {
        $opInfo = Openplanet::parseToolInfo(urldecode($rawInfo));

        if ($opInfo == false)
            return;

        if (config('opcontrol.devVersionOnly') && !$opInfo->devMode)
            return;
        
        // has openplanet
        $msg = "Player " . $player->NickName . " (Login: " . $player->Login . ") is using Openplanet";
        Log::warning($msg);

        if (config('opcontrol.warning.notifyAdmins') && !self::isAllowed($player)) {
            warningMessage($msg)->sendAdmin();
        }
        
        // handle player (if possible)
        self::warnPlayer($player, $opInfo);
        self::forceSpectator($player);
        self::scheduleKick($player, config('opcontrol.autoKick.delay'));
    }

    public static function onDisconnect(Player $player) {
        Server::kick($player->Login);
    }

    public static function forceSpectator(Player $player) {
        if (config('opcontrol.autoSpec.enabled') && !self::isAllowed($player)) {
            Server::forceSpectator($player->Login, 1);
            self::sendWarning('You were automatically forced to spectator.', $player);
        }
    }

    public static function scheduleKick(Player $player, int $delay=0) {
        if (config('opcontrol.autoKick.enabled') && !self::isAllowed($player)) {
            self::sendWarning('You will be kicked in ' . $delay . ' second(s) for using Openplanet.', $player);

            if ($delay > 0) {
                Timer::create('opcontrol.kick.' . $player->Login, function() use ($player) {
                    self::kickPlayer($player);
                }, $delay.'s');
            } else {
                self::kickPlayer($player);
            }
        }
    }

    public static function kickPlayer(Player $player) {
        Server::kick($player->Login, 'Openplanet is not allowed on this server.');

        if (config('opcontrol.warning.notifyAdmins')) {
            infoMessage('Player ' . $player->NickName . ' was kicked for using Openplanet.')->sendAdmin();
        }
    }

    public static function warnPlayer(Player $player, OpenplanetInfo $opInfo) {
        if (config('opcontrol.warning.showPlayerWarning') && !self::isAllowed($player)) {
            self::sendWarning('Openplanet ' . ($opInfo->devMode ? 'developer mode' : '') . ' detected!', $player);
            Template::show($player, 'OpenplanetControl.warning-window', compact('opInfo'));
        }
    }

    public static function isAllowed(Player $player) {
        $ignoreAdmins = config('opcontrol.whitelist.ignoreAdmins');

        if ($player->hasAccess('opcontrol_ignore'))
            return true;

        $whitelist = config('opcontrol.whitelist');

        if ($whitelist == null)
            return false;

        foreach ($whitelist as $login)
            if ($player->Login === $login)
                return true;

        return false;
    }

    public static function sendWarning(string $message, Player $player) {
        warningMessage('[Openplanet Control] ' . $message)->send($player);
    }

    public static function cmdEnable(Player $player) {
        ConfigController::saveConfig('opcontrol.enabled', true);
        successMessage('Openplanet Control is now $<$0f0enabled$>')->send($player);
    }

    public static function cmdDisable(Player $player) {
        ConfigController::saveConfig('opcontrol.enabled', false);
        successMessage('Openplanet Control is now $<$f00disabled$>')->send($player);
    }
}
