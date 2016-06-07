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

if (function_exists('posix_getuid') && posix_getuid() === 0)
    die('Do not start Nuwani as root, or you will break the gameserver.' . PHP_EOL);

if (!defined ('DEBUG_MODE')) {
        define ('DEBUG_MODE', isset($argv[1]) && $argv[1] == 'debug' ? true : false);
}

function CreateBot($nickname, $slave) {
    return array(
        'Nickname'      => $nickname,
        'AltNickname'   => $nickname . '_',
        'Username'      => $nickname,
        'Realname'      => 'LVP IRC Bot',
        
        'Network'       => 'GTANet',
        
        'BindIP'        => '82.192.76.195',
        'SSL'           => 6697,
        
        'Slave'         => $slave,

        'OnConnect' => array(
            'Channels' => array(
                '#LVP,#LVP.nl,#LVP.echo'
            ),
            
            // Commands to execute on connection, before joining any channels.
            //'PRIVMSG NickServ :IDENTIFY '
        ),
        
        'QuitMessage'   => 'Las Venturas Playground (http://www.sa-mp.nl/)'
    );
}
  
$aConfiguration = array(
    'Networks' => array(
        'GTANet' => array(
            '82.192.76.195:+6697', // maple
            '46.105.46.97:+6697', // aventine
        )
    ),

    'Bots' => array(
        CreateBot('Nuwani', false),
        CreateBot('Nuwoni', true),
        CreateBot('Nuwuni', true),
        CreateBot('Nuweni', true),
        CreateBot('Nuwini', true),
        CreateBot('Nowani', true),
    ),

    'Owners' => array(
        'Prefix'        => '>>',
        array(
            'Username'      => '*!Peter@netstaff.irc.gtanet.com',
            'Password'      => '',
            'Identified'    => true
        ),
        array(
            'Username'      => 'Russell_!russell@sa-mp.nl',
            'Password'      => '',
            'Identified'    => true
        ),
	array(
            'Username' => 'MrBondt!mrbondt@staff.irc.gtanet.com',
            'Password' => '',
            'Identified' => true
	)
    ),
    
    'PriorityQueue' => array(),
    
    'MySQL' => array(
        'enabled'       => false,
        'hostname'      => 'localhost',
        'username'      => 'root',
        'password'      => '',
        'database'      => '',
        'restart'       => 30
    ),
    
    // Las Venturas Playground echo configuration.
    'Playground' => array(
        'message_feeds' => array(
            array(
                'name'      => 'public',
                'bind_to'   => '0.0.0.0',
                'port'      => 26667,
                'channel'   => '#LVP.echo',
                'event_listener' => array(
                    // Channel identifiers as defined in the 'channels' configuration below.
                    'announcement_channels' => array('managers','development','crew'),
                    // Directory in which log files will be written.
                    'logs_directory' => '/home/samp/Server/logs/',
                ),
            ),
            array(
                'name'      => 'developer',
                'bind_to'   => '0.0.0.0',
                'port'      => 26669,
                'channel'   => '#LVP.Dev.echo',
                'event_listener' => array(
                    // Channel identifiers as defined in the 'channels' configuration below.
                    'announcement_channels' => 'development',
                    // Directory in which log files will be written.
                    'logs_directory' => '/home/samp/Server/logs/',
                )
            )
        ),
        
        'server' => array(
            'hostname'  => 'play.sa-mp.nl',
            'port'      => 7777,
            'password'  => ''
        ),
        
        // The file to which commands towards in-game will be written.
        'command_file' => '/home/samp/Server/scriptfiles/irccmd.txt',
        
        // Password key used to hash password on LVP.
        'password_key' => array(
            'public' => '',
            'developer' => '^&lvp__@'
        ),
        
        'database' => array(
            'public' => array(
                'hostname'  => 'localhost',
                'username'  => 'nuwani',
                'password'  => '',
                'database'  => 'lvp_mainserver'
            ),
            'developer' => array(
                'hostname'  => 'localhost',
                'username'  => 'nuwani',
                'password'  => '',
                'database'  => 'lvp_testserver'
            )
        ),
        
        'format_file' => __DIR__ . '/Modules/Playground/MessageFormat.php',

        'channels' => array(
            // The channel that will be used for determining access rights, based on IRC user level.
            'rights'        => '#LVP.echo',
            'managers'      => '#LVP.Managers',
            'management'    => '#LVP.Management',
            'crew'          => '#LVP.Crew',
            'development'   => '#LVP.Dev',
            'vip'           => '#LVP.VIP',
            'public'        => array(
                '#LVP', '#LVP.NL'
            )
        ),

	   'debug' => DEBUG_MODE
    ),

    'ErrorHandling' => Nuwani \ ErrorExceptionHandler :: ERROR_OUTPUT_ALL,
    'SleepTimer'    => 40000
);

if (DEBUG_MODE) {
    $aConfiguration['Bots'] = array(CreateBot('Neweni', false));
    $aConfiguration['Bots'][0]['OnConnect']['Channels'] = array('#LVP.echo');
    $aConfiguration['Owners']['Prefix'] = '<<';
    $aConfiguration['Playground']['database']['database'] = 'lvp_mainserver_test';
}
