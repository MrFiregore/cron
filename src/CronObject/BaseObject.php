<?php

    namespace Firegore\Cron\CronObject;

    use Firegore\Mysql\Sql;
    use Illuminate\Support\Collection;

    /**
     * Class BaseObject.
     */
    abstract class BaseObject extends Collection
    {
        abstract public function table ();

        abstract public function pk ();

        /**
         * Builds collection entity.
         *
         * @param array|mixed $data
         */
        public function __construct ($data = null)
        {
            parent::__construct($this->getRawResult($data));

            $this->mapRelatives();
        }

        public static function getObjectName ()
        {
            return (new \ReflectionClass(get_called_class()))->getShortName();
        }

        /**
         * Property relations.
         *
         * @return array
         */
        abstract public function relations ();

        /**
         * Map property relatives to appropriate objects.
         *
         * @return array|void
         */
        public function mapRelatives ()
        {
            $relations = $this->relations();
            if (empty($relations) || !is_array($relations)) {
                return false;
            }

            $results = $this->all();
            foreach ($results as $key => $data) {
                if (array_key_exists($key, $relations)) {
                    $class = $relations[$key];
                    if (is_array($data)) {
                        $results[$key] = $this->recursiveMapRelatives($class, $data);
                    } else {
                        $results[$key] = new $class($data);
                    }
                }
            }

            return $this->items = $results;
        }

        protected function recursiveMapRelatives ($class, $data)
        {
            if (is_array($data) && array_keys($data) === range(0, count($data) - 1)) {
                $array = [];
                foreach ($data as $item) {
                    $array[] = $this->recursiveMapRelatives($class, $item);
                }
                return new Collection($array);
            } else {
                return new $class($data);
            }
        }

        /**
         * Returns raw response.
         *
         * @return array|mixed
         */
        public function getRawResponse ()
        {
            return $this->items;
        }

        /**
         * Returns raw result.
         *
         * @param $data
         *
         * @return mixed
         */
        public function getRawResult ($data)
        {
            return array_get($data, 'result', $data);
        }

        /**
         * Get Status of request.
         *
         * @return mixed
         */
        public function getStatus ()
        {
            return array_get($this->items, 'ok', false);
        }

        public function updateValue ($prop, $val = null)
        {
            if (!$this->has($prop)) return false;
            if ($this->get($prop) === $val || gettype($this->get($prop)) !== gettype($val)) return false;
            $query =
                "UPDATE " . $this->table() . " SET $prop = " . Sql::getInstance()
                                                                  ->getValidFormat($this->table(), $prop, $val) . " WHERE " . $this->pk() . " = " . Sql::getInstance()
                                                                                                                                                       ->getValidFormat($this->table(), $this->pk(), $this->getId());
            Sql::getInstance()
               ->queryMysql($query);
            return Sql::getInstance()
                      ->affectedRows();
        }

        /**
         * Magic method to get properties dynamically.
         *
         * @param $name
         * @param $arguments
         *
         * @return mixed
         */
        public function __call ($name, $arguments)
        {
            $action = substr($name, 0, 3);
            if (!in_array($action, ['get', 'set'])) {
                return false;
            }
            $property = snake_case(substr($name, 3));
            $response = $this->get($property);
            if ($action === 'set') {
                $value = isset($arguments[0]) ? $arguments[0] : null;
                return $this->updateValue($property, $value);
            }
            return $response;
            // Map relative property to an object
            $relations = $this->relations();
            if (null != $response && isset($relations[$property])) {
                return new $relations[$property]($response);
            }

            return $response;
        }
    }
