<?php

namespace Deployer;

use Exception;

require 'recipe/laravel.php';

set('github_token', function () {
    $env = parse_ini_file('.env');
    $envToken = getenv('GITHUB_TOKEN');
    if($envToken){
        return $envToken;
    }
    $localEnvToken = $env['GITHUB_TOKEN'];
    if($localEnvToken){
        return $localEnvToken;
    }
    throw new Exception("Github token needs to be set in env");
});

set('github_user', function () {
    $env = parse_ini_file('.env');
    $envUser = getenv('GITHUB_USER');
    if($envUser){
        return $envUser;
    }
    $localEnvUser = $env['GITHUB_USER'];
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
    ->set('remote_user', 'aiontheball')
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

task('deploy:npm_build', function () {
    run('cd {{release_path}} && npm ci');
    run("cd {{release_path}} && npm run build");
});

task('deploy:restart_worker', function () {
    // use visudo to edit /etc/sudoers
    // aiontheball ALL = (root) NOPASSWD: /usr/bin/supervisorctl restart aiontheball-horizon\:aiontheball-horizon_00
//    run('sudo supervisorctl restart aiontheball-horizon:aiontheball-horizon_00'); // this is a hard restart!
    artisan('horizon:terminate')();
});

task('artisan_cache', function () {
    artisan('config:cache')();
    artisan('route:cache')();
    artisan('view:cache')();
    artisan('icon:cache')();
    artisan('event:cache')();
    artisan('optimize')();
    artisan('filament:cache-components')();
});

task('download_duckdb', function () {
    artisan('laravel-duckdb:download-cli')();
});

task('deploy:opcache_reset', function () {
    artisan('opcache:reset')();
});

task('deploy', [
    'deploy:prepare',
    'deploy:composer_token',
    'deploy:vendors',
    'deploy:npm_build',
    'artisan:storage:link',
    'artisan:migrate',
    'artisan_cache',
    'deploy:publish',
    'deploy:opcache_reset',
    'deploy:restart_worker',
    'deploy:cleanup'
]);

