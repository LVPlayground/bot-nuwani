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

/**
 * This array defines the format that will be used to display in-game messages to the
 * IRC channel. Each entry has one required entry, "match", and a number of options to
 * influence the way the message is shared with others.
 *
 * Available operations for each entry:
 *
 *     'match' - regular expression the raw message should match against. REQUIRED.
 *
 * Available operations for formatting the message (must have one of these):
 *
 *     'format' - format that the match will be replaced in, per the preg_replace syntax.
 *
 * Then there also are a few modifiers available, zero or more per rule.
 *
 *     'prefix' - prefix to use in front of the channel name, i.e. "&" for &#Channel.
 *     'destination' - change the destination of the message, instead of the default channel.
 *     'event' - call a method on the bot's ServerEventListener object.
 *
 * This file can be updated at any time, and can be reloaded in Nuwani by typing the
 * !reloadformat command as a protected user in the right channel.
 */

use Playground \ TargetChannel;

$format = array(

    // ---------------------------------------------------------------------------------------------
    // Formatting related to players joining and leaving Las Venturas Playground.
    // ---------------------------------------------------------------------------------------------

    // [join] playerId playerName
    array(
        'match'     => '/^\[join\] (\d+) ([^\s]+)$/',
        'format'    => '02[\1] 03*** \2 joined the game.'
    ),

    // [login] playerId playerName
    array(
        'match'     => '/^\[login\] (\d+) ([^\s]+)$/',
        'format'    => '2[\1] 3*** \2 has logged in.'
    ),

    // [guestlogin] playerId oldPlayerName newPlayerName
    array(
        'match'     => '/^\[guestlogin\] (\d+) ([^\s]+) (.+)$/',
        'format'    => '2[\1] 3*** \2 decided to play as \3 (guest).'
    ),

    // [leave] playerId playerName reason
    array(
        'match'     => '/^\[leave\] (\d+) ([^\s]+) (.+)$/',
        'function'  => function($matches) {
            if (count($matches) != 4)
                return '';

            $reasons = array('timeout', 'leaving', 'kicked');
            return sprintf('02[%d] 03*** %s left the game (%s).', $matches[1], $matches[2], $reasons[(int)$matches[3]]);
        }
    ),

    // [joinipgpci] playerId playerIp playerName playerGpci
    array(
        'match'     => '/^\[joinipgpci\] (\d+) ([^\s]+) ([^\s]+) ([^\s]+)$/',
        'format'    => '4IP Address \3 (Id:\1): \2 / Serial: \4',
        'prefix'    => '%'
    ),

    // ---------------------------------------------------------------------------------------------
    // Formatting related to several kinds of player messages on the server.
    // ---------------------------------------------------------------------------------------------

    // [text] playerId playerName message
    array(
        'match'     => '/^\[text\] (\d+) ([^\s]+) ?(.*)$/',
        'format'    => '02[\1]07 \2: \3'
    ),

    // [me] playerId playerName message
    array(
        'match'     => '/^\[me\] (\d+) ([^\s]+) ?(.*)$/',
        'format'    => '02[\1]07 \2 \3'
    ),

    // [say] playerName message
    array(
        'match'     => '/^\[say\] ([^\s]+) ?(.*)$/',
        'format'    => '10*** \1 on IRC: \2'
    ),

    // [worldchat] worldId playerId playerName message
    array(
        'match'     => '/^\[worldchat\] ([\d\-]+) (\d+) ([^\s]+) ?(.*)$/',
        'format'    => '2*** 7Worldchat (Id:\1) 2[\2] 7\3: \4',
        'prefix'    => '+'
    ),

    // [gang] playerId playerName gangId gangNameLength gangName message
    array(
        'match'     => '/^\[gang\] (\d+) ([^\s]+) (\d+) (\d+) (.+)$/',
        'function'  => function($matches) {
            $playerId = $matches[1];
            $playerName = $matches[2];
            $gangId = $matches[3];

            $gangNameLength = $matches[4];

            $gangName = substr($matches[5], 0, $gangNameLength);
            $message = substr($matches[5], $gangNameLength + 1);

            return '2*** 7Gang ' . $gangName . ' (Id:' . $gangId . ') '
                . '2[' . $playerId . '] 7' . $playerName . ': '
                . $message;
        },
        'prefix'    => '%'
    ),

    // [regular] playerName playerId message
    array(
        'match'     => '/^\[regular\] ([^\s]+) (\d+) ?(.*)$/',
        'format'    => '12Regular Chat 2[\2] 07\1: \3',
        'prefix'    => '+'
    ),

    // [vipchat] playerName playerId message
    array(
        'match'     => '/^\[vipchat\] ([^\s]+) (\d+) ?(.*)$/',
        'format'    => '12VIP Chat 2[\2] 07\1: \3',
        'prefix'    => '+'
    ),

    // [noidmsg] playerName message
    array(
        'match'     => '/^\[noidmsg\] ([^\s]+) (.+)$/',
        'format'    => '02[--]07 \1: \2'
    ),

    // [adminmsg] playerName playerId message
    array(
        'match'     => '/^\[adminmsg\] ([^\s]+) (\d+) *(.*)$/',
        'format'    => '2*** 7Admin \1 (Id:\2): \3',
        'prefix'    => '%'
    ),

    // [report] playerName playerId reportedPlayerName reportedPlayerId reason
    array(
        'match'     => '/^\[report\] ([^\s]+) (\d+) ([^\s]+) (\d+) *(.*)$/',
        'format'    => '2*** 7Report \1 (Id:\2): Player: \3 (Id:\4) - Reason: \5',
        'prefix'    => '%'
    ),

    // [pm] senderName senderId recipientName recipientId message
    array(
        'match'     => '/^\[pm\] ([^\s]+) ([^\s]+) ([^\s]+) ([^\s]+) ?(.*)$/',
        'format'    => '7PM from5 \17 (Id:\2) to5 \37 (Id:\4): \5',
        'prefix'    => '%'
    ),

    // [phone] senderName senderId recipientName recipientId message
    array(
        'match'     => '/^\[phone\] ([^\s]+) ([^\s]+) ([^\s]+) ([^\s]+) ?(.*)$/',
        'format'    => '7Call from5 \17 (Id:\2) to5 \37 (Id:\4): \5',
        'prefix'    => '%'
    ),

    // [ircpm] playerId playerName targetName message
    array(
        'match'     => '/^\[ircpm\] (\d+) ([^\s]+) ([^\s]+) ?(.*)$/',
        'function'  => function($matches) {
            if (count($matches) != 5 || substr($matches[3], 0, 1) == '#')
                return '';

            $bot = \ Nuwani \ BotManager::getInstance()->offsetGet('master');
            if ($bot instanceof \ Nuwani \ BotGroup)
                $bot = $bot->current();

            if ($bot instanceof \ Nuwani \ Bot)
                $bot->send('NOTICE ' . $matches[3] . ' :07PM from 05' . $matches[2] . ' 07(' . $matches[1] . '): ' . $matches[4]);

            return '05*** ' . $matches[2] . ' (' . $matches[1] . ') has sent an IRC PM to ' . $matches[3] . ': ' . $matches[4];
        },

        'prefix'    => '%'
    ),

    // ---------------------------------------------------------------------------------------------
    // Formatting related to fighting and dying between and of players.
    // ---------------------------------------------------------------------------------------------

    // [death] playerName
    array(
        'match'     => '/^\[death\] ([^\s]+)$/',
        'format'    => '04*** \1 has died.'
    ),

    // [kill] victimName victimId killerName killerId reason
    array(
        'match'     => '/^\[kill\] ([^\s]+) (\d+) ([^\s]+) (\d+) (\d+)$/',
        'function'  => function($matches) {
            if (count($matches) != 6)
                return '';

            $deathReasons = array(
                'Unarmed',           'Brass Knuckle', 'Golf Club',
                'Night Stick',       'Knife',         'Baseball Bat',
                'Shovel',            'Pool Cue',      'Katana',
                'Chainsaw',          'Dildo',         'Dildo',
                'Dildo',             'Dildo',         'Flowers',
                'Cane',              'Grenade',       'Teargas',
                'Molotov',           'Rockets',       'Heat-seeking Rocket',
                'Hydra Rocket',      'Pistol',        'Silenced Pistol',
                'Desert Eagle',      'Shotgun',       'Sawnoff Shotgun',
                'Combat Shotgun',    'Tec 9',         'MP5',
                'AK47',              'M4',            'Micro Uzi',
                'Country Rifle',     'Sniper Rifle',  'Rocket Launcher',
                'Rocket Launcher',   'Flamethrower',  'Minigun',
                'Satchel Charge',    'Detonator',     'Spraycan',
                'Fire Extinguisher', 'Camera',        'Nightvision',
                'Infrared Vision',   'Parachute',     'Fake Pistol',
                'Vehicle',           'Drowned',       'Splash'
			);

            $reason = '';
            if (isset($deathReasons[(int) $matches[5]]))
                $reason = ': ' . $deathReasons[(int) $matches[5]];

            return '04*** ' . $matches[1] . ' (Id:' . $matches[2] . ') has been killed by ' . $matches[3] . ' (Id:' . $matches[4] . ')' . $reason;
        }
    ),

    // [killtime] playerName numberOfKills
    array(
        'match'     => '/^\[killtime\] ([^\s]+) (\d+)$/',
        'format'    => '04*** \1 has won the killtime with \2 kills!'
    ),

    // [killtime] None -
    array(
        'match'     => '/^\[killtime\] None -$/',
        'format'    => '04*** Nobody has won the killtime.'
    ),

    // ---------------------------------------------------------------------------------------------
    // Formatting related to several kinds of announcement messages on the server.
    // ---------------------------------------------------------------------------------------------

    // [announce] message
    array(
        'match'     => '/^\[announce\] (.+)$/',
        'format'    => '10*** \1'
    ),

    // [admin] message
    array(
        'match'     => '/^\[admin\] (.+)$/',
        'format'    => '05*** \1',
        'prefix'    => '%'
    ),

    // [withdraw] playerId playerName amount
    array(
        'match'     => '/^\[withdraw\] (\d+) ([^\s]+) (\d+)$/',
        'format'    => '', // already covered by the admin message.
        'prefix'    => '%'
    ),

    // [moneyt] [TkW]Shade 16 1000000 eF.HoodyFnH 0
    array(
        'match'     => '/^\[moneyt\] ([^\s]+) (\d+) (\d+) ([^\s]+) (\d+)$/',
        'format'    => '', // already covered by the admin message, although it misses the IDs.
        'prefix'    => '%'
    ),

    // ---------------------------------------------------------------------------------------------
    // Formatting related to messages that we want to be send to different destinations.
    // ---------------------------------------------------------------------------------------------

    // [init]
    array(
        'match'     => '/^\[init\] (.*)$/',
        'format'    => '4*** Global Gamemode Initialization',
        'event'     => 'onGamemodeInitialization'
    ),

    // [crew] playerName message
    array(
        'match'         => '/^\[crew\] ([^\s]+) ?(.*)$/',
        'format'        => '4Message from \1: \2',
        'destination'   => TargetChannel::crewChannel()
    ),

    // [man] playerName message
    array(
        'match'         => '/^\[man\] ([^\s]+) ?(.*)$/',
        'format'        => '4Message from \1: \2',
        'destination'   => TargetChannel::managementChannel()
    ),

    // ---------------------------------------------------------------------------------------------
    // Formatting related to minigames and chat-games on the server.
    // ---------------------------------------------------------------------------------------------

    // [reaction] message
    array(
        'match'     => '/^\[reaction\] (.+?)$/',
        'format'    => '4*** First player to type \1 wins \$5.000!',
        'block-destination' => TargetChannel::developmentEchoChannel()
    ),

    // [reaction2] message
    array(
        'match'     => '/^\[reaction2\] (.+?)$/',
        'format'    => '4*** First player to solve \1 wins \$5.000!',
        'block-destination' => TargetChannel::developmentEchoChannel()
    ),

    // [wonreaction] playerName seconds
    array(
        'match'     => '/^\[wonreaction\] ([^\s]+) (.+?)$/',
        'format'    => '4*** \1 has won the reaction test in \2 seconds!'
    ),

    // [buy] propertyPrice playerName propertyName
    array(
        'match'     => '/^\[buy\] (\d+) ([^\s]+) (.*)$/',
        'format'    => '10*** \2 has bought the \3 property for \$\1.'
    ),

    // [sold] propertyPrice playerName propertyName
    array(
        'match'     => '/^\[sold\] (\d+) ([^\s]+) (.*)$/',
        'format'    => '10*** \2 has sold the \3 property for \$\1.'
    ),

    // [soldall] playerId playerName earnings
    array(
        'match'     => '/^\[soldall\] (\d+) ([^\s]+) (.*)$/',
        'format'    => '10*** \2 (Id:\1) has sold all of his/her properties for \3.'
    ),

    // [gta] requiredVehicle award
    array(
        'match'     => '/^\[gta\] (.+?) (\d+)$/',
        'format'    => '3Grand Theft Auto: The merchant now requires \1 for $\2',
        'block-destination' => TargetChannel::developmentEchoChannel()
    ),

    // ---------------------------------------------------------------------------------------------
    // Miscellaneous formatting that doesn't fit elsewhere.
    // ---------------------------------------------------------------------------------------------

    // [extern] ...
    // All messages with this prefix will be delivered to the development echo instead.
    array(
        'match'     => '/^\[extern\] (.+)$/',
        'format'    => '\1',

        // Send the result of this format to another message feed.
        'message-feed' => 'developer'
    ),

    // [why] requesterId playerName
    array(
        'match'     => '/^\[why\] (\d+) ([^\s]+)$/',
        'function'  => function($matches) {
            if (count($matches) != 3)
                return '';

            $requestee = (int) $matches[1];
            $playerName = $matches[2];

            // TODO: We should be able to return multiple results here.
            // FIXME: This always outputs an empty result with year 1970.
            $logs = \ Playground \ BanManager::GetPlayerLog($playerName, 0, 1);
            $message = 'adminm Nuwani ' . date('Y-m-d', $logs['date']) . ' -  ' . $logs['type'] . ' by ' . $logs['admin'] . ': ' . $logs['message'];
            \ Playground::sendIngameCommand($message);

            return '';
        }
    ),

    // [error] message
    array(
        'match'     => '/^\[error\] (.*)$/',
        'format'    => '04*** \1',
        'prefix'    => '%'
    ),

    // [notconnected] playerId
    array(
        'match'     => '/^\[notconnected\] (\d+)/',
        'format'    => '04* Error: The Id \1 is not connected.'
    )
);
