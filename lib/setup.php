<?php
/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * openpne3 setup package
 *
 * @package    OpenPNE
 * @subpackage opWebInstallerPlugin
 * @author     Shinichi Urabe <urabe@tejimaya.com>
 */
//error_reporting(0);

$requests = new sfWebRequest($this->dispatcher);
require_once(dirname(__FILE__).'/form/opOpenPNESetupForm.class.php');
$form = new opOpenPNESetupForm();
$path = dirname(__FILE__).'/../apps/pc_frontend/modules/setup/templates/';

if ($params = $requests->getParameter($form->getName()))
{
  $form->bind($params);
  if (!$form->isValid())
  {
    require_once($path.'indexSuccess.php');
  }
  else
  {
    require_once($path.'progressSuccess.php');
    chdir(sfConfig::get('sf_root_dir'));
    $sfbin = sfConfig::get('sf_root_dir').'/symfony';
    $options = ' --dbms='.$params['DBMS'].' --username='.$params['username'].' --password='.$params['password'].' --hostname='.$params['hostname'].' --port='.$params['port'].' --dbname='.$params['database'].' --sock='.$params['socket'];
    passthru($sfbin.' openpne:webInstall'.$options, $result);
    echo $result;
  }
}
else
{
  require_once($path.'indexSuccess.php');
}
exit;

function cliLoader($dispatcher)
{
  include_once(dirname(__FILE__).'/vendor/pake/bin/pake.php');

  $pake = pakeApp::get_instance();
  try
  {
    $ret = $pake->run(dirname(__FILE__).'/vendor/pake/pakefile.php', 'clear-cache');
  }
  catch (pakeException $e)
  {
    print "<strong>ERROR</strong>: ".$e->getMessage();
  }
}
