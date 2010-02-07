<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

class openpneWebInstallTask extends openpneInstallTask
{
  protected function configure()
  {
    $this->namespace        = 'openpne';
    $this->name             = 'webInstall';

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', null),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
    ));

    $this->addOption('dbms', null, sfCommandOption::PARAMETER_REQUIRED, 'The dbms', 'mysql');
    $this->addOption('username', null, sfCommandOption::PARAMETER_REQUIRED, 'The dbms username', null);
    $this->addOption('password', null, sfCommandOption::PARAMETER_OPTIONAL, 'The dbms user password', '');
    $this->addOption('hostname', null, sfCommandOption::PARAMETER_REQUIRED, 'The dbms hostname', 'localhost');
    $this->addOption('port', null, sfCommandOption::PARAMETER_OPTIONAL, 'The dbms port', '');
    $this->addOption('dbname', null, sfCommandOption::PARAMETER_REQUIRED, 'The database name', null);
    $this->addOption('sock', null, sfCommandOption::PARAMETER_OPTIONAL, 'The socket', null);

    $this->briefDescription = 'Install OpenPNE for web';
    $this->detailedDescription = <<<EOF
The [openpne:webInstall|INFO] task installs and configures OpenPNE.
Call it with:

  [./symfony openpne:webInstall|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $this->doInstall(
      $options['dbms'],
      $options['username'],
      $options['password'],
      $options['hostname'],
      $options['port'],
      $options['dbname'],
      $options['sock'],
      $options
//      array('application' => null, 'env' => 'prod')
    );

    if ($this->params['dbms'] === 'sqlite')
    {
      $this->getFilesystem()->chmod($dbname, 0666);
    }

    $this->publishAssets();

    // _PEAR_call_destructors() causes an E_STRICT error
    error_reporting(error_reporting() & ~E_STRICT);

    $this->logSection('installer', 'installation is completed!');
  }

  protected function doInstall($dbms, $username, $password, $hostname, $port, $dbname, $sock, $options)
  {
    @parent::fixPerms();
    @parent::clearCache();
    parent::configureDatabase($dbms, $username, $password, $hostname, $port, $dbname, $sock, $options);
    self::buildDb($options);
  }

  protected function buildDb($options)
  {
    $tmpdir = sfConfig::get('sf_data_dir').'/fixtures_tmp';
    $this->getFilesystem()->mkdirs($tmpdir);
    $this->getFilesystem()->remove(sfFinder::type('file')->in(array($tmpdir)));

    $pluginDirs = sfFinder::type('dir')->name('data')->in(sfFinder::type('dir')->name('op*Plugin')->maxdepth(1)->in(sfConfig::get('sf_plugins_dir')));
    $fixturesDirs = sfFinder::type('dir')->name('fixtures')
      ->prune('migrations', 'upgrade')
      ->in(array_merge(array(sfConfig::get('sf_data_dir')), $this->configuration->getPluginSubPaths('/data'), $pluginDirs));
    $i = 0;
    foreach ($fixturesDirs as $fixturesDir)
    {
      $files = sfFinder::type('file')->name('*.yml')->sort_by_name()->in(array($fixturesDir));

      foreach ($files as $file)
      {
        $this->getFilesystem()->copy($file, $tmpdir.'/'.sprintf('%03d_%s_%s.yml', $i, basename($file, '.yml'), md5(uniqid(rand(), true))));
      }
      $i++;
    }

    $task = new sfDoctrineBuildTask($this->dispatcher, $this->formatter);
    $task->setCommandApplication($this->commandApplication);
    $task->setConfiguration($this->configuration);
    $task->run(array(), array(
      'no-confirmation' => true,
      'db'              => true,
      'model'           => false,
      'forms'           => false,
      'filters'         => false,
      'sql'             => true,
      'and-load'        => $tmpdir,
      'application'     => $options['application'],
      'env'             => $options['env'],
    ));

    $this->getFilesystem()->remove(sfFinder::type('file')->in(array($tmpdir)));
    $this->getFilesystem()->remove($tmpdir);
  }
}
