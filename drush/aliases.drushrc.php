<?php

/**
 * Use Drupals autoloader to get required yaml library.
 */
$autoload = require __DIR__ . '/../vendor/autoload.php';

/**
 * Read the defined drush environments from configuration file.
 */
$environments_file = dirname(__DIR__) . '/config/environments.yml';
if (!file_exists($environments_file)) {
  drush_set_error(dt('Could not find required environments.yml in @file', array('@file' => $environments_file)));
  exit(1);
}

$contents = file_get_contents($environments_file);
$environments = Symfony\Component\Yaml\Yaml::parse($contents);

/**
 * Ensure all configuration values are present or else exit.
 */
$required = [
  'DRUSH_DEVELOPMENT_URI',
  'DRUSH_DEVELOPMENT_ROOT',
  'DRUSH_DEVELOPMENT_HOST',
  'DRUSH_DEVELOPMENT_USER',
  'DRUSH_PRODUCTION_URI',
  'DRUSH_PRODUCTION_ROOT',
  'DRUSH_PRODUCTION_HOST',
  'DRUSH_PRODUCTION_USER',
  'DRUSH_STAGING_URI',
  'DRUSH_STAGING_ROOT',
  'DRUSH_STAGING_HOST',
  'DRUSH_STAGING_USER'
];
$missing = array_diff($required, array_keys($environments));
if (!empty($missing)) {
  drush_set_error(dt('The following environment values are missing from environments.yml: @vars', array('@vars' => implode(', ', $missing))));
  exit(1);
}

/**
 * Determine which host we are on.
 */
switch ($environments['DRUSH_ENV']) {
  case 'local':
  case 'production':
  case 'development':
  case 'staging':
    $env = $environments['DRUSH_ENV'];
    break;
  default:
    exec('hostname', $hostname);
    exec('whoami', $whoami);
    if ($hostname[0] == 'minasanor') {
      $env = 'staging';
    }
    elseif ($whoami[0] == 'vagrant') {
      $env = 'development';
    }
    elseif ($whoami[0] == 'deploy') {
      $env = 'production';
    }
    else {
      $env = 'local';
    }
    break;
}

# Tables to exclude data from during sql sync/dump
$structure_tables = array('advagg_*', 'cache', 'cache_*', 'history', 'search_*', 'sessions', 'watchdog', 'webform_submitted_data');
# Directories to exclude during rsync
$rsync_exclude = array('styles', 'js', 'css', 'xmlsitemap', 'ctools', 'languages', 'advagg_css', 'advagg_js', '*.mp3', '*.mp4', '*.wmv', '*.mov', '*.zip', '*.gz', '*.pdf');

$base_options = array(
  'path-aliases' => array(
    '%files' => 'sites/default/files',
  ),
  'command-specific' => array(
    'sql-dump' => array(
      'no-ordered-dump' => TRUE,
      'structure-tables-list' => implode(',', $structure_tables),
    ),
    'core-rsync' => array(
      'verbose' => TRUE,
      'mode' => 'rlpzO',
      'no-perms' => TRUE,
      'exclude-paths' => implode(':', $rsync_exclude),
    ),
  ),
);

// The locally installed virtual machine.
$aliases['dev'] = $base_options + array(
  'uri' => $environments['DRUSH_DEVELOPMENT_URI'],
  'root' => $environments['DRUSH_DEVELOPMENT_ROOT'],

  'target-command-specific' => array(
    'sql-sync' => array(
      # Do not cache the sql-dump file.
      'no-cache' => TRUE,
      # Leverage multiple value inserts to sql 4x faster.
      # @see http://knackforge.com/blog/sivaji/how-make-drush-sql-sync-faster
      'no-ordered-dump' => TRUE,
      'structure-tables-list' => implode(',', $structure_tables),
      # Reset the admin users password.
      'reset-admin-password' => $environments['DRUSH_DEVELOPMENT_ADMIN_PASS'] ?: 'drupal',
      # Obscure user email addresses and reset passwords.
      'sanitize' => TRUE,
      'confirm-sanitizations' => TRUE,
    ),
  ),
);
// Add the vagrant ssh connection if we're on the local machine
if ($env == 'local') {
  exec("ssh-add -l >/dev/null 2>&1", $output, $exit);
  switch ($exit) {
    case '2':
      drush_set_error(dt('No SSH agent running: eval `ssh-agent -s`')); break;
    case '1':
      drush_set_error(dt('The SSH agent has no identities: ssh-add')); break;
  }

  $aliases['dev'] += array(
    'remote-host' => $environments['DRUSH_DEVELOPMENT_HOST'],
    'remote-user' => $environments['DRUSH_DEVELOPMENT_USER'],
    // rsync doesn't expand ~
    'ssh-options' => '-o ForwardAgent=yes -o PasswordAuthentication=no -i ' . $_SERVER['HOME'] . '/.vagrant.d/insecure_private_key',
  );
}


// The staging enviornment available on minasanor.
$aliases['staging'] = $base_options + array(
  'uri' => $environments['DRUSH_STAGING_URI'],
  'root' => $environments['DRUSH_STAGING_ROOT'],
  'remote-host' => $environments['DRUSH_STAGING_HOST'],
  'remote-user' => $environments['DRUSH_STAGING_USER'],
  'ssh-options' => '-o ForwardAgent=yes',

  'target-command-specific' => array(
    'sql-sync' => array(
      'no-cache' => TRUE,
      'no-ordered-dump' => TRUE,
      'structure-tables-list' => implode(',', $structure_tables),
    ),
  ),
);

// The production environment.
$aliases['production'] = $base_options + array(
  'uri' =>         $environments['DRUSH_PRODUCTION_URI'],
  'root' =>        $environments['DRUSH_PRODUCTION_ROOT'],
  'remote-host' => $environments['DRUSH_PRODUCTION_HOST'],
  'remote-user' => $environments['DRUSH_PRODUCTION_USER'],
  'ssh-options' => '-o ForwardAgent=yes',
  // Prevent accidental writes to production environment.
  'target-command-specific' => array(
    'sql-sync' => array('simulate' => TRUE),
    'core-rsync' => array('simulate' => TRUE),
  ),
  'source-command-specific' => array(
    'sql-sync' => array(
      # Do not cache the sql-dump file.
      'no-cache' => TRUE,
      # Leverage multiple value inserts to sql 4x faster.
      # @see http://knackforge.com/blog/sivaji/how-make-drush-sql-sync-faster
      'no-ordered-dump' => TRUE,
      'structure-tables-list' => implode(',', $structure_tables),
    ),
  ),
);

// Only the staging environment has access to the production environment, if
// we're running drush from somewhere else, use the staging environment as a
// proxy.
if ($env != 'staging') {
  $aliases['production']['ssh-options'] = '-o "ProxyCommand '
    . 'ssh ' . $aliases['staging']['remote-user']. '@' . $aliases['staging']['remote-host']
    . ' nc %h %p 2> /dev/null"';
}
