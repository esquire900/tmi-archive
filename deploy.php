<?php

namespace Deployer;

use Exception;

require 'recipe/laravel.php';

set('github_token', function () {
    $envToken = getenv('GITHUB_TOKEN');
    if($envToken){
        return $envToken;
    }
    $env = is_file('.env') ? parse_ini_file('.env') : [];
    $localEnvToken = $env['GITHUB_TOKEN'] ?? null;
    if($localEnvToken){
        return $localEnvToken;
    }
    throw new Exception("Github token needs to be set in env");
});

set('github_user', function () {
    $envUser = getenv('GITHUB_USER');
    if($envUser){
        return $envUser;
    }
    $env = is_file('.env') ? parse_ini_file('.env') : [];
    $localEnvUser = $env['GITHUB_USER'] ?? null;
    if($localEnvUser){
        return $localEnvUser;
    }
    throw new Exception("Github user needs to be set in env");
});

set('repository', 'https://{{github_user}}:{{github_token}}@github.com/esquire900/tmi-archive.git');

task('deploy:composer_token', function () {
    run('{{bin/composer}} config -g github-oauth.github.com {{github_token}}');
});
set('keep_releases', 5);

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);
date_default_timezone_set("Europe/Amsterdam");

// Hosts
host('plesk')
    ->set('remote_user', 'tmi-archive')
    ->set('deploy_path', '~/httpdocs')
    ->set('branch', 'master')
    ->setTag('production')
    ->set('bin/composer', function () {
        return '{{bin/php}} /usr/lib/plesk-9.0/composer.phar';
    })
    ->set('bin/php', function () {
        return '/opt/plesk/php/8.4/bin/php';
    });

// Hooks

after('deploy:failed', 'deploy:unlock');


task('artisan_cache', function () {
    artisan('config:cache')();
    artisan('route:cache')();
    artisan('view:cache')();
    artisan('icons:cache')();
    artisan('event:cache')();
    artisan('optimize')();
    artisan('filament:cache-components')();
});

task('deploy', [
    'deploy:prepare',
    'deploy:composer_token',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:migrate',
    'artisan_cache',
    'deploy:publish',
    'deploy:cleanup'
]);

