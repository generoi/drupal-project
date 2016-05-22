<?php

/**
 * @file
 * Contains \GeneroDrupalProject\composer\GeneroScriptHandler.
 */

namespace GeneroDrupalProject\composer;

use Composer\Script\Event;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Filesystem\Filesystem;

class GeneroScriptHandler {

  public static function vagrantRsync(Event $event) {
    $machine_id = self::getVagrantMachineId();
    if (!$machine_id || !self::commandExists('vagrant', $event)) {
      return;
    }
    if (!self::isVmRunning($machine_id, $event)) {
      return;
    }

    // Execute vagrant rsync.
    $event->getIO()->write("Execute vagrant rsync");
    $executor = new ProcessExecutor($event->getIO());
    $output = NULL;
    $executor->execute('vagrant rsync', $output);
  }

  protected static function getVagrantMachineId() {
    $machine_id_files = glob(getcwd() . '/.vagrant/machines/*/virtualbox/id');
    if (empty($machine_id_files)) {
      return FALSE;
    }
    return file_get_contents(reset($machine_id_files));
  }

  protected static function isVmRunning($machine_id, $event) {
    $executor = new ProcessExecutor($event->getIO());
    $output = NULL;
    $executor->execute('VBoxManage list runningvms', $output);
    return preg_match('/^"[^"]+" \{' . $machine_id . '\}$/', $output);
  }

  protected static function commandExists($command, $event) {
    $executor = new ProcessExecutor($event->getIO());

    $binary = (PHP_OS === 'WINNT') ? "$command.exe" : $command;
    $find = (PHP_OS === 'WINNT') ? 'where' : 'which';

    $cmd = sprintf('%s %s', escapeshellarg($find), escapeshellarg($command));
    $output = NULL;

    return ($executor->execute($cmd, $output) === 0);
  }
}
