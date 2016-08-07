<?php

/**
 * Nuwani PHP IRC Bot Framework
 * Copyright (c) 2006-2010 The Nuwani Project
 *
 * Nuwani is a framework for IRC Bots built using PHP. Nuwani speeds up bot
 * development by handling basic tasks as connection- and bot management, timers
 * and module managing. Features for your bot can easily be added by creating
 * your own modules, which will receive callbacks from the framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) 2006-2010 The Nuwani Project
 * @package Evaluation Module
 * @author Peter Beverloo <peter@lvp-media.com>
 * @author Dik Grapendaal <dik@sa-mp.nl>
 * @see http://nuwani.googlecode.com
 */

use Nuwani\Bot;
use Nuwani\Configuration;
use Nuwani\Database;
use Nuwani\ErrorExceptionHandler;
use Nuwani\ISecurityProvider;
use Nuwani\ModuleBase;
use Nuwani\ModuleManager;
use Nuwani\Timer;

class Evaluation extends ModuleBase implements ISecurityProvider
{
        /**
         * The property which contains an array of the people who will be
         * allowed to evaluate PHP code.
         *
         * @var array
         */

        private $m_aOwnerInfo;

        /**
         * Determaines the prefix of the evaluation messages, which can be
         * a single character, but also a line of text.
         *
         * @var string
         */

        private $m_sPrefix;

        /**
         * The constructor will initialise a list of the people who are allowed
         * to evaluate direct PHP code, using the configuration manager.
         */

        public function __construct ()
        {
                $pConfiguration = Configuration :: getInstance ();
                $aConfiguration = $pConfiguration -> get ('Owners');

                if (!count ($aConfiguration))
                {
                        return ;
                }

                $this -> m_sPrefix = $aConfiguration ['Prefix'];
                $this -> m_aOwnerInfo = array ();

                for ($i = 0; isset ($aConfiguration [$i]); $i++)
                {
                        list ($Nickname, $Username, $Hostname) = preg_split ('/!|@/s', strtolower ($aConfiguration [$i] ['Username']));
                        $this -> m_aOwnerInfo [] = array
                        (
                                'Nickname'      => $Nickname,
                                'Username'      => $Username,
                                'Hostname'      => $Hostname,
                                'Password'      => $aConfiguration [$i] ['Password'],
                                'Identified'    => (isset ($aConfiguration [$i] ['Identified']) ? $aConfiguration [$i] ['Identified'] : false),
                                'CachedUser'    => ''
                        );
                }
        }

        /**
         * This function will be called whenever a new module is being loaded.
         * In here we simply check whether we have to re-register ourselfes as a
         * security module.
         *
         * @param ModuleBase $pModule The module that is being loaded.
         */

        public function onModuleLoad ($pModule)
        {
                if (in_array ('ISecurityModule', class_implements ($pModule)))
                {
                        $pModule -> registerSecurityProvider ($this, ISecurityProvider :: BOT_OWNER);
                }
        }

        /**
         * This function will check whether the security level as specified by
         * the second argument against the user the $pBot variable contains.
         *
         * @param Bot $pBot The bot which we should check security against.
         * @param integer nSecurityLevel Related level of security.
         * @return boolean
         */

        public function checkSecurity (Bot $pBot, $nSecurityLevel)
        {
                $sUser = $pBot -> In -> User;
                foreach ($this -> m_aOwnerInfo as & $aOwnerInfo)
                {
                        if ($aOwnerInfo ['Identified'] === true &&
                           ($aOwnerInfo ['CachedUser'] == $sUser ||
                           ($aOwnerInfo ['CachedUser'] == '' && $this -> confirmIdentity ($pBot, $aOwnerInfo))))
                        {
                                return true ;
                        }
                }

                return false ;
        }

        /**
         * This function gets invoked when someone changes their nickname. In
         * here we have to check whether this person was logged in with this
         * module.
         *
         * @param Bot $pBot The bot which received this message.
         * @param string $sOldName Old nickname of the person who changes his nick.
         * @param string $sNewName And the new nickname of this same person.
         */

        public function onChangeNick (Bot $pBot, $sOldName, $sNewName)
        {
                $sUser = $pBot -> In -> User;
                foreach ($this -> m_aOwnerInfo as & $aOwnerInfo)
                {
                        if ($aOwnerInfo ['Identified'] === true && $aOwnerInfo ['CachedUser'] == $sUser)
                        {
                                $aOwnerInfo ['CachedUser'] = $sNewName . '!' . $pBot -> In -> Username . '@' . $pBot -> In -> Hostname;
                                return ;
                        }
                }
        }

        /**
         * When one of the owners quits IRC, we log them out for security
         * purposes. Some random guy might take his/her nickname and immitate
         * the user!
         *
         * @param Bot $pBot The bot which received this message.
         * @param string $sNickname The user who is quitting IRC.
         * @param string $sReason Why is this user quitting IRC?
         */

        public function onQuit (Bot $pBot, $sNickname, $sReason)
        {
                $sUser = $pBot -> In -> User;
                foreach ($this -> m_aOwnerInfo as & $aOwnerInfo)
                {
                        if ($aOwnerInfo ['Identified'] === true && $aOwnerInfo ['CachedUser'] == $sUser)
                        {
                                $aOwnerInfo ['CachedUser'] = '';
                                $aOwnerInfo ['Identified'] = false;
                                return ;
                        }
                }
        }

        /**
         * PHP Code evaluations may occur in public channels, such as #Sonium
         * which is frequently used for testing. This function will check
         * whether we're allowed to evaluate anything here.
         *
         * @param Bot $pBot The bot who received the public channel message.
         * @param string $sChannel Channel in which we received the message.
         * @param string $sNickname The nickname associated with this message.
         * @param string $sMessage And of course the actual message we received.
         */

        public function onChannelPrivmsg (Bot $pBot, $sChannel, $sNickname, $sMessage)
        {
                if (substr ($sMessage, 0, strlen ($this -> m_sPrefix)) != $this -> m_sPrefix)
                {
                        return false ;
                }

                $sUser = $pBot -> In -> User;
                foreach ($this -> m_aOwnerInfo as & $aOwnerInfo)
                {
                        if ($aOwnerInfo ['CachedUser'] == $sUser || ($aOwnerInfo ['CachedUser'] == '' && $this -> confirmIdentity ($pBot, $aOwnerInfo)))
                        {
                                if ($aOwnerInfo ['Identified'] === true)
                                {
                                        if ($aOwnerInfo ['CachedUser'] == '')
                                        {
                                                $aOwnerInfo ['CachedUser'] = $sUser;
                                        }

                                        $this -> parseEvaluation ($pBot, $sChannel, $sMessage);
                                        return true ;
                                }
                        }
                }

                return false ;
        }

        /**
         * People are free to send private messages to the bot, which gets
         * handled right here. This function does the same as onChannelPrivmsg.
         *
         * @param Bot $pBot Bot that received this privage message.
         * @param string $sNickname Source of the message.
         * @param string $sMessage The message that got PM'ed to us.
         */

        public function onPrivmsg (Bot $pBot, $sNickname, $sMessage)
        {
                if (substr ($sMessage, 0, strlen ($this -> m_sPrefix)) != $this -> m_sPrefix)
                {
                        if (substr ($sMessage, 0, 6) == 'login ')
                        {
                                foreach ($this -> m_aOwnerInfo as & $aOwnerInfo)
                                {
                                        if (($aOwnerInfo ['Nickname'] == strtolower ($pBot -> In -> Nickname) || $aOwnerInfo ['Nickname'] == '*') &&
                                            ($aOwnerInfo ['Username'] == strtolower ($pBot -> In -> Username) || $aOwnerInfo ['Username'] == '*') &&
                                            ($aOwnerInfo ['Hostname'] == strtolower ($pBot -> In -> Hostname) || $aOwnerInfo ['Hostname'] == '*'))
                                        {
                                                if (md5 (substr ($sMessage, 6)) == $aOwnerInfo ['Password'])
                                                {
                                                        $aOwnerInfo ['CachedUser'] = $pBot -> In -> User;
                                                        $aOwnerInfo ['Identified'] = true ;

                                                        $pBot -> send ('PRIVMSG ' . $sNickname . ' :You have successfully identified yourself!');
                                                        return true;
                                                }
                                                else
                                                {
                                                        $pBot -> send ('PRIVMSG ' . $sNickname . ' :Incorrect password.');
                                                        return false;
                                                }
                                        }
                                }

                                return false ;
                        }

                        if (substr ($sMessage, 0, 6) == 'logout')
                        {
                                $sUser = $pBot -> In -> User;
                                foreach ($this -> m_aOwnerInfo as & $aOwnerInfo)
                                {
                                        if ($aOwnerInfo ['Identified'] === true && $aOwnerInfo ['CachedUser'] == $sUser)
                                        {
                                                $aOwnerInfo ['Identified'] = false;
                                                $aOwnerInfo ['CachedUser'] = '';

                                                $pBot -> send ('PRIVMSG ' . $sNickname . ' :You have been logged out successfully.');
                                                return true;
                                        }
                                }
                        }

                        return false;
                }

                $sUser = $pBot -> In -> User;
                foreach ($this -> m_aOwnerInfo as & $aOwnerInfo)
                {
                        if ($aOwnerInfo ['Identified'] === true && ($aOwnerInfo ['CachedUser'] == $sUser || ($aOwnerInfo ['CachedUser'] == '' && $this -> confirmIdentity ($pBot, $aOwnerInfo))))
                        {
                                if ($aOwnerInfo ['CachedUser'] == '')
                                {
                                        $aOwnerInfo ['CachedUser'] = $sUser;
                                }

                                $this -> parseEvaluation ($pBot, $sNickname, $sMessage);
                                return true ;
                        }
                }

                return false ;
        }

        /**
         * After we have confirmed the identity of the one evaluating code on
         * this bot, we're ready to parse the evaluation string into the options.
         *
         * @param Bot $pBot Bot that should handle the evaluation.
         * @param string $sDestination Where should the output be redirected?
         * @param string $sEvaluation Line of code that should be executed.
         */

        private function parseEvaluation (Bot $pBot, $sDestination, $sEvaluation)
        {
                $nFirstSpace = strpos ($sEvaluation, ' ');
                if ($nFirstSpace === false)
                {
                        return false ;
                }

                $sEvaluationIdent = substr ($sEvaluation, 0, $nFirstSpace);
                $sEvaluation      = substr ($sEvaluation, strlen ($sEvaluationIdent) + 1);

                $aOptions = array
                (
                        'Type'          => 'PHP',
                        'Buffering'     => true,
                        'Operation'     => $sEvaluation,
                        'Delay'         => 0,
                        'MaxLines'      => 4
                );

                if (strlen ($sEvaluationIdent) != strlen ($this -> m_sPrefix)) // Parse extra options
                {
                        $sEvaluationIdent = str_replace ($this -> m_sPrefix, '', $sEvaluationIdent);

                        if (strpos ($sEvaluationIdent, '!') !== false)
                        {
                                $sEvaluationIdent  = str_replace ('!', '', $sEvaluationIdent);
                                $aOptions ['Type'] = 'EXEC';
                        }
                        else if (strpos ($sEvaluationIdent, '@') !== false)
                        {
                                $sEvaluationIdent      = str_replace ('@', '', $sEvaluationIdent);
                                $aOptions ['Type']     = 'SQL';
                                $aOptions ['MaxLines'] = 5;
                        }

                        $aMatches = array ();
                        preg_match_all ('/(\d+)(s|m|h)/', $sEvaluationIdent, $aMatches);
                        foreach ($aMatches [0] as $iIndex => $sMatch)
                        {
                                $nThisDelay = $aMatches [1] [$iIndex];
                                switch ($aMatches [2] [$iIndex])
                                {
                                        case 'h':
                                        case 'H':
                                                $nThisDelay *= 60; // drop through

                                        case 'm':
                                        case 'M':
                                                $nThisDelay *= 60;
                                                break;
                                }

                                $sEvaluationIdent = str_replace ($sMatch, '', $sEvaluationIdent);
                                $aOptions ['Delay'] += $nThisDelay;
                        }

                        if (strpos ($sEvaluationIdent, 'out') !== false)
                        {
                                $sEvaluationIdent = str_replace ('out', '', $sEvaluationIdent);
                                $aOptions ['Buffering'] = false ;
                        }

                        if ($sEvaluationIdent != '')
                        {
                                /** Some weird fucked up prefix, ignore the line. **/
                                return false;
                        }
                }

                if ($aOptions ['Delay'] > 0)
                {
                        Timer :: create (function () use ($pBot, $sDestination, $aOptions)
                        {
                                $pModules = ModuleManager :: getInstance ();
                                if (isset ($pModules ['Evaluation']))
                                {
                                        $pModules ['Evaluation'] -> doEvaluation ($pBot, $sDestination, $aOptions);
                                }

                        }, $aOptions ['Delay'] * 1000, Timer :: TIMEOUT);

                        return true;
                }

                $this -> doEvaluation ($pBot, $sDestination, $aOptions);
                return true;
        }

        /**
         * This function will immediatly execute the evaluation that we're doing
         * right now. Evaluation delays are already handled by the parser.
         *
         * @param Bot $pBot Bot that should handle the evaluation.
         * @param string $sDestination Where should the output be redirected?
         * @param array $aOptions Array with the parsed options for this evaluation.
         */

        public function doEvaluation (Bot $pBot, $sDestination, $aOptions)
        {
                ob_start ();
                ErrorExceptionHandler :: $Source = $sDestination ;

                switch ($aOptions ['Type'])
                {
                        case 'PHP':
                        {
                                eval ($aOptions ['Operation']);
                                break;
                        }

                        case 'EXEC':
                        {
                                echo shell_exec ($aOptions ['Operation']);
                                break;
                        }

                        case 'SQL':
                        {
                                $pDatabase = Database :: getInstance ();
                                if ($pDatabase == null || $pDatabase -> connect_error)
                                {
                                        echo '4* Database Error: Could not connect to database';

                                        if ($pDatabase != null)
                                        {
                                                echo ': "' . $pDatabase -> connect_error . '"';
                                        }
                                        echo '.';

                                        break ;
                                }

                                $pResult = $pDatabase -> query ($aOptions ['Operation']);

                                if ($pResult === false)
                                {
                                        echo '4* Database Error: ' . $pDatabase -> error;
                                }
                                else if ($pResult -> num_rows == 0)
                                {
                                        echo '10* No rows.';
                                }
                                else
                                {
                                        $aFields = $aFieldLen = array ();

                                        foreach ($pResult -> fetch_fields () as $pField)
                                        {
                                                $aFields [$pField -> name]   = array ($pField -> name);
                                                $aFieldLen [$pField -> name] = strlen ($pField -> name);
                                        }

                                        while (($aRow = $pResult -> fetch_assoc ()) != null)
                                        {
                                                foreach ($aRow as $sField => $mValue)
                                                {
                                                        if ($mValue == null)
                                                        {
                                                                $mValue = 'NULL';
                                                        }

                                                        $aFields [$sField][] = $mValue;
                                                        $aFieldLen [$sField] = max ($aFieldLen [$sField], strlen ((string) $mValue));
                                                }
                                        }

                                        // Output is imminent.
                                        for ($i = 0; $i <= $pResult -> num_rows; $i ++)
                                        {
                                                // 1 extra loop for the header.
                                                foreach (array_keys ($aFields) as $sField)
                                                {
                                                        echo sprintf ('| ' . ($i == 0 ? '' : '') . '%\'' . chr (160) . '-' . $aFieldLen [$sField] . 's'.($i == 0 ? '' : '') . ' ', $aFields [$sField][$i]);
                                                }
                                                echo '|' . PHP_EOL;
                                        }
                                }

                                break ;
                        }
                }

                $aOutput = explode ("\n", trim (ob_get_clean ()));
                if (count ($aOutput) > $aOptions ['MaxLines'] + 1 && $aOptions ['Buffering'] == true)
                {
                        $nSize   = count ($aOutput);
                        $aOutput = array_slice ($aOutput, 0, $aOptions ['MaxLines']);
                        $aOutput [] = '10* Displayed ' . $aOptions ['MaxLines'] . ' out of ' . $nSize . ' lines.';
                }

                if (isset ($pBot) && $pBot instanceof Bot)
                {
                        foreach ($aOutput as $sLine)
                        {
                                $pBot -> send ('PRIVMSG ' . $sDestination . ' :' . trim ($sLine), false);
                        }
                }
        }

        /**
         * This function will try to confirm the identity of this logged in
         * owner, who has no hostname cached. Could in theory be a performance
         * penalty.
         *
         * @param Bot $pBot Bot that this message is incoming on.
         * @param array $aOwnerInfo Information about the owner to check.
         * @return boolean
         */

        private function confirmIdentity (Bot $pBot, & $aOwnerInfo)
        {
                if (($aOwnerInfo ['Nickname'] == strtolower ($pBot -> In -> Nickname) || $aOwnerInfo ['Nickname'] == '*') &&
                    ($aOwnerInfo ['Username'] == strtolower ($pBot -> In -> Username) || $aOwnerInfo ['Username'] == '*') &&
                    ($aOwnerInfo ['Hostname'] == strtolower ($pBot -> In -> Hostname) || $aOwnerInfo ['Hostname'] == '*'))
                {
                        $aOwnerInfo ['CachedUser'] = $pBot -> In -> User;
                        return true;
                }

                return false;
        }
}
