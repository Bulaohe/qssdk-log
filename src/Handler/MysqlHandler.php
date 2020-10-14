<?php declare(strict_types=1);

namespace Ssdk\Oalog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PDO;

/**
 * Monolog Mysql Handler
 * 
 * @author Simon Quan
 *
 */
class MysqlHandler extends AbstractProcessingHandler {
    
    protected $pdo;
    protected $tableName;
    protected $pdoStatement;
    protected $initialized = false;
    
    /**
     * Constructor
     *
     * @param PDO $pdo PDO Connector for the database
     * @param bool $tableName Table in the database to store the logs in
     * @param bool|int $level Debug level which this handler should store
     * @param bool $bubble
     */
    public function __construct(PDO $pdo = null, string $tableName, int $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName . '_' . date('Ym');
        parent::__construct($level, $bubble);
    }
    
    /**
     * Initializes this handler by creating the table if it not exists
     */
    private function initialize()
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS `' . $this->tableName . '` ' . ' ( 
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `message` varchar(255) NOT NULL,
                  `context` text NOT NULL,
                  `extra` text NOT NULL,
                  `level` int(10) unsigned NOT NULL,
                  `level_name` varchar(31) NOT NULL,
                  `channel` varchar(255) NOT NULL,
                  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `level` (`level`),
                  KEY `level_name` (`level_name`),
                  KEY `channel` (`channel`),
                  KEY `datetime` (`datetime`)
                ) ENGINE=InnoDB AUTO_INCREMENT=84230 DEFAULT CHARSET=utf8mb4'
        );
        
        $this->initialized = true;
    }
    
    /**
     * Insert the data to the logger table
     *
     * @param  array $data
     * @return bool
     */
    private function insert(array $data)
    {
        if (! $this->initialized) {
            $this->initialize();
        }
        
        if ($this->pdoStatement === null) {
            $sql = "INSERT INTO {$this->tableName} (message, context, extra, level, level_name, channel, datetime)";
            $sql .= " VALUES (:message, :context, :extra, :level, :level_name, :channel, :datetime)";
            $this->pdoStatement = $this->pdo->prepare($sql);
        }
        return $this->pdoStatement->execute($data);
    }
    
    /**
     * Writes the record down to the log
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record):void
    {
        $deal = true;
        
        if (! isset($record['message'])) {
            $deal = false;
        }
        if (! isset($record['level'])) {
            $deal = false;
        }
        if (! isset($record['level_name'])) {
            $deal = false;
        }
        if (! isset($record['channel'])) {
            $deal = false;
        }
        if (! isset($record['datetime']) && ! ($record['datetime'] instanceof \DateTimeImmutable)) {
            $deal = false;
        }
        
        if (! isset($record['context'])) {
            $record['context'] = [];
        }
        if (! isset($record['extra'])) {
            $record['extra'] = [];
        }
        
        if ($deal) {
            $this->insert([
                ':message' => $record['message'],
                ':context' => json_encode($record['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ':extra' => json_encode($record['extra'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ':level' => $record['level'],
                ':level_name' => $record['level_name'],
                ':channel' => $record['channel'],
                ':datetime' => $record['datetime']->format('Y-m-d H:i:s')
            ]);
        }
    }
}