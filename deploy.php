<?php

namespace Deployer;

require_once 'recipe/common.php';

date_default_timezone_set("UTC");

set('keep_releases', 2);
set('shared_dirs', ['runtime', '__pycache__']);
set('writable_dirs', ['runtime', '../static', 'static/mp3/']);
set('writable_mode', 'chmod');
set('writable_use_sudo', false);
set('writable_chmod_mode', '777');

set('python', '/usr/bin/python3');

host('plesknew')
   ->stage('production')
   ->set('deploy_path', '~/httpdocs')
   ->user('tmi-archive');

// server has pull rights

set('github_token', function () {
    return getenv('GITHUB_TOKEN');
});

set('repository', 'https://esquire900:{{github_token}}@github.com/esquire900/tmi-archive.git');


task('deploy:install_poetry', function () {
    run('cd {{release_path}} && ~/.local/bin/poetry install');
})->desc('custom settings shit enzo');

task('deploy:custom_stuff', function () {
    upload('./.env.live', '{{release_path}}/../../.env');
})->desc('custom settings shit enzo');

task('deploy:run_migrations', function () {
    $python_loc = '{{release_path}}/.venv/bin/python';
    run($python_loc . " {{release_path}}/manage.py migrate");
    run("cd {{release_path}} && " . $python_loc . " manage.py collectstatic --noinput");
    run("cp /var/www/vhosts/tmi-archive.com/httpdocs/current/static/* /var/www/vhosts/tmi-archive.com/static.tmi-archive.com/static -r");

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
   'deploy:install_poetry',
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
