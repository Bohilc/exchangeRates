<?php
/**
 * Created by PhpStorm.
 * User: bohilc
 * Date: 10.03.19
 * Time: 21:13
 */

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCache extends Command
{
    protected static $defaultName = 'app:clean:cache';

    private $cache;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->cache = new Filesystemcache();

        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setDescription('Clear cache')
            ->setHelp('This command clear cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cache->clear();
        $output->writeln("Cache has been cleaned!");
    }
}