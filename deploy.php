<?php

namespace Deployer;

require_once 'recipe/common.php';

date_default_timezone_set("UTC");

set('keep_releases', 2);
set('shared_dirs', ['runtime', '__pycache__']);
set('writable_dirs', ['runtime', '../static']);
set('writable_mode', 'chmod');
set('writable_use_sudo', false);
set('writable_chmod_mode', '777');
set('env', [
    # deploys venv in local dir, so every release gets its own environment
    'PIPENV_VENV_IN_PROJECT' => 'true',
]);

host('46.4.74.147')
    ->stage('production')
    ->set('deploy_path', '~/httpdocs')
    ->user('tmi-archive');

// server has pull rights

set('github_token', function () {
    return getenv('GITHUB_TOKEN');
});

set('repository', 'https://esquire900:{{github_token}}@github.com/esquire900/tmi-archive.git');


task('deploy:install_pipenv', function () {
    run('cd {{release_path}} && export PIPENV_VENV_IN_PROJECT=1 && /usr/local/bin/pipenv install --python /usr/bin/python');
})->desc('custom settings shit enzo');

task('deploy:custom_stuff', function () {
    upload('tmi_archive/settings_live.py', '{{release_path}}/tmi-archive/settings_local.py');
})->desc('custom settings shit enzo');

task('deploy:run_migrations', function () {
    $python_loc = '{{release_path}}/.venv/bin/python';
    run($python_loc . " {{release_path}}/manage.py migrate");
    run("cd {{release_path}} && " . $python_loc . " manage.py collectstatic --noinput");
})->desc('migrated');

task('deploy:restart', function () {
    run("touch {{release_path}}/../../restart.txt");
})->desc('Restarted');

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:install_pipenv',
    'deploy:custom_stuff',
    'deploy:run_migrations',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'deploy:restart',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');
after('deploy', 'success');
