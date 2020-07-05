<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 18:45:59
 */

namespace Server\Console;


use Noodlehaus\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReloadCmd extends Command
{
    protected $config;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->config = new Config(CONFIG_DIR);
    }

    protected function configure()
    {
        $this->setName('reload')->setDescription("Reload server");
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new SymfonyStyle($input, $output);
        $server_name = $this->config['name'] ?? 'demaxiya~';

        $master_pid = exec("ps -ef | grep $server_name-Master | grep -v 'grep ' | awk '{print $2}'");
        $manager_pid = exec("ps -ef | grep $server_name-Manager | grep -v 'grep ' | awk '{print $2}'");
        if (empty($master_pid)) {
            $io->warning("server $server_name not run");
            return;
        }
        posix_kill($manager_pid, SIGUSR1);
        $io->success("server $server_name reload");
    }
}
