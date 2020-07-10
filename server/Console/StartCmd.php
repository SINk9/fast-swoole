<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-09 14:25:57
 */

namespace Server\Console;


use App\AppServer;
use Server\Ports\PortManager;
use Noodlehaus\Config;
use Server\Start;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StartCmd extends Command
{
    protected $config;

    /**
     * StartCmd constructor.
     * @param null $name
     * @throws \Noodlehaus\Exception\EmptyDirectoryException
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->config = new Config(CONFIG_DIR);
    }

    protected function configure()
    {
        $this->setName('start')->setDescription("Start server");
        $this->addOption('daemonize', "d", InputOption::VALUE_NONE, 'Who do you want daemonize?');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Who do you want debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //开始
        $io = new SymfonyStyle($input, $output);
        $server_name = $this->config['name'] ?? 'swoole';
        $master_pid = exec("ps -ef | grep $server_name-Master | grep -v 'grep ' | awk '{print $2}'");
        if (!empty($master_pid)) {
            $io->warning("$server_name server already running");
            return 1;
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

        //是否是守护进程
        if ($input->getOption('daemonize')) {
            Start::setDaemonize();
            $io->note("Input php Start.php stop to quit. Start success.");
        } else {
            $io->note("Press Ctrl-C to quit. Start success.");
        }
        $server = new AppServer();
        //是否Debug
        if ($input->getOption('debug')) {
            Start::setDebug(true);
            $io->note("Set Debug Start success.");
        }
        $server->start();
        return 1;
    }
}
