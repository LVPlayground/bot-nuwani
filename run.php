<?php
/**
 * Nuwani PHP IRC Bot Framework
 * Copyright (c) 2006-2010 The Nuwani Project
 * Nuwani is a framework for IRC Bots built using PHP. Nuwani speeds up bot
 * development by handling basic tasks as connection- and bot management, timers
 * and module managing. Features for your bot can easily be added by creating
 * your own modules, which will receive callbacks from the framework.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @copyright Copyright (c) 2006-2010 The Nuwani Project
 * @package   Nuwani
 * @author    Peter Beverloo <peter@lvp-media.com>
 * @author    Dik Grapendaal <dik@sa-mp.nl>
 * @see       http://nuwani.googlecode.com
 */

define('NUWANI_STARTTIME', microtime(true));

define('NUWANI_NAME', 'Nuwani');
define('NUWANI_VERSION', 'v3.0-a1');
define('NUWANI_VERSION_STR', NUWANI_NAME . ' ' . NUWANI_VERSION);

error_reporting(E_ALL);
set_time_limit(0);
chdir(__DIR__);

if(version_compare(PHP_VERSION, '7.0') < 0) {
    die ('You need PHP 7.0 or higher to run ' . NUWANI_VERSION_STR . '.' . PHP_EOL);
}

require 'Sources/Singleton.php';
require 'Sources/ModuleBase.php';
require 'Sources/Configuration.php';
require 'Sources/BotManager.php';
require 'Sources/ErrorExceptionHandler.php';
require 'Sources/BotGroup.php';
require 'Sources/Database.php';
require 'Sources/NetworkManager.php';
require 'Sources/ModuleManager.php';
require 'Sources/Socket.php';
require 'Sources/Timer.php';
require 'Sources/Bot.php';
require 'config.php';

if($_SERVER ['argc'] >= 2 && $_SERVER ['argv'] [1] == 'restart') {
    /** Make sure the still running bot has time to disconnect. **/
    sleep(1);
}

Nuwani\Configuration::getInstance()->register($aConfiguration);
Nuwani\NetworkManager::getInstance()->Initialise($aConfiguration ['Networks']);
Nuwani\BotManager::getInstance()->Initialise($aConfiguration ['Bots']);

Nuwani\ErrorExceptionHandler::getInstance()->Initialise($aConfiguration ['ErrorHandling']);

$g_bRun = true;
while($g_bRun) {
    try {
        Nuwani\BotManager::getInstance()->process();
        Nuwani\ModuleManager::getInstance()->onTick();
        Nuwani\Timer::process();

        if(count(Nuwani\BotManager::getInstance()) == 0) {
            $g_bRun = false;
        }

        usleep($aConfiguration ['SleepTimer']);
    } catch(Exception $pException) {
        Nuwani\ErrorExceptionHandler::getInstance()->processException($pException);
        if(ob_get_level() >= 1) {
            ob_end_flush();
        }
    }
}

/** Destructors will be called by PHP automatically. **/
