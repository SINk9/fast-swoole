<?php
/**
 * @Author: sink
 * @Date:   2019-08-05 14:35:15
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 10:14:24
 */

namespace Server\Console;


use App\Test;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestCmd extends Command
{

    /**
     * StartCmd constructor.
     * @param null $name
     * @throws \Noodlehaus\Exception\EmptyDirectoryException
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('test')->setDescription("Test server");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $test = new Test();
        $test->start();
        return 1;
    }


}
