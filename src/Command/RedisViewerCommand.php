<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use \Redis;
use \Exception;

class RedisViewerCommand extends Command
{
    protected static $defaultName = 'app:redis:view';
    private Redis $redis;

    public function __construct()
    {
        parent::__construct();
        $this->redis = new Redis();
    }

    protected function configure()
    {
        $this->setDescription('檢視 Redis 快取內容');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            if (!$this->redis->connect('redis', 6379)) {
                throw new Exception('Redis 連線失敗');
            }
            
            // 檢查 Redis 連線狀態
            $io->note('檢查 Redis 連線狀態...');
            $info = $this->redis->info();
            $io->writeln("Redis 版本: " . $info['redis_version']);
            
            $keys = $this->redis->keys('*');
            
            if (empty($keys)) {
                $io->warning([
                    'Redis 中沒有資料',
                    '資料庫索引: ' . $this->redis->select(0),
                    '已用記憶體: ' . $info['used_memory_human']
                ]);
                return Command::SUCCESS;
            }

            $io->title('Redis 快取內容');
            
            foreach ($keys as $key) {
                $this->displayKeyInfo($io, $key);
            }
        } catch (Exception $e) {
            $io->error('Redis 錯誤: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayKeyInfo(SymfonyStyle $io, string $key)
    {
        $type = $this->redis->type($key);
        $value = $this->getValueByType($key, $type);
        
        $io->section("Key: $key");
        $io->table(
            ['Type', 'Value'],
            [[$type, print_r($value, true)]]
        );
    }

    private function getValueByType(string $key, int $type)
    {
        switch($type) {
            case Redis::REDIS_STRING:
                return $this->redis->get($key);
            case Redis::REDIS_HASH:
                return $this->redis->hGetAll($key);
            case Redis::REDIS_LIST:
                return $this->redis->lRange($key, 0, -1);
            case Redis::REDIS_SET:
                return $this->redis->sMembers($key);
            default:
                return '不支援的資料類型';
        }
    }
}