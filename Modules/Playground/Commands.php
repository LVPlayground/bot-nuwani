<?php
/**
 * Copyright (c) 2006-2013 Las Venturas Playground
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 */

namespace Playground;

use \ LVP;
use \ ModuleBase;
use \ Nuwani;
use \ Nuwani \ Bot;
use \ Nuwani \ BotManager;
use \ Playground;
use \ UserStatus;

class Commands {
    const MESSAGE_MAX_LENGTH = 450;

    public static function ProcessCommand(Bot $bot, $command, $parameters, $channel, $nickname, $userLevel) {
        switch ($command) {
            // -----------------------------------------------------------------
            // Commands available to everyone.
            // -----------------------------------------------------------------

            case 'msg':
                if (self::isEchoChannel($channel))
                    self::OnMessageCommand($bot, $parameters, $channel, $nickname, 'msg', 'msg');
                return true;

            case 'Players':
            case 'players':
                self::OnPlayersCommand($bot, $parameters, $channel, $nickname);
                return true;

            // -----------------------------------------------------------------
            // Commands available to voiced people.
            // -----------------------------------------------------------------

            case 'pm':
                if (self::isChannelOfType($channel, 'crew', 'vip', 'echo') && $userLevel >= UserStatus::IsVoiced)
                    self::OnPrivateMessageCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'getid':
                if ($userLevel >= UserStatus::IsVoiced)
                    self::OnGetIdMessageCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'getname':
                if ($userLevel >= UserStatus::IsVoiced)
                    self::OnGetNameMessageCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'vip':
                if (self::isChannelOfType($channel, 'echo', 'vip') && $userLevel >= UserStatus::IsVoiced)
                    self::OnMessageCommand($bot, $parameters, $channel, $nickname, 'vip', 'vipm');
                return true;

            // -----------------------------------------------------------------
            // Commands available to people with half-operator rights.
            // -----------------------------------------------------------------

            case 'say':
                if (self::isEchoChannel($channel) && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnMessageCommand($bot, $parameters, $channel, $nickname, 'say', 'say');
                return true;

            case 'kick':
                if (self::isChannelOfType($channel, 'echo', 'crew', 'management') && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnBanKickCommand($bot, $parameters, $channel, $nickname, 'kick');
                return true;

            case 'ban':
                if (self::isChannelOfType($channel, 'echo', 'crew', 'management') && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnBanKickCommand($bot, $parameters, $channel, $nickname, 'ban');
                return true;

            case 'jail':
                if (self::isEchoChannel($channel) && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnJailCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'unjail':
                if (self::isEchoChannel($channel) && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnSimplePlayerIdCommand($bot, $parameters, $channel, $nickname, 'unjail');
                return true;

            case 'mute':
                if (self::isChannelOfType($channel, 'echo', 'crew') && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnMuteCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'unmute':
                if (self::isChannelOfType($channel, 'echo', 'crew') && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnSimplePlayerIdCommand($bot, $parameters, $channel, $nickname, 'unmute');
                return true;

            case 'announce':
                if (self::isEchoChannel($channel) && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnMessageCommand($bot, $parameters, $channel, $nickname, $command, 'announce');
                return true;

            case 'admin':
                if (self::isChannelOfType($channel, 'echo', 'crew', 'management') && $userLevel >= UserStatus::IsHalfOperator)
                    self::OnMessageCommand($bot, $parameters, $channel, $nickname, 'admin', 'adminm');
                return true;

            case 'isbanned':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnIsBannedCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'banip':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnBanIpAddressCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'banserial':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnBanGpciCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'lastbans':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnLastBansCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'unban':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnUnbanCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'why':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnPlayerLogCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'addnote':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnAddNoteCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'crash':
                if (self::isDevelopmentChannel($channel))
                    self::OnCrashCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'createtestacc':
                if (self::isDevelopmentChannel($channel))
                    self::OnCreateTestAccountCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'clearbetabans':
                if (self::isDevelopmentChannel($channel))
                    self::OnClearBetaBansCommand($bot, $channel, $nickname);
                return true;

            case 'connect': // DEPRECATED: this is the original command, already known by a few people
            case 'connectbot':
            case 'reconnectbot':
                if ($userLevel >= UserStatus::IsHalfOperator)
                    self::OnReconnectBotCommand($bot, $parameters, $channel, $nickname);
                return true;


            // -----------------------------------------------------------------
            // Commands available to people with operator rights.
            // -----------------------------------------------------------------

            case 'givetempadmin':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnSimplePlayerIdCommand($bot, $parameters, $channel, $nickname, 'givetempadmin');
                return true;

            case 'taketempadmin':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnSimplePlayerIdCommand($bot, $parameters, $channel, $nickname, 'taketempadmin');
                return true;

            case 'getvalue':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnGetValueCommand($bot, $parameters, $channel, $nickname, $userLevel);
                return true;

            case 'supported':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnSupportedCommand($bot, $parameters, $channel, $nickname, $userLevel);
                return true;

            case 'setvalue':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnSetValueCommand($bot, $parameters, $channel, $nickname, $userLevel);
                return true;

            case 'changenickname':
            case 'changenick':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnChangeNicknameCommand($bot, $parameters, $channel, $nickname, /** management **/ true);
                return true;

            case 'nickhistory':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnNicknameHistoryCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'aliases':
                if ($userLevel >= UserStatus::IsOperator)
                    self::OnAliasesCommand($bot, $parameters, $channel, $nickname);
                return true;

            // -----------------------------------------------------------------
            // Commands available to people with protected rights.
            // -----------------------------------------------------------------

            case 'givevip':
                if (self::isManagementChannel($channel) && $userLevel >= UserStatus::IsProtected)
                    self::OnGiveVipCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'globalannouncement':
                if (self::isManagementChannel($channel) && $userLevel >= UserStatus::IsProtected)
                    self::OnGlobalAnnouncement($bot, $parameters, $channel, $nickname);
                return true;

            case 'changepassword':
            case 'changepass':
                if (self::isManagementChannel($channel) && $userLevel >= UserStatus::IsProtected)
                    self::OnChangePasswordCommand($bot, $parameters, $channel, $nickname, /** management **/ true);
                return true;

            case 'addalias':
                if ($userLevel >= UserStatus::IsProtected)
                    self::OnAddAliasCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'removealias':
                if ($userLevel >= UserStatus::IsProtected)
                    self::OnRemoveAliasCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'raw':
                if ($userLevel >= UserStatus::IsProtected)
                    self::OnRawGamemodeCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'rcon':
                if ($userLevel >= UserStatus::IsProtected)
                    self::OnRemoteCommand($bot, $parameters, $channel, $nickname);
                return true;

            case 'reloadformat':
                if ($userLevel >= UserStatus::IsProtected)
                    self::OnReloadFormatCommand($bot, $parameters, $channel, $nickname);
                return true;
        }

        return false;
    }

    public static function ProcessPrivateCommand(Bot $bot, $command, $parameters, $nickname) {
        switch ($command) {
            // -----------------------------------------------------------------
            // Commands available to people in private chat with the bot.
            // -----------------------------------------------------------------

            case 'changepassword':
            case 'changepass':
                self::OnChangePasswordCommand($bot, $parameters, $nickname, $nickname, /** management **/ false);
                return true;

            case 'changenickname':
            case 'changenick':
                self::OnChangeNicknameCommand($bot, $parameters, $nickname, $nickname, /** management **/ false);
                return true;
        }

        return false;
    }

    private static function isChannelOfType($channel, $types) {
        $types = func_get_args();
        array_shift($types);

        $result = false;
        foreach ($types as $type) {
            switch ($type) {
                case 'crew':
                    $result |= self::isCrewChannel($channel);
                    break;

                case 'dev':
                    $result |= self::isDevelopmentChannel($channel);
                    break;

                case 'echo':
                    $result |= self::isEchoChannel($channel);
                    break;

                case 'management':
                    $result |= self::isManagementChannel($channel);
                    break;

                case 'vip':
                    $result |= self::isVipChannel($channel);
                    break;
            }
        }

        return $result;
    }

    private static function isCrewChannel($channel) {
        return TargetChannel::isCrewChannel($channel);
    }

    private static function isDevelopmentChannel($channel) {
        return TargetChannel::isDevelopmentChannel($channel);
    }

    private static function isEchoChannel($channel) {
        return TargetChannel::isPublicEchoChannel($channel);
    }

    private static function isManagementChannel($channel) {
        return TargetChannel::isManagementChannel($channel);
    }

    private static function isVipChannel($channel) {
        return TargetChannel::isVipChannel($channel);
    }

    // !msg [message], !vip [message], !say [message]
    private static function OnMessageCommand(Bot $bot, $parameters, $channel, $nickname, $ircCommand, $ingameCommand) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!' . $ircCommand . ' [message]');
            return;
        }

        $message = implode(' ', $parameters);
        if (strlen($message) > 128)
            $message = substr($message, 0, 125) . '...';

        Playground::sendIngameCommand($ingameCommand . ' ' . $nickname . ' ' . $message);
    }

    // !pm [target] [message]
    private static function OnPrivateMessageCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) < 2 || !is_numeric($parameters[0])) {
            CommandHelper::usageMessage($bot, $channel, '!pm [playerId] [message]');
            return;
        }

        $message = implode(' ', array_slice($parameters, 1));
        if (strlen($message) > 128)
            $message = substr($message, 0, 125) . '...';

        Playground::sendIngameCommand('pm ' . $nickname . ' ' . $parameters[0] . ' ' . $message);
    }

    // !players [nickname]?
    private static function OnPlayersCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            $playerList = LVP::onlinePlayers();
            $message = '7Online players (' . count($playerList) . '): ';

            if (count($playerList) == 0)
                $message .= '14There are no players on the server.';
            else {
                $playerNames = array();
                foreach($playerList as $player) {
                    $playerNames[] = self::colorizeNickname($player);
                }

                $message .= implode(', ', $playerNames);
            }

            // 512 (max IRC message length) - 2 (\r\n) - 50 (max channel name) - 10 (PRIVMSG part + safety) = 450
            $wrapped = wordwrap($message, 450);
            foreach (explode("\n", $wrapped) as $message) {
                $bot->send('PRIVMSG ' . $channel . ' :' . $message);
            }
            return;
        }

        $playerInfo = LVP::findProfileByNickname($parameters[0]);
        if ($playerInfo->exists() === false) {
            $bot->send('PRIVMSG ' . $channel . ' :The requested player is not registered with Las Venturas Playground.');
            return;
        }

        $message = $playerInfo['username'];
        if ($playerInfo['online_time'] == 0) {
            $message .= ' has not been online yet.';
        } else {
            $onlineHours = floor($playerInfo['online_time'] / 3600);
            $onlineMinutes = floor(($playerInfo['online_time'] - $onlineHours * 3600) / 60);

            if ($onlineHours >= 1)
                $message .= sprintf(' has been online for %d hour%s and %d minute%s.', $onlineHours, $onlineHours > 1 ? 's' : '', $onlineMinutes, $onlineMinutes > 1 ? 's' : '');
            else
                $message .= sprintf(' has been online for %d minute%s.', $onlineMinutes, $onlineMinutes > 1 ? 's' : '');
        }

        $ratio = $playerInfo['death_count'] == 0 ? 1.0 : ($playerInfo['kill_count'] / $playerInfo['death_count']);
        $levelName = strtolower($playerInfo['level']);

        if ($levelName == 'management')
            $levelName = 'management member';

        $message .= sprintf(' The player has killed %d people so far, and has died %d times themself, which makes a ratio of %.2f.', $playerInfo['kill_count'], $playerInfo['death_count'], $ratio);
        $message .= sprintf(' %s is a%s %s on Las Venturas Playground, and was last ingame at %s.', $playerInfo['username'], $levelName == 'administrator' ? 'n' : '', $levelName, $playerInfo['last_seen']);

        if ($playerInfo['is_vip'])
            $message .= ' This player is a ' . ModuleBase::COLOUR_BLUE . 'VIP' . ModuleBase::CLEAR . '.';

        $message .= ' - ' . ModuleBase::UNDERLINE . 'http://profile.sa-mp.nl/' . urlencode($playerInfo['username']);

        $bot->send('PRIVMSG ' . $channel . ' :' . $message);
    }

    private static function colorizeNickname($player) {
        $levelColor = '';

        // This isn't a registered player
        if ($player['account'] === false) {
            $levelColor = ModuleBase::COLOUR_DRAKGREY;
        }
        else {
            switch ($player['account']['level']) {
                case 'Player': {
                    // Order is important. Since it's a player first check for developer-status.
                    // After that for VIP-status.
                    if ($player['account']['is_developer'] == 1) {
                        $levelColor = ModuleBase::COLOUR_DARKBLUE;
                    }
                    elseif ($player['account']['is_vip'] == 1) {
                        $levelColor = ModuleBase::COLOUR_BLUE;
                    }
                    break;
                }
                case 'Administrator':
                    $levelColor = ModuleBase::COLOUR_RED;
                    break;
                case 'Management':
                    $levelColor = ModuleBase::COLOUR_DARKGREEN;
                    break;
            }
        }

        return $levelColor . $player['nickname'] . (strlen($levelColor) > 0 ? ModuleBase::CLEAR : '');
    }

    // !getid [nickname]
    private static function OnGetIdMessageCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!getid [nickname]');
            return;
        }

        $onlinePlayers = LVP::findOnlinePlayersByPartialNickname($parameters[0]);
        if (count($onlinePlayers) == 0) {
            CommandHelper::infoMessage($bot, $channel, 'No online players found matching "' . $parameters[0] . '".');
            return;
        }

        $list = '';
        foreach ($onlinePlayers as $playerId => $playerInfo) {
            $list .= self::colorizeNickname($playerInfo) . ' (ID:' . $playerId . '), ';
        }

        $maxListLength = self::MESSAGE_MAX_LENGTH - 40;
        if (strlen($list) > $maxListLength) {
            $list = wordwrap($list, $maxListLength);
            list($list) = explode("\n", $list);
            // Find the last comma and cut the message there.
            $list = substr($list, 0, strrpos($list, ',')) . ', ...';
        } else {
            $list = substr($list, 0, -2);
        }

        CommandHelper::infoMessage($bot, $channel, 'Online players found (' . count($onlinePlayers) . '): ' . $list);
    }

    // !getname [playerId]
    private static function OnGetNameMessageCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0 || !ctype_digit($parameters[0])) {
            CommandHelper::usageMessage($bot, $channel, '!getname [playerId]');
            return;
        }

        $onlinePlayer = LVP::getOnlinePlayerById($parameters[0]);
        if ($onlinePlayer == null) {
            CommandHelper::infoMessage($bot, $channel, 'There is no player with ID ' . $parameters[0] . ' online at the moment.');
            return;
        }

        CommandHelper::infoMessage($bot, $channel, 'Player with ID ' . $parameters[0] . ' has nickname "' . self::colorizeNickname($onlinePlayer) . '".');
    }

    // !kick [playerId] [reason], !ban [playerId] [reason]
    private static function OnBanKickCommand(Bot $bot, $parameters, $channel, $nickname, $type) {
        if (count($parameters) < 2 || !is_numeric($parameters[0])) {
            CommandHelper::usageMessage($bot, $channel, '!' . $type . ' [playerId] [reason]');
            return;
        }

        $playerId = array_shift($parameters);
        $message = implode(' ', $parameters);

        if (strlen($message) < 5) {
            CommandHelper::errorMessage($bot, $channel, 'The reason for this ' . $type . ' must be longer than five characters.');
            return;
        }

        Playground::sendIngameCommand($type . ' ' . $nickname . ' ' . $playerId . ' ' . $message);
    }

    // !lastbans
    private static function OnLastBansCommand(Bot $bot, $parameters, $channel, $nickname) {
        // Use default limit for now.
        $lastBans = BanManager::GetRecentBans();

        $message = 'Last banned players: ';
        foreach ($lastBans as $ban) {
            $message .= $ban['player'] . ' 3(' . $ban['ip'] . '), ';
        }

        CommandHelper::infoMessage($bot, $channel, substr($message, 0, -2));
    }

    // !unban [ipAddress/serial] [note]?
    private static function OnUnbanCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!unban [ipAddress/serial] [note]?');
            return;
        }

        $banValue = array_shift($parameters);
        $note = implode(' ', $parameters);

        if ($note == '') {
            $note = 'Unbanned';
        }

        $isIpSearch = true;
        if (filter_var($banValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || $banValue == '127.0.0.1') {
            // Welp, perhaps a serial(hash)?
            if (!is_numeric($banValue) && strlen($banValue) < 10) {
                CommandHelper::errorMessage($bot, $channel, 'Invalid serial given.');
                return;
            }
            else
                $isIpSearch = false;

            if (!$isIpSearch) {
                CommandHelper::errorMessage($bot, $channel, 'Invalid IP address given.');
                return;
            }
        }

        if ($isIpSearch) {
            $unbanType = 'IP address';
            $existingBan = BanManager::UnbanIp($banValue, $nickname, $note);
        } else {
            $unbanType = 'serial';
            $existingBan = BanManager::UnbanGpci($banValue, $nickname, $note);
        }

        if ($existingBan != null) {
            Playground::sendIngameCommand('reloadbans');

            CommandHelper::infoMessage($bot, $channel, 'The ' . $unbanType . ' ' . $banValue . ' (nickname: ' . $existingBan['player'] . ') has been unbanned.');
        } else {
            CommandHelper::infoMessage($bot, $channel, 'The ' . $unbanType . ' ' . $banValue . ' is currently not banned.');
        }
    }

    // !isbanned [ipAddress/serial]
    private static function OnIsBannedCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!isbanned [ipAddress/serial]');
            return;
        }

        $banValue = array_shift($parameters);

        $isIpSearch = true;
        if (filter_var($banValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || $banValue == '127.0.0.1') {
            // Welp, perhaps a serial(hash)?
            if (!is_numeric($banValue) && strlen($banValue) < 10) {
                CommandHelper::errorMessage($bot, $channel, 'Invalid serial given.');
                return;
            }
            else
				$isIpSearch = false;

            if (!$isIpSearch) {
                CommandHelper::errorMessage($bot, $channel, 'Invalid IP address given.');
                return;
            }
        }

        $result = BanManager::FindBannedPlayer($banValue);
        $ipOrSerialHash = $isIpSearch ? 'IP address' : 'serial hash';
        if ($result === false) {
            CommandHelper::infoMessage($bot, $channel, 'The ' . $ipOrSerialHash . ' ' . $banValue . ' is currently not banned.');
        } else {
            $bannedValue = $isIpSearch ? $result['ip'] : $result['gpci'];
            CommandHelper::infoMessage($bot, $channel, 'The ' . $ipOrSerialHash . ' ' . $banValue . ' is currently banned: ' . $result['player'] . ' (' . $bannedValue . '), reason: ' . $result['message']);
        }
    }

    // !banip [ipAddress] [playerName] [duration] [reason]
    private static function OnBanIpAddressCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) < 4) {
            CommandHelper::usageMessage($bot, $channel, '!banip [ipAddress] [playerName] [duration] [reason]');
            return;
        }

        $ipAddress = array_shift($parameters);
        $playerName = array_shift($parameters);
        $duration = array_shift($parameters);
        $reason = implode(' ', $parameters);

        if (!self::AreGivenBanParametersValid($bot, $channel, $ipAddress, $playerName, $duration, $reason))
            return;

        BanManager::BanIp($ipAddress, $playerName, $nickname, $duration, $reason);
        Playground::sendIngameCommand('reloadbans');

        CommandHelper::infoMessage($bot, $channel, 'The IP address ' . $ipAddress . ' (' . $playerName . ') has been banned, for ' . $duration . ' day(s).');
    }

    // !banserial [serial] [playerName] [duration] [reason]
    private static function OnBanGpciCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) < 4) {
            CommandHelper::usageMessage($bot, $channel, '!banserial [serial] [playerName] [duration] [reason]');
            return;
        }

        $gpci = array_shift($parameters);
        $playerName = array_shift($parameters);
        $duration = array_shift($parameters);
        $reason = implode(' ', $parameters);

        if (!self::AreGivenBanParametersValid($bot, $channel, $gpci, $playerName, $duration, $reason))
            return;

        BanManager::BanGpci($gpci, $playerName, $nickname, $duration, $reason);
        Playground::sendIngameCommand('reloadbans');

        CommandHelper::infoMessage($bot, $channel, 'The serial ' . $gpci . ' (' . $playerName . ') has been banned, for ' . $duration . ' day(s).');
    }

    // !why [playerName]
    private static function OnPlayerLogCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!why [playerName]');
            return;
        }

        $nickname = $parameters[0];

        // Default offsets for now.
        $playerLog = BanManager::GetPlayerLog($nickname);
        if ($playerLog === false) {
            CommandHelper::channelMessage($bot, $channel, '04*** No items found for player ' . $nickname . '.');
            return;
        }

        $headerMessage = '04*** Player log for ' . $nickname . ' (' . $playerLog['total_results'] . ' items)';
        if ($playerLog['total_results'] > 5) {
            $headerMessage .= ' - Complete log: http://sa-mp.nl/players/banlog/1/date/desc/nickname/' . urlencode($nickname) . '.html';
        }

        CommandHelper::channelMessage($bot, $channel, $headerMessage);
        foreach ($playerLog as $key => $logEntry) {
            if ($key === 'total_results')
                continue;

            $banDuration = round($logEntry['duration'] / 86400) /* seconds in a day */ > 0 ? round($logEntry['duration'] / 86400) : 0;
            CommandHelper::channelMessage($bot, $channel, '4[' . $logEntry['date'] . '] 3(' . $logEntry['type']
                . ' by ' . $logEntry['admin'] . '): ' . trim($logEntry['message'])
                . ($banDuration > 0 ? ' 5(Duration: ' .  $banDuration . ' day(s))' : '')
                . (strlen($logEntry['ip']) > 0 ? ' 14(IP: ' . $logEntry['ip'] . ')' : ''));
        }
    }

    // !addnote [playerName] [note]
    private static function OnAddNoteCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) < 2) {
            CommandHelper::usageMessage($bot, $channel, '!addnote [playerName] [note]');
            return;
        }

        $playerName = array_shift($parameters);
        $note = implode(' ', $parameters);

        if (strlen($playerName) < 3) {
            CommandHelper::errorMessage($bot, $channel, 'The player name needs to be at least 3 characters.');
            return;
        }

        if (strlen($note) < 5) {
            CommandHelper::errorMessage($bot, $channel, 'The note needs to be at least 5 characters.');
            return;
        }

        BanManager::AddEntryForPlayer($playerName, $nickname, BanManager::NoteEntry, $note);
        CommandHelper::infoMessage($bot, $channel, 'The note for ' . $playerName . ' has been added.');
    }

    // !jail [playerId] [durationMinutes]?
    private static function OnJailCommand(Bot $bot, $parameters, $channel, $nickname) {
        $playerId = -1;
        $durationMinutes = 2;

        if (count($parameters) >= 2 && is_numeric($parameters[1]))
            $durationMinutes = (int) $parameters[1];
        if (count($parameters) >= 1 && is_numeric($parameters[0]))
            $playerId = (int) $parameters[0];

        if ($playerId == -1 || $playerId < 0 || $playerId > 500 || $durationMinutes < 1 || $durationMinutes > 60) {
            CommandHelper::usageMessage($bot, $channel, '!jail [playerId] [durationMinutes]?');
            return;
        }

        Playground::sendIngameCommand('jail ' . $nickname . ' ' . $playerId . ' ' . $durationMinutes);
    }

    // !mute [playerId] [durationMinutes]?
    private static function OnMuteCommand(Bot $bot, $parameters, $channel, $nickname) {
        $playerId = -1;
        $durationMinutes = -1;

        if (count($parameters) >= 2 && is_numeric($parameters[1]))
            $durationMinutes = (int) $parameters[1];

        if (count($parameters) >= 1 && is_numeric($parameters[0]))
            $playerId = (int) $parameters[0];


        if ($playerId < 0 || $playerId > 500 || $durationMinutes < -1 || $durationMinutes > 60) {
            CommandHelper::usageMessage($bot, $channel, '!mute [playerId] [durationMinutes]?');
            return;
        }

        Playground::sendIngameCommand('mute ' . $nickname . ' ' . $playerId . ' ' . $durationMinutes);
    }

    // !crash [address]
    private static function OnCrashCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0)
            CommandHelper::usageMessage($bot, $channel, '!crash [address]');
        else {
            $address = trim(preg_replace('/0x([0]{0,99})/', '', $parameters[0]));
            if (strlen($address) != 6)
                CommandHelper::errorMessage($bot, $channel, 'Address must be in HEX.');
            else {
                $knownCrashes = file('Data/crashes.txt');
                $found = false;
                foreach ($knownCrashes as $crash) {
                    if (substr($crash, 0, 6) == strtoupper($address)) {
                        CommandHelper::infoMessage($bot, $channel, trim(substr($crash, 9)));
                        $found = true;
                    }
                }

                if (!$found)
                    CommandHelper::errorMessage($bot, $channel, 'No crash information found.');
            }
        }
    }

    // Creates a new connection to the database of the test server and returns the
    // new MySQLi instance. No verification will be done here.
    private static function TestDatabaseConnection() {
        $config = Nuwani\Configuration::getInstance()->get('Playground');
        $credentials = $config['database']['developer'];

        return new \MySQLi($credentials['hostname'], $credentials['username'], $credentials['password'], $credentials['database']);
    }

    // !createtestacc [username] [password] [level]
    private static function OnCreateTestAccountCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) < 3) {
            CommandHelper::usageMessage($bot, $channel, '!createtestacc [username] [password] [level] [isdev=0] [isvip=0] [isvidmod=0]');
            return;
        }

        static $validLevels = array(
            'Player', 'Administrator', 'Management'
        );
        static $validBoolean = array(
            '0', '1'
        );

        $username = $parameters[0];
        $password = $parameters[1];
        $level = $parameters[2];
        $isDev = isset($parameters[3]) && in_array($parameters[3], $validBoolean) ? $parameters[3] : 0;
        $isVip = isset($parameters[4]) && in_array($parameters[4], $validBoolean) ? $parameters[4] : 0;
        $isVipMod = isset($parameters[5]) && in_array($parameters[5], $validBoolean) ? $parameters[5] : 0;

        if (!in_array($level, $validLevels)) {
            CommandHelper::errorMessage($bot, $channel, 'Level must be one of: ' . implode(', ', $validLevels));
            return;
        }

        // Separately connect to the test-server database.
        $db = self::TestDatabaseConnection();

        $config = Nuwani\Configuration::getInstance()->get('Playground');
        $salt = $config['password_key']['developer'];

        // Check whether username is in use.
        $statement = $db->prepare(
            'SELECT user_id
            FROM users_nickname
            WHERE LOWER(nickname) = ?');
        $lowerUsername = strtolower($username);
        $statement->bind_param('s', $lowerUsername);
        $statement->execute();
        $statement->store_result();
        if ($statement->num_rows > 0) {
            CommandHelper::errorMessage($bot, $channel, 'The nickname ' . $username . ' is already taken.');
            return;
        }
        $statement->close();

        $randomCode = mt_rand(100000000, 999999999);
        $hashedPassword = sha1($randomCode . $password . $salt);

        // Create new account.
        $statement = $db->prepare(
            'INSERT INTO users
                (username, password, password_salt, validated, level, is_developer, is_vip, is_vip_mod)
            VALUES
                (?, ?, ?, 1, ?, ?, ?, ?)');
        $statement->bind_param('ssssiii', $username, $hashedPassword, $randomCode, $level, $isDev, $isVip, $isVipMod);
        $statement->execute();

        if ($statement->affected_rows == 0) {
            CommandHelper::errorMessage('Your test account could not be created. If this problems persists, please create a ticket in Trac.');
            return;
        }

        $userId = $statement->insert_id;
        $db->query('INSERT INTO users_mutable (user_id) VALUES (' . $userId . ')');
        $db->query('INSERT INTO users_nickname (user_id, nickname) VALUES (' . $userId . ', "' .
            $db->real_escape_string($username) . '")');

        $db->close();

        CommandHelper::successMessage($bot, $channel, 'Your test account with nickname ' . $username . ' has been created.');
    }

    // !clearbetabans
    private static function OnClearBetaBansCommand(Bot $bot, $channel, $nickname) {
        $db = self::TestDatabaseConnection();

        // Verify that we're not accidentially modifying the lvp_mainserver database. This should be seen
        // as an ASSERT(), because clearing all bans in the real database would be very bad.
        $currentDatabaseQuery = $db->query('SELECT DATABASE()');
        if ($currentDatabaseQuery === false || $currentDatabaseQuery->num_rows != 1)
            return; // could not select the database.

        $currentDatabase = $currentDatabaseQuery->mysqli_fetch_row();
        if ($currentDatabase[0] == 'lvp_mainserver')
            return; // we're in lvp_mainserver. ouch.

        // By this time we're certain that we're not in the real database. Truncate the table.
        $db->query('TRUNCATE bans');

        // And close the connection because we don't own it.
        $db->close();
    }

    // !reconnectbot [botName]
    private static function OnReconnectBotCommand(Bot $bot, $parameters, $channel, $nickname) {
        $availableNicknames = array();
        $allBotsGroup = BotManager::getInstance()->getBotList();
        foreach ($allBotsGroup as $groupBot) {
            $availableNicknames[] = $groupBot['Nickname'];
        }

        if (count($parameters) != 1) {
            CommandHelper::usageMessage($bot, $channel, '!reconnectbot [botName] ' . ModuleBase::COLOUR_DARKGREY . ' -- Available bots: ' . implode(', ', $availableNicknames));
            return;
        }

        $botName = $parameters[0];
        if (in_array($botName, $availableNicknames)) {
            $foundBot = BotManager::getInstance()->offsetGet($botName);
            if ($foundBot !== false) {
                $foundBot->connect();
                CommandHelper::successMessage($bot, $channel, 'Bot ' . $botName . ' is reconnecting. Hold on!');
                return;
            }
        }

        CommandHelper::errorMessage($bot, $channel, 'There is no bot called ' . $botName . '. Use !reconnectbot without a name for a list of bot names.');
    }

    // !{give,take}tempadmin [playerId], !unjail, !unmute
    private static function OnSimplePlayerIdCommand(Bot $bot, $parameters, $channel, $nickname, $command) {
        if (count($parameters) == 0 || !is_numeric($parameters[0])) {
            CommandHelper::usageMessage($bot, $channel, '!' . $command . ' [playerId]');
            return;
        }

        Playground::sendIngameCommand($command . ' ' . $nickname . ' ' . $parameters[0]);
    }

    // !getvalue [playerName] [field]
    private static function OnGetValueCommand(Bot $bot, $parameters, $channel, $nickname, $userLevel) {
        if (count($parameters) != 2) {
            CommandHelper::usageMessage($bot, $channel, '!getvalue [playerName] [field]');
            return;
        }

        $player = LVP::findProfileByNickname($parameters[0]);
        $field = strtolower($parameters[1]);

        if ($player->exists() == false) {
            $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The player "' . $parameters[0] . '" does not exist.');
            return;
        }

        $supportedFields = self::GetAvailableTableColumnsForLevel($userLevel);
        if (isset($supportedFields[$field]) === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The field "' . $field . '" does not exist, or you may not access it. See !supported.');
            return;
        }

        $value = $player->offsetGet($field);
        if ($supportedFields[$field] == 'ip')
            $value = long2ip($value);

        $bot->send('PRIVMSG ' . $channel . ' :5Value of "' . $field . '": ' . $value);
    }

    // !setvalue [playerName] [field] [value]
    private static function OnSetValueCommand(Bot $bot, $parameters, $channel, $nickname, $userLevel) {
        if (count($parameters) < 3) {
            CommandHelper::usageMessage($bot, $channel, '!setvalue [playerName] [field] [value]');
            return;
        }

        $player = LVP::findProfileByNickname($parameters[0]);
        $field = strtolower($parameters[1]);
        $value = implode(' ', array_slice($parameters, 2));

        if ($player->exists() == false) {
            $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The player "' . $parameters[0] . '" does not exist.');
            return;
        }

        $supportedFields = self::GetAvailableTableColumnsForLevel($userLevel);
        if (isset($supportedFields[$field]) === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The field "' . $field . '" does not exist, or you may not access it. See !supported.');
            return;
        }

        $formattedValue = null;
        switch ($supportedFields[$field]) {
            case 'numeric':
                // TODO: This really should support floats too.
                if (strtolower(substr($value, 0, 2)) == '0x' && ctype_xdigit(substr($value, 2))) {
                    $formattedValue = hexdec($value);
                } else if (preg_match('/^-?[0-9]+$/', $value) != 0) {
                    $formattedValue = (int) $value;
                } else {
                    $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The field "' . $field . '" must be set to a number. Hexadecimal numbers are supported using this format: 0xDEADBEEF.');
                    return;
                }
                break;

            case 'date':
                if (strtotime($value) === false) {
                    $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The field "' . $field . '" must be set to a date (YY-MM-DD HH:II:SS).');
                    return;
                }

                $formattedValue = date('Y-m-d H:i:s', strtotime($value));
                break;

            case 'ip':
                if (long2ip(ip2long($value)) != $value) {
                    $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The field "' . $field . '" must be set to an IP address.');
                    return;
                }

                $formattedValue = ip2long($value);
                break;

            default:
                if (is_array($supportedFields[$field])) {
                    if (in_array(strtolower($value), $supportedFields[$field]) === false) {
                        $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The field "' . $field . '" must be one of [' . implode(', ', $supportedFields[$field]) . '].');
                        return;
                    }

                    $formattedValue = $value;
                    break;
                }

                $bot->send('PRIVMSG ' . $channel . ' :04*** Error: The field "' . $field . '" has an unknown formatting method: ' . $supportedFields[$field]);
                break;
        }

        $player->offsetSet($field, $formattedValue);
        $player->save();

        $bot->send('PRIVMSG ' . $channel . ' :3The profile has been updated.');
    }

    // !supported
    private static function OnSupportedCommand(Bot $bot, $parameters, $channel, $nickname, $userLevel) {
        $supported = self::GetAvailableTableColumnsForLevel($userLevel);
        if (count($supported) == 0) {
            $bot->send('PRIVMSG ' . $channel . ' :04*** Error: Unable to get the supported columns.');
            return;
        }

        ksort($supported);
        foreach (array_chunk(array_keys($supported), 25) as $columns)
            $bot->send('PRIVMSG ' . $channel . ' :5Supported fields: ' . implode(', ', $columns));
    }

    // Utility function for getting the columns available in the users and users_mutable table.
    private static function GetAvailableTableColumnsForLevel($level) {
        $database = \ Playground \ Database::instance();
        $columns = array();

        foreach (array('users', 'users_mutable') as $table) {
            $query = $database->query('SHOW COLUMNS FROM ' . $table);
            if ($query === false || $query->num_rows == 0)
                continue;

            while ($row = $query->fetch_assoc()) {
                if (self::IsColumnImmutable($row['Field']))
                    continue; // this field may not be updated through Nuwani.

                if (self::IsColumnDeprecated($row['Field']))
                    continue; // this field is no longer supported.

                if ($level < UserStatus::IsProtected && self::IsColumnLimitedToProtectedChannelOperators($row['Field']))
                    continue; // this field may only be updated by Management members.

                $columns[$row['Field']] = self::DetermineColumnType($row['Field'], $row['Type']);
            }
        }

        return $columns;
    }

    // Utility function to determine whether a certain field is only writable for the Management.
    private static function IsColumnLimitedToProtectedChannelOperators($column) {
        return $column == 'last_ip' ||
               $column == 'level' ||
               $column == 'validated';
    }

    // Utility function to determine whether a certain field is no longer supported by the gamemode.
    private static function IsColumnDeprecated($column) {
        return $column == 'clock' ||
               $column == 'clock_tz' ||
               $column == 'color' ||
               $column == 'money_bank_limit' ||
               $column == 'message_flags' ||
               $column == 'platinum_account' ||
               $column == 'platinum_earnings' ||
               $column == 'plus_points' ||
               $column == 'pro_account' ||
               $column == 'is_vip' ||
               $column == 'is_vip_mod';
    }

    // Utility function to determine whether a certain field cannot be changed at all.
    private static function IsColumnImmutable($column) {
        return $column == 'password' ||
               $column == 'password_salt' ||
               $column == 'settings' ||
               $column == 'updated' ||
               $column == 'user_id' ||
               $column == 'username';
    }

    // Utility function to determine what the type of data is expected for this column. There are four
    // possible return types, a string [numeric, date, ip, string] or an array with enum values.
    private static function DetermineColumnType($column, $type) {
        $type = strtolower($type);
        if ($column == 'last_ip')
            return 'ip';

        if (substr($type, 0, 3) == 'int' ||
            substr($type, 0, 7) == 'tinyint' ||
            substr($type, 0, 8) == 'smallint' ||
            substr($type, 0, 9) == 'mediumint' ||
            substr($type, 0, 6) == 'bigint' ||
            substr($type, 0, 5) == 'float' ||
            substr($type, 0, 6) == 'double')
            return 'numeric';

        if (substr($type, 0, 4) == 'date' ||
            substr($type, 0, 8) == 'datetime' ||
            substr($type, 0, 9) == 'timestamp')
            return 'date';

        if (substr($type, 0, 4) == 'char' ||
            substr($type, 0, 7) == 'varchar')
            return 'string';

        if (substr($type, 0, 4) == 'enum')
            return explode('\',\'', substr($type, 6, -2));

        return $type;
    }

    // !raw [command]
    private static function OnRawGamemodeCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!raw [command]');
            return;
        }

        Playground::sendIngameCommand(implode(' ', $parameters));
        CommandHelper::successMessage($bot, $channel, 'The raw gamemode command has been sent to the server.');
    }

    // !rcon [command]
    private static function OnRemoteCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!rcon [command]');
            return;
        }

        LVP::sendRemoteCommand(implode(' ', $parameters));
        CommandHelper::successMessage($bot, $channel, 'The rcon command has been sent to the server.');
    }

    // !reloadformat
    private static function OnReloadFormatCommand(Bot $bot, $parameters, $channel, $nickname) {
        MessageFormatter::Reload();
        $bot->send('PRIVMSG ' . $channel . ' :The message format syntax has been reloaded.');
    }

    // !nickhistory [nickname]
    private static function OnNicknameHistoryCommand($bot, $parameters, $channel, $nickname) {
        if (count($parameters) != 1) {
            CommandHelper::usageMessage($bot, $channel, '!nickhistory [nickname]');
            return;
        }

        $player = LVP::findProfileByNickname($parameters[0]);
        if ($player->exists() == false) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: No player with the given nickname could be found.');
            return;
        }

        $nicknames = array();
        foreach ($player->previousUsernames() as $nicknameData)
            $nicknames[$nicknameData['nickname']] = 1;

        $message = '07Previous nicknames: ';

        asort($nicknames);
        if (count($nicknames) > 0)
            $message .= implode(', ', array_keys($nicknames));
        else
            $message .= '15No previous names could be found.';

        $bot->send('PRIVMSG ' . $channel . ' :' . $message);
    }

    // Utility function to to share code between changing nickname and password commands.
    private static function SelectPrivilegedPlayerFromCommand($bot, $parameters, $channel, $nickname, $isManagement, $command, &$value) {
        $player = false;
        if ($isManagement == true) {
            // It's a management member forcefully changing the nickname.
            if (count($parameters) != 2) {
                CommandHelper::usageMessage($bot, $channel, '!change' . $command . ' [nickname] [new ' . $command . ']');
                return false;
            }

            $player = LVP::findProfileByNickname($parameters[0]);
            $value = $parameters[1];

            if ($player->exists() == false) {
                $bot->send('PRIVMSG ' . $channel . ' :04Error: No player with the given nickname could be found.');
                return false;
            }
        } else {
            // It's a player trying to change their own nickname.
            if (count($parameters) != 3) {
                CommandHelper::usageMessage($bot, $channel, '!change' . $command . ' [nickname] [password] [new ' . $command . ']');
                return false;
            }

            $player = LVP::findProfileByNickname($parameters[0]);
            $value = $parameters[2];

            if ($player->exists() == false || $player->validatePassword($parameters[1]) === false) {
                $bot->send('PRIVMSG ' . $channel . ' :04Error: Either the given nickname doesn\'t exist, or the entered password is incorrect.');
                foreach (TargetChannel::crewAnnouncementChannels() as $announceChannel)
                    $bot->send('PRIVMSG ' . $announceChannel . ' :04*** ' . $nickname . ' tried to change the ' . $command . ' of "' . $parameters[0] . '", but entered wrong information.');

                return false;
            }
        }

        return $player;
    }

    // Management: !changenick Nickname NewNickname
    // Private: !changenick Nickname Password NewNickname
    private static function OnChangeNicknameCommand($bot, $parameters, $channel, $nickname, $isManagement) {
        $player = self::SelectPrivilegedPlayerFromCommand($bot, $parameters, $channel, $nickname, $isManagement, 'nickname', $newNickname);
        $currentNickname = isset($parameters[0]) ? $parameters[0] : '';

        if ($player === false || $player->exists() === false)
            return; // this case occurs when the user enters wrong information.

        if (self::IsValidNickname($newNickname) === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: The new nickname is not valid. It must be between 3 and 23 characters and can only contain: a-z, 0-9, [, ], (, ), ., $, =, @');
            return;
        }

        $newNicknamePlayer = LVP::findProfileByNickname($newNickname);
        if ($newNicknamePlayer->exists() === true) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: A user with the new nickname already exists.');
            return;
        }

        if ($isManagement == false) {
            // This method can return false, but thanks to prior checks and PHP's loose typedness, we can just go ahead.
            $lastNicknameChange = $player->lastNicknameChange();
            if ($lastNicknameChange + (LVP::DaysBetweenNicknameChanges * 86400) > time()) {
                $bot->send('PRIVMSG ' . $channel . ' :04Error: You need to wait at least ' . LVP::DaysBetweenNicknameChanges . ' days between nickname changes. Your last nickname change was ' . date('Y-m-d H:i:s', $lastNicknameChange) . '.');
                return;
            }
        }

        if ($player->isOnline()) {
            CommandHelper::errorMessage($bot, $channel, 'You are currently ingame. Unfortunately, it is not possible to change your nickname while you are ingame. Please quit the game and try again.');
            return;
        }

        $player->setUsername($newNickname);
        $bot->send('PRIVMSG ' . $channel . ' :03Success: The nickname has been updated to ' . $newNickname);

        if ($isManagement === true)
            return; // no need to announce for management members.

        foreach (TargetChannel::crewAnnouncementChannels() as $announceChannel)
            $bot->send('PRIVMSG ' . $announceChannel . ' :04*** ' . $nickname . ' has changed the username of "' . $currentNickname . '" to "' . $newNickname . '".');
    }

    // !givevip [nickname]
    private static function OnGiveVipCommand(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!givevip [nickname]');
            return;
        }

        $player = LVP::findProfileByNickname($parameters[0]);
        if ($player->exists() === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: No player with this nickname could be found.');
            return;
        }

        $player->offsetSet('is_vip', 1);
        $player->offsetSet('is_vip_mod', 1);
        $player->save();

        CommandHelper::successMessage($bot, $channel, 'Player ' . $parameters[0] . ' is now a VIP.');
    }

    // !globalannouncement [message]
    private static function OnGlobalAnnouncement(Bot $bot, $parameters, $channel, $nickname) {
        if (count($parameters) == 0) {
            CommandHelper::usageMessage($bot, $channel, '!globalannouncement [message]');
            return;
        }

        $destination = implode(',', TargetChannel::chatChannels());
        $prefix = 'PRIVMSG ' . $destination . ' :' . ModuleBase::BOLD . ModuleBase::COLOUR_DARKGREEN;
        $bot->send($prefix . '=== Announcement by the LVP Management ===');
        $bot->send($prefix . implode(' ', $parameters));
        $bot->send($prefix . '==========================================');
    }

    // Management: !changepass Nickname NewPassword
    // Private: !changepass Nickname Password NewPassword
    private static function OnChangePasswordCommand($bot, $parameters, $channel, $nickname, $isManagement) {
        $player = self::SelectPrivilegedPlayerFromCommand($bot, $parameters, $channel, $nickname, $isManagement, 'password', $newPassword);

        if ($player === false || $player->exists() === false)
            return; // this case occurs when the user enters wrong information.

        if (strlen($newPassword) < 6) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: The password needs to be longer than five characters.');
            return;
        }

        $player->setPassword($newPassword);
        $bot->send('PRIVMSG ' . $channel . ' :03Success: The password has been updated!');

        if ($isManagement === true)
            return; // no need to announce for management members.

        foreach (TargetChannel::crewAnnouncementChannels() as $announceChannel)
            $bot->send('PRIVMSG ' . $announceChannel . ' :04*** ' . $nickname . ' has changed the password of "' . $parameters[0] . '".');
    }

    // !aliases Nickname
    private static function OnAliasesCommand($bot, $parameters, $channel, $nickname) {
        if (count($parameters) != 1) {
            CommandHelper::usageMessage($bot, $channel, '!aliases [nickname]');
            return;
        }

        $player = LVP::findProfileByNickname($parameters[0]);
        if ($player->exists() === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: No player with this nickname could be found.');
            return;
        }

        $bot->send('PRIVMSG ' . $channel . ' :07Aliases of 05' . $player['username'] . ': ' . implode(', ', $player->listNicknames()));
    }

    // !addalias Nickname Alias
    private static function OnAddAliasCommand($bot, $parameters, $channel, $nickname) {
        if (count($parameters) != 2) {
            CommandHelper::usageMessage($bot, $channel, '!addalias [nickname] [alias]');
            return;
        }

        $player = LVP::findProfileByNickname($parameters[0]);
        if ($player->exists() === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: No player with this nickname could be found.');
            return;
        }

        if (self::IsValidNickname($parameters[1]) === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: The given alias name is not a valid player name.');
            return;
        }

        $aliasPlayer = LVP::findProfileByNickname($parameters[1]);
        if ($aliasPlayer->exists()) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: A player already exists for the given alias.');
            return;
        }

        if ($player->addNickname($parameters[1]) === false)
            $bot->send('PRIVMSG ' . $channel . ' :04Error: Unable to add the given alias, an unknown error occurred.');
        else
            $bot->send('PRIVMSG ' . $channel . ' :03Success: The alias has been added from this nickname.');
    }

    // !removealias Nickname Alias
    private static function OnRemoveAliasCommand($bot, $parameters, $channel, $nickname) {
        if (count($parameters) != 2) {
            CommandHelper::usageMessage($bot, $channel, '!removealias [nickname] [alias]');
            return;
        }

        $player = LVP::findProfileByNickname($parameters[0]);
        if ($player->exists() === false) {
            $bot->send('PRIVMSG ' . $channel . ' :04Error: No player with this nickname could be found.');
            return;
        }

        if ($player->removeNickname($parameters[1]) === false)
            $bot->send('PRIVMSG ' . $channel . ' :04Error: Unable to remove the given alias. Does it exist?');
        else
            $bot->send('PRIVMSG ' . $channel . ' :03Success: The alias has been removed from this nickname.');
    }

    // Utility function to validate a nickname.
    private static function IsValidNickname($nickname) {
        return preg_match('/^[A-Za-z0-9\[\]\.\$\=\@\(\)_]{3,23}$/', $nickname) == 1;
    }

    // Utility function to check for valid parameters in the banip/-serial cmd
    private static function AreGivenBanParametersValid($bot, $channel, $banValue, $playerName, $duration, $reason) {
        $isIpSearch = true;
        if (filter_var($banValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || $banValue == '127.0.0.1') {
            // Welp, perhaps a serial(hash)?
            if (!is_numeric($banValue) && strlen($banValue) < 10) {
                CommandHelper::errorMessage($bot, $channel, 'Invalid serial given.');
                return false;
            }
            else
                $isIpSearch = false;

            if (!$isIpSearch) {
                CommandHelper::errorMessage($bot, $channel, 'Invalid IP address given.');
                return false;
            }
        }

        if (strlen($playerName) < 3) {
            CommandHelper::errorMessage($bot, $channel, 'The player name needs to be at least 3 characters.');
            return false;
        }

        if (!is_numeric($duration) && $duration < 1) {
            CommandHelper::errorMessage($bot, $channel, 'The duration should be given in number of days.');
            return false;
        }

        if (strlen($reason) < 5) {
            CommandHelper::errorMessage($bot, $channel, 'The reason needs to be at least 5 characters.');
            return false;
        }

        return true;
    }
};
