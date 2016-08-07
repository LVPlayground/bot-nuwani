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
use Nuwani\BotManager;
use Nuwani\Configuration;
use Nuwani\ModuleManager;

require_once __DIR__ . '/BanManager.php';
require_once __DIR__ . '/CommandHelper.php';
require_once __DIR__ . '/TargetChannel.php';
require_once __DIR__ . '/GpciManager.php';
require_once __DIR__ . '/Commands.php';
require_once __DIR__ . '/LVP.php';
require_once __DIR__ . '/MessageFeed.php';
require_once __DIR__ . '/MessageFeedEventListener.php';
require_once __DIR__ . '/MessageFormatter.php';

use Playground\Commands;
use Playground\MessageFeed;
use Playground\MessageFeedEventListener;
use Playground\MessageFormatter;
use Playground\TargetChannel;

class Playground extends ModuleBase {
    // This corresponds to the version given by !version in #LVP.Dev.
    // Update it when a new version of the module is released.
    const PlaygroundVersion = '28.3';
    const CommandPrefix = '!';

    private $m_configuration;
    private $m_messageFeeds;
    private $m_server;
    private $m_rightChannel;

    private static $m_commandFile;
    private static $m_debug;

    public function __construct() {
        $this->m_configuration = Configuration::getInstance()->get('Playground');
        $this->m_messageFeeds = array();

        foreach ($this->m_configuration['message_feeds'] as $feed)
            $this->m_messageFeeds[$feed['name']] = new MessageFeed($feed['bind_to'], $feed['port'], $feed['channel'], new MessageFeedEventListener($feed['event_listener']));

        $databaseConfiguration = $this->m_configuration['database']['public'];
        $this->m_server = $this->m_configuration['server'];
        $this->m_rightChannel = $this->m_configuration['channels']['rights'];

        LVP::setRemoteCommandInformation($this->m_server['hostname'], $this->m_server['port'], $this->m_server['password']);
        LVP::setDatabaseInformation($databaseConfiguration['hostname'], $databaseConfiguration['username'],
            $databaseConfiguration['password'], $databaseConfiguration['database']);
        LVP::setPasswordHashKey($this->m_configuration['password_key']['public']);

        self::$m_commandFile = $this->m_configuration['command_file'];

        $this->setDebug($this->m_configuration['debug']);

        TargetChannel::Initialize($this->m_configuration['message_feeds'], $this->m_configuration['channels']);
        MessageFormatter::Initialize($this->m_configuration['format_file']);
    }

    // Convenience method, so that we can toggle it using an evaluation expression rather quickly.
    public static function enableDebug($value) {
        ModuleManager::getInstance()->offsetGet('Playground')->setDebug($value);
    }

    // Enable debugging functionality, since this may result in a lot of data, we want this to be toggleable during runtime.
    public function setDebug($value) {
        $this->m_configuration['debug'] = $value == true;
        self::$m_debug = $this->m_configuration['debug'];

        foreach ($this->m_messageFeeds as $messageFeed)
            $messageFeed->setDebug($this->m_configuration['debug']);
    }

    // Invoked on every frame of the bot (rather frequently).
    public function onTick() {
        // TODO Get bot group of bots actually connected.
        $botGroup = BotManager::getInstance()->getBotList();
        $botArray = array();

        foreach ($botGroup as $bot)
            $botArray[] = $bot;

        foreach ($this->m_messageFeeds as $feed) {
            $feed->poll(function ($message) use ($botArray, $feed) {
                $this->processMessage($botArray, $feed, $message);
            });
        }
    }

    private function processMessage($botArray, $feed, $message) {
        // A message has been received from the server. We need to format it using the
        // formatter, and then distribute it to the channel assigned to this feed.
        $processedMessage = MessageFormatter::Format($message, $feed->channel(), $feed->eventListener());
        if ($processedMessage === null)
            return;

        if ($this->m_configuration['debug'])
            file_put_contents('Data/PlaygroundFormattedMessages.log', date('[r] ') . $processedMessage['message'] . PHP_EOL, FILE_APPEND);

        if ($processedMessage['message-feed'] !== null && isset($this->m_messageFeeds[$processedMessage['message-feed']])) {
            $this->processMessage($botArray, $this->m_messageFeeds[$processedMessage['message-feed']], $processedMessage['message']);
            return;
        }

        $destination = $processedMessage['destination'];
        $prefix = $processedMessage['prefix'];

        // FIXME: We should do load balancing here.
        if (is_array($destination)) {
            $destination = implode(',' . $prefix, $destination);
        }
        $botArray[array_rand($botArray)]->send('PRIVMSG ' . $prefix . $destination . ' :' . $processedMessage['message']);
    }

    // Invoked when someone types something in a public channel.
    public function onChannelPrivmsg(Bot $bot, $channel, $nickname, $message) {
        if (substr($message, 0, 1) != self::CommandPrefix)
            return;

        // HACK: Disregard messages intended for the JC-MP echo.
        if (strtoupper($channel) == '#LVP.JCMP')
            return;

        $channelTracker = ModuleManager::getInstance()->offsetGet('ChannelTracker');
        if ($channelTracker === false) {
            echo '[Playground] Disregarding command as the Channel Tracker is not available.' . PHP_EOL;
            return;
        }

        $userLevel = $channelTracker->highestUserLevelForChannel($nickname, $this->m_rightChannel);
        $parameters = preg_split('/\s+/', $message);
        $command = substr(array_shift($parameters), 1);

        return Commands::ProcessCommand($bot, $command, $parameters, $channel, $nickname, $userLevel);
    }

    // Invoked when someone types something in private chat to the bot.
    public function onPrivmsg(Bot $bot, $nickname, $message) {
        if (substr($message, 0, 1) != self::CommandPrefix)
            return;

        $parameters = preg_split('/\s+/', $message);
        $command = substr(array_shift($parameters), 1);

        return Commands::ProcessPrivateCommand($bot, $command, $parameters, $nickname);
    }

    public function onCTCP(Bot $bot, $source, $nickname, $type, $message) {
        if (trim($type) == 'LVPVERSION') {
            // Send CTCP reply.
            $bot->send('NOTICE ' . $nickname . ' :' . self :: CTCP . $type . ' Playground Module Version: ' . self::PlaygroundVersion);
        }
    }

    public static function sendIngameCommand($command) {
        if (self::$m_debug) {
            file_put_contents('Data/PlaygroundCommands.log', date('[r] ') . $command . PHP_EOL, FILE_APPEND);
        }

        file_put_contents(self::$m_commandFile, $command);
    }
}
