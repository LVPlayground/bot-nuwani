<?php
// Lost Venturas Playground <http://jcmp.nl/>
// Copyright (c) 2014 The Lost Venturas Playground authors. All rights reserved.
//
// Licensed under the MIT license, a copy of which is available in the LICENSE file.

$basicTranslate = function($format) {
    return function ($parameters) use ($format) {
        foreach ($parameters as $key => $value)
            $format = str_replace('{' . $key . '}', $value, $format);

        return $format;
    };
};

$filters = array(
    // Event invoked when a player joins the server.
    'join' => $basicTranslate('02[{playerId}] 03*** {nickname} joined the game.'),

    // Event invoked when a player leaves the server.
    'part' => $basicTranslate('02[{playerId}] 03*** {nickname} left the game.'),

    // Event invoked when a player types a (public) message in the chatbox.
    'msg' => $basicTranslate('02[{playerId}]07 {nickname}: {message}'),

    // Event invoked when a message has been displayed in-game originating from IRC.
    'ircmsg' => $basicTranslate('02[--]07 {nickname}: {message}'),

    // Event invoked when a player has died.
    // TODO: Handle |reason| properly by translating it to something textual.
    'death' => $basicTranslate('4*** {nickname} has died'),

    // Event invoked when a player has been killed by another player.
    // TODO: Handle |reason| properly by translating it to something textual.
    'kill' => $basicTranslate('4*** {nickname} (Id: {playerId}) has been killed by {killerName} (Id: {killerId})'),
);
