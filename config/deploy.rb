package = JSON.parse(File.read('package.json'))

set :application,    package['name']
set :repo_url,       package['repository']['url']
set :branch,         package['config']['branch'] || 'master'
set :theme,          package['config']['theme']

# Root directory where backups will be placed.
set :backup_dir,     -> { "#{fetch(:deploy_to)}/backup" }
# Backup directories, currently only DB is suppored by drush.rake
set :backup_dirs,    %w[db]

# Debugging
set :log_level,      :info
set :pty,            true

# Symlink the following paths from outside the git repository
set :linked_files,   fetch(:linked_files, []).push('.env', 'web/sites/default/settings.local.php')
set :linked_dirs,    fetch(:linked_dirs, []).push('web/sites/default/files')

# Rsync the locally compiled assets.
set :assets_compile, 'npm run-script build'
set :assets_output,  package['config']['assets']

# Flags used by logs-tasks
set :tail_options,   '-n 100 -f'
set :rsync_options,  '--recursive --times --compress --human-readable --progress'

# Other software options.
set :drush_cmd,               'drush'
set :drush_sql_dump_options,  '--structure-tables-list=cache,cache_*,history,search_*,sessions,watchdog --gzip'
set :varnish_cmd,             '/usr/bin/varnishadm -S /etc/varnish/secret'
set :varnish_address,         '127.0.0.1:6082'
set :varnish_ban_pattern,     'req.url ~ ^/'

# Slackistrano (change to true)
set :slack_run_starting,      -> { false }
set :slack_run_finishing,     -> { false }
set :slack_run_failed,        -> { false }
# Add an incoming webhook at https://<team>.slack.com/services/new/incoming-webhook
# set :slack_webhook, 'https://hooks.slack.com/services/XXX/XXX/XXX'

namespace :deploy do
  after :restart, :cache_clear do end

  before :starting, :check do
    invoke 'deploy:check:pushed'
    invoke 'deploy:check:assets'
    invoke 'deploy:check:sshagent'
  end

  before :updated, 'composer:install'

  after :updated, :drupal_online do
    invoke 'assets:push'
    invoke 'drush:site_offline'
    invoke 'drush:backupdb' if fetch(:stage) == :production
    invoke 'cache:apc' if fetch(:stage) == :production
    invoke 'cache:all'
    invoke 'drush:updatedb'
    invoke 'drush:site_online'
    # invoke 'cache:varnish' if fetch(:stage) == :production
  end

  after :reverted, :cache_clear do
    invoke 'cache:apc' if fetch(:stage) == :production
    invoke 'cache:all'
    # invoke 'cache:varnish' if fetch(:stage) == :production
  end

end
