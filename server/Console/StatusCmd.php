<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 21:44:12
 */

namespace Server\Console;


use Noodlehaus\Config;
use Server\Ports\PortManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCmd extends Command
{
    protected $config;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->config = new Config(CONFIG_DIR);
    }

    protected function configure()
    {
        $this->setName('status')->setDescription("Server Status");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $server_name = $this->config['name'] ?? 'demaxiya~';
        $master_pid = exec("ps -ef | grep $server_name-Master | grep -v 'grep ' | awk '{print $2}'");
        if(empty($master_pid)){
            $master_pid_path = $this->config->get('server.set.pid_file');
            if (file_exists($master_pid_path)) {
                $master_pid  = explode("\n", file_get_contents($master_pid_path));
                $master_pid = $master_pid[0];
            }
        }

        $io->title('WELCOME START SWOOLE DISTRIBUTED, HAVE FUN!');
        $io->table(
            [
                "System",
                "PHP Version",
                "Swoole Version",
                "Worker Num",
                "Task Num"
            ],
            [
                [
                    PHP_OS,
                    PHP_VERSION,
                    SWOOLE_VERSION,
                    $this->config->get('server.set.worker_num', 0),
                    $this->config->get('server.set.task_worker_num', 0)
                ]
            ]
        );
        $io->section('Port information');
        $ports = $this->config['ports'];
        $show = [];
        foreach ($ports as $key => $value) {
            $middleware = '';
            foreach ($value['middlewares'] ?? [] as $m) {
                $middleware .= '[' . $m . ']';
            }
            $show[] = [
                PortManager::getTypeName($value['socket_type']),
                $value['socket_name'],
                $value['socket_port'],
                $value['pack_tool'] ?? PortManager::getTypeName($value['socket_type']),
                $middleware
            ];
        }
        $io->table(
            ['S_TYPE', 'S_NAME', 'S_PORT', 'S_PACK', 'S_MIDD'],
            $show
        );
        if (!empty($master_pid)) {
            $io->note("$server_name server already running");
        } else {
            $io->note("$server_name server not run");
        }
        $this->info(sprintf('共消耗内存: %sM', memory_get_peak_usage() / 1024 / 1024));
    }
}
