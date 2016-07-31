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

use Nuwani\Bot;
use Nuwani \ ModuleManager;

class UserStatus {
    const IsVisitor = 1;
    const IsVoiced = 2;
    const IsHalfOperator = 4;
    const IsOperator = 8;
    const IsProtected = 16;
    const IsOwner = 32;
};

class ChannelTracker extends ModuleBase {
    const DataDir = 'Data/ChannelTracker';

    public $m_channels;

    public static $enableLogging = true;

    public function __construct() {
        if (!is_dir(self::DataDir)) {
            mkdir(self::DataDir, 0777, true);
        }
    }

    public function userLevelForChannel($nickname, $channel) {
        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            return 0;

        if (isset($this->m_channels[$channel][$nickname]) === false)
            return 0;

        return $this->m_channels[$channel][$nickname];
    }

    public function highestUserLevelForChannel($nickname, $channel) {
        $status = $this->userLevelForChannel($nickname, $channel);
        if ($status & UserStatus::IsOwner)
            return UserStatus::IsOwner;
        if ($status & UserStatus::IsProtected)
            return UserStatus::IsProtected;
        if ($status & UserStatus::IsOperator)
            return UserStatus::IsOperator;
        if ($status & UserStatus::IsHalfOperator)
            return UserStatus::IsHalfOperator;
        if ($status & UserStatus::IsVoiced)
            return UserStatus::IsVoiced;

        return UserStatus::IsVisitor;
    }

    public function onChannelJoin(Bot $bot, $channel, $nickname) {
        self::log('JOIN ' . $channel . ' ' . $nickname);

        if ($nickname == $bot['Nickname'])
            $bot->send('NAMES ' . $channel);

        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            $this->m_channels[$channel] = array();

        self::log('Channel ' . $channel . ' - Added ' . $nickname . ' with level ' . UserStatus::IsVisitor
            . ' (' . self::formatLevel(UserStatus::IsVisitor) . ')');
        $this->m_channels[$channel][$nickname] = UserStatus::IsVisitor;
    }

    public function onChannelLeave(Bot $bot, $channel, $nickname) {
        $channel = strtolower($channel);
        if ($nickname == $bot['Nickname']) {
            self::log('Removed channel ' . $channel);
            unset($this->m_channels[$channel]);
            return;
        }

        if (isset($this->m_channels[$channel]) === false || isset($this->m_channels[$channel][$nickname]) === false)
            return;

        self::log('Channel ' . $channel . ' - Removed ' . $nickname);
        unset($this->m_channel[$channel][$nickname]);
    }

    public function onChannelKick(Bot $bot, $channel, $kicked, $kicker, $reason) {
        self::log('KICK ' . $kicked . ' ' . $kicker . ' ' . $channel);
        $this->onChannelLeave($bot, $channel, $kicked);
    }

    public function onChannelPart(Bot $bot, $channel, $nickname, $reason) {
        self::log('PART ' . $channel . ' ' . $nickname);
        $this->onChannelLeave($bot, $channel, $nickname);
    }

    public function onQuit(Bot $bot, $nickname, $reason) {
        self::log('QUIT ' . $nickname);

        if ($nickname == $bot['Nickname']) {
            // TODO Might want to remove all our data here
            return;
        }

        foreach ($this->m_channels as $channel => &$users) {
            if (isset($users[$nickname])) {
                self::log('Channel ' . $channel . ' - Removed ' . $nickname);
                unset($users[$nickname]);
            }
        }
    }

    public function onChangeNick(Bot $bot, $formerNickname, $nickname) {
        self::log('NICK ' . $formerNickname . ' ' . $nickname);

        foreach ($this->m_channels as $channel => &$users) {
            if (isset($users[$formerNickname]) === false)
                continue;

            self::log('Channel ' . $channel . ' - Updated ' . $formerNickname . ' to ' . $nickname);
            $users[$nickname] = $users[$formerNickname];
            unset($users[$formerNickname]);
        }
    }

    public function onChannelNames(Bot $bot, $channel, $names) {
        self::log('NAMES ' . $channel . ' ' . $names);

        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            return;

        foreach (preg_split('/\s+/', $names, -1, PREG_SPLIT_NO_EMPTY) as $user) {
            $level = UserStatus::IsVisitor;
            $offset = 0;

            for ($length = strlen($user); $offset < $length; ++$offset) {
                switch (substr($user, $offset, 1)) {
                    case '~':
                        $level |= UserStatus::IsOwner;
                        break;
                    case '&':
                        $level |= UserStatus::IsProtected;
                        break;
                    case '@':
                        $level |= UserStatus::IsOperator;
                        break;
                    case '%':
                        $level |= UserStatus::IsHalfOperator;
                        break;
                    case '+':
                        $level |= UserStatus::IsVoiced;
                        break;
                    default:
                        break 2;
                }
            }

            self::log('Channel ' . $channel . ' - Added ' . substr($user, $offset)
                . ' with level ' . $level . ' (' . self::formatLevel($level) . ')');
            $this->m_channels[$channel][substr($user, $offset)] = $level;
        }
    }

    public function onChannelPrivmsg(Bot $bot, $channel, $nickname, $message) {
        $chunks = explode(' ', $message, 2);

        if ($chunks[0] != '!channeltrackingstate')
            return;

        $channel = strtolower(isset($chunks[1]) ? $chunks[1] : $channel);
        $output = '';

        if (isset($this->m_channels[$channel])) {
            foreach ($this->m_channels[$channel] as $nicknameInChannel => $level) {
                $output .= self::formatLevel($level) . $nicknameInChannel . ',';
            }

            $bot->send('NOTICE ' . $nickname . ' :' . $output);
        }
    }

    public function onChannelMode(Bot $bot, $channel, $modes) {
        self::log('MODE ' . $channel . ' ' . $modes);

        $channel = strtolower($channel);
        if (isset($this->m_channels[$channel]) === false)
            return;

        $modes = preg_split('/\s+/', $modes, -1, PREG_SPLIT_NO_EMPTY);
        $commandOperator = 1;
        $modeAddition = true;

        for ($index = 0, $length = strlen($modes[0]); $index < $length; ++$index) {
            switch ($modes[0][$index]) {
                case '+':
                    $modeAddition = true;
                    break;
                case '-':
                    $modeAddition = false;
                    break;

                case 'q':
                case 'a':
                case 'o':
                case 'h':
                case 'v':
                    $right = $this->rightForChannelMode($modes[0][$index]);
                    if ($modeAddition === true) {
                        $old = $this->m_channels[$channel][$modes[$commandOperator]];
                        $this->m_channels[$channel][$modes[$commandOperator]] |= $right;
                        $new = $this->m_channels[$channel][$modes[$commandOperator]];

                        self::log('Channel ' . $channel . ' - Nickname ' . $modes[$commandOperator]
                            . ' - Added right ' . $right . ' (' . self::formatLevel($right) . ')'
                            . ' - Old level ' . $old . ' (' . self::formatLevel($old) . ')'
                            . ' - New level ' . $new . ' (' . self::formatLevel($new) . ')');
                    }
                    else {
                        $old = $this->m_channels[$channel][$modes[$commandOperator]];
                        $this->m_channels[$channel][$modes[$commandOperator]] &= ~ $right;
                        $new = $this->m_channels[$channel][$modes[$commandOperator]];

                        self::log('Channel ' . $channel . ' - Nickname ' . $modes[$commandOperator]
                            . ' - Removed right ' . $right . ' (' . self::formatLevel($right) . ')'
                            . ' - Old level ' . $old . ' (' . self::formatLevel($old) . ')'
                            . ' - New level ' . $new . ' (' . self::formatLevel($new) . ')');
                    }
                    $commandOperator++;
                    break;

                case 'b':
                case 'k':
                case 'l':
                case 'd':
                case 'e':
                case 'F':
                case 'f':
                case 'g':
                case 'H':
                case 'l':
                case 'J':
                case 'j':
                case 'L':
                case 'w':
                case 'W':
                    if ($modeAddition === true)
                        $commandOperator++;
                    break;
            }
        }
    }

    private function rightForChannelMode($mode) {
        switch ($mode) {
            case 'q':
                return UserStatus::IsOwner;
            case 'a':
                return UserStatus::IsProtected;
            case 'o':
                return UserStatus::IsOperator;
            case 'h':
                return UserStatus::IsHalfOperator;
            case 'v':
                return UserStatus::IsVoiced;
        }

        return UserStatus::IsVisitor;
    }

    private static function formatLevel($level) {
        $formatted = '';
        if ($level & UserStatus::IsOwner)
            $formatted .= '~';
        if ($level & UserStatus::IsProtected)
            $formatted .= '&';
        if ($level & UserStatus::IsOperator)
            $formatted .= '@';
        if ($level & UserStatus::IsHalfOperator)
            $formatted .= '%';
        if ($level & UserStatus::IsVoiced)
            $formatted .= '+';
        return $formatted;
    }

    private static function log($line) {
        if (self::$enableLogging) {
            file_put_contents(self::DataDir . '/' . date('Y-m-d') . '.log', date('[Y-m-d H:i:s] ') . trim($line) . PHP_EOL, FILE_APPEND);
        }
    }
};
