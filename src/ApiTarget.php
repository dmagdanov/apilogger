<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace nsu\apilogger;

use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\log\LogRuntimeException;
use yii\log\Target;

/**
 * DbTarget stores log messages in a database table.
 *
 * The database connection is specified by [[db]]. Database schema could be initialized by applying migration:
 *
 * ```
 * yii migrate --migrationPath=@yii/log/migrations/
 * ```
 *
 * If you don't want to use migration and need SQL instead, files for all databases are in migrations directory.
 *
 * You may change the name of the table used to store the data by setting [[logTable]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ApiTarget extends Target
{
    const TYPES = array(
        1 => 'error',
        2 => 'warning',
        3 => 'info',
        4 => 'debug',
        //[5 => '']
    );

    /**
     * Initializes the DbTarget component.
     * This method will initialize the [[db]] property to make sure it refers to a valid DB connection.
     * @throws InvalidConfigException if [[db]] is invalid.
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Stores log messages to DB.
     * Starting from version 2.0.14, this method throws LogRuntimeException in case the log can not be exported.
     * @throws Exception
     * @throws LogRuntimeException
     * @throws \yii\httpclient\Exception
     * @throws InvalidConfigException
     */
    public function export()
    {
        foreach ($this->messages as $message) {

            list($text, $level, $category, $timestamp) = $message;

            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Exception || $text instanceof \Throwable) {
                    $text = (string) $text;
                } else {
                    $text = VarDumper::export($text);
                }
            }

            $data[] = [
                'type' => self::TYPES[$level],
                'dateTime' => $this->getTime($timestamp),
                'message' => $text,
                'category' => $category,
                'prefix' => $this->getMessagePrefix($message)
            ];
        }

        $client = new Client();
        $client->createRequest()
            ->setMethod('POST')
            ->setFormat(Client::FORMAT_JSON)
            ->setUrl('http://logger-dev/api/message')
            // Пока временный ключ для тестирования
            ->addHeaders(['key' => 123])
            ->setData($data)
            ->send();
    }
}
