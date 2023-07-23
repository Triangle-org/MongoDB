<?php

declare(strict_types=1);

/**
 * @package     Triangle MongoDB Plugin
 * @link        https://github.com/Triangle-org/MongoDB
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
 * @license     GNU Affero General Public License, version 3
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Triangle\MongoDB\Queue;

use Carbon\Carbon;
use Illuminate\Queue\DatabaseQueue;
use MongoDB\Operation\FindOneAndUpdate;
use StdClass;
use Triangle\MongoDB\Connection;

/**
 *
 */
class MongoQueue extends DatabaseQueue
{
    /**
     * The expiration time of a job.
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * The connection name for the queue.
     * @var string
     */
    protected $connectionName;

    /**
     * @inheritdoc
     */
    public function __construct(Connection $database, $table, $default = 'default', $retryAfter = 60)
    {
        parent::__construct($database, $table, $default, $retryAfter);
        $this->retryAfter = $retryAfter;
    }

    /**
     * @inheritdoc
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if ($this->retryAfter !== null) {
            $this->releaseJobsThatHaveBeenReservedTooLong($queue);
        }

        if ($job = $this->getNextAvailableJobAndReserve($queue)) {
            return new MongoJob(
                $this->container, $this, $job, $this->connectionName, $queue
            );
        }
    }

    /**
     * Release the jobs that have been reserved for too long.
     * @param string $queue
     * @return void
     */
    protected function releaseJobsThatHaveBeenReservedTooLong($queue)
    {
        $expiration = Carbon::now()->subSeconds($this->retryAfter)->getTimestamp();

        $reserved = $this->database->collection($this->table)
            ->where('queue', $this->getQueue($queue))
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<=', $expiration)
            ->get();

        foreach ($reserved as $job) {
            $this->releaseJob($job['_id'], $job['attempts']);
        }
    }

    /**
     * Release the given job ID from reservation.
     * @param string $id
     * @param int $attempts
     * @return void
     */
    protected function releaseJob($id, $attempts)
    {
        $this->database->table($this->table)->where('_id', $id)->update([
            'reserved' => 0,
            'reserved_at' => null,
            'attempts' => $attempts,
        ]);
    }

    /**
     * Get the next available job for the queue and mark it as reserved.
     * When using multiple daemon queue listeners to process jobs there
     * is a possibility that multiple processes can end up reading the
     * same record before one has flagged it as reserved.
     * This race condition can result in random jobs being run more then
     * once. To solve this we use findOneAndUpdate to lock the next jobs
     * record while flagging it as reserved at the same time.
     * @param string|null $queue
     * @return StdClass|null
     */
    protected function getNextAvailableJobAndReserve($queue)
    {
        $job = $this->database->getCollection($this->table)->findOneAndUpdate(
            [
                'queue' => $this->getQueue($queue),
                'reserved' => ['$ne' => 1],
                'available_at' => ['$lte' => Carbon::now()->getTimestamp()],
            ],
            [
                '$set' => [
                    'reserved' => 1,
                    'reserved_at' => Carbon::now()->getTimestamp(),
                ],
                '$inc' => [
                    'attempts' => 1,
                ],
            ],
            [
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                'sort' => ['available_at' => 1],
            ]
        );

        if ($job) {
            $job->id = $job->_id;
        }

        return $job;
    }

    /**
     * @inheritdoc
     */
    public function deleteAndRelease($queue, $job, $delay)
    {
        $this->deleteReserved($queue, $job->getJobId());
        $this->release($queue, $job->getJobRecord(), $delay);
    }

    /**
     * @inheritdoc
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->collection($this->table)->where('_id', $id)->delete();
    }
}
