<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Redis;
use Exception;

class RedisViewerCommand extends Command
{
    protected static $defaultName = 'app:redis:view';

    protected function configure()
    {
        $this->setDescription('檢視 Redis 快取內容');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $redis = new \Redis();
            if (!@$redis->connect('redis', 6379)) {
                throw new \Exception('無法連接到 Redis 服務器');
            }
            
            $io->success('成功連接到 Redis');
            $keys = $redis->keys('*');
            
            if (empty($keys)) {
                $io->warning('Redis 中沒有資料');
                return Command::SUCCESS;
            }

            $io->title('Redis 快取內容');
            foreach ($keys as $key) {
                $io->writeln("Key: $key = " . $redis->get($key));
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}