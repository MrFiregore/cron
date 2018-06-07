<?php

    namespace Firegore\Cron\CronObject;

    use DateTime;


    /**
     * Class Cron.
     *
     *
     * @method int      getId()     Unique identifier for this cron job.
     * @method string   getName()     Unique identifier for this cron job.
     * @method string   getCommand()     Unique identifier for this cron job.
     * @method string   getMinute()     Unique identifier for this cron job.
     * @method string   getHour()     Unique identifier for this cron job.
     * @method string   getMonth()     Unique identifier for this cron job.
     * @method string   getMonthDay()     Unique identifier for this cron job.
     * @method string   getWeekDay()     Unique identifier for this cron job.
     * @method bool     getIsActive()     Unique identifier for this cron job.
     * @method int      getPriority()     Unique identifier for this cron job.
     * @method bool     getStandalone()     Unique identifier for this cron job.
     * @method int      getOptions()     Unique identifier for this cron job.
     * @method string   getLastError()     Unique identifier for this cron job.
     * @method DateTime getLastErrorDate()     Unique identifier for this cron job.
     * @method DateTime getLastTrigger()     Unique identifier for this cron job.
     * @method DateTime getLastSuccess()     Unique identifier for this cron job.
     * @method DateTime getLastFail()     Unique identifier for this cron job.
     * @method DateTime getCreatedAt()     Unique identifier for this cron job.
     * @method DateTime getUpdatedAt()     Unique identifier for this cron job.
     */
    class Cron extends BaseObject
    {
        public function table ()
        {
            return "cron";
        }

        public function pk ()
        {
            return "id";
        }

        /**
         * {@inheritdoc}
         */
        public function relations ()
        {
            return [
                "last_error_date" => DateTime::class,
                "last_trigger"    => DateTime::class,
                "last_success"    => DateTime::class,
                "last_fail"       => DateTime::class,
                "created_at"      => DateTime::class,
                "updated_at"      => DateTime::class,
            ];
        }
    }
