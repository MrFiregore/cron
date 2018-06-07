<?php

    namespace Firegore\Cron;

    use Firegore\Mysql\Sql;
    use Firegore\Cron\CronObject\Cron as CronObject;


    class Cron
    {
        /**
         * @var \Firegore\Mysql\Sql $mysql
         */
        protected static $mysql;
        protected        $db_name      = "cron_db";
        protected        $locked_tasks = [];

        function __construct (
            string $db_name = null,
            string $db_user = null,
            string $db_password = null,
            string $db_host = null,
            string $db_port = null
        ) {
            $this->db_name = $db_name ? $db_name : $this->db_name;
            $this->setMysql(new Sql($db_host, $db_user, $db_password, $this->db_name, $db_port));
            $this->buildDB();


        }


        public function triggerAll ()
        {
            $minute    = (int)date("i");
            $hour      = (int)date("G");
            $month_day = (int)date("j");
            $month     = (int)date('m');
            $week_day  = (int)date('w');

            $jobs = $this->getActiveActions($minute, $hour, $month_day, $month, $week_day);

            foreach ($jobs as $job) {
                $job = new CronObject($job);
                $this->execute($job);
            }
        }

        /**
         * [execute description]
         *
         * @param  CronObject $job [description]
         *
         * @return bool          [description]
         */
        public function execute (CronObject $job)
        {
            $class = "Firegore\\API\\Cron\\Handle" . $job->getCommand();
            if (class_exists($class)) {
                if (!$this->isLocked($job->getId())) {
                    $this->lock($job->getId());
                    $this->eventEmitter->addListener(
                        CronEvent::class, function ($event) use ($job, $class) {
                        $job->setLastTrigger(new DateTime);
                        Log::cron("Executing task: " . $job->getName());
                        try {
                            $handler = new $class($job);
                            $handler->handle($event);
                            $job->setLastSuccess(new DateTime);
                            Log::cron("Finish executing task: " . $job->getName());
                        }
                        catch (\Exception $e) {
                            Log::error("Error executing task: " . $job->getName() . ".\n Error message: " . $e->getMessage() . " code( " . $e->getCode() . " ).\nFile: " . __FILE__ . " -> Line : " . __LINE__);
                            Log::cron("Error executing task: " . $job->getName());
                            $job->setLastError(new DateTime);

                        }

                    }
                    );
                    $this->unlock($job->getId());
                }
            }
        }

        public function getActiveActions ($minute = -1, $hour = -1, $month_day = -1, $month = -1, $week_day = -1)
        {
            $query = "SELECT *
              FROM cron
              WHERE
              is_active = 1
              AND
              minute IN (-1 , $minute)
              AND
              hour IN (-1 , $hour)
              AND
              month_day IN (-1 , $month_day)
              AND
              month IN (-1 , $month)
              AND
              week_day IN (-1 , $week_day)
              ORDER BY standalone ASC, priority DESC
              ";
            return self::getMysql()
                       ->fetchArray(
                           self::getMysql()
                               ->queryMysql($query)
                       );
        }

        /**
         * @param int $task_id
         *
         * @return bool
         */
        public function isLocked ($task_id)
        {
            return (file_exists(__LOCK__ . "{$task_id}.lock"));
        }

        /**
         * Use if you want to
         *
         * @param int $task_id
         */
        public function lock ($task_id)
        {
            $this->locked_tasks[] = $task_id;
            file_put_contents(__LOCK__ . "{$task_id}.lock", 1);
        }

        /**
         * @param int $task_id
         */
        public function unlock ($task_id)
        {
            $file = __LOCK__ . "{$task_id}.lock";
            if (file_exists($file)) {
                unlink($file);
                unset($this->locked_tasks[$task_id]);
            }
        }

        /**
         * @return \Firegore\Mysql\Sql
         */
        public function getMysql ()
        {
            if (!self::$mysql) $this->setMysql();
            return self::$mysql;
        }

        /**
         * @param \Firegore\Mysql\Sql $mysql
         */
        public function setMysql (\Firegore\Mysql\Sql $mysql = null)
        {
            if (is_null($mysql)) {
                $mysql = Sql::getInstance();
            }
            self::$mysql = $mysql;
        }

        protected function buildDB ()
        {
            if (!self::getMysql()
                     ->issetDB($this->db_name)) {
                if (self::getMysql()
                        ->queryMysql("CREATE DATABASE " . $this->db_name)) {

                }
            }
            self::getMysql()
                ->select_db($this->db_name);
            if (!self::getMysql()
                     ->issetTable('cron')) {
                self::getMysql()
                    ->queryMysql(
                        "
                            CREATE TABLE `cron` (
                              `id` int(11) UNSIGNED NOT NULL,
                              `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
                              `command` text COLLATE utf8_unicode_ci NOT NULL,
                              `minute` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-1',
                              `hour` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-1',
                              `month_day` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-1',
                              `month` varchar(24) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-1',
                              `week_day` varchar(14) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-1',
                              `is_active` tinyint(1) DEFAULT '1',
                              `priority` tinyint(4) NOT NULL DEFAULT '5',
                              `standalone` tinyint(1) NOT NULL,
                              `options` text COLLATE utf8_unicode_ci,
                              `last_error` longtext COLLATE utf8_unicode_ci NOT NULL,
                              `last_error_date` datetime NOT NULL,
                              `last_trigger` datetime NOT NULL,
                              `last_success` datetime NOT NULL,
                              `last_fail` datetime NOT NULL,
                              `created_at` datetime NOT NULL,
                              `updated_at` datetime NOT NULL
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
                  "
                    );
                self::getMysql()
                    ->queryMysql('ALTER TABLE `cron` ADD PRIMARY KEY (`id`)');
                self::getMysql()
                    ->queryMysql('ALTER TABLE `cron`  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT');
                self::getMysql()
                    ->queryMysql('COMMIT');
            }
        }
    }
